<?php
/**
 * WhatsappController
 * Standalone module controller for WhatsApp Orders channel dispatches,
 * stock inwards, stock allocations, returns & exchanges, and customers.
 */

require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Services/ShopService.php';
require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';
require_once BASE_PATH . '/app/Models/ChannelDamage.php';
require_once BASE_PATH . '/app/Models/WarehouseReturn.php';
require_once BASE_PATH . '/app/Models/WarehouseReturnItem.php';

class WhatsappController {

    /**
     * Helper to get WhatsApp channel record
     */
    private function getChannel(): array {
        $channels = function_exists('getScopedSalesChannels') ? getScopedSalesChannels('WHATSAPP') : [];
        if (!$channels) {
            Response::error(409, 'No WhatsApp channel is configured for the active concern.');
        }
        return $channels[0];
    }

    private function getWhatsappChannelIds(): array {
        $channels = function_exists('getScopedSalesChannels') ? getScopedSalesChannels('WHATSAPP') : [];
        return array_map('intval', array_column($channels, 'id'));
    }

    /** Resolve the WhatsApp stock location from the allocation's channel destination. */
    private function getWhatsappAllocationChannelId(array $allocation): int {
        $whatsappChannelIds = $this->getWhatsappChannelIds();
        $destinationType = strtoupper(trim((string)($allocation['destination_type'] ?? '')));
        $destinationId = (int)($allocation['destination_id'] ?? 0);
        if ($destinationType === 'CHANNEL' && in_array($destinationId, $whatsappChannelIds, true)) {
            return $destinationId;
        }

        $channelId = (int)($allocation['channel_id'] ?? 0);
        if (in_array($channelId, $whatsappChannelIds, true)) {
            return $channelId;
        }

        $defaultChannelId = (int)($this->getChannel()['id'] ?? 0);
        if (in_array($defaultChannelId, $whatsappChannelIds, true)) {
            return $defaultChannelId;
        }

        return (int)($whatsappChannelIds[0] ?? 3);
    }

    private function buildWhatsappAllocWhere(string $prefix = 'wa'): array {
        $chanIds = $this->getWhatsappChannelIds();
        $placeholders = implode(',', array_fill(0, count($chanIds), '?'));
        $destList = [];
        foreach ($chanIds as $id) {
            $destList[] = 'CHANNEL_' . $id;
        }
        $destPlaceholders = implode(',', array_fill(0, count($destList), '?'));
        $sql = "(({$prefix}.destination_type = 'CHANNEL' AND {$prefix}.destination_id IN ($placeholders))
                 OR {$prefix}.destination_type IN ($destPlaceholders)
                 OR {$prefix}.destination_type = 'WHATSAPP'
                 )";
        $params = array_merge($chanIds, $destList);
        return [$sql, $params];
    }

    /**
     * WhatsApp Channel Dashboard & Orders List
     */
    public function index(): void {
        $chan = $this->getChannel();
        $concernId = ConcernTransactionService::activeConcernId();
        list($allocWhereSql, $allocWhereParams) = $this->buildWhatsappAllocWhere('wa');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $warehouseId = trim($_GET['warehouse_id'] ?? '');

        $where = [$allocWhereSql];
        $where[] = "wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED')";
        $params = $allocWhereParams;

        if (!empty($search)) {
            $where[] = "(wa.allocation_number LIKE ? OR sc.name LIKE ? OR wa.notes LIKE ?
                         OR ao.outward_number LIKE ? OR ao.vehicle_number LIKE ? OR ao.driver_name LIKE ?
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

        if ($status === 'RECEIVED') {
            $where[] = "wa.status IN ('RECEIVED', 'COMPLETED', 'DELIVERED')";
        } elseif ($status === 'PARTIAL_RECEIVED') {
            $where[] = "wa.status IN ('PARTIAL_RECEIVED', 'IN_PROGRESS')";
        } elseif ($status === 'DISPATCHED') {
            $where[] = "wa.status IN ('DISPATCHED', 'APPROVED')";
        } elseif (!empty($status)) {
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
                    COALESCE(sc.name, 'WhatsApp Channel') as channel_name,
                    COALESCE(sc.name, 'WhatsApp Channel') as customer_or_channel,
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
                     WHERE ai.allocation_id = wa.id) as amount,
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
                    wa.created_at as date
             FROM warehouse_allocations wa
             LEFT JOIN sales_channels sc ON wa.channel_id = sc.id
             WHERE {$whereClause}
             ORDER BY wa.created_at DESC",
            $params
        );

        $totalAllocationsCount = count($allocations);
        $allocatedInventoryQty = 0;
        $allocatedTotalValue   = 0.0;
        $dispatchedCount       = 0;

        foreach ($allocations as $a) {
            $allocatedInventoryQty += (int)($a['total_quantity'] ?? 0);
            $allocatedTotalValue   += (float)($a['total_value'] ?? 0);
            if (in_array($a['status'], ['DISPATCHED', 'COMPLETED', 'DELIVERED'])) {
                $dispatchedCount++;
            }
        }

        $salesStats = Database::fetchOne(
            "SELECT COUNT(*) as order_count,
                    COALESCE(SUM(s.total_amount), 0) as total_revenue,
                    COALESCE(SUM((SELECT SUM(quantity) FROM sale_items WHERE sale_id = s.id)), 0) as sold_qty
             FROM sales s
             WHERE s.concern_id = ?
               AND (s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%')
               AND s.status != 'CANCELLED'",
            [$concernId]
        );

        $orderCount   = (int)($salesStats['order_count'] ?? 0);
        $totalRevenue = (float)($salesStats['total_revenue'] ?? 0);
        $soldQty      = (int)($salesStats['sold_qty'] ?? 0);

        $waChanIds = $this->getWhatsappChannelIds();
        $placeholders = implode(',', array_fill(0, count($waChanIds), '?'));
        $liveStock = Database::fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN sb.stock_state = 'AVAILABLE' THEN sb.quantity ELSE 0 END), 0) as available_qty,
                    COALESCE(SUM(CASE WHEN sb.stock_state = 'DAMAGED' THEN sb.quantity ELSE 0 END), 0) as damaged_qty,
                    COALESCE(SUM(CASE WHEN sb.stock_state = 'AVAILABLE' THEN sb.quantity * COALESCE(NULLIF(pv.selling_price, 0), NULLIF(pv.mrp, 0), 250) ELSE 0 END), 0) as available_value
             FROM stock_balances sb
             LEFT JOIN product_variants pv ON sb.variant_id = pv.id
             WHERE sb.location_type = 'CHANNEL' AND sb.location_id IN ($placeholders)
               AND sb.concern_id = " . (int)currentConcernId(),
            $waChanIds
        );

        $liveAvailQty = (float)($liveStock['available_qty'] ?? 0);
        $liveAvailVal = (float)($liveStock['available_value'] ?? 0);

        $availableStockQty   = $liveAvailQty > 0 ? (int)$liveAvailQty : max(0, $allocatedInventoryQty - $soldQty);
        $availableStockValue = $liveAvailVal > 0 ? $liveAvailVal : round($availableStockQty * ($allocatedInventoryQty > 0 ? ($allocatedTotalValue / $allocatedInventoryQty) : 0), 2);

        $recentSales = Database::fetchAll(
            "SELECT s.*, 
                    s.invoice_number as number,
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name, 
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_or_channel, 
                    COALESCE(c.phone, s.customer_phone, '') as customer_phone,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as quantity,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as total_pcs,
                    s.total_amount as amount,
                    s.created_at as date
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.concern_id = ?
               AND (s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%')
             ORDER BY s.created_at DESC LIMIT 10",
            [$concernId]
        );

        Response::view('whatsapp_orders.index', [
            'pageTitle'             => 'WhatsApp Orders Dashboard',
            'activeModule'          => 'whatsapp',
            'activeMenu'            => 'wh-whatsapp-dashboard',
            'breadcrumb'            => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Dashboard']
            ],
            'channel'               => $chan,
            'allocations'           => $allocations,
            'orders'                => $recentSales,
            'recentSales'           => $recentSales,
            'allocatedInventoryQty' => $allocatedInventoryQty,
            'allocatedTotalValue'   => $allocatedTotalValue,
            'soldQty'               => $soldQty,
            'orderCount'            => $orderCount,
            'totalRevenue'          => $totalRevenue,
            'availableStockQty'     => $availableStockQty,
            'availableStockValue'   => $availableStockValue,
            'totalAllocationsCount' => $totalAllocationsCount,
            'totalAllocatedQty'     => $allocatedInventoryQty,
            'totalAllocatedValue'   => $allocatedTotalValue,
            'dispatchedCount'       => $dispatchedCount,
            'search'                => $search,
            'status'                => $status,
            'dateFrom'              => $dateFrom,
            'dateTo'                => $dateTo,
        ]);
    }

    public function whatsappChannelOrders(): void {
        $this->index();
    }

    /**
     * Stock Inward & GRN Receiving for WhatsApp Channel
     */
    public function whatsappInwardIndex(): void {
        $chan = $this->getChannel();
        list($allocWhereSql, $allocWhereParams) = $this->buildWhatsappAllocWhere('wa');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        $warehouseId = trim($_GET['warehouse_id'] ?? '');

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
                    COALESCE(sc.name, 'WhatsApp Channel') as channel_name,
                    COALESCE(w.name, 'Main Warehouse') as warehouse_name,
                    w.code as warehouse_code,
                    ao.outward_number, ao.vehicle_number, ao.driver_name, ao.driver_contact, ao.transporter_name, ao.total_boxes, ao.dispatch_date,
                    (SELECT COALESCE(SUM(CASE WHEN dispatched_qty > 0 THEN dispatched_qty ELSE requested_qty END), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_quantity,
                    (SELECT COALESCE(SUM(received_qty), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_received_quantity,
                    (SELECT COALESCE(SUM(damaged_qty), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_damaged_quantity,
                    (SELECT COUNT(DISTINCT variant_id) FROM allocation_items WHERE allocation_id = wa.id) as distinct_items_count,
                    (SELECT COALESCE(SUM((CASE WHEN ai.dispatched_qty > 0 THEN ai.dispatched_qty ELSE ai.requested_qty END) * COALESCE(NULLIF(pv.selling_price, 0), NULLIF(pv.mrp, 0), 250)), 0)
                     FROM allocation_items ai
                     JOIN product_variants pv ON ai.variant_id = pv.id
                     WHERE ai.allocation_id = wa.id) as total_value
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
            $itemsSql = "SELECT ai.*, pv.sku, pv.qr_code, pv.size_id, pv.color_id, pv.selling_price, pv.mrp,
                                s.name as size_name, c.name as color_name, c.hex_code as color_hex,
                                p.name as product_name, p.code as product_code, p.image_url as product_image, cat.name as category_name
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

        $pendingBatchesCount = 0;
        $totalPendingMissingQty = 0;
        $receivedBatchesCount = 0;
        $totalReceivedQty = 0;
        $totalDispatchedQty = 0;
        $totalValuation = 0.0;

        foreach ($allocations as &$a) {
            $a['items'] = $allocItemsMap[$a['id']] ?? [];
            $a['total_variants'] = count($a['items']);
            $a['batch_number'] = $a['allocation_number'];
            $a['quantity'] = (int)($a['total_quantity'] ?? 0);
            $totalValuation += (float)($a['total_value'] ?? 0);
            $totalDispatchedQty += $a['quantity'];

            $st = strtoupper($a['status'] ?? 'PENDING');
            $goodQty = (int)($a['total_received_quantity'] ?? 0);
            $damagedQty = (int)($a['total_damaged_quantity'] ?? 0);
            if ($a['quantity'] > 0 && $goodQty === 0 && $damagedQty === 0 && in_array($st, ['COMPLETED', 'RECEIVED', 'DELIVERED'], true)) {
                foreach ($a['items'] as &$item) {
                    $item['received_qty'] = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : ($item['requested_qty'] ?? 0));
                    $goodQty += (int)$item['received_qty'];
                }
                unset($item);
            }
            $receivedQty = $goodQty + $damagedQty;
            $a['good_received_quantity'] = $goodQty;
            $a['received_qty'] = $goodQty;
            $a['damaged_qty'] = $damagedQty;
            $a['total_received_quantity'] = $receivedQty;
            $a['missing_qty'] = max(0, $a['quantity'] - $receivedQty);

            if ($a['quantity'] > 0 && $a['missing_qty'] === 0) {
                $a['status_state'] = 'RECEIVED';
                $receivedBatchesCount++;
                $totalReceivedQty += $goodQty;
            } elseif ($receivedQty > 0 || in_array($st, ['IN_PROGRESS', 'PARTIAL_RECEIVED'], true)) {
                $a['status_state'] = 'PARTIAL';
                $pendingBatchesCount++;
                $totalPendingMissingQty += $a['missing_qty'];
            } else {
                $a['status_state'] = 'IN_TRANSIT';
                $pendingBatchesCount++;
                $totalPendingMissingQty += $a['quantity'];
            }
        }
        unset($a);

        $returnInwards = Database::fetchAll(
            "SELECT r.*, s.invoice_number, COALESCE(c.name, s.customer_name, 'Walk-in Customer') as customer_name, COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                    (SELECT COALESCE(SUM(quantity), 0) FROM return_items WHERE return_id = r.id) as total_returned_qty,
                    (SELECT COALESCE(SUM(quantity), 0) FROM return_items WHERE return_id = r.id AND (item_condition = 'GOOD' OR item_condition IS NULL)) as good_qty,
                    (SELECT COALESCE(SUM(quantity), 0) FROM return_items WHERE return_id = r.id AND item_condition = 'DAMAGED') as damaged_qty
             FROM returns r
             JOIN sales s ON r.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE r.concern_id = ? AND s.concern_id = r.concern_id
               AND (s.sales_channel = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%')
             ORDER BY r.created_at DESC",
            [ConcernTransactionService::activeConcernId()]
        );

        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];

        Response::view('whatsapp_orders.inward', [
            'pageTitle'              => 'WhatsApp Stock Inward & QR Receiving',
            'activeModule'           => 'whatsapp',
            'activeMenu'             => 'wh-whatsapp-inward',
            'breadcrumb'             => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Stock Inward & GRN']
            ],
            'channel'                => $chan,
            'allocations'            => $allocations,
            'batches'                => $allocations,
            'returnInwards'          => $returnInwards,
            'warehouses'             => $warehouses,
            'warehouseId'            => $warehouseId,
            'totalInwardCount'       => count($allocations),
            'pendingBatchesCount'    => $pendingBatchesCount,
            'totalPendingMissingQty' => $totalPendingMissingQty,
            'receivedBatchesCount'   => $receivedBatchesCount,
            'totalReceivedQty'       => $totalReceivedQty,
            'totalDispatchedQty'     => $totalDispatchedQty,
            'totalValuation'         => $totalValuation,
            'search'                 => $search,
            'status'                 => $status,
            'dateFrom'               => $dateFrom,
            'dateTo'                 => $dateTo,
        ]);
    }

    public function viewWhatsappAllocation(int $id): void {
        $this->viewWhatsappInward($id, 'allocations');
    }

    public function viewWhatsappInward(int $id, string $fromSection = 'inward'): void {
        if (!empty($_GET['from']) && $_GET['from'] === 'allocations') {
            $fromSection = 'allocations';
        }
        $chan = $this->getChannel();
        $allocation = Database::fetchOne(
            "SELECT wa.*, 
                    COALESCE(sc.name, 'WhatsApp Channel') as channel_name,
                    COALESCE(w.name, 'Main Warehouse') as warehouse_name,
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
            Response::redirect(url('warehouse/whatsapp-orders/inward'));
            return;
        }

        $items = Database::fetchAll(
            "SELECT ai.*, pv.sku, pv.qr_code, pv.qr_type as variant_qr_type, pv.is_complementary as variant_is_complementary,
                    pv.size_id, pv.color_id, pv.selling_price, pv.mrp,
                    p.name as product_name, p.code as product_code, p.image_url as product_image,
                    p.is_complementary as product_is_complementary,
                    p.qr_code_prefix, p.category_code, p.subcategory_code, p.sub_subcategory_code,
                    cat.name as category_name, subcat.name as subcategory_name, subsub.name as sub_subcategory_name,
                    wc.category_type as warehouse_category_type,
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
                : ($warehouseCategoryType === 'SERIES' || $variantQrType === 'SERIES_BATCH' ? 'SERIES' : 'NORMAL');

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
                if (in_array($item['product_type'], ['SERIES', 'COMPLEMENTARY'], true)) {
                    $savedQrCodes = array_fill(0, $exp, reset($savedQrCodes));
                }
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
                        'product_type'  => $item['product_type'],
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
                        'product_type'  => $item['product_type'],
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

        // Allocation items may contain one row per serialized piece. Present a single
        // product/variant row in the inward matrix while retaining all source IDs for
        // posting grouped receive quantities back to their original allocation rows.
        $groupedItems = [];
        foreach ($items as $item) {
            $groupKey = (int)$item['variant_id'];
            if (!isset($groupedItems[$groupKey])) {
                $item['allocation_item_ids'] = [(int)$item['id']];
                $item['piece_qrs'] = array_values($item['piece_qrs'] ?? []);
                $groupedItems[$groupKey] = $item;
                continue;
            }

            $grouped =& $groupedItems[$groupKey];
            $grouped['quantity'] += (int)$item['quantity'];
            $grouped['requested_qty'] = (int)($grouped['requested_qty'] ?? 0) + (int)($item['requested_qty'] ?? $item['quantity']);
            $grouped['dispatched_qty'] = (int)($grouped['dispatched_qty'] ?? 0) + (int)($item['dispatched_qty'] ?? $item['quantity']);
            $grouped['received_qty'] += (int)$item['received_qty'];
            $grouped['damaged_qty'] += (int)$item['damaged_qty'];
            $grouped['missing_qty'] += (int)$item['missing_qty'];
            $grouped['allocation_item_ids'][] = (int)$item['id'];
            $grouped['piece_qrs'] = array_merge($grouped['piece_qrs'], $item['piece_qrs'] ?? []);
            unset($grouped);
        }
        $items = array_values($groupedItems);
        $pieceQRs = [];
        foreach ($items as &$groupedItem) {
            $allTags = [];
            $pieceNumber = 1;
            foreach ($groupedItem['piece_qrs'] as &$piece) {
                $piece['item_id'] = (int)$groupedItem['id'];
                $piece['piece_no'] = $pieceNumber++;
                $tag = trim((string)($piece['tag_code'] ?? ''));
                if ($tag !== '' && !in_array($tag, $allTags, true)) {
                    $allTags[] = $tag;
                }
                $pieceQRs[] = $piece;
            }
            unset($piece);
            $groupedItem['qr_codes'] = $allTags;
            $groupedItem['qr_range'] = $allTags ? implode(', ', $allTags) : ($groupedItem['sku'] ?? '');
        }
        unset($groupedItem);

        $activeMenu = ($fromSection === 'allocations') ? 'wh-whatsapp-allocations' : 'wh-whatsapp-inward';
        $breadcrumb = ($fromSection === 'allocations') ? [
            ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
            ['label' => 'WhatsApp Inventory', 'url' => url('warehouse/whatsapp-orders/allocations')],
            ['label' => 'Allocation #' . ($allocation['allocation_number'] ?? $id)]
        ] : [
            ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
            ['label' => 'Stock Inward', 'url' => url('warehouse/whatsapp-orders/inward')],
            ['label' => 'Cross-Check Batch']
        ];

        Response::view('whatsapp_orders.inward_view', [
            'pageTitle'     => 'Allocation & Inward Batch #' . ($allocation['allocation_number'] ?? ('ALC-' . str_pad($id, 5, '0', STR_PAD_LEFT))),
            'activeModule'  => 'whatsapp',
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
                'warehouse_name'       => $allocation['channel_name'] ?? 'WhatsApp Channel',
                'warehouse_code'       => 'WA-01',
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

    public function receiveWhatsappInward(int $id): void {
        $allocation = Database::fetchOne("SELECT * FROM warehouse_allocations WHERE id = ?", [$id]);
        if (!$allocation) {
            $_SESSION['flash_error'] = "Stock Allocation not found.";
            Response::redirect(url('warehouse/whatsapp-orders/inward'));
            return;
        }

        $items = Database::fetchAll("SELECT * FROM allocation_items WHERE allocation_id = ? ORDER BY id ASC", [$id]);
        $isReceiveAll  = !empty($_POST['receive_all']);
        $receivedInput = $_POST['received_qty'] ?? [];
        $damagedInput  = $_POST['damaged_qty'] ?? [];

        // The detail screen submits one total per variant. Expand those grouped totals
        // across the original allocation rows so existing per-piece records stay intact.
        $allocationGroups = [];
        foreach ($items as $item) {
            $variantId = (int)($item['variant_id'] ?? 0);
            $allocationGroups[$variantId][] = $item;
        }
        $groupedQuantitiesByItemId = [];
        foreach ($allocationGroups as $groupItems) {
            if (!$groupItems) {
                continue;
            }
            $groupKey = (string)$groupItems[0]['id'];
            if (!array_key_exists($groupKey, $receivedInput) && !array_key_exists($groupKey, $damagedInput)) {
                continue;
            }

            $groupExpected = 0;
            foreach ($groupItems as $groupItem) {
                $groupExpected += (int)($groupItem['dispatched_qty'] > 0 ? $groupItem['dispatched_qty'] : ($groupItem['requested_qty'] ?? 1));
            }
            $targetReceived = max(0, min($groupExpected, (int)($receivedInput[$groupKey] ?? 0)));
            $targetDamaged = max(0, min($groupExpected - $targetReceived, (int)($damagedInput[$groupKey] ?? 0)));

            foreach ($groupItems as $groupItem) {
                $itemId = (int)$groupItem['id'];
                $capacity = (int)($groupItem['dispatched_qty'] > 0 ? $groupItem['dispatched_qty'] : ($groupItem['requested_qty'] ?? 1));
                $receivedForItem = min($capacity, $targetReceived);
                $targetReceived -= $receivedForItem;
                $damagedForItem = min($capacity - $receivedForItem, $targetDamaged);
                $targetDamaged -= $damagedForItem;
                $groupedQuantitiesByItemId[$itemId] = [
                    'received_qty' => $receivedForItem,
                    'damaged_qty' => $damagedForItem,
                ];
            }
        }

        $sessionUserId = Session::get('user_id');
        $userId = is_numeric($sessionUserId) && (int)$sessionUserId > 0
            ? (int)$sessionUserId
            : null;
        if ($userId !== null && !Database::fetchOne("SELECT id FROM users WHERE id = ?", [$userId])) {
            error_log("WhatsApp inward received an invalid session user ID; stock transactions will be recorded without a user.");
            $userId = null;
        }
        $channelId     = $this->getWhatsappAllocationChannelId($allocation);

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
                } elseif (isset($groupedQuantitiesByItemId[(int)$itemId])) {
                    $newRec = $groupedQuantitiesByItemId[(int)$itemId]['received_qty'];
                    $newDmg = $groupedQuantitiesByItemId[(int)$itemId]['damaged_qty'];
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
                        notes: "Inward received into WhatsApp Channel for Allocation #" . ($allocation['allocation_number'] ?? $id)
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
                        notes: "Inward adjustment (reduced) for WhatsApp Channel Allocation #" . ($allocation['allocation_number'] ?? $id)
                    );
                }

                $deltaDmg = $newDmg - $prevDmg;
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
                        notes: "Damaged stock logged during inward for Allocation #" . ($allocation['allocation_number'] ?? $id)
                    );

                    // Auto-record directly into WhatsApp Channel Damage management
                    ChannelDamage::logInwardDamage(
                        channelId: $channelId,
                        variantId: (int)$item['variant_id'],
                        quantity: $deltaDmg,
                        allocationId: $id,
                        notes: "Damaged stock detected during WhatsApp inward receipt for Allocation #" . ($allocation['allocation_number'] ?? $id),
                        reportedBy: $userId,
                        defectType: 'INWARD_DEFECT',
                        severity: 'MODERATE'
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
                        notes: "Damaged stock adjustment (reduced) for WhatsApp Channel Allocation #" . ($allocation['allocation_number'] ?? $id)
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
            $_SESSION['flash_success'] = "Stock Allocation #" . ($allocation['allocation_number'] ?? $id) . " fully received & credited to WhatsApp Channel inventory ({$totalRec} pcs)!";
        }

        Response::redirect(url('warehouse/whatsapp-orders/inward'));
    }

    /**
     * AJAX endpoint to retrieve full traceability, inward audit log & stock ledger transactions
     */
    public function ajaxBatchHistory(string $id): void {
        $allocId = (int)$id;
        $allocation = Database::fetchOne(
            "SELECT wa.*, 
                    COALESCE(sc.name, 'WhatsApp Orders Channel') as channel_name,
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

        // Fetch actual stock ledger postings
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

        // Fetch channel damages logged for this allocation
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

        Response::jsonSuccess('WhatsApp Inward batch lifecycle history loaded', [
            'allocation' => $allocation,
            'items'      => $items,
            'txns'       => $txns,
            'damages'    => $damages,
        ]);
    }

    /**
     * Stock Allocations List for WhatsApp Orders
     */
    public function whatsappAllocations(): void {
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 3);
        $activeTab = $_GET['tab'] ?? 'stock';
        $productType = strtolower(trim($_GET['product_type'] ?? 'normal'));

        $search = trim($_GET['search'] ?? '');
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $stockStatus = strtolower(trim($_GET['stock_status'] ?? ''));
        $sort = $_GET['sort'] ?? 'name_asc';
        $allocStatus = trim($_GET['alloc_status'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        // Fetch active categories for filter
        $categories = Database::fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");

        $chanIds = $this->getWhatsappChannelIds();
        $chanIn = implode(',', array_fill(0, count($chanIds), '?'));
        $destList = [];
        foreach ($chanIds as $cid) {
            $destList[] = 'CHANNEL_' . $cid;
        }
        $destIn = implode(',', array_fill(0, count($destList), '?'));

        // Base query for WhatsApp Stock Items
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
                       WHERE si.variant_id = pv.id AND (sl.sales_channel = 'WHATSAPP' OR sl.notes LIKE '%WhatsApp%') AND sl.status != 'CANCELLED'
                   ), 0) as gross_sold_quantity,
                   COALESCE((
                       SELECT SUM(ri.quantity) 
                       FROM return_items ri 
                       JOIN returns r ON ri.return_id = r.id 
                       WHERE ri.variant_id = pv.id AND (r.source_type = 'WHATSAPP' OR r.notes LIKE '%WhatsApp%') AND r.status != 'REJECTED'
                   ), 0) as returned_quantity,
                   COALESCE((
                       SELECT SUM(ei.quantity) 
                       FROM exchange_items ei 
                       JOIN exchanges e ON ei.exchange_id = e.id 
                       WHERE ei.variant_id = pv.id AND ei.item_type = 'NEW' AND (e.source_type = 'WHATSAPP' OR e.notes LIKE '%WhatsApp%')
                   ), 0) as exchanged_out_quantity,
                   GREATEST(0, COALESCE((
                       SELECT SUM(si.quantity) 
                       FROM sale_items si 
                       JOIN sales sl ON si.sale_id = sl.id 
                       WHERE si.variant_id = pv.id AND (sl.sales_channel = 'WHATSAPP' OR sl.notes LIKE '%WhatsApp%') AND sl.status != 'CANCELLED'
                   ), 0) - COALESCE((
                       SELECT SUM(ri.quantity) 
                       FROM return_items ri 
                       JOIN returns r ON ri.return_id = r.id 
                       WHERE ri.variant_id = pv.id AND (r.source_type = 'WHATSAPP' OR r.notes LIKE '%WhatsApp%') AND r.status != 'REJECTED'
                   ), 0) + COALESCE((
                       SELECT SUM(ei.quantity) 
                       FROM exchange_items ei 
                       JOIN exchanges e ON ei.exchange_id = e.id 
                       WHERE ei.variant_id = pv.id AND ei.item_type = 'NEW' AND (e.source_type = 'WHATSAPP' OR e.notes LIKE '%WhatsApp%')
                   ), 0)) as sold_quantity,
                   COALESCE((
                       SELECT SUM(ai.received_qty) 
                       FROM allocation_items ai 
                       JOIN warehouse_allocations wa ON ai.allocation_id = wa.id 
                       WHERE wa.concern_id = " . (int)currentConcernId() . " AND ai.variant_id = pv.id AND ((wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($chanIn)) OR wa.destination_type IN ($destIn) OR wa.destination_type = 'WHATSAPP')
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
                    WHERE wa.concern_id = " . (int)currentConcernId() . " AND ai.variant_id = pv.id AND wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED') AND ((wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($chanIn)) OR wa.destination_type IN ($destIn) OR wa.destination_type = 'WHATSAPP')
                )
            )
        ";

        $stockParams = array_merge(
            $chanIds, // available
            $chanIds, // damaged
            // gross_sold has no params
            // returned has no params
            // exchanged_out has no params
            // net sold has no params
            $chanIds, $destList, // inward
            $chanIds, // where exists stock
            $chanIds, $destList // where exists inward
        );
        $allStockItems = Database::fetchAll($stockSql, $stockParams);

        $summary = [
            'total_units'          => 0,
            'total_skus'           => count($allStockItems),
            'total_valuation'      => 0.0,
            'total_mrp_valuation'  => 0.0,
            'in_stock_count'       => 0,
            'low_stock_count'      => 0,
            'out_of_stock_count'   => 0,
            'damaged_units'        => 0,
            'damaged_loss'         => 0.0,
            'total_sold_units'     => 0,
            'total_returned_units' => 0,
            'total_exchanged_units'=> 0,
            'total_inward_units'   => 0
        ];

        foreach ($allStockItems as $it) {
            $avail     = (float)$it['available_quantity'];
            $dmg       = (float)$it['damaged_quantity'];
            $sold      = (float)$it['sold_quantity'];
            $ret       = (float)($it['returned_quantity'] ?? 0);
            $exc       = (float)($it['exchanged_out_quantity'] ?? 0);
            $inw       = (float)$it['total_inward_quantity'];
            $sellPrice = (float)($it['selling_price'] > 0 ? $it['selling_price'] : ($it['mrp'] > 0 ? $it['mrp'] : 100));
            $mrpPrice  = (float)($it['mrp'] > 0 ? $it['mrp'] : $sellPrice);
            $costPrice = (float)($it['production_cost'] > 0 ? $it['production_cost'] : ($it['purchase_price'] > 0 ? $it['purchase_price'] : $sellPrice));

            $summary['total_units']          += $avail;
            $summary['total_valuation']      += ($avail * $sellPrice);
            $summary['total_mrp_valuation']  += ($avail * $mrpPrice);
            $summary['damaged_units']        += $dmg;
            $summary['damaged_loss']         += ($dmg * $costPrice);
            $summary['total_sold_units']     += $sold;
            $summary['total_returned_units'] += $ret;
            $summary['total_exchanged_units']+= $exc;
            $summary['total_inward_units']   += $inw;

            $minStock = (int)($it['min_stock'] ?? 5);
            if ($avail <= 0) {
                $summary['out_of_stock_count']++;
            } elseif ($avail <= $minStock) {
                $summary['low_stock_count']++;
            } else {
                $summary['in_stock_count']++;
            }
        }

        // Filter stock items
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

        // Sort stock items
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

        // Allocations query for Tab 2
        list($allocWhereSql, $allocWhereParams) = $this->buildWhatsappAllocWhere('wa');
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
                    COALESCE(sc.name, 'WhatsApp Channel') as channel_name,
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

        Response::view('whatsapp_orders.allocations', [
            'pageTitle'             => 'WhatsApp Stock & Inventory',
            'activeModule'          => 'whatsapp',
            'activeMenu'            => 'wh-whatsapp-allocations',
            'breadcrumb'            => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
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
     * WhatsApp Product Stock Details & Full Lifecycle History
     */
    public function whatsappInventoryDetail(int $id): void {
        $chan = $this->getChannel();
        $chanIds = $this->getWhatsappChannelIds();
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
                       WHERE sb.variant_id = pv.id AND sb.location_type = 'CHANNEL' AND sb.location_id IN ($chanIn) AND sb.stock_state = 'AVAILABLE'
                   ), 0) as available_quantity,
                   COALESCE((
                       SELECT SUM(sb.quantity) FROM stock_balances sb 
                       WHERE sb.variant_id = pv.id AND sb.location_type = 'CHANNEL' AND sb.location_id IN ($chanIn) AND sb.stock_state = 'DAMAGED'
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
            Response::redirect(url('warehouse/whatsapp-orders/allocations'));
            return;
        }

        // Series/complementary products use a shared QR for a pack. Their
        // piece status must follow the live channel balance, not one sale
        // quantity being assigned to the shared QR as SOLD.
        $isPackType = !empty($item['product_is_complementary'])
            || !empty($item['is_complementary'])
            || strtoupper((string)($item['warehouse_category_type'] ?? '')) === 'COMPLEMENTARY'
            || strtoupper((string)($item['warehouse_category_type'] ?? '')) === 'SERIES'
            || strtoupper((string)($item['qr_type'] ?? '')) === 'SERIES_BATCH'
            || (int)($item['pack_quantity'] ?? 1) > 1;

        // 1. Stock Ledger / Transactions for this variant at WhatsApp Channel
        $transactions = Database::fetchAll(
            "SELECT st.*, u.full_name as user_name
             FROM stock_transactions st
             LEFT JOIN users u ON st.user_id = u.id
             WHERE st.location_type = 'CHANNEL' AND st.location_id IN ($chanIn) AND st.variant_id = ?
               AND st.concern_id = " . (int)currentConcernId() . "
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
             WHERE wa.concern_id = " . (int)currentConcernId() . " AND ai.variant_id = ? AND wa.status NOT IN ('REQUESTED', 'PENDING', 'CANCELLED', 'REJECTED') AND ((wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($chanIn)) OR wa.destination_type IN ($destIn) OR wa.destination_type = 'WHATSAPP')
             ORDER BY wa.id DESC",
            array_merge([$variantId], $chanIds, $destList)
        );

        // 3. WhatsApp Sales Orders History for this variant
        $salesHistory = Database::fetchAll(
            "SELECT si.*, s.invoice_number, s.customer_id, s.status as sale_status, s.payment_status, s.created_at as sale_date,
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE si.variant_id = ? AND (s.sales_channel = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%')
             ORDER BY s.created_at DESC",
            [$variantId]
        );

        // 4. WhatsApp Damage History
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

        // 5. WhatsApp Returns History for this variant
        $returnsHistory = Database::fetchAll(
            "SELECT ri.*, r.return_number, r.return_type, r.reason as return_reason, r.refund_amount, r.refund_mode, r.refund_status, r.status as return_status, r.created_at as return_date,
                    s.invoice_number as sale_invoice,
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone
             FROM return_items ri
             JOIN returns r ON ri.return_id = r.id
             LEFT JOIN sales s ON r.sale_id = s.id
             LEFT JOIN customers c ON r.customer_id = c.id
             WHERE ri.variant_id = ? AND (r.source_type = 'WHATSAPP' OR s.sales_channel = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%')
             ORDER BY r.created_at DESC",
            [$variantId]
        );

        // 6. WhatsApp Exchanges History for this variant (both OLD returned and NEW dispatched)
        $exchangesHistory = Database::fetchAll(
            "SELECT ei.*, e.exchange_number, e.original_amount, e.new_amount, e.difference_amount, e.settlement_mode, e.payment_action, e.status as exchange_status, e.created_at as exchange_date,
                    s.invoice_number as sale_invoice, r.return_number,
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone
             FROM exchange_items ei
             JOIN exchanges e ON ei.exchange_id = e.id
             LEFT JOIN returns r ON e.return_id = r.id
             LEFT JOIN sales s ON e.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE ei.variant_id = ? AND (e.source_type = 'WHATSAPP' OR s.sales_channel = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%')
             ORDER BY e.created_at DESC",
            [$variantId]
        );

        // 7. Warehouse Returns History for this variant (dispatched back to Central Warehouse)
        $warehouseReturnsHistory = Database::fetchAll(
            "SELECT wr.*, wri.id as return_item_id, wri.variant_id, wri.returned_qty, wri.good_qty, wri.damaged_qty, wri.defective_qty, wri.classification,
                    w.name as destination_warehouse_name
             FROM warehouse_return_items wri
             JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id
             LEFT JOIN warehouses w ON wr.destination_warehouse_id = w.id
             WHERE wri.variant_id = ?
               AND (wr.source_type = 'WHATSAPP' OR wr.source_id IN ($chanIn))
               AND wr.status NOT IN ('CANCELLED')
             ORDER BY wr.id DESC",
            array_merge([$variantId], $chanIds)
        );

        // 8. Piece-by-Piece QR & Serial Tag Codes Resolution
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
                // If damage was logged during inward transit, map to allocation_id instead of floor double-counting
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

        // Gather tags from WhatsApp Sales Orders
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
                            'customer_name'  => $sale['customer_name'] ?? 'WhatsApp Customer',
                            'date'           => $sale['sale_date'] ?? $sale['created_at'],
                        ];
                    }
                }
            } else {
                $unassignedSoldCount += (int)$sale['quantity'];
            }
        }

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
                'customer_name'  => $ret['customer_name'] ?? 'WhatsApp Customer',
                'date'           => $ret['return_date'] ?? $ret['created_at'],
            ];
            // Check if return record references specific tags
            if (!empty($ret['piece_tag'])) {
                $pTags = array_map('trim', explode(',', $ret['piece_tag']));
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
                $pTags = array_map('trim', explode(',', $pTagStr));
                foreach ($pTags as $pt) {
                    if (!$pt) continue;
                    if ($ex['item_type'] === 'NEW') {
                        $exchangeDispatchedTagsMap[$pt] = [
                            'exchange_id'     => $ex['exchange_id'] ?? $ex['id'],
                            'exchange_number' => $ex['exchange_number'],
                            'sale_invoice'    => $ex['sale_invoice'] ?? '-',
                            'return_number'   => $ex['return_number'] ?? '',
                            'customer_name'   => $ex['customer_name'] ?? 'WhatsApp Customer',
                            'date'            => $ex['exchange_date'] ?? $ex['created_at'],
                        ];
                    } elseif ($ex['item_type'] === 'OLD') {
                        $returnedTagsMap[$pt] = [
                            'exchange_id'     => $ex['exchange_id'] ?? $ex['id'],
                            'exchange_number' => $ex['exchange_number'],
                            'return_number'   => $ex['return_number'] ?? $ex['exchange_number'],
                            'condition'       => 'GOOD',
                            'reason'          => 'Product Exchange (Old Piece Returned)',
                            'sale_invoice'    => $ex['sale_invoice'] ?? '-',
                            'customer_name'   => $ex['customer_name'] ?? 'WhatsApp Customer',
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
            $notesJson = json_decode((string)($wrItem['notes'] ?? ''), true);
            foreach (($notesJson['scanned_tags'] ?? []) as $scan) {
                if ((int)($scan['variant_id'] ?? 0) === $variantId && !empty($scan['tag_code'])) {
                    $warehouseReturnedTagsMap[trim($scan['tag_code'])] = $wrInfo;
                    $foundSpecificTags = true;
                }
            }
            if (!empty($wrItem['notes']) && !is_array($notesJson)) {
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
                $fallbackReturnedTag = $prefix
                    . date('my', strtotime($wrItem['created_at'] ?? 'now'))
                    . str_pad((string)($wrItem['return_item_id'] ?? 0), 3, '0', STR_PAD_LEFT);
                $warehouseReturnedTagsMap[$fallbackReturnedTag] = $wrInfo;
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
            // This is an inventory page: dispatched but unreceived pieces are not stock.
            $exp = min($dispatchedQty, max(0, $rec + $dmg));
            $allocMonthStr = sprintf('%02d', date('n', strtotime($inw['alloc_date'] ?? 'now')));
            $allocYearStr  = date('y', strtotime($inw['alloc_date'] ?? 'now'));

            $transitDmgRecord = $allocTransitDamageMap[$allocId] ?? null;

            // Inventory detail represents pieces physically received into WhatsApp,
            // not every QR selected for the warehouse dispatch. Prefer the inward
            // scan list; older records fall back to the received + damaged count.
            $savedQrs = [];
            if (!empty($inw['received_qr_codes'])) {
                $decodedReceivedQrs = json_decode((string)$inw['received_qr_codes'], true);
                $savedQrs = is_array($decodedReceivedQrs)
                    ? array_values(array_filter(array_map('trim', $decodedReceivedQrs)))
                    : array_values(array_filter(array_map('trim', explode(',', (string)$inw['received_qr_codes']))));
            }
            if (empty($savedQrs) && !empty($inw['selected_qr_codes'])) {
                $selectedQrs = array_values(array_filter(array_map('trim', explode(',', (string)$inw['selected_qr_codes']))));
                $physicallyReceivedQty = max(0, (int)($inw['received_qty'] ?? 0) + (int)($inw['damaged_qty'] ?? 0));
                $savedQrs = array_slice($selectedQrs, 0, $physicallyReceivedQty);
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
                            $statusMeta = array_merge($rInfo, ['is_restocked' => true, 'restock_badge' => 'Restocked from Return/Exchange']);
                        } else {
                            $status = 'DAMAGED';
                            $statusMeta = array_merge($rInfo, ['is_returned_damaged' => true, 'damage_reason' => 'Returned Damaged Stock']);
                        }
                    } elseif (!$isPackType && isset($exchangeDispatchedTagsMap[$tagCode])) {
                        $status = 'SOLD';
                        $statusMeta = array_merge($exchangeDispatchedTagsMap[$tagCode], ['is_exchange_dispatched' => true, 'dispatch_badge' => 'Dispatched in Exchange']);
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
                            $statusMeta = array_merge($rInfo, ['is_restocked' => true, 'restock_badge' => 'Restocked from Return/Exchange']);
                        } else {
                            $status = 'DAMAGED';
                            $statusMeta = array_merge($rInfo, ['is_returned_damaged' => true, 'damage_reason' => 'Returned Damaged Stock']);
                        }
                    } elseif (!$isPackType && isset($exchangeDispatchedTagsMap[$tagCode])) {
                        $status = 'SOLD';
                        $statusMeta = array_merge($exchangeDispatchedTagsMap[$tagCode], ['is_exchange_dispatched' => true, 'dispatch_badge' => 'Dispatched in Exchange']);
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
                    'warehouse_name'    => 'WhatsApp Channel Inventory',
                    'inward_date'       => date('Y-m-d H:i:s'),
                    'status'            => $isDmg ? 'DAMAGED' : 'AVAILABLE',
                    'meta'              => $isDmg ? ['damage_reason' => 'Stock Defect Quarantine'] : [],
                ];
            }
        }

        // A return QR can be created/scanned after the original allocation
        // list was built. Add those exact scanned tags as returned pieces so
        // the Returned tab can render them instead of showing only a count.
        foreach ($warehouseReturnedTagsMap as $returnedTag => $returnedMeta) {
            $alreadyPresent = false;
            foreach ($pieceTags as $existingPiece) {
                if (strcasecmp((string)($existingPiece['tag_code'] ?? ''), (string)$returnedTag) === 0) {
                    $alreadyPresent = true;
                    break;
                }
            }
            if ($alreadyPresent) continue;
            $pieceTags[] = [
                'piece_no' => count($pieceTags) + 1,
                'batch_piece_no' => count($pieceTags) + 1,
                'tag_code' => $returnedTag,
                'qr_payload' => $returnedTag,
                'sku' => $item['sku'],
                'mrp' => $fmtMrp,
                'disc' => $fmtDisc,
                'selling_price' => $costVal,
                'allocation_id' => null,
                'allocation_number' => 'WhatsApp Return to Warehouse',
                'warehouse_name' => $returnedMeta['warehouse_name'] ?? 'Central Warehouse',
                'inward_date' => $returnedMeta['date'] ?? date('Y-m-d H:i:s'),
                'status' => 'RETURNED',
                'meta' => $returnedMeta,
            ];
        }

        // Apply unassigned warehouse returns from the newest allocated pieces
        if ($unassignedWarehouseReturnedGoodCount > 0) {
            for ($idx = count($pieceTags) - 1; $idx >= 0; $idx--) {
                if ($unassignedWarehouseReturnedGoodCount <= 0) break;
                if ($pieceTags[$idx]['status'] === 'AVAILABLE') {
                    $pieceTags[$idx]['status'] = 'RETURNED';
                    $pieceTags[$idx]['meta'] = [
                        'is_warehouse_returned' => true,
                        'return_number'         => $latestWarehouseReturn['return_number'] ?? 'Warehouse Return',
                        'warehouse_name'        => $latestWarehouseReturn['destination_warehouse_name'] ?? 'Central Warehouse',
                        'reason'                => $latestWarehouseReturn['reason'] ?? 'Returned to Central Warehouse',
                        'date'                  => $latestWarehouseReturn['created_at'] ?? date('Y-m-d H:i:s'),
                    ];
                    $unassignedWarehouseReturnedGoodCount--;
                }
            }
        }

        if ($unassignedWarehouseReturnedDamagedCount > 0) {
            for ($idx = count($pieceTags) - 1; $idx >= 0; $idx--) {
                if ($unassignedWarehouseReturnedDamagedCount <= 0) break;
                if ($pieceTags[$idx]['status'] === 'DAMAGED') {
                    $pieceTags[$idx]['status'] = 'RETURNED';
                    $pieceTags[$idx]['meta'] = [
                        'is_warehouse_returned' => true,
                        'return_number'         => $latestWarehouseReturn['return_number'] ?? 'Warehouse Return',
                        'warehouse_name'        => $latestWarehouseReturn['destination_warehouse_name'] ?? 'Central Warehouse',
                        'reason'                => $latestWarehouseReturn['reason'] ?? 'Damaged Stock Returned to Central Warehouse',
                        'date'                  => $latestWarehouseReturn['created_at'] ?? date('Y-m-d H:i:s'),
                    ];
                    $unassignedWarehouseReturnedDamagedCount--;
                }
            }
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
                    $pt['meta'] = ['invoice_number' => 'WhatsApp Order'];
                    $unassignedSoldCount--;
                }
            }
            unset($pt);
        }

        // Summary Calculations for this Product
        $totalInwardQty   = array_sum(array_column($inwardHistory, 'received_qty'));
        $totalSoldQty     = array_sum(array_column($salesHistory, 'quantity'));
        $totalSoldAmt     = array_sum(array_column($salesHistory, 'total_price'));
        $totalReturnedQty = array_sum(array_column($returnsHistory, 'quantity'));
        $totalExchangedQty= count(array_filter($exchangesHistory, fn($e) => ($e['item_type'] ?? '') === 'NEW'));
        $totalWhReturnedQty = array_sum(array_column($warehouseReturnsHistory, 'returned_qty'));
        $totalDmgQty      = array_sum(array_column($damageHistory, 'quantity'));
        $totalDmgLoss     = array_sum(array_column($damageHistory, 'total_loss'));

        Response::view('whatsapp_orders.inventory_detail', [
            'pageTitle'        => 'Product Stock Details: ' . ($item['sku'] ?? 'Stock Item'),
            'activeModule'     => 'whatsapp',
            'activeMenu'       => 'wh-whatsapp-allocations',
            'breadcrumb'       => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Stock Inventory', 'url' => url('warehouse/whatsapp-orders/allocations')],
                ['label' => $item['sku'] ?? 'Product History']
            ],
            'channel'          => $chan,
            'item'             => $item,
            'pieceTags'        => $pieceTags,
            'transactions'     => $transactions,
            'inwardHistory'    => $inwardHistory,
            'salesHistory'     => $salesHistory,
            'damageHistory'    => $damageHistory,
            'returnsHistory'   => $returnsHistory,
            'exchangesHistory' => $exchangesHistory,
            'warehouseReturnsHistory' => $warehouseReturnsHistory,
            'metrics'          => [
                'total_inward_qty'      => $totalInwardQty,
                'total_sold_qty'        => $totalSoldQty,
                'total_returned_qty'    => $totalReturnedQty,
                'total_exchanged_qty'   => $totalExchangedQty,
                'total_wh_returned_qty' => $totalWhReturnedQty,
                'net_sold_qty'          => max(0, $totalSoldQty - $totalReturnedQty + $totalExchangedQty),
                'total_sold_amt'        => $totalSoldAmt,
                'total_dmg_qty'         => $totalDmgQty,
                'total_dmg_loss'        => $totalDmgLoss,
            ]
        ]);
    }

    /**
     * Create Stock Request Form for WhatsApp Channel
     */
    public function createWhatsappStockRequest(): void {
        $chan = $this->getChannel();
        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];
        $variants = Database::fetchAll(
            "SELECT pv.id, pv.sku, pv.mrp, pv.selling_price, p.name as product_name,
                    COALESCE(wc.name, c.name, 'General') as category_name,
                    COALESCE((
                        SELECT SUM(quantity) FROM stock_balances 
                        WHERE variant_id = pv.id AND stock_state = 'AVAILABLE' AND location_type = 'WAREHOUSE'
                          AND concern_id = " . (int)currentConcernId() . "
                    ), 0) as stock_qty
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE pv.is_active = 1
             ORDER BY p.name ASC, pv.sku ASC"
        );

        Response::view('whatsapp_orders.create_request', [
            'pageTitle'    => 'Create WhatsApp Stock Request',
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'wh-whatsapp-allocations',
            'breadcrumb'   => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Inventory', 'url' => url('warehouse/whatsapp-orders/allocations')],
                ['label' => 'New Stock Request']
            ],
            'channel'      => $chan,
            'warehouses'   => $warehouses,
            'variants'     => $variants,
        ]);
    }

    public function storeWhatsappStockRequest(): void {
        CSRF::verify();

        $chan = $this->getChannel();
        $channelId = (int)$chan['id'];
        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        ConcernTransactionService::assertFacility('WHATSAPP', $channelId);
        ConcernTransactionService::assertFacility('WAREHOUSE', $warehouseId);
        $notes = trim($_POST['notes'] ?? '');
        $items = $_POST['items'] ?? [];

        if (!$warehouseId) {
            Session::flash('error', 'Source warehouse is required for fulfilling the stock request.');
            Response::redirect(url('warehouse/whatsapp-orders/requests/create'));
            return;
        }

        if (empty($items) || !is_array($items)) {
            Session::flash('error', 'Please select at least one product item for the stock request.');
            Response::redirect(url('warehouse/whatsapp-orders/requests/create'));
            return;
        }

        $allocationNumber = WarehouseAllocation::generateRequestNumber('WREQ-');
        $allocationId = WarehouseAllocation::create([
            'allocation_number' => $allocationNumber,
            'warehouse_id'      => $warehouseId,
            'destination_type'  => 'CHANNEL',
            'destination_id'    => $channelId,
            'channel_id'        => $channelId,
            'requested_by'      => Auth::id() ?? 1,
            'status'            => 'REQUESTED',
            'notes'             => $notes ?: 'WhatsApp Channel Stock Requisition',
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

        Session::flash('success', "WhatsApp Stock Request #{$allocationNumber} submitted successfully!");
        Response::redirect(url('warehouse/whatsapp-orders/allocations'));
    }

    /**
     * Orders History for WhatsApp Channel
     */
    public function whatsappOrdersHistory(): void {
        $chan = $this->getChannel();
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $where = ["s.concern_id = ?", "(s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%')"];
        $params = [ConcernTransactionService::activeConcernId()];

        if (!empty($search)) {
            $where[] = "(s.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR s.notes LIKE ? OR s.biller_name LIKE ?)";
            $sWild = "%{$search}%";
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
        }

        if (!empty($status)) {
            $where[] = "s.status = ?";
            $params[] = $status;
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
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                    c.city as customer_city,
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

        Response::view('whatsapp_orders.history', [
            'pageTitle'          => 'WhatsApp Orders History',
            'activeModule'       => 'whatsapp',
            'activeMenu'         => 'wh-whatsapp-history',
            'breadcrumb'         => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
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
            'status'             => $status,
            'dateFrom'           => $dateFrom,
            'dateTo'             => $dateTo,
        ]);
    }

    /**
     * Returns & Exchanges Dashboard for WhatsApp Channel
     */
    public function whatsappReturnsIndex(): void {
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $returnType = trim($_GET['type'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $where = ["r.concern_id = ?", "s.concern_id = r.concern_id", "r.source_type = 'WHATSAPP'", "(s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%')"];
        $params = [ConcernTransactionService::activeConcernId()];

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
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name,
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
             LEFT JOIN exchanges e ON e.return_id = r.id AND e.concern_id = r.concern_id
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

        $whatsappChannelIds = $this->getWhatsappChannelIds();
        $channelPlaceholders = implode(',', array_fill(0, count($whatsappChannelIds), '?'));
        $catalogVariants = Database::fetchAll(
            "SELECT pv.id, pv.sku, pv.qr_code, pv.mrp,
                    COALESCE(NULLIF(pv.selling_price, 0), pv.mrp) as selling_price,
                    p.name as product_name, p.code as product_code,
                    COALESCE(sz.name, 'Free Size') as size_name,
                    COALESCE(clr.name, 'Standard') as color_name,
                    COALESCE((SELECT SUM(quantity) FROM stock_balances
                              WHERE variant_id = pv.id AND location_type = 'CHANNEL'
                                AND location_id IN ($channelPlaceholders) AND concern_id = " . (int)currentConcernId() . " AND stock_state = 'AVAILABLE'), 0) as available_stock
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors clr ON pv.color_id = clr.id
             WHERE p.is_active = 1
             ORDER BY p.name ASC, sz.sort_order ASC",
            $whatsappChannelIds
        );

        Response::view('channel_returns', [
            'pageTitle'           => 'WhatsApp Returns & Exchanges',
            'activeModule'        => 'whatsapp',
            'activeMenu'          => 'wh-whatsapp-returns',
            'breadcrumb'          => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Returns & Exchanges']
            ],
            'returns'             => $returns,
            'totalReturnsCount'   => $totalReturnsCount,
            'totalExchangesCount' => $totalExchangesCount,
            'totalRefundAmount'   => $totalRefundAmount,
            'totalDamagedQty'     => $totalDamagedQty,
            'catalogVariants'     => $catalogVariants,
            'isWebsiteReturns'    => false,
            'search'              => $search,
            'status'              => $status,
            'returnType'          => $returnType,
            'dateFrom'            => $dateFrom,
            'dateTo'              => $dateTo,
        ]);
    }

    public function whatsappReturnsLookup(): void {
        $concernId = ConcernTransactionService::activeConcernId();
        $term = trim($_REQUEST['q'] ?? $_REQUEST['query'] ?? $_REQUEST['term'] ?? $_REQUEST['invoice_number'] ?? $_REQUEST['search'] ?? '');
        if (empty($term)) {
            Response::jsonError("Please provide an invoice number, customer phone, or piece QR tag.");
            return;
        }

        // A phone lookup must show every matching WhatsApp invoice so the
        // operator can choose the correct bill before selecting return items.
        $phoneTerm = preg_replace('/[^0-9+]/', '', $term);
        if (strlen(preg_replace('/[^0-9]/', '', $phoneTerm)) >= 7) {
            $phoneSales = Database::fetchAll(
                "SELECT s.*, COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name,
                        COALESCE(c.phone, s.customer_phone, '-') as customer_phone, c.email as customer_email
                 FROM sales s LEFT JOIN customers c ON s.customer_id = c.id
                 WHERE s.concern_id = ? AND (c.phone LIKE ? OR s.customer_phone LIKE ?)
                   AND (s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%' OR s.invoice_number LIKE 'WA-%')
                 ORDER BY s.id DESC LIMIT 50",
                [$concernId, "%{$phoneTerm}%", "%{$phoneTerm}%"]
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
                    "SELECT id, return_number FROM returns WHERE sale_id = ? AND concern_id = ? AND source_type = 'WHATSAPP' ORDER BY id DESC LIMIT 1",
                    [(int)$phoneSale['id'], $concernId]
                );
                $phoneOrders[] = [
                    'sale' => $phoneSale,
                    'items' => $phoneItems,
                    'already_returned' => (bool)$existingInvoiceReturn,
                    'return_number' => $existingInvoiceReturn['return_number'] ?? null,
                ];
            }
            if (count($phoneOrders) > 1) {
                Response::jsonSuccess('WhatsApp invoices found for this customer.', $phoneOrders);
                return;
            }
        }

        // Search sales by invoice_number, customer phone/name, or piece tag in sale_items
        $sale = Database::fetchOne(
            "SELECT s.*, 
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name,
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
             AND s.concern_id = ?
             AND (s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%' OR s.invoice_number LIKE 'WA-%')
             ORDER BY s.id DESC LIMIT 1",
            [
                $term, "%{$term}%", 
                $term, "%{$term}%", 
                $term, "%{$term}%", 
                "%{$term}%", "%{$term}%", 
                "%{$term}%", $term,
                "%{$term}%", $term,
                $concernId
            ]
        );

        // Fallback search across all sales channels if not matched by WhatsApp filter
        if (!$sale) {
            $sale = Database::fetchOne(
                "SELECT s.*, 
                        COALESCE(c.name, s.customer_name, 'Customer') as customer_name,
                        COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                        c.email as customer_email
                 FROM sales s
                 LEFT JOIN customers c ON s.customer_id = c.id
                 WHERE s.concern_id = ? AND (s.invoice_number = ?
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
                 AND (s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%' OR s.invoice_number LIKE 'WA-%')
                 ORDER BY s.id DESC LIMIT 1",
                [
                    $concernId,
                    $term, "%{$term}%", 
                    $term, "%{$term}%", 
                    $term, "%{$term}%", 
                    "%{$term}%", "%{$term}%", 
                    "%{$term}%", $term,
                    "%{$term}%", $term
                ]
            );
        }

        if (!$sale) {
            Response::jsonError("No billed order found for search: " . htmlspecialchars($term));
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
            "SELECT id, return_number FROM returns WHERE sale_id = ? AND concern_id = ? AND source_type = 'WHATSAPP' ORDER BY id DESC LIMIT 1",
            [(int)$sale['id'], $concernId]
        );

        Response::jsonSuccess("WhatsApp Sale record loaded successfully", [
            [
                'sale'            => $sale,
                'items'           => $items,
                'already_returned' => (bool)$existingInvoiceReturn,
                'return_number'   => $existingInvoiceReturn['return_number'] ?? null,
            ]
        ]);
    }

    public function processWhatsappReturn(): void {
        CSRF::verify();

        $saleId = (int)($_POST['sale_id'] ?? 0);
        $returnType = strtoupper(trim($_POST['return_type'] ?? 'RETURN'));
        $reason = trim($_POST['reason'] ?? 'WhatsApp Customer Request');
        $refundMethod = strtoupper(trim($_POST['refund_method'] ?? 'CASH'));
        $items = $_POST['items'] ?? [];
        $replacements = $_POST['replacement_items'] ?? [];

        if (!$saleId || empty($items) || !is_array($items)) {
            Session::flash('error', 'Invalid WhatsApp return request payload. Please select items to return.');
            Response::redirect(url('warehouse/whatsapp-orders/returns'));
            return;
        }

        $concernId = ConcernTransactionService::activeConcernId();
        $sale = Database::fetchOne(
            "SELECT * FROM sales
             WHERE id = ? AND concern_id = ?
               AND (sales_channel = 'WHATSAPP' OR sale_type = 'WHATSAPP' OR notes LIKE '%WhatsApp%' OR invoice_number LIKE 'WA-%')",
            [$saleId, $concernId]
        );
        if (!$sale) {
            Session::flash('error', 'WhatsApp sale record not found.');
            Response::redirect(url('warehouse/whatsapp-orders/returns'));
            return;
        }

        // Return eligibility is invoice-based: one invoice can be returned or
        // exchanged only once, regardless of how many invoices share a phone.
        $existingInvoiceReturn = Database::fetchOne(
            "SELECT return_number FROM returns WHERE sale_id = ? AND concern_id = ? AND source_type = 'WHATSAPP' ORDER BY id DESC LIMIT 1",
            [$saleId, $concernId]
        );
        if ($existingInvoiceReturn) {
            Session::flash('error', "Invoice {$sale['invoice_number']} was already returned/exchanged under {$existingInvoiceReturn['return_number']}.");
            Response::redirect(url('warehouse/whatsapp-orders/returns'));
            return;
        }

        $chan = $this->getChannel();
        $channelId = (int)$chan['id'];
        if (!empty($sale['sales_channel_id'])) {
            ConcernTransactionService::assertFacility('WHATSAPP', (int)$sale['sales_channel_id']);
        }
        $whatsappChannelIds = $this->getWhatsappChannelIds();
        $replacementStockPlan = [];
        $replacementStockDispatched = [];

        // A same-product exchange must use a successfully scanned replacement
        // QR/SKU and that replacement must exist in the WhatsApp channel stock.
        if ($returnType === 'EXCHANGE_SAME') {
            $replacementQtyByVariant = [];
            foreach ($items as $itm) {
                $swapVarId = (int)($itm['exchange_variant_id'] ?? 0);
                $swapQty = (int)($itm['qty'] ?? $itm['quantity'] ?? 0);
                $swapTag = trim($itm['replacement_tag'] ?? '');
                if ($swapQty <= 0) continue;
                if ($swapVarId <= 0 || $swapTag === '') {
                    Session::flash('error', 'Please scan and enter a valid replacement product QR/SKU for every exchange item.');
                    Response::redirect(url('warehouse/whatsapp-orders/returns'));
                    return;
                }
                $replacementQtyByVariant[$swapVarId] = ($replacementQtyByVariant[$swapVarId] ?? 0) + $swapQty;
            }

            foreach ($replacementQtyByVariant as $swapVarId => $requestedQty) {
                $remainingQty = $requestedQty;
                $stockPlan = [];
                foreach ($whatsappChannelIds as $stockChannelId) {
                    $available = (int)StockService::getBalance((int)$swapVarId, 'CHANNEL', (int)$stockChannelId, 'AVAILABLE');
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
                    $replacement = Database::fetchOne("SELECT sku FROM product_variants WHERE id = ?", [$swapVarId]);
                    $availableQty = $requestedQty - $remainingQty;
                    Session::flash('error', "Replacement product " . ($replacement['sku'] ?? ('#' . $swapVarId)) . " has only {$availableQty} available in WhatsApp stock; {$requestedQty} requested.");
                    Response::redirect(url('warehouse/whatsapp-orders/returns'));
                    return;
                }
                $replacementStockPlan[(int)$swapVarId] = $stockPlan;
            }
        }

        // Enforce the one-time return rule on the server as well as in the UI.
        // This prevents a second return through a replayed or manually edited request.
        foreach ($items as $k => $itm) {
            $saleItemId = is_numeric($k) ? (int)$k : (int)($itm['sale_item_id'] ?? 0);
            $requestedQty = (int)($itm['qty'] ?? $itm['quantity'] ?? 0);
            if ($saleItemId <= 0 || $requestedQty <= 0) continue;
            $itemRow = Database::fetchOne("SELECT quantity FROM sale_items WHERE id = ? AND sale_id = ?", [$saleItemId, $saleId]);
            if (!$itemRow) {
                Session::flash('error', 'Invalid sale item selected for return.');
                Response::redirect(url('warehouse/whatsapp-orders/returns'));
                return;
            }
            $alreadyReturned = Database::fetchOne(
                "SELECT COALESCE(SUM(quantity), 0) total FROM return_items WHERE sale_item_id = ?",
                [$saleItemId]
            );
            $remainingQty = max(0, (int)$itemRow['quantity'] - (int)($alreadyReturned['total'] ?? 0));
            if ($requestedQty > $remainingQty) {
                $invoiceLabel = $sale['invoice_number'] ?? ('Sale #' . $saleId);
                Session::flash('error', "This item from {$invoiceLabel} has already been returned in full. Only {$remainingQty} piece(s) remain returnable.");
                Response::redirect(url('warehouse/whatsapp-orders/returns'));
                return;
            }
        }

        $totalReturnVal = 0.0;
        foreach ($items as $k => $itm) {
            $qty = (int)($itm['qty'] ?? $itm['quantity'] ?? 0);
            $price = (float)($itm['unit_price'] ?? 0);
            if ($price <= 0 && is_numeric($k)) {
                $siRow = Database::fetchOne("SELECT unit_price FROM sale_items WHERE id = ?", [(int)$k]);
                if ($siRow) $price = (float)$siRow['unit_price'];
            }
            if ($qty > 0) {
                $totalReturnVal += ($qty * $price);
            }
        }

        $totalReplacementVal = 0.0;
        if (($returnType === 'EXCHANGE' || $returnType === 'EXCHANGE_DIFFERENT') && !empty($replacements) && is_array($replacements)) {
            foreach ($replacements as $rep) {
                $q = (int)($rep['quantity'] ?? 0);
                $p = (float)($rep['unit_price'] ?? 0);
                if ($q > 0) {
                    $totalReplacementVal += ($q * $p);
                }
            }
        } elseif ($returnType === 'EXCHANGE_SAME') {
            foreach ($items as $k => $itm) {
                $q = (int)($itm['qty'] ?? $itm['quantity'] ?? 0);
                $swapVarId = (int)($itm['exchange_variant_id'] ?? $itm['variant_id'] ?? 0);
                $p = 0.0;
                if ($swapVarId > 0) {
                    $pvRow = Database::fetchOne("SELECT selling_price FROM product_variants WHERE id = ?", [$swapVarId]);
                    if ($pvRow) $p = (float)$pvRow['selling_price'];
                }
                if ($p <= 0) {
                    $p = (float)($itm['unit_price'] ?? 0);
                }
                if ($q > 0) {
                    $totalReplacementVal += ($q * $p);
                }
            }
        }

        $differenceAmount = $totalReplacementVal - $totalReturnVal;

        $dbReturnType = ($returnType === 'RETURN') ? 'RETURN' : 'EXCHANGE';
        $validRefundModes = ['CASH', 'UPI', 'CARD', 'ONLINE', 'CREDIT_NOTE'];
        $dbRefundMode = in_array($refundMethod, $validRefundModes) ? $refundMethod : 'CASH';
        $refundAmount = $differenceAmount < 0 ? abs($differenceAmount) : ($returnType === 'RETURN' ? $totalReturnVal : 0.0);
        $userId = Auth::id() ?? Session::userId() ?? 1;

        $returnNumber = 'RET-WA-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        Database::execute(
            "INSERT INTO returns (return_number, concern_id, source_type, sale_id, customer_id, return_type, reason, inspection_status,
                                 refund_amount, refund_mode, refund_status, total_amount, status, processed_by, approved_by, notes, created_at, updated_at)
             VALUES (?, ?, 'WHATSAPP', ?, ?, ?, ?, 'APPROVED', ?, ?, 'COMPLETED', ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $returnNumber,
                $concernId,
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
                    StockService::increase($varId, 'CHANNEL', $channelId, 'AVAILABLE', (float)$qty, 'RETURN_REINWARD', 'returns', $returnId, $userId, "WhatsApp Return Good Stock Re-Inward");
                } else {
                    StockService::increase($varId, 'CHANNEL', $channelId, 'DAMAGED', (float)$qty, 'RETURN_DAMAGED', 'returns', $returnId, $userId, "WhatsApp Return Damaged Stock");
                }
            }
        }

        if ($returnType === 'EXCHANGE' || $returnType === 'EXCHANGE_SAME' || $returnType === 'EXCHANGE_DIFFERENT') {
            $exchNumber = 'EXC-WA-' . date('Ymd') . '-' . mt_rand(1000, 9999);
            $payAction = $differenceAmount > 0 ? 'COLLECT' : ($differenceAmount < 0 ? 'REFUND' : 'NONE');
            Database::execute(
                "INSERT INTO exchanges (exchange_number, concern_id, return_id, source_type, sale_id, original_amount, new_amount, difference_amount, settlement_mode, payment_action, status, payment_status, notes, created_at, updated_at)
                 VALUES (?, ?, ?, 'WHATSAPP', ?, ?, ?, ?, ?, ?, 'COMPLETED', 'PAID', ?, NOW(), NOW())",
                [
                    $exchNumber,
                    $concernId,
                    $returnId,
                    $saleId,
                    $totalReturnVal,
                    $totalReplacementVal,
                    $differenceAmount,
                    $dbRefundMode,
                    $payAction,
                    'WhatsApp Product Exchange'
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
                    $p = 0.0;
                    if ($swapVarId > 0) {
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
                                'WhatsApp Same Product Swap Dispatch'
                            );
                            $replacementStockDispatched[$swapVarId][$stockChannelId] = $alreadyDispatched + $dispatchQty;
                            $remainingSwapQty -= $dispatchQty;
                            if ($remainingSwapQty === 0) {
                                break;
                            }
                        }
                        if ($remainingSwapQty > 0) {
                            throw new RuntimeException('WhatsApp replacement stock changed while the exchange was being processed.');
                        }
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
                        StockService::decrease($repVarId, 'CHANNEL', $channelId, 'AVAILABLE', $repQty, 'EXCHANGE_DISPATCH', 'exchanges', $returnId, $userId, "WhatsApp Exchange New Catalog Swap Dispatch");
                    }
                }
            }
        }

        Session::flash('success', "WhatsApp Return/Exchange #{$returnNumber} processed successfully!");
        Response::redirect(url('warehouse/whatsapp-orders/returns'));
    }

    public function whatsappReturnInvoice(int $id): void {
        $return = Database::fetchOne(
            "SELECT r.*, 
                    s.invoice_number as sale_invoice,
                    s.invoice_number,
                    s.created_at as sale_date,
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name, 
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
             WHERE r.id = ? AND r.concern_id = ? AND r.source_type = 'WHATSAPP'",
            [$id, ConcernTransactionService::activeConcernId()]
        );

        if (!$return) {
            Session::flash('error', 'Return record not found.');
            Response::redirect(url('warehouse/whatsapp-orders/returns'));
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

        Response::view('whatsapp_orders.return_invoice', [
            'pageTitle'     => 'Return Invoice #' . $return['return_number'],
            'return'        => $return,
            'items'         => $items,
            'exchangeItems' => $exchangeItems
        ]);
    }

    public function whatsappReturnView(int $id): void {
        $this->whatsappReturnInvoice($id);
    }

    /**
     * WhatsApp Customer Directory & Statistics
     */
    public function whatsappCustomers(): void {
        $search = trim($_GET['search'] ?? '');
        $where = ["(s.sales_channel = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%')"];
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

        Response::view('whatsapp_orders.customers', [
            'pageTitle'    => 'WhatsApp Customers Directory',
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'wh-whatsapp-customers',
            'breadcrumb'   => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Customers']
            ],
            'customers'    => $customers,
            'search'       => $search
        ]);
    }

    /**
     * WhatsApp Damage Management Dashboard & Items List
     */
    public function whatsappDamageIndex(): void {
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 3);

        $filters = [
            'channel_id'  => $channelId,
            'status'      => $_GET['status'] ?? '',
            'defect_type' => $_GET['defect_type'] ?? '',
            'search'      => $_GET['search'] ?? '',
            'date_from'   => $_GET['date_from'] ?? '',
            'date_to'     => $_GET['date_to'] ?? '',
        ];

        $damages = ChannelDamage::getAll($filters);
        $kpi = ChannelDamage::getSummaryStats($channelId);

        Response::view('whatsapp_orders.damage.index', [
            'damages'      => $damages,
            'stats'        => $kpi,
            'kpi'          => $kpi,
            'filters'      => $filters,
            'channel'      => $chan,
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'wh-whatsapp-damage',
            'pageTitle'    => 'WhatsApp Damage Management'
        ]);
    }

    /**
     * Create Manual Damage Entry
     */
    public function createWhatsappDamage(): void {
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 3);
        $chanIds = $this->getWhatsappChannelIds();
        $chanIn = implode(',', array_fill(0, count($chanIds), '?'));

        $prefillVariantId = (int)($_GET['variant_id'] ?? 0);
        $prefillTagCode   = trim($_GET['tag_code'] ?? '');
        $prefillAllocId   = (int)($_GET['allocation_id'] ?? 0);

        $defectTypes = [
            'FABRIC_DEFECT'    => 'Fabric Defect (Tear/Hole/Stain)',
            'STITCHING_DEFECT' => 'Stitching / Seam Defect',
            'COLOR_MISMATCH'   => 'Color / Dyeing Inconsistency',
            'SIZE_MISMATCH'    => 'Incorrect Size / Label Error',
            'TRANSIT_DAMAGE'   => 'Transit / Handling Damage',
            'DIRTY_SOILED'     => 'Soiled / Stained Goods',
            'MISSING_PARTS'    => 'Missing Buttons / Accessories',
            'PACKAGING_DAMAGED'=> 'Damaged Packaging / Broken Seal',
            'OTHER'            => 'Other Defect'
        ];

        // Fetch variants available/inwarded into WhatsApp channel
        $variants = Database::fetchAll(
            "SELECT pv.*, p.name as product_name, p.code as product_code, p.image_url as product_image,
                    c.name as color_name, s.name as size_name,
                    COALESCE((
                        SELECT SUM(sb.quantity) FROM stock_balances sb
                        WHERE sb.variant_id = pv.id AND sb.location_type = 'CHANNEL' AND sb.location_id IN ($chanIn) AND sb.stock_state = 'AVAILABLE'
                    ), 0) as available_qty
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             ORDER BY p.name ASC, pv.sku ASC",
            $chanIds
        );

        Response::view('whatsapp_orders.damage.create', [
            'channel'          => $chan,
            'defectTypes'      => $defectTypes,
            'variants'         => $variants,
            'prefillVariantId' => $prefillVariantId,
            'prefillTagCode'   => $prefillTagCode,
            'prefillAllocId'   => $prefillAllocId,
            'activeModule'     => 'whatsapp',
            'activeMenu'       => 'wh-whatsapp-damage',
            'pageTitle'        => 'Log WhatsApp Damage',
            'error'            => $_SESSION['flash_error'] ?? null
        ]);
    }

    /**
     * Store Manual Damage Entry
     */
    public function storeWhatsappDamage(): void {
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 3);
        $chanIds = $this->getWhatsappChannelIds();
        $sessionUserId = Session::get('user_id');
        $userId = is_numeric($sessionUserId) && (int)$sessionUserId > 0
            ? (int)$sessionUserId
            : null;
        if ($userId !== null && !Database::fetchOne("SELECT id FROM users WHERE id = ?", [$userId])) {
            error_log("WhatsApp damage received an invalid session user ID; stock transactions will be recorded without a user.");
            $userId = null;
        }

        $variantId = (int)($_POST['variant_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));
        $costPerUnit = (float)($_POST['cost_per_unit'] ?? 0);
        $defectType = trim($_POST['defect_type'] ?? 'OTHER');
        $sourceType = trim($_POST['source_type'] ?? 'STORAGE_HANDLING');
        $damageReason = trim($_POST['damage_reason'] ?? 'Manual WhatsApp defect quarantine');
        $defectNotes = trim($_POST['defect_notes'] ?? '');
        $photoUrl = trim($_POST['photo_url'] ?? '');
        $tagCode = trim($_POST['tag_code'] ?? '');
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
            Response::redirect(url('warehouse/whatsapp-orders/damage/create'));
            return;
        }

        // Existing damage records without corresponding stock movements can remain
        // when an older request failed halfway through. Reuse those records on retry.
        $reusableDamageRecordsByTag = [];

        // Validate all items first
        foreach ($items as $idx => $itm) {
            $vId = (int)($itm['variant_id'] ?? 0);
            $qty = max(1, (int)($itm['quantity'] ?? 1));
            $tCode = trim($itm['tag_code'] ?? '');

            if ($vId <= 0) {
                $_SESSION['flash_error'] = "Invalid product variant on item #" . ($idx + 1);
                Response::redirect(url('warehouse/whatsapp-orders/damage/create'));
                return;
            }

            if (!empty($tCode)) {
                $existingDamage = Database::fetchOne(
                    "SELECT id, damage_number, channel_id, variant_id, quantity, status FROM channel_damages
                     WHERE (tag_code = ? OR tag_code LIKE ? OR tag_code LIKE ? OR tag_code LIKE ?)
                       AND status NOT IN ('REWORK_RETURNED_TO_STOCK', 'CANCELLED', 'RESOLVED')
                     LIMIT 1",
                    [$tCode, "{$tCode},%", "%,{$tCode}", "%,{$tCode},%"]
                );
                if ($existingDamage) {
                    $sameDamageRecord = (int)$existingDamage['channel_id'] === $channelId
                        && (int)$existingDamage['variant_id'] === $vId
                        && (int)$existingDamage['quantity'] === $qty
                        && strtoupper((string)$existingDamage['status']) === 'LOGGED';
                    $stockMovementExists = Database::fetchOne(
                        "SELECT id FROM stock_transactions
                         WHERE variant_id = ? AND location_type = 'CHANNEL' AND location_id = ?
                           AND concern_id = " . (int)currentConcernId() . "
                           AND transaction_type = 'DAMAGE_LOGGED' AND reference_type = 'channel_damages'
                           AND notes LIKE ?
                         LIMIT 1",
                        [$vId, $channelId, '% ' . $existingDamage['damage_number'] . '%']
                    );

                    if ($sameDamageRecord && !$stockMovementExists) {
                        $reusableDamageRecordsByTag[$tCode] = $existingDamage;
                    } else {
                        $_SESSION['flash_error'] = "Piece tag '{$tCode}' is ALREADY logged in Damaged Stock (Ref: {$existingDamage['damage_number']}). Only available good inventory stock can be logged as damaged.";
                        Response::redirect(url('warehouse/whatsapp-orders/damage/create'));
                        return;
                    }
                }

                $variantType = Database::fetchOne(
                    "SELECT COALESCE(wc.category_type, 'NORMAL') as category_type, COALESCE(pv.is_complementary, p.is_complementary, 0) as is_complementary, pv.qr_type, pv.pack_quantity
                     FROM product_variants pv JOIN products p ON p.id = pv.product_id
                     LEFT JOIN warehouse_categories wc ON wc.id = p.warehouse_category_id WHERE pv.id = ? LIMIT 1",
                    [$vId]
                ) ?: [];
                $isAggregateStock = in_array(strtoupper((string)($variantType['category_type'] ?? 'NORMAL')), ['SERIES', 'COMPLEMENTARY'], true)
                    || !empty($variantType['is_complementary'])
                    || strtoupper((string)($variantType['qr_type'] ?? '')) === 'SERIES_BATCH'
                    || (int)($variantType['pack_quantity'] ?? 1) > 1;
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
                    Response::redirect(url('warehouse/whatsapp-orders/damage/create'));
                    return;
                }
            }

            $avail = 0;
            foreach ($chanIds as $stockChannelId) {
                $avail += StockService::getBalance($vId, 'CHANNEL', (int)$stockChannelId, 'AVAILABLE');
            }
            if ($avail < $qty) {
                $_SESSION['flash_error'] = "Insufficient available good stock in WhatsApp Channel for item #" . ($idx + 1) . " (Available: {$avail}, Requested: {$qty}).";
                Response::redirect(url('warehouse/whatsapp-orders/damage/create'));
                return;
            }
        }

        // Process all items atomically so a stock movement failure cannot leave a
        // damage record behind without the matching available/damaged ledger entries.
        $loggedNumbers = [];
        $totalQtyLogged = 0;
        Database::beginTransaction();
        try {
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

            $dmgNum = $reusableDamageRecordsByTag[$tCode]['damage_number'] ?? null;
            if (!$dmgNum) {
                $dmgNum = ChannelDamage::create([
                    'channel_id'       => $channelId,
                    'variant_id'       => $vId,
                    'allocation_id'    => $allocId,
                    'tag_code'         => $tCode,
                    'quantity'         => $qty,
                    'cost_per_unit'    => $cUnit,
                    'defect_type'      => $defectType,
                    'source_type'      => $sourceType,
                    'damage_reason'    => $damageReason,
                    'defect_notes'     => $defectNotes,
                    'photo_url'        => $photoUrl,
                    'status'           => 'LOGGED',
                    'reported_by'      => $userId
                ]);
            }
            $damageRecord = Database::fetchOne("SELECT id FROM channel_damages WHERE damage_number = ?", [$dmgNum]);
            $damageRecordId = (int)($damageRecord['id'] ?? 0);

            $stockChannelPlaceholders = implode(',', array_fill(0, count($chanIds), '?'));
            $stockLocation = Database::fetchOne(
                "SELECT location_id FROM stock_balances
                 WHERE variant_id = ? AND location_type = 'CHANNEL' AND location_id IN ($stockChannelPlaceholders)
                   AND concern_id = " . (int)currentConcernId() . " AND stock_state = 'AVAILABLE' AND quantity >= ?
                 ORDER BY quantity DESC LIMIT 1",
                array_merge([$vId], $chanIds, [(float)$qty])
            );
            $movementLocationId = (int)($stockLocation['location_id'] ?? $channelId);

            StockService::decrease(
                variantId: $vId,
                locationType: 'CHANNEL',
                locationId: $movementLocationId,
                stockState: 'AVAILABLE',
                quantity: (float)$qty,
                transactionType: 'DAMAGE_LOGGED',
                referenceType: 'channel_damages',
                referenceId: $damageRecordId,
                userId: $userId,
                notes: "Damaged stock quarantined: {$dmgNum}"
            );

            StockService::increase(
                variantId: $vId,
                locationType: 'CHANNEL',
                locationId: $movementLocationId,
                stockState: 'DAMAGED',
                quantity: (float)$qty,
                transactionType: 'DAMAGE_LOGGED',
                referenceType: 'channel_damages',
                referenceId: $damageRecordId,
                userId: $userId,
                notes: "Damaged stock quarantined: {$dmgNum}"
            );

            $loggedNumbers[] = $dmgNum;
            $totalQtyLogged += $qty;
        }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }

        $summaryMsg = count($loggedNumbers) === 1
            ? "Damage record {$loggedNumbers[0]} logged successfully ({$totalQtyLogged} pcs quarantined)."
            : count($loggedNumbers) . " damage records (" . implode(', ', array_slice($loggedNumbers, 0, 3)) . (count($loggedNumbers) > 3 ? '...' : '') . ") logged successfully ({$totalQtyLogged} pcs quarantined).";

        $_SESSION['flash_success'] = $summaryMsg;
        Response::redirect(url('warehouse/whatsapp-orders/damage'));
    }

    /**
     * View Single Damage Record
     */
    public function viewWhatsappDamage(int $id): void {
        $damage = ChannelDamage::find($id);
        if (!$damage) {
            $_SESSION['flash_error'] = "Damage record not found.";
            Response::redirect(url('warehouse/whatsapp-orders/damage'));
            return;
        }

        Response::view('whatsapp_orders.damage.view', [
            'damage'       => $damage,
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'wh-whatsapp-damage',
            'pageTitle'    => 'Damage Record: ' . $damage['damage_number']
        ]);
    }

    /**
     * WhatsApp Damaged Stock Outward Batches List
     */
    public function whatsappDamageOutwardList(): void {
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 3);

        $batches = Database::fetchAll(
            "SELECT wr.*, 
                    w.name as warehouse_name, w.code as warehouse_code,
                    u.full_name as created_by_name,
                    (SELECT COALESCE(SUM(returned_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as total_quantity,
                    (SELECT COUNT(*) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as items_count
             FROM warehouse_returns wr
             LEFT JOIN warehouses w ON wr.warehouse_id = w.id
             LEFT JOIN users u ON wr.received_by = u.id
             WHERE wr.source_type = 'WHATSAPP' AND wr.source_id = ? AND wr.concern_id = ?
             ORDER BY wr.created_at DESC",
            [$channelId, ConcernTransactionService::activeConcernId()]
        );

        $kpi = [
            'total_batches' => count($batches),
            'in_transit'    => count(array_filter($batches, fn($b) => in_array($b['status'], ['RECEIVED', 'IN_TRANSIT', 'INSPECTING', 'PENDING']))),
            'completed'     => count(array_filter($batches, fn($b) => in_array($b['status'], ['COMPLETED', 'CLASSIFIED']))),
            'total_pieces'  => array_sum(array_column($batches, 'total_quantity'))
        ];

        Response::view('whatsapp_orders.damage.outward_list', [
            'batches'      => $batches,
            'kpi'          => $kpi,
            'channel'      => $chan,
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'wh-whatsapp-damage',
            'pageTitle'    => 'Damaged Outward Batches'
        ]);
    }

    /**
     * Create Damaged Stock Outward Batch Form
     */
    public function createWhatsappDamageOutward(): void {
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 3);

        $pendingDamages = ChannelDamage::getPendingOutward($channelId);
        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];

        Response::view('whatsapp_orders.damage.outward_create', [
            'channel'            => $chan,
            'pendingDamages'     => $pendingDamages,
            'warehouses'         => $warehouses,
            'defaultWarehouseId' => $warehouses[0]['id'] ?? 1,
            'activeModule'       => 'whatsapp',
            'activeMenu'         => 'wh-whatsapp-damage',
            'pageTitle'          => 'Create Damaged Outward Batch',
            'error'              => $_SESSION['flash_error'] ?? null
        ]);
    }

    /**
     * Store Damaged Stock Outward Batch
     */
    public function storeWhatsappDamageOutward(): void {
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 3);
        $userId = Session::get('user_id') ?? 1;

        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $carrierName = trim($_POST['carrier_name'] ?? '');
        $vehicleNo   = trim($_POST['vehicle_no'] ?? '');
        $driverName  = trim($_POST['driver_name'] ?? '');
        $driverPhone = trim($_POST['driver_phone'] ?? '');
        $remarks     = trim($_POST['remarks'] ?? '');

        $damageIds   = $_POST['damage_ids'] ?? [];
        $dispatchQty = $_POST['dispatch_qty'] ?? [];

        if ($warehouseId <= 0 || empty($damageIds)) {
            $_SESSION['flash_error'] = "Please select a destination warehouse and at least one damaged product item.";
            Response::redirect(url('warehouse/whatsapp-orders/damage/outward/create'));
            return;
        }

        // Generate return number
        $returnNumber = 'RET-WA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $notesData = [
            'carrier_name' => $carrierName,
            'vehicle_no'   => $vehicleNo,
            'driver_name'  => $driverName,
            'driver_phone' => $driverPhone,
            'remarks'      => $remarks
        ];

        // Insert into warehouse_returns
        Database::execute(
            "INSERT INTO warehouse_returns 
             (return_number, concern_id, source_type, source_id, warehouse_id, destination_warehouse_id, reason, status, notes, received_by, created_at, updated_at)
             VALUES (?, ?, 'WHATSAPP', ?, ?, ?, 'WHATSAPP_DAMAGED_RETURN', 'RECEIVED', ?, ?, NOW(), NOW())",
            [
                $returnNumber,
                ConcernTransactionService::activeConcernId(),
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

            // Insert into warehouse_return_items
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

            // Update ChannelDamage record
            Database::execute(
                "UPDATE channel_damages SET outward_batch_id = ?, status = 'DISPATCHED', updated_at = NOW() WHERE id = ?",
                [$returnId, $dmg['id']]
            );

            // Decrease DAMAGED stock from WhatsApp channel
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
        Response::redirect(url('warehouse/whatsapp-orders/damage/outward/view/' . $returnId));
    }

    /**
     * Helper to fetch return batch with full details
     */
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
             WHERE wr.id = ? AND wr.concern_id = ?",
            [$id, ConcernTransactionService::activeConcernId()]
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

        // Fetch associated damage records for piece tags
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

    /**
     * View Damaged Stock Outward Batch Details
     */
    public function viewWhatsappDamageOutward(int $id): void {
        $batch = $this->getDamageOutwardBatch($id);
        if (!$batch) {
            $_SESSION['flash_error'] = "Outward Batch not found.";
            Response::redirect(url('warehouse/whatsapp-orders/damage/outward'));
            return;
        }

        Response::view('whatsapp_orders.damage.outward_view', [
            'batch'        => $batch,
            'items'        => $batch['items'],
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'wh-whatsapp-damage',
            'pageTitle'    => 'Outward Batch: ' . ($batch['return_number'] ?? ('RET-WA-' . $batch['id']))
        ]);
    }

    /**
     * Print Delivery Gate Pass for Damaged Outward Batch
     */
    public function printWhatsappDamageGatePass(int $id): void {
        $batch = $this->getDamageOutwardBatch($id);
        if (!$batch) {
            echo "Outward Batch not found.";
            return;
        }

        Response::viewOnly('whatsapp_orders.damage.gatepass', [
            'batch' => $batch,
            'items' => $batch['items']
        ]);
    }

    /**
     * Print Carton QR Label for Damaged Outward Batch
     */
    public function printWhatsappDamageCartonLabel(int $id): void {
        $batch = $this->getDamageOutwardBatch($id);
        if (!$batch) {
            echo "Outward Batch not found.";
            return;
        }

        Response::viewOnly('whatsapp_orders.damage.carton_label', [
            'batch' => $batch,
            'items' => $batch['items']
        ]);
    }

    /**
     * AJAX Search for Products / Variants in Damage Module
     */
    public function ajaxLookupDamageProduct(): void {
        header('Content-Type: application/json');
        $rawCode = trim($_REQUEST['query'] ?? $_REQUEST['q'] ?? $_REQUEST['code'] ?? $_REQUEST['search'] ?? '');
        $chan = $this->getChannel();
        $channelId = (int)($chan['id'] ?? 3);
        $chanIds = $this->getWhatsappChannelIds();

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
                            COALESCE(p.gst_percentage, 5.0) as gst_rate,
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
                    COALESCE(p.gst_percentage, 5.0) as gst_rate,
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

        $results = [];
        foreach ($variants as $v) {
            $avail = 0;
            $dmgStock = 0;
            foreach ($chanIds as $wId) {
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
            $avail = 0;
            $dmgStock = 0;
            foreach ($chanIds as $wId) {
                $avail += StockService::getBalance((int)$foundVariant['id'], 'CHANNEL', $wId, 'AVAILABLE');
                $dmgStock += StockService::getBalance((int)$foundVariant['id'], 'CHANNEL', $wId, 'DAMAGED');
            }
            if ($avail <= 0) {
                $avail = StockService::getBalance((int)$foundVariant['id'], 'SHOP', 1, 'AVAILABLE');
            }
            // Restriction 1: Check if piece tag is already logged as damaged / quarantined
            if (!empty($matchedTagCode)) {
                $existingDamage = Database::fetchOne(
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

                $variantType = Database::fetchOne(
                    "SELECT COALESCE(wc.category_type, 'NORMAL') as category_type, COALESCE(pv.is_complementary, p.is_complementary, 0) as is_complementary, pv.qr_type, pv.pack_quantity
                     FROM product_variants pv JOIN products p ON p.id = pv.product_id
                     LEFT JOIN warehouse_categories wc ON wc.id = p.warehouse_category_id WHERE pv.id = ? LIMIT 1",
                    [(int)$foundVariant['id']]
                ) ?: [];
                $isAggregateStock = in_array(strtoupper((string)($variantType['category_type'] ?? 'NORMAL')), ['SERIES', 'COMPLEMENTARY'], true)
                    || !empty($variantType['is_complementary'])
                    || strtoupper((string)($variantType['qr_type'] ?? '')) === 'SERIES_BATCH'
                    || (int)($variantType['pack_quantity'] ?? 1) > 1;
                // Shared batch tags may be sold while aggregate stock remains.
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
            foreach ($chanIds as $stockChannelId) {
                $avail += StockService::getBalance((int)$foundVariant['id'], 'CHANNEL', (int)$stockChannelId, 'AVAILABLE');
            }
            if ($avail <= 0) {
                echo json_encode([
                    'success' => false,
                    'is_out_of_stock' => true,
                    'message' => "Product variant '{$foundVariant['product_name']}' ({$foundVariant['sku']}) has 0 available good inventory stock in WhatsApp channel."
                ]);
                return;
            }

            $dmgStock = StockService::getBalance((int)$foundVariant['id'], 'CHANNEL', $channelId, 'DAMAGED');
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
                'message' => "Product variant not found in WhatsApp inventory for '{$rawCode}'.",
                'results' => $results
            ]);
        }
    }

    /**
     * AJAX Lookup for Return & Exchange Piece Tag / Barcode / SKU
     */
    public function ajaxLookupReturnPiece(): void {
        header('Content-Type: application/json');
        $rawCode = trim($_REQUEST['query'] ?? $_REQUEST['q'] ?? $_REQUEST['code'] ?? $_REQUEST['search'] ?? '');
        $whatsappChannelIds = $this->getWhatsappChannelIds();
        
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

        // Strategy 1: Piece tag in allocation_items (selected_qr_codes)
        foreach ($candidateCodes as $c) {
            if (empty($c) || strlen($c) < 2) continue;
            $allocItem = Database::fetchOne(
                "SELECT ai.variant_id, ai.selected_qr_codes
                 FROM allocation_items ai
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
                    break;
                }
            }
        }

        // Strategy 2: Piece tag in sale_items (scanned_qr_codes)
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (empty($c) || strlen($c) < 2) continue;
                $saleItem = Database::fetchOne(
                    "SELECT si.variant_id 
                     FROM sale_items si
                     WHERE (si.scanned_qr_codes = ? 
                        OR si.scanned_qr_codes LIKE ? 
                        OR si.scanned_qr_codes LIKE ? 
                        OR si.scanned_qr_codes LIKE ?
                        OR FIND_IN_SET(?, REPLACE(COALESCE(si.scanned_qr_codes, ''), ' ', '')) > 0
                        OR si.qr_code = ?)
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

        // Strategy 3: Direct match on candidate codes (SKU, QR Code, Product Code, ID)
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
                     WHERE pv.sku = ? OR pv.qr_code = ? OR p.code = ? OR (pv.id = ? AND ? > 0)
                     LIMIT 1",
                    [$c, $c, $c, is_numeric($c) ? (int)$c : 0, is_numeric($c) ? (int)$c : 0]
                );
                if ($foundVariant) {
                    $matchedTagCode = $c;
                    break;
                }
            }
        }

        // Strategy 4: Fuzzy / Substring match
        if (!$foundVariant) {
            foreach ($candidateCodes as $c) {
                if (strlen($c) < 2) continue;
                $foundVariant = Database::fetchOne(
                    "SELECT pv.*, p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                            c.name as color_name, s.name as size_name
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN colors c ON pv.color_id = c.id
                     LEFT JOIN sizes s ON pv.size_id = s.id
                     WHERE pv.sku LIKE ? OR p.name LIKE ? OR p.code LIKE ? OR pv.qr_code LIKE ?
                     LIMIT 1",
                    ["%{$c}%", "%{$c}%", "%{$c}%", "%{$c}%"]
                );
                if ($foundVariant) {
                    $matchedTagCode = $c;
                    break;
                }
            }
        }

        if ($foundVariant) {
            $sellingPrice = (float)($foundVariant['selling_price'] ?? $foundVariant['price'] ?? 0);
            $channelPlaceholders = implode(',', array_fill(0, count($whatsappChannelIds), '?'));
            $availableStock = (float)(Database::fetchOne(
                "SELECT COALESCE(SUM(quantity), 0) as available_stock
                 FROM stock_balances
                 WHERE variant_id = ? AND location_type = 'CHANNEL'
                   AND location_id IN ($channelPlaceholders) AND concern_id = " . (int)currentConcernId() . " AND stock_state = 'AVAILABLE'",
                array_merge([(int)$foundVariant['id']], $whatsappChannelIds)
            )['available_stock'] ?? 0);
            $payload = [
                'id'            => (int)$foundVariant['id'],
                'variant_id'    => (int)$foundVariant['id'],
                'product_id'    => (int)$foundVariant['product_id'],
                'product_name'  => $foundVariant['product_name'],
                'product_code'  => $foundVariant['product_code'],
                'sku'           => $foundVariant['sku'],
                'size_name'     => $foundVariant['size_name'] ?? 'Free Size',
                'color_name'    => $foundVariant['color_name'] ?? 'Standard',
                'selling_price' => $sellingPrice,
                'price'         => $sellingPrice,
                'mrp'           => (float)($foundVariant['mrp'] ?? $sellingPrice),
                'stock'         => $availableStock,
                'tag_code'      => $matchedTagCode
            ];
            echo json_encode([
                'success'  => true,
                'tag_code' => $matchedTagCode,
                'data'     => $payload,
                'variant'  => $payload,
                'product'  => $payload
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "No product or piece tag matching '{$rawCode}' found."
            ]);
        }
    }

    // ==========================================
    // WHATSAPP CHANNEL EXPENSE MANAGEMENT & OUTFLOW
    // ==========================================

    public function expensesIndex(): void {
        require_once BASE_PATH . '/app/Services/ExpenseService.php';
        $chan = $this->getChannel();
        $channelId = (int)$chan['id'];
        $concernId = ConcernTransactionService::activeConcernId();

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

        $expenses = ExpenseService::getExpenses('WHATSAPP', $channelId, $filters);
        $summary = ExpenseService::getSummary('WHATSAPP', $channelId, $startDate, $endDate);
        $categories = ExpenseService::getCategories('WHATSAPP');

        // Fetch WhatsApp staff list for agent tagging
        $staffMembers = Database::fetchAll("SELECT id, full_name, email FROM users WHERE is_active = 1 ORDER BY full_name ASC");

        // Fetch recent sales for quick order-tag dropdown
        $recentOrders = Database::fetchAll(
            "SELECT id, invoice_number, customer_name, total_amount 
             FROM sales 
             WHERE concern_id = ? AND (sales_channel = 'WHATSAPP' OR sale_type = 'WHATSAPP')
             ORDER BY id DESC LIMIT 50",
            [$concernId]
        );

        Response::view('whatsapp_orders.expenses.index', [
            'pageTitle'     => 'WhatsApp Channel Expenses & Outflow',
            'activeModule'  => 'whatsapp',
            'activeMenu'    => 'wh-whatsapp-expenses',
            'breadcrumb'    => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Channel Expenses'],
            ],
            'channel'       => $chan,
            'expenses'      => $expenses,
            'summary'       => $summary,
            'categories'    => $categories,
            'staffMembers'  => $staffMembers,
            'recentOrders'  => $recentOrders,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'filters'       => $filters,
        ]);
    }

    public function createExpense(): void {
        $this->expensesIndex();
    }

    public function storeExpense(): void {
        require_once BASE_PATH . '/app/Services/ExpenseService.php';
        $chan = $this->getChannel();
        $userId = Session::userId() ?? 1;

        $payload = $_POST;
        $payload['location_type'] = 'WHATSAPP';
        $payload['location_id'] = (int)$chan['id'];
        $payload['location_name'] = $chan['name'] ?? 'WhatsApp Channel';

        try {
            $res = ExpenseService::recordExpense($payload, $userId);
            Session::flash('success', "WhatsApp Channel Expense Voucher #{$res['expense_number']} for ₹" . number_format($res['amount'], 2) . " recorded successfully.");
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect(url('warehouse/whatsapp-orders/expenses'));
    }

    public function printExpenseVoucher(int $id): void {
        require_once BASE_PATH . '/app/Services/ExpenseService.php';
        $expense = ExpenseService::getVoucherDetails($id);
        if (!$expense) {
            die("WhatsApp expense record not found.");
        }

        Response::viewOnly('whatsapp_orders.expenses.voucher', [
            'expense' => $expense,
            'module'  => 'whatsapp'
        ]);
    }

    public function confirmDeleteExpense(int $id): void {
        require_once BASE_PATH . '/app/Services/ExpenseService.php';
        $expense = ExpenseService::getVoucherDetails($id);
        $channel = $this->getChannel();
        if (!$expense || ($expense['location_type'] ?? '') !== 'WHATSAPP' || (int)($expense['location_id'] ?? 0) !== (int)$channel['id']) {
            Session::flash('error', 'WhatsApp expense record not found.');
            Response::redirect(url('warehouse/whatsapp-orders/expenses'));
            return;
        }

        Response::view('whatsapp_orders.expenses.delete_confirm', [
            'pageTitle' => 'Confirm WhatsApp Expense Deletion',
            'activeModule' => 'whatsapp',
            'activeMenu' => 'wh-whatsapp-expenses',
            'breadcrumb' => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Channel Expenses', 'url' => url('warehouse/whatsapp-orders/expenses')],
                ['label' => 'Confirm Deletion'],
            ],
            'expense' => $expense,
        ]);
    }

    public function deleteExpense(int $id): void {
        if (!hasPermission('whatsapp_expenses', 'delete')) {
            Session::flash('error', 'Access denied. You do not have permission to delete WhatsApp expenses.');
            Response::redirect(url('warehouse/whatsapp-orders/expenses'));
            return;
        }

        CSRF::verify();
        require_once BASE_PATH . '/app/Services/ExpenseService.php';
        $expense = ExpenseService::getVoucherDetails($id);
        $channel = $this->getChannel();
        if (!$expense || ($expense['location_type'] ?? '') !== 'WHATSAPP' || (int)($expense['location_id'] ?? 0) !== (int)$channel['id']) {
            Session::flash('error', 'WhatsApp expense record not found.');
            Response::redirect(url('warehouse/whatsapp-orders/expenses'));
            return;
        }

        $userId = Session::userId() ?? 1;
        if (!ExpenseService::deleteExpense($id, $userId)) {
            Session::flash('error', 'WhatsApp expense could not be deleted.');
            Response::redirect(url('warehouse/whatsapp-orders/expenses'));
            return;
        }

        Session::flash('success', "Expense record deleted successfully.");
        Response::redirect(url('warehouse/whatsapp-orders/expenses'));
    }

    /**
     * =========================================================================
     * RETURN TO WAREHOUSE (WHATSAPP CHANNEL -> CENTRAL WAREHOUSE DISPATCH)
     * =========================================================================
     */

    /**
     * WhatsApp Returns to Warehouse List & Dispatch Dashboard
     */
    public function returnToWarehouseList(): void {
        $chan = $this->getChannel();
        $chanId = (int)$chan['id'];

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => trim($_GET['status'] ?? 'ALL'),
        ];

        $data = $this->getWhatsappWarehouseReturns($chanId, $filters);

        Response::view('whatsapp_orders.return_to_warehouse.index', [
            'pageTitle'    => 'WhatsApp Return to Warehouse Consignments',
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'whatsapp-return-to-wh',
            'breadcrumb'   => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Return to Warehouse'],
            ],
            'channel'      => $chan,
            'returns'      => $data['returns'],
            'kpis'         => $data['kpis'],
            'filters'      => $filters,
        ]);
    }

    /**
     * Create New Return to Warehouse Form for WhatsApp Channel
     */
    public function createReturnToWarehouse(): void {
        $chan = $this->getChannel();
        $chanId = (int)$chan['id'];
        $chanIds = $this->getWhatsappChannelIds();
        $placeholders = implode(',', array_fill(0, count($chanIds), '?'));

        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];

        // Fetch inventory items available in WhatsApp channel (both AVAILABLE and DAMAGED stock)
        $inventory = Database::fetchAll(
            "SELECT sb.variant_id, sb.stock_state, sb.quantity,
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
               AND sb.concern_id = " . (int)currentConcernId() . " AND sb.quantity > 0
             ORDER BY c.name ASC, p.name ASC, pv.sku ASC, sb.stock_state ASC",
            $chanIds
        );

        // Fetch piece tags mapped to variants for this channel
        $allocTags = Database::fetchAll(
            "SELECT ai.variant_id, ai.selected_qr_codes
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
             WHERE wa.concern_id = " . (int)currentConcernId() . "
               AND wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($placeholders)
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
            $pieceRes = $this->resolveChannelPieceTags($chanId, $vid, $chanIds, 'WHATSAPP');
            $item['piece_tags'] = ($item['stock_state'] === 'DAMAGED') ? $pieceRes['damaged'] : $pieceRes['available'];
            $item['piece_tags_all'] = $pieceRes['all'];

            $catName = $item['category_name'] ?? 'General';
            if (!in_array($catName, $categories)) {
                $categories[] = $catName;
            }
        }

        Response::view('whatsapp_orders.return_to_warehouse.create', [
            'pageTitle'    => 'Create Return to Warehouse Dispatch',
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'whatsapp-return-to-wh',
            'breadcrumb'   => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Return to Warehouse', 'url' => url('warehouse/whatsapp-orders/return-to-warehouse')],
                ['label' => 'New Return Dispatch'],
            ],
            'channel'      => $chan,
            'warehouses'   => $warehouses,
            'inventory'    => $inventory,
            'categories'   => $categories,
        ]);
    }

    /**
     * Store and Process Return to Warehouse for WhatsApp Channel
     */
    public function storeReturnToWarehouse(): void {
        $chan = $this->getChannel();
        $chanId = (int)$chan['id'];
        $userId = Session::userId() ?? 1;

        try {
            $result = $this->createWhatsappWarehouseReturn($chanId, $_POST, $userId);
            $returnNum = is_array($result) ? $result['return_number'] : $result;
            $returnId = is_array($result) ? $result['id'] : null;

            Session::setFlash('success', "Return to Warehouse #$returnNum created successfully and dispatched! Channel stock updated.");
            
            if ($returnId) {
                Response::redirect(url('warehouse/whatsapp-orders/return-to-warehouse/view/' . $returnId . '?outward_success=1'));
            } else {
                Response::redirect(url('warehouse/whatsapp-orders/return-to-warehouse'));
            }
        } catch (Exception $e) {
            Session::setFlash('error', "Failed to create return to warehouse: " . $e->getMessage());
            Response::redirect(url('warehouse/whatsapp-orders/return-to-warehouse/create'));
        }
    }

    /**
     * View Return to Warehouse Details Page for WhatsApp Channel
     */
    public function viewReturnToWarehouse(string $id): void {
        $details = $this->getWhatsappWarehouseReturnDetails((int)$id);
        if (!$details) {
            Session::setFlash('error', "Return consignment #$id not found.");
            Response::redirect(url('warehouse/whatsapp-orders/return-to-warehouse'));
            return;
        }

        Response::view('whatsapp_orders.return_to_warehouse.view', array_merge($details, [
            'pageTitle'    => 'Return #' . $details['return']['return_number'] . ' Manifest',
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'whatsapp-return-to-wh',
            'breadcrumb'   => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Return to Warehouse', 'url' => url('warehouse/whatsapp-orders/return-to-warehouse')],
                ['label' => $details['return']['return_number']],
            ],
        ]));
    }

    /**
     * Print Delivery Gate Pass / Return Challan for WhatsApp Return to Warehouse
     */
    public function printReturnToWarehouseGatePass(string $id): void {
        $details = $this->getWhatsappWarehouseReturnDetails((int)$id);
        if (!$details) {
            Session::setFlash('error', "Return consignment #$id not found.");
            Response::redirect(url('warehouse/whatsapp-orders/return-to-warehouse'));
            return;
        }

        Response::viewOnly('whatsapp_orders.return_to_warehouse.gatepass', $details);
    }

    /**
     * Print Carton QR Shipping Label for WhatsApp Return to Warehouse
     */
    public function printReturnToWarehouseLabel(string $id): void {
        $details = $this->getWhatsappWarehouseReturnDetails((int)$id);
        if (!$details) {
            Session::setFlash('error', "Return consignment #$id not found.");
            Response::redirect(url('warehouse/whatsapp-orders/return-to-warehouse'));
            return;
        }

        Response::viewOnly('whatsapp_orders.return_to_warehouse.label', $details);
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
     * Update Product Selling Price & MRP from WhatsApp Inventory
     */
    public function updateProductPrice(): void {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                  (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) ||
                  isset($_POST['is_ajax']) || isset($_GET['ajax']);

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
        $notes = trim($_POST['notes'] ?? $_POST['revision_notes'] ?? 'WhatsApp Inventory price adjustment');

        if (empty($variantIds)) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => 'Please select at least one product variant.'], 400);
                return;
            }
            Session::setFlash('error', 'Please select at least one product variant.');
            Response::redirect(url('warehouse/whatsapp-orders/allocations'));
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
            Response::redirect(url('warehouse/whatsapp-orders/allocations'));
        } catch (Exception $e) {
            if ($isAjax) {
                Response::json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }
            Session::setFlash('error', 'Failed to update price: ' . $e->getMessage());
            Response::redirect(url('warehouse/whatsapp-orders/allocations'));
        }
    }

    /**
     * Helper: Get WhatsApp Return Consignments & KPIs
     */
    private function getWhatsappWarehouseReturns(int $channelId, array $filters = []): array {
        $chanIds = $this->getWhatsappChannelIds();
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
                WHERE wr.concern_id = ? AND (wr.source_type = 'WHATSAPP' OR (wr.source_type = 'CHANNEL' AND wr.source_id IN ($placeholders)))
                  AND wr.source_id IN ($placeholders)";
        
        $params = array_merge([ConcernTransactionService::activeConcernId()], $chanIds, $chanIds);

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
                   WHERE wr.concern_id = ? AND (wr.source_type = 'WHATSAPP' OR (wr.source_type = 'CHANNEL' AND wr.source_id IN ($placeholders)))
                     AND wr.source_id IN ($placeholders)";
        $kpis = Database::fetchOne($kpiSql, array_merge([ConcernTransactionService::activeConcernId()], $chanIds, $chanIds)) ?: [
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
     * Helper: Create WhatsApp Warehouse Return and deduct Channel Stock
     */
    private function createWhatsappWarehouseReturn(int $channelId, array $payload, int $userId): array {
        $warehouseId = (int)($payload['warehouse_id'] ?? 1);
        ConcernTransactionService::assertFacility('WHATSAPP', $channelId);
        ConcernTransactionService::assertFacility('WAREHOUSE', $warehouseId);
        $reason = trim($payload['reason'] ?? 'Damaged & Defective Stock Return');
        $items = $payload['items'] ?? [];

        if (empty($items)) {
            throw new InvalidArgumentException("Please select at least one product item to return to the warehouse.");
        }

        Database::beginTransaction();
        try {
            $today = date('Ymd');
            $prefix = 'RET-WA-' . $today . '-';
            $last = Database::fetchOne(
                "SELECT return_number FROM warehouse_returns WHERE return_number LIKE ? AND concern_id = ? ORDER BY id DESC LIMIT 1",
                [$prefix . '%', ConcernTransactionService::activeConcernId()]
            );
            $seq = 1;
            if ($last && !empty($last['return_number'])) {
                $parts = explode('-', $last['return_number']);
                $seq = (int)end($parts) + 1;
            }
            $returnNumber = sprintf('%s%04d', $prefix, $seq);

            $carrierName  = trim($payload['carrier_name'] ?? '');
            $vehicleNo    = trim($payload['vehicle_no'] ?? '');
            $driverName   = trim($payload['driver_name'] ?? '');
            $driverPhone  = trim($payload['driver_phone'] ?? '');
            $trackingNo   = trim($payload['tracking_no'] ?? '');
            $cartonCount  = (int)($payload['carton_count'] ?? 1);
            $dispatchDate = trim($payload['dispatch_date'] ?? date('Y-m-d H:i:s'));
            $remarks      = trim($payload['remarks'] ?? ($payload['notes'] ?? ''));

            $notesData = [
                'carrier_name'  => $carrierName ?: 'GRM Logistics / Handover',
                'vehicle_no'    => $vehicleNo,
                'driver_name'   => $driverName,
                'driver_phone'  => $driverPhone,
                'tracking_no'   => $trackingNo,
                'carton_count'  => max(1, $cartonCount),
                'dispatch_date' => $dispatchDate,
                'remarks'       => $remarks,
                'scanned_tags'  => array_values(array_filter(array_map(static function ($it) {
                    $rawTags = $it['scanned_qr_codes'] ?? '[]';
                    $tags = is_array($rawTags) ? $rawTags : json_decode((string)$rawTags, true);
                    if (!is_array($tags)) $tags = [];
                    if (!$tags && !empty($it['tag_code'])) $tags[] = $it['tag_code'];
                    return [
                        'variant_id' => (int)($it['variant_id'] ?? 0),
                        'tag_code' => trim((string)($it['tag_code'] ?? ($tags[0] ?? ''))),
                        'tag_codes' => array_values(array_unique(array_filter(array_map(
                            static fn($tag): string => trim((string)$tag),
                            $tags
                        ), static fn(string $tag): bool => $tag !== ''))),
                    ];
                }, $items), static fn($it) => $it['variant_id'] > 0 && !empty($it['tag_codes']))),
            ];
            $serializedNotes = json_encode($notesData);

            Database::execute(
                "INSERT INTO warehouse_returns (return_number, concern_id, source_type, source_id, warehouse_id, destination_warehouse_id, reason, status, notes, created_at, updated_at)
                 VALUES (?, ?, 'WHATSAPP', ?, ?, ?, ?, 'RECEIVED', ?, NOW(), NOW())",
                [$returnNumber, ConcernTransactionService::activeConcernId(), $channelId, $warehouseId, $warehouseId, $reason, $serializedNotes]
            );
            $returnId = (int)Database::lastInsertId();

            $processedCount = 0;
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

                // The inventory screen aggregates all configured WhatsApp
                // channel locations. Use the actual channel balance that has
                // stock instead of always deducting the default channel.
                $stockChannelIds = $this->getWhatsappChannelIds();
                $stockChannelPlaceholders = implode(',', array_fill(0, count($stockChannelIds), '?'));
                $balanceRec = Database::fetchOne(
                    "SELECT * FROM stock_balances
                     WHERE variant_id = ? AND location_type = 'CHANNEL'
                       AND location_id IN ($stockChannelPlaceholders)
                       AND concern_id = " . (int)currentConcernId() . " AND stock_state = ? AND quantity > 0
                     ORDER BY quantity DESC LIMIT 1",
                    array_merge([$variantId], $stockChannelIds, [$stockState])
                );
                $currentStock = (float)($balanceRec['quantity'] ?? 0);
                if ($currentStock < $qty) {
                    $pv = Database::fetchOne("SELECT sku FROM product_variants WHERE id = ?", [$variantId]);
                    $sku = $pv['sku'] ?? "#$variantId";
                    throw new RuntimeException("Insufficient {$stockState} stock for SKU '{$sku}'. Requested: {$qty}, Available: {$currentStock}");
                }

                $rawQrCodes = $it['scanned_qr_codes'] ?? '[]';
                $scannedQrCodes = is_array($rawQrCodes) ? $rawQrCodes : json_decode((string)$rawQrCodes, true);
                if (!is_array($scannedQrCodes)) {
                    throw new InvalidArgumentException('Scanned product QR data is invalid.');
                }
                if (!$scannedQrCodes && !empty($it['tag_code'])) {
                    $scannedQrCodes[] = trim((string)$it['tag_code']);
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

                StockService::decrease(
                    variantId: $variantId,
                    locationType: 'CHANNEL',
                    locationId: (int)($balanceRec['location_id'] ?? $channelId),
                    stockState: $stockState,
                    quantity: (float)$qty,
                    transactionType: 'TRANSFER_OUT',
                    referenceType: 'warehouse_returns',
                    referenceId: $returnId,
                    userId: $userId,
                    notes: "WhatsApp Return to Warehouse #{$returnNumber} ({$classification})"
                );

                $processedCount++;
            }

            if ($processedCount === 0) {
                throw new InvalidArgumentException("No valid items with quantity > 0 were provided.");
            }

            if (class_exists('AuditService')) {
                AuditService::log('WHATSAPP', 'RETURN_TO_WAREHOUSE_DISPATCH', 'warehouse_returns', $returnId, null, [
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
     * Helper: Get Complete Details of a WhatsApp Return Consignment
     */
    private function getWhatsappWarehouseReturnDetails(int $returnId): ?array {
        $ret = Database::fetchOne(
            "SELECT wr.*, 
                    w.name as warehouse_name, w.code as warehouse_code, w.address as warehouse_address, w.phone as warehouse_phone,
                    sc.name as channel_name, sc.code as channel_code,
                    u.full_name as inspected_by_name
             FROM warehouse_returns wr
             JOIN warehouses w ON wr.warehouse_id = w.id
             LEFT JOIN sales_channels sc ON (wr.source_id = sc.id)
             LEFT JOIN users u ON wr.inspected_by = u.id
             WHERE (wr.id = ? OR wr.return_number = ?) AND wr.concern_id = ?",
            [$returnId, (string)$returnId, ConcernTransactionService::activeConcernId()]
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
        $notesData = [];
        if (!empty($ret['notes'])) {
            $decoded = json_decode($ret['notes'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $notesData = $decoded;
                $outward = array_merge($outward, $decoded);
                $ret['notes'] = $outward['remarks'] ?? '';
            }
        }
        $notesTagsByVariant = [];
        foreach (($notesData['scanned_tags'] ?? []) as $scan) {
            $variantId = (int)($scan['variant_id'] ?? 0);
            $tags = $scan['tag_codes'] ?? (!empty($scan['tag_code']) ? [$scan['tag_code']] : []);
            if ($variantId <= 0 || !is_array($tags)) continue;
            foreach ($tags as $tag) {
                $tag = trim((string)$tag);
                if ($tag !== '') $notesTagsByVariant[$variantId][$tag] = $tag;
            }
        }
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
            $savedQrCodes = json_decode((string)($it['scanned_qr_codes'] ?? ''), true);
            if (!is_array($savedQrCodes)) {
                $savedQrCodes = array_values($notesTagsByVariant[(int)$it['variant_id']] ?? []);
            }
            if (!$savedQrCodes && (int)$it['returned_qty'] === 1) {
                $channelIds = $this->getWhatsappChannelIds();
                $channelId = (int)($ret['source_id'] ?? 0);
                if ($channelId > 0 && in_array($channelId, $channelIds, true)) {
                    $pieceTags = $this->resolveChannelPieceTags($channelId, (int)$it['variant_id'], $channelIds, 'WHATSAPP')['all'];
                    if (count($pieceTags) === 1) $savedQrCodes = $pieceTags;
                }
            }
            $it['scanned_qr_codes'] = array_values(array_filter(
                array_map('strval', $savedQrCodes),
                static fn(string $qr): bool => trim($qr) !== ''
            ));
            $it['returned_tag_code'] = $it['scanned_qr_codes'][0] ?? '';
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
    private function resolveChannelPieceTags(int $channelId, int $variantId, array $chanIds, string $channelName = 'WHATSAPP'): array {
        $placeholders = implode(',', array_fill(0, count($chanIds), '?'));

        // 1. Inward Allocation Tags (in allocation order)
        $allocItems = Database::fetchAll(
            "SELECT ai.selected_qr_codes, wa.allocation_number
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
             WHERE ai.variant_id = ? 
               AND wa.destination_type = 'CHANNEL' AND wa.destination_id IN ($placeholders)
             ORDER BY wa.id ASC, ai.id ASC",
            array_merge([$variantId], $chanIds)
        );

        $allAllocatedTags = [];
        $allocRef = '';
        foreach ($allocItems as $ai) {
            if (empty($allocRef) && !empty($ai['allocation_number'])) {
                $allocRef = $ai['allocation_number'];
            }
            if (!empty($ai['selected_qr_codes'])) {
                $decoded = json_decode($ai['selected_qr_codes'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $t) {
                        $tStr = is_array($t) ? ($t['tag_code'] ?? ($t['qr_code'] ?? '')) : (string)$t;
                        $tStr = trim($tStr);
                        if ($tStr && !in_array($tStr, $allAllocatedTags)) {
                            $allAllocatedTags[] = $tStr;
                        }
                    }
                } else {
                    $tags = explode(',', $ai['selected_qr_codes']);
                    foreach ($tags as $t) {
                        $t = trim($t);
                        if ($t && !in_array($t, $allAllocatedTags)) {
                            $allAllocatedTags[] = $t;
                        }
                    }
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
            "SELECT wr.notes, wr.created_at, wri.id as return_item_id, wri.variant_id, wri.returned_qty, wri.classification,
                    cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name
             FROM warehouse_return_items wri
             JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id
             JOIN product_variants pv ON wri.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN warehouse_categories cat ON p.warehouse_category_id = cat.id
             LEFT JOIN warehouse_subcategories subcat ON p.warehouse_subcategory_id = subcat.id
             LEFT JOIN warehouse_sub_subcategories subsubcat ON p.warehouse_sub_subcategory_id = subsubcat.id
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
            $notesJson = json_decode((string)($rr['notes'] ?? ''), true);
            foreach (($notesJson['scanned_tags'] ?? []) as $scan) {
                if ((int)($scan['variant_id'] ?? 0) === $variantId) {
                    $scannedTags = $scan['tag_codes'] ?? (!empty($scan['tag_code']) ? [$scan['tag_code']] : []);
                    foreach ($scannedTags as $scannedTag) {
                        $tag = trim((string)$scannedTag);
                        if ($tag !== '' && !in_array($tag, $returnedTags, true)) $returnedTags[] = $tag;
                        if ($tag !== '') $hasQr = true;
                    }
                }
            }
            if (!empty($rr['notes']) && !is_array($notesJson)) {
                $notesStr = $rr['notes'];
                if (str_starts_with($notesStr, 'QR: ')) {
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
                // Backward-compatible tag for returns created before scanned
                // QR values were persisted in warehouse return notes.
                $initial = static function ($value): string {
                    $clean = preg_replace('/[^A-Za-z0-9]/', '', trim((string)$value));
                    return $clean !== '' ? strtoupper(substr($clean, 0, 1)) : 'X';
                };
                $fallbackTag = $initial($rr['category_name'] ?? '')
                    . $initial($rr['subcategory_name'] ?? '')
                    . $initial($rr['sub_subcategory_name'] ?? '')
                    . date('my', strtotime($rr['created_at'] ?? 'now'))
                    . str_pad((string)($rr['return_item_id'] ?? 0), 3, '0', STR_PAD_LEFT);
                if (!in_array($fallbackTag, $returnedTags, true)) $returnedTags[] = $fallbackTag;
                $warehouseReturnedTagsMap[$fallbackTag] = [
                    'warehouse_return_id' => null,
                    'return_number' => 'Warehouse Return',
                    'warehouse_name' => 'Central Warehouse',
                    'classification' => $rr['classification'] ?? 'GOOD',
                    'reason' => 'Returned to Central Warehouse',
                    'date' => $rr['created_at'] ?? date('Y-m-d H:i:s'),
                    'status' => 'RECEIVED',
                    'is_warehouse_returned' => true,
                ];
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
        $chanIds = $this->getWhatsappChannelIds();
        $placeholders = implode(',', array_fill(0, count($chanIds), '?'));
        $targetStockState = strtoupper(trim($stockState ?: 'AVAILABLE'));
        if (!in_array($targetStockState, ['AVAILABLE', 'DAMAGED', 'ALL'])) {
            $targetStockState = 'AVAILABLE';
        }

        $sql = "SELECT sb.*, 
                       pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost, 
                       pv.weight as variant_weight, pv.image_url as variant_image,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       p.category_code, p.subcategory_code, p.sub_subcategory_code, p.qr_code_prefix,
                       p.description, p.material, p.hsn_code, p.gst_percentage, p.min_stock, p.max_stock, p.source_type,
                       s.name as size_name, c.name as color_name, 
                       cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                       b.name as brand_name,
                       (SELECT quantity FROM stock_balances WHERE variant_id = sb.variant_id AND location_type = 'CHANNEL' AND location_id IN ($placeholders) AND concern_id = " . (int)currentConcernId() . " AND stock_state = 'AVAILABLE' LIMIT 1) as available_quantity,
                       (SELECT quantity FROM stock_balances WHERE variant_id = sb.variant_id AND location_type = 'CHANNEL' AND location_id IN ($placeholders) AND concern_id = " . (int)currentConcernId() . " AND stock_state = 'DAMAGED' LIMIT 1) as damaged_quantity
                FROM stock_balances sb
                JOIN product_variants pv ON sb.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN sizes s ON pv.size_id = s.id
                LEFT JOIN colors c ON pv.color_id = c.id
                WHERE sb.location_type = 'CHANNEL' AND sb.location_id IN ($placeholders)
                  AND sb.concern_id = " . (int)currentConcernId() . " AND sb.variant_id = ? "
                . ($targetStockState !== 'ALL' ? " AND sb.stock_state = ? " : "") . " LIMIT 1";

        $params = array_merge($chanIds, $chanIds, $chanIds, [$variantId]);
        if ($targetStockState !== 'ALL') {
            $params[] = $targetStockState;
        }
        $item = Database::fetchOne($sql, $params);

        if (!$item) {
            $fallbackSql = "SELECT pv.*, pv.id as variant_id, 0 as quantity, 0 as available_quantity, 0 as damaged_quantity,
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
                            WHERE pv.id = ?";
            $item = Database::fetchOne($fallbackSql, [$variantId]);
        }

        if ($item) {
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

            $pieceRes = $this->resolveChannelPieceTags($channelId, $variantId, $chanIds, 'WHATSAPP');
            $inwardRef = !empty($pieceRes['inward_ref']) ? $pieceRes['inward_ref'] : 'Stock Allocation';

            $pName = $item['product_name'] ?? 'Product';
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
                $targetQty = (int)($item['damaged_quantity'] ?? $item['quantity'] ?? 0);
                $tagsToUse = $pieceRes['damaged'];
                for ($i = 0; $i < $targetQty; $i++) {
                    $tag = $tagsToUse[$i] ?? sprintf('%s-DMG-%02d', $sku, $i + 1);
                    $pieces[] = [
                        'piece_number'     => $i + 1,
                        'tag_code'         => $tag,
                        'qr_payload'       => $tag,
                        'status'           => 'DAMAGED',
                        'product_name'     => $pName,
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
                $targetQty = (int)($item['available_quantity'] ?? $item['quantity'] ?? 0);
                $tagsToUse = $pieceRes['available'];
                for ($i = 0; $i < $targetQty; $i++) {
                    $tag = $tagsToUse[$i] ?? sprintf('%s-%02d', $sku, $i + 1);
                    $pieces[] = [
                        'piece_number'     => $i + 1,
                        'tag_code'         => $tag,
                        'qr_payload'       => $tag,
                        'status'           => 'AVAILABLE',
                        'product_name'     => $pName,
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
        }

        return $item;
    }
}
