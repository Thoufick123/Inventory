<?php
/**
 * Shop Service — Central Business Logic for Retail Shop & POS
 * Handles QR-driven POS billing, stock deductions, invoice rendering,
 * returns, and warehouse indent requests.
 */

require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Services/SettingsService.php';
require_once BASE_PATH . '/app/Services/CashService.php';
require_once BASE_PATH . '/app/Models/WarehouseAllocation.php';
require_once BASE_PATH . '/app/Models/AllocationItem.php';
require_once BASE_PATH . '/app/Models/Warehouse.php';
require_once BASE_PATH . '/app/Models/Shop.php';
require_once BASE_PATH . '/app/Models/ShopDamage.php';

class ShopService {

    /**
     * Get the active primary shop record (auto-creates if missing).
     */
    public static function getActiveShop(?int $requestedShopId = null): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            Response::redirect(url('admin/concern/select'), 'error', 'Select an active concern before opening shop operations.');
        }

        // If staff is restricted to a specific shop, always enforce their assigned shop
        $scopedShopId = function_exists('currentShopId') ? currentShopId() : null;
        if ($scopedShopId === 0) {
            Response::error(403, 'The assigned shop is not available in the active concern.');
        }
        if ($scopedShopId) {
            $shop = Database::fetchOne(
                "SELECT * FROM shops WHERE id = ? AND concern_id = ?",
                [$scopedShopId, $concernId]
            );
            if ($shop) return $shop;
        }

        if ($requestedShopId) {
            $shop = Database::fetchOne(
                "SELECT * FROM shops WHERE id = ? AND concern_id = ?",
                [$requestedShopId, $concernId]
            );
            if ($shop) return $shop;
        }

        $shop = Database::fetchOne(
            "SELECT * FROM shops WHERE concern_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1",
            [$concernId]
        );
        if (!$shop) {
            Response::error(409, 'No active shop is configured for the selected concern. Ask an administrator to create one.');
        }
        return $shop;
    }

    /**
     * Get Dashboard Summary Statistics for a Shop with rich revenue analytics & chart datasets.
     */
    public static function getDashboardStats(int $shopId): array {
        $today = date('Y-m-d');
        
        // Today's Sales
        $todayStats = Database::fetchOne(
            "SELECT COUNT(id) as total_invoices, 
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN payment_mode = 'CASH' THEN total_amount ELSE 0 END), 0) as cash_total,
                    COALESCE(SUM(CASE WHEN payment_mode = 'UPI' THEN total_amount ELSE 0 END), 0) as upi_total,
                    COALESCE(SUM(CASE WHEN payment_mode = 'CARD' THEN total_amount ELSE 0 END), 0) as card_total,
                    COALESCE(MAX(total_amount), 0) as highest_sale
             FROM sales 
             WHERE shop_id = ? AND sale_type = 'SHOP' AND DATE(created_at) = ? AND status != 'CANCELLED'",
            [$shopId, $today]
        );

        // This Month's Sales
        $monthStats = Database::fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) as month_revenue, COUNT(id) as month_invoices
             FROM sales 
             WHERE shop_id = ? AND sale_type = 'SHOP' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status != 'CANCELLED'",
            [$shopId]
        );

        // Low stock items count in this shop
        $minStock = (int)SettingsService::get('low_stock_threshold', 5);
        $lowStockCount = (int)(Database::fetchOne(
            "SELECT COUNT(sb.id) as cnt
             FROM stock_balances sb
             WHERE sb.location_type = 'SHOP' AND sb.location_id = ? AND sb.stock_state = 'AVAILABLE' AND sb.quantity <= ?",
            [$shopId, $minStock]
        )['cnt'] ?? 0);

        // Returns today count
        $returnsToday = (int)(Database::fetchOne(
            "SELECT COUNT(r.id) as cnt 
             FROM returns r 
             JOIN sales s ON r.sale_id = s.id 
             WHERE s.shop_id = ? AND DATE(r.created_at) = ?",
            [$shopId, $today]
        )['cnt'] ?? 0);

        // Recent Sales List
        $recentSales = Database::fetchAll(
            "SELECT s.*, c.name as customer_name, c.phone as customer_phone, u.full_name as staff_name
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN users u ON s.billing_staff_id = u.id
             WHERE s.shop_id = ? AND s.sale_type = 'SHOP'
             ORDER BY s.created_at DESC LIMIT 10",
            [$shopId]
        );

        // ==========================================
        // REVENUE CHART DATASETS
        // ==========================================

        // 1. Last 7 Days Daily Revenue
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $label = date('D, d M', strtotime("-$i days"));
            $shortLabel = date('D', strtotime("-$i days"));
            $last7Days[$d] = [
                'date'        => $d,
                'label'       => $label,
                'short_label' => $shortLabel,
                'revenue'     => 0.0,
                'invoices'    => 0,
                'cash'        => 0.0,
                'digital'     => 0.0,
            ];
        }
        $startDate7 = date('Y-m-d', strtotime("-6 days"));
        $sales7Days = Database::fetchAll(
            "SELECT DATE(created_at) as s_date, 
                    COUNT(id) as inv_count, 
                    COALESCE(SUM(total_amount), 0) as tot_rev,
                    COALESCE(SUM(CASE WHEN payment_mode = 'CASH' THEN total_amount ELSE 0 END), 0) as cash_rev,
                    COALESCE(SUM(CASE WHEN payment_mode != 'CASH' THEN total_amount ELSE 0 END), 0) as digital_rev
             FROM sales
             WHERE shop_id = ? AND sale_type = 'SHOP' AND DATE(created_at) >= ? AND status != 'CANCELLED'
             GROUP BY DATE(created_at)",
            [$shopId, $startDate7]
        );
        foreach ($sales7Days as $row) {
            $sd = $row['s_date'];
            if (isset($last7Days[$sd])) {
                $last7Days[$sd]['revenue'] = (float)$row['tot_rev'];
                $last7Days[$sd]['invoices'] = (int)$row['inv_count'];
                $last7Days[$sd]['cash'] = (float)$row['cash_rev'];
                $last7Days[$sd]['digital'] = (float)$row['digital_rev'];
            }
        }

        // 2. Last 30 Days Daily Revenue
        $last30Days = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $label = date('d M', strtotime("-$i days"));
            $last30Days[$d] = [
                'date'     => $d,
                'label'    => $label,
                'revenue'  => 0.0,
                'invoices' => 0,
            ];
        }
        $startDate30 = date('Y-m-d', strtotime("-29 days"));
        $sales30Days = Database::fetchAll(
            "SELECT DATE(created_at) as s_date, 
                    COUNT(id) as inv_count, 
                    COALESCE(SUM(total_amount), 0) as tot_rev
             FROM sales
             WHERE shop_id = ? AND sale_type = 'SHOP' AND DATE(created_at) >= ? AND status != 'CANCELLED'
             GROUP BY DATE(created_at)",
            [$shopId, $startDate30]
        );
        foreach ($sales30Days as $row) {
            $sd = $row['s_date'];
            if (isset($last30Days[$sd])) {
                $last30Days[$sd]['revenue'] = (float)$row['tot_rev'];
                $last30Days[$sd]['invoices'] = (int)$row['inv_count'];
            }
        }

        // 3. Last 6 Months Revenue Trend
        $last6Months = [];
        for ($i = 5; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months"));
            $label = date('M Y', strtotime("-$i months"));
            $last6Months[$ym] = [
                'month'    => $ym,
                'label'    => $label,
                'revenue'  => 0.0,
                'invoices' => 0,
            ];
        }
        $startMonth = date('Y-m-01', strtotime("-5 months"));
        $sales6Months = Database::fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as s_month, 
                    COUNT(id) as inv_count, 
                    COALESCE(SUM(total_amount), 0) as tot_rev
             FROM sales
             WHERE shop_id = ? AND sale_type = 'SHOP' AND created_at >= ? AND status != 'CANCELLED'
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')",
            [$shopId, $startMonth]
        );
        foreach ($sales6Months as $row) {
            $sm = $row['s_month'];
            if (isset($last6Months[$sm])) {
                $last6Months[$sm]['revenue'] = (float)$row['tot_rev'];
                $last6Months[$sm]['invoices'] = (int)$row['inv_count'];
            }
        }

        // 4. Payment Mode Breakdown (This Month)
        $paymentBreakdown = Database::fetchAll(
            "SELECT payment_mode, 
                    COUNT(id) as count, 
                    COALESCE(SUM(total_amount), 0) as total 
             FROM sales 
             WHERE shop_id = ? AND sale_type = 'SHOP' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status != 'CANCELLED'
             GROUP BY payment_mode",
            [$shopId]
        );

        // 5. Hourly Revenue Velocity for Today (8 AM to 10 PM)
        $hourlySales = [];
        for ($h = 8; $h <= 22; $h++) {
            $hKey = sprintf('%02d', $h);
            $hLabel = date('g A', strtotime("$hKey:00:00"));
            $hourlySales[$hKey] = [
                'hour'     => $hKey,
                'label'    => $hLabel,
                'revenue'  => 0.0,
                'invoices' => 0,
            ];
        }
        $todayHourly = Database::fetchAll(
            "SELECT DATE_FORMAT(created_at, '%H') as s_hour, 
                    COUNT(id) as inv_count, 
                    COALESCE(SUM(total_amount), 0) as tot_rev
             FROM sales
             WHERE shop_id = ? AND sale_type = 'SHOP' AND DATE(created_at) = ? AND status != 'CANCELLED'
             GROUP BY DATE_FORMAT(created_at, '%H')",
            [$shopId, $today]
        );
        foreach ($todayHourly as $row) {
            $sh = $row['s_hour'];
            if (isset($hourlySales[$sh])) {
                $hourlySales[$sh]['revenue'] = (float)$row['tot_rev'];
                $hourlySales[$sh]['invoices'] = (int)$row['inv_count'];
            }
        }

        // 6. Top Selling Products (This Month)
        $topSellingProducts = Database::fetchAll(
            "SELECT p.name as product_name, pv.sku, c.name as category_name,
                    SUM(si.quantity) as total_sold_qty,
                    SUM(si.total_amount) as total_sold_amount
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE s.shop_id = ? AND s.sale_type = 'SHOP' AND MONTH(s.created_at) = MONTH(CURRENT_DATE()) AND YEAR(s.created_at) = YEAR(CURRENT_DATE()) AND s.status != 'CANCELLED'
             GROUP BY pv.id
             ORDER BY total_sold_qty DESC
             LIMIT 5",
            [$shopId]
        );

        // Summary Calculations
        $todayInvoices = (int)($todayStats['total_invoices'] ?? 0);
        $todayRevenue = (float)($todayStats['total_revenue'] ?? 0);
        $avgOrderValueToday = $todayInvoices > 0 ? round($todayRevenue / $todayInvoices, 2) : 0.0;

        $monthInvoices = (int)($monthStats['month_invoices'] ?? 0);
        $monthRevenue = (float)($monthStats['month_revenue'] ?? 0);
        $avgOrderValueMonth = $monthInvoices > 0 ? round($monthRevenue / $monthInvoices, 2) : 0.0;

        return [
            'today_revenue'          => $todayRevenue,
            'today_invoices'         => $todayInvoices,
            'cash_total'             => (float)($todayStats['cash_total'] ?? 0),
            'upi_total'              => (float)($todayStats['upi_total'] ?? 0),
            'card_total'             => (float)($todayStats['card_total'] ?? 0),
            'highest_sale_today'     => (float)($todayStats['highest_sale'] ?? 0),
            'avg_order_value_today'  => $avgOrderValueToday,
            'month_revenue'          => $monthRevenue,
            'month_invoices'         => $monthInvoices,
            'avg_order_value_month'  => $avgOrderValueMonth,
            'low_stock_count'        => $lowStockCount,
            'returns_today'          => $returnsToday,
            'recent_sales'           => $recentSales,
            'daily_revenue_7days'    => array_values($last7Days),
            'daily_revenue_30days'   => array_values($last30Days),
            'monthly_revenue_6months'=> array_values($last6Months),
            'payment_breakdown'      => $paymentBreakdown,
            'hourly_revenue_today'   => array_values($hourlySales),
            'top_selling_products'   => $topSellingProducts,
        ];
    }

    /**
     * Get Filtered Summary Card Metrics and Trend Data based on period (today, week, month, custom start/end date).
     */
    public static function getFilteredMetrics(int $shopId, string $period = 'today', ?string $customStart = null, ?string $customEnd = null): array {
        $today = date('Y-m-d');
        $startDate = $today;
        $endDate = $today;
        $periodTitle = "Today's Sales";
        $periodSubtitle = "Invoices Today";

        if ($period === 'week') {
            $startDate = date('Y-m-d', strtotime('-6 days'));
            $endDate = $today;
            $periodTitle = "This Week's Sales";
            $periodSubtitle = "Invoices (Last 7 Days)";
        } elseif ($period === 'month') {
            $startDate = date('Y-m-01');
            $endDate = $today;
            $periodTitle = "This Month's Sales";
            $periodSubtitle = "Invoices (" . date('M Y') . ")";
        } elseif ($period === 'custom') {
            $startDate = !empty($customStart) ? date('Y-m-d', strtotime($customStart)) : date('Y-m-d', strtotime('-30 days'));
            $endDate = !empty($customEnd) ? date('Y-m-d', strtotime($customEnd)) : $today;
            if ($startDate > $endDate) {
                $tmp = $startDate;
                $startDate = $endDate;
                $endDate = $tmp;
            }
            $periodTitle = "Period Sales";
            $periodSubtitle = date('d M Y', strtotime($startDate)) . ' – ' . date('d M Y', strtotime($endDate));
        }

        // Query aggregated sales metrics in [startDate, endDate]
        $stats = Database::fetchOne(
            "SELECT COUNT(id) as total_invoices,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN payment_mode = 'CASH' THEN total_amount ELSE 0 END), 0) as cash_total,
                    COALESCE(SUM(CASE WHEN payment_mode = 'UPI' THEN total_amount ELSE 0 END), 0) as upi_total,
                    COALESCE(SUM(CASE WHEN payment_mode = 'CARD' THEN total_amount ELSE 0 END), 0) as card_total,
                    COALESCE(SUM(CASE WHEN payment_mode NOT IN ('CASH', 'UPI', 'CARD') THEN total_amount ELSE 0 END), 0) as other_total,
                    COALESCE(MAX(total_amount), 0) as highest_sale
             FROM sales
             WHERE shop_id = ? AND sale_type = 'SHOP' AND DATE(created_at) >= ? AND DATE(created_at) <= ? AND status != 'CANCELLED'",
            [$shopId, $startDate, $endDate]
        );

        $totalInvoices = (int)($stats['total_invoices'] ?? 0);
        $totalRevenue = (float)($stats['total_revenue'] ?? 0);
        $avgOrderValue = $totalInvoices > 0 ? round($totalRevenue / $totalInvoices, 2) : 0.0;

        // Low stock count (live)
        $minStock = (int)SettingsService::get('low_stock_threshold', 5);
        $lowStockCount = (int)(Database::fetchOne(
            "SELECT COUNT(sb.id) as cnt
             FROM stock_balances sb
             WHERE sb.location_type = 'SHOP' AND sb.location_id = ? AND sb.stock_state = 'AVAILABLE' AND sb.quantity <= ?",
            [$shopId, $minStock]
        )['cnt'] ?? 0);

        // Returns in period
        $returnsCount = (int)(Database::fetchOne(
            "SELECT COUNT(r.id) as cnt 
             FROM returns r 
             JOIN sales s ON r.sale_id = s.id 
             WHERE s.shop_id = ? AND DATE(r.created_at) >= ? AND DATE(r.created_at) <= ?",
            [$shopId, $startDate, $endDate]
        )['cnt'] ?? 0);

        // Day-by-day continuous breakdown for chart in [startDate, endDate]
        $daysDiff = (int)ceil((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;
        $daysDiff = max(1, min(90, $daysDiff)); // Cap at 90 days for daily rendering
        
        $dailyMap = [];
        $curr = strtotime($startDate);
        $endTs = strtotime($endDate);
        while ($curr <= $endTs) {
            $dStr = date('Y-m-d', $curr);
            $dailyMap[$dStr] = [
                'date'        => $dStr,
                'label'       => date($daysDiff > 14 ? 'd M' : 'D, d M', $curr),
                'short_label' => date('d M', $curr),
                'revenue'     => 0.0,
                'invoices'    => 0,
                'cash'        => 0.0,
                'digital'     => 0.0,
            ];
            $curr = strtotime('+1 day', $curr);
        }

        $salesDaily = Database::fetchAll(
            "SELECT DATE(created_at) as s_date,
                    COUNT(id) as inv_count,
                    COALESCE(SUM(total_amount), 0) as tot_rev,
                    COALESCE(SUM(CASE WHEN payment_mode = 'CASH' THEN total_amount ELSE 0 END), 0) as cash_rev,
                    COALESCE(SUM(CASE WHEN payment_mode != 'CASH' THEN total_amount ELSE 0 END), 0) as digital_rev
             FROM sales
             WHERE shop_id = ? AND sale_type = 'SHOP' AND DATE(created_at) >= ? AND DATE(created_at) <= ? AND status != 'CANCELLED'
             GROUP BY DATE(created_at)",
            [$shopId, $startDate, $endDate]
        );

        foreach ($salesDaily as $row) {
            $sd = $row['s_date'];
            if (isset($dailyMap[$sd])) {
                $dailyMap[$sd]['revenue'] = (float)$row['tot_rev'];
                $dailyMap[$sd]['invoices'] = (int)$row['inv_count'];
                $dailyMap[$sd]['cash'] = (float)$row['cash_rev'];
                $dailyMap[$sd]['digital'] = (float)$row['digital_rev'];
            }
        }

        // Payment breakdown in period
        $paymentBreakdown = Database::fetchAll(
            "SELECT payment_mode, 
                    COUNT(id) as count, 
                    COALESCE(SUM(total_amount), 0) as total 
             FROM sales 
             WHERE shop_id = ? AND sale_type = 'SHOP' AND DATE(created_at) >= ? AND DATE(created_at) <= ? AND status != 'CANCELLED'
             GROUP BY payment_mode",
            [$shopId, $startDate, $endDate]
        );

        // Top selling in period
        $topSelling = Database::fetchAll(
            "SELECT p.name as product_name, pv.sku, c.name as category_name,
                    SUM(si.quantity) as total_sold_qty,
                    SUM(si.total_amount) as total_sold_amount
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE s.shop_id = ? AND s.sale_type = 'SHOP' AND DATE(s.created_at) >= ? AND DATE(s.created_at) <= ? AND s.status != 'CANCELLED'
             GROUP BY pv.id
             ORDER BY total_sold_qty DESC
             LIMIT 5",
            [$shopId, $startDate, $endDate]
        );

        $cashTotal = (float)($stats['cash_total'] ?? 0);
        $upiTotal = (float)($stats['upi_total'] ?? 0);
        $cardTotal = (float)($stats['card_total'] ?? 0);
        $digitalTotal = (float)($upiTotal + $cardTotal + ($stats['other_total'] ?? 0));

        return [
            'period'           => $period,
            'period_title'     => $periodTitle,
            'period_subtitle'  => $periodSubtitle,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'total_revenue'    => $totalRevenue,
            'total_invoices'   => $totalInvoices,
            'cash_total'       => $cashTotal,
            'upi_total'        => $upiTotal,
            'card_total'       => $cardTotal,
            'digital_total'    => $digitalTotal,
            'avg_order_value'  => $avgOrderValue,
            'highest_sale'     => (float)($stats['highest_sale'] ?? 0),
            'low_stock_count'  => $lowStockCount,
            'returns_count'    => $returnsCount,
            'daily_chart_data' => array_values($dailyMap),
            'payment_breakdown'=> $paymentBreakdown,
            'top_selling'      => $topSelling,
        ];
    }

    /**
     * Find Product Variant by QR Code, Barcode, SKU, Piece Tag, or Composite QR Payload.
     */
    /**
     * Resolve and fallback variant selling price and MRP across all related tables,
     * ensuring prices are never lost and auto-healing the variant record in database.
     */
    public static function resolveVariantPrices(
        array &$variant, 
        ?string $scannedTag = null, 
        ?float $parsedMrp = null, 
        ?float $parsedSellingPrice = null
    ): array {
        $varId = (int)($variant['id'] ?? ($variant['variant_id'] ?? 0));
        $prodId = (int)($variant['product_id'] ?? 0);
        $sellingPrice = (float)($variant['selling_price'] ?? 0);
        $mrp = (float)($variant['mrp'] ?? 0);
        $cost = (float)($variant['purchase_price'] > 0 ? $variant['purchase_price'] : ($variant['production_cost'] ?? 0));

        // 1. Explicit parsed prices from QR Payload (e.g. MRP:300, Price:250, Disc:250)
        if ($sellingPrice <= 0 && $parsedSellingPrice !== null && $parsedSellingPrice > 0) {
            $sellingPrice = $parsedSellingPrice;
        }
        if ($mrp <= 0 && $parsedMrp !== null && $parsedMrp > 0) {
            $mrp = $parsedMrp;
        }

        // 2. Look up QC Items (tag_code match or variant_id match)
        if (($sellingPrice <= 0 || $mrp <= 0) && (!empty($scannedTag) || $varId > 0)) {
            $qc = null;
            if (!empty($scannedTag)) {
                $qc = Database::fetchOne(
                    "SELECT base_price, discount_price FROM qc_items 
                     WHERE (tag_code = ? OR tag_code LIKE ?) AND (base_price > 0 OR discount_price > 0) 
                     ORDER BY id DESC LIMIT 1",
                    [$scannedTag, '%' . $scannedTag . '%']
                );
            }
            if (!$qc && $varId > 0) {
                $qc = Database::fetchOne(
                    "SELECT base_price, discount_price FROM qc_items 
                     WHERE variant_id = ? AND (base_price > 0 OR discount_price > 0) 
                     ORDER BY id DESC LIMIT 1",
                    [$varId]
                );
            }
            if ($qc) {
                $qcBase = (float)($qc['base_price'] ?? 0);
                $qcDisc = (float)($qc['discount_price'] ?? 0);
                if ($mrp <= 0 && $qcBase > 0) $mrp = $qcBase;
                if ($sellingPrice <= 0 && $qcDisc > 0) $sellingPrice = $qcDisc;
                if ($sellingPrice <= 0 && $qcBase > 0) $sellingPrice = $qcBase;
            }
        }

        // 3. Look up Label Items (variant_id)
        if (($sellingPrice <= 0 || $mrp <= 0) && $varId > 0) {
            $lbl = Database::fetchOne(
                "SELECT base_price, discount_price FROM label_items 
                 WHERE variant_id = ? AND (base_price > 0 OR discount_price > 0) 
                 ORDER BY id DESC LIMIT 1",
                [$varId]
            );
            if ($lbl) {
                $lblBase = (float)($lbl['base_price'] ?? 0);
                $lblDisc = (float)($lbl['discount_price'] ?? 0);
                if ($mrp <= 0 && $lblBase > 0) $mrp = $lblBase;
                if ($sellingPrice <= 0 && $lblDisc > 0) $sellingPrice = $lblDisc;
                if ($sellingPrice <= 0 && $lblBase > 0) $sellingPrice = $lblBase;
            }
        }

        // 4. Look up Purchase Items (variant_id)
        if (($sellingPrice <= 0 || $mrp <= 0) && $varId > 0) {
            $pi = Database::fetchOne(
                "SELECT mrp, unit_price FROM purchase_items 
                 WHERE variant_id = ? AND (mrp > 0 OR unit_price > 0) 
                 ORDER BY id DESC LIMIT 1",
                [$varId]
            );
            if ($pi) {
                $piMrp = (float)($pi['mrp'] ?? 0);
                $piUnit = (float)($pi['unit_price'] ?? 0);
                if ($mrp <= 0 && $piMrp > 0) $mrp = $piMrp;
                if ($sellingPrice <= 0 && $piMrp > 0) $sellingPrice = $piMrp;
                if ($sellingPrice <= 0 && $piUnit > 0) $sellingPrice = $piUnit;
            }
        }

        // 5. Look up Sibling Variants of the same parent product
        if (($sellingPrice <= 0 || $mrp <= 0) && $prodId > 0) {
            $sib = Database::fetchOne(
                "SELECT selling_price, mrp, purchase_price, production_cost FROM product_variants 
                 WHERE product_id = ? AND id != ? AND (selling_price > 0 OR mrp > 0) 
                 ORDER BY id ASC LIMIT 1",
                [$prodId, $varId]
            );
            if ($sib) {
                $sibSelling = (float)($sib['selling_price'] ?? 0);
                $sibMrp = (float)($sib['mrp'] ?? 0);
                if ($mrp <= 0 && $sibMrp > 0) $mrp = $sibMrp;
                if ($sellingPrice <= 0 && $sibSelling > 0) $sellingPrice = $sibSelling;
                if ($sellingPrice <= 0 && $sibMrp > 0) $sellingPrice = $sibMrp;
            }
        }

        // 6. Look up Historical Sales of this variant / product
        if (($sellingPrice <= 0 || $mrp <= 0) && ($varId > 0 || $prodId > 0)) {
            $saleRow = Database::fetchOne(
                "SELECT si.price, si.unit_price, si.mrp FROM sale_items si 
                 JOIN product_variants pv ON si.variant_id = pv.id 
                 WHERE (si.variant_id = ? OR pv.product_id = ?) AND (si.unit_price > 0 OR si.price > 0) 
                 ORDER BY si.id DESC LIMIT 1",
                [$varId, $prodId]
            );
            if ($saleRow) {
                $sUnit = (float)($saleRow['unit_price'] > 0 ? $saleRow['unit_price'] : ($saleRow['price'] ?? 0));
                $sMrp = (float)($saleRow['mrp'] ?? 0);
                if ($mrp <= 0 && $sMrp > 0) $mrp = $sMrp;
                if ($sellingPrice <= 0 && $sUnit > 0) $sellingPrice = $sUnit;
            }
        }

        // 7. Look up Production Batch Cost per piece
        if (($sellingPrice <= 0 || $mrp <= 0 || $cost <= 0) && $varId > 0) {
            $pb = Database::fetchOne(
                "SELECT pb.cost_per_piece FROM production_batch_items pbi 
                 JOIN production_batches pb ON pbi.production_batch_id = pb.id 
                 WHERE pbi.variant_id = ? AND pb.cost_per_piece > 0 
                 ORDER BY pbi.id DESC LIMIT 1",
                [$varId]
            );
            if ($pb && (float)$pb['cost_per_piece'] > 0) {
                $batchCost = (float)$pb['cost_per_piece'];
                if ($cost <= 0) $cost = $batchCost;
            }
        }

        // 8. Cost Price fallback if still 0
        if ($sellingPrice <= 0 && $cost > 0) {
            $sellingPrice = $cost;
        }
        if ($mrp <= 0 && $cost > 0) {
            $mrp = $cost;
        }

        // 9. Cross-normalization
        if ($sellingPrice > 0 && $mrp <= 0) {
            $mrp = $sellingPrice;
        } elseif ($mrp > 0 && $sellingPrice <= 0) {
            $sellingPrice = $mrp;
        }
        if ($mrp > 0 && $mrp < $sellingPrice) {
            $mrp = $sellingPrice;
        }

        // 10. Auto-heal DB record if price was recovered
        if ($varId > 0 && ($sellingPrice > 0 || $mrp > 0)) {
            $origSell = (float)($variant['selling_price'] ?? 0);
            $origMrp = (float)($variant['mrp'] ?? 0);
            if ($origSell <= 0 || $origMrp <= 0) {
                try {
                    Database::execute(
                        "UPDATE product_variants SET selling_price = ?, mrp = ?, updated_at = NOW() WHERE id = ? AND (selling_price <= 0 OR mrp <= 0)",
                        [$sellingPrice, $mrp, $varId]
                    );
                } catch (\Throwable $e) {}
            }
        }

        // Apply back to variant array
        $variant['selling_price'] = $sellingPrice;
        $variant['mrp'] = $mrp;
        $variant['purchase_price'] = $cost;
        $variant['production_cost'] = $cost;

        return $variant;
    }

    public static function findVariantByQR(string $code, int $shopId): ?array {
        $code = trim($code);
        if (empty($code)) return null;

        // Decode URL encoding if present (e.g. %7C -> |, %20 -> space, %3A -> :)
        $code = urldecode($code);

        $candidates = [$code];
        $pieceTag = null;
        $parsedMrp = null;
        $parsedSellingPrice = null;

        // 1. JSON Payload parsing
        if (str_starts_with($code, '{') && str_ends_with($code, '}')) {
            $json = json_decode($code, true);
            if (is_array($json)) {
                if (!empty($json['sku'])) {
                    $candidates[] = trim($json['sku']);
                }
                if (!empty($json['qr_code'])) {
                    $candidates[] = trim($json['qr_code']);
                }
                if (!empty($json['tag_code'])) {
                    $candidates[] = trim($json['tag_code']);
                    $pieceTag = trim($json['tag_code']);
                }
                if (!empty($json['mrp']) && is_numeric($json['mrp'])) {
                    $parsedMrp = (float)$json['mrp'];
                }
                if (!empty($json['price']) && is_numeric($json['price'])) {
                    $parsedSellingPrice = (float)$json['price'];
                }
                if (!empty($json['selling_price']) && is_numeric($json['selling_price'])) {
                    $parsedSellingPrice = (float)$json['selling_price'];
                }
                if (!empty($json['batch_number'])) {
                    $batchItem = Database::fetchOne(
                        "SELECT pbi.variant_id 
                         FROM production_batch_items pbi 
                         JOIN production_batches pb ON pbi.production_batch_id = pb.id 
                         WHERE pb.batch_number = ? LIMIT 1", 
                        [$json['batch_number']]
                    );
                    if ($batchItem && !empty($batchItem['variant_id'])) {
                        $vRow = Database::fetchOne("SELECT sku FROM product_variants WHERE id = ?", [$batchItem['variant_id']]);
                        if ($vRow && !empty($vRow['sku'])) $candidates[] = $vRow['sku'];
                    }
                }
            }
        }

        // 2. Pipe-delimited Composite QR payload parsing (e.g. "BSP092608|PROD-4-5Y|MRP:300.00|Disc:300.00")
        if (strpos($code, '|') !== false) {
            $parts = explode('|', $code);
            foreach ($parts as $idx => $part) {
                $clean = trim($part);
                if (empty($clean)) continue;
                // Add raw part
                $candidates[] = $clean;
                
                // If part has prefix like MRP:300, Price:300, SKU:xxx, Tag:xxx, Disc:300
                if (preg_match('/^MRP\s*:\s*([\d\.]+)/i', $clean, $m)) {
                    $parsedMrp = (float)$m[1];
                }
                if (preg_match('/^(?:PRICE|SELLING_PRICE|DISC|DISCOUNT|RATE)\s*:\s*([\d\.]+)/i', $clean, $m)) {
                    $parsedSellingPrice = (float)$m[1];
                }
                if (preg_match('/^(?:MRP|PRICE|SKU|TAG|DISC|CODE)\s*:\s*(.+)$/i', $clean, $m)) {
                    $candidates[] = trim($m[1]);
                }
                if ($idx === 0 && preg_match('/^[A-Za-z0-9_-]+$/', $clean)) {
                    $pieceTag = $clean;
                }
            }
        }

        // Remove duplicates and blanks
        $candidates = array_unique(array_filter($candidates));

        // 3. Direct SKU / QR Code / Product Code lookup across all candidates
        $baseSql = "SELECT pv.*, p.name as product_name, p.code as product_code, p.gst_percentage,
                           p.image_url as product_image, pv.image_url as variant_image,
                           s.name as size_name, c.name as color_name, b.name as brand_name,
                           cat.name as category_name,
                           wc.category_type as warehouse_category_type,
                           p.is_complementary as p_is_comp,
                           p.warehouse_category_id
                    FROM product_variants pv
                    JOIN products p ON pv.product_id = p.id
                    LEFT JOIN sizes s ON pv.size_id = s.id
                    LEFT JOIN colors c ON pv.color_id = c.id
                    LEFT JOIN brands b ON p.brand_id = b.id
                    LEFT JOIN categories cat ON p.category_id = cat.id
                    LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
                    WHERE pv.is_active = 1 ";

        foreach ($candidates as $cand) {
            $variant = Database::fetchOne(
                $baseSql . "AND (pv.sku = ? OR pv.qr_code = ? OR p.code = ?) LIMIT 1",
                [$cand, $cand, $cand]
            );
            if ($variant) {
                return self::formatVariantForPOS($variant, $shopId, $pieceTag ?? $cand, $parsedMrp, $parsedSellingPrice);
            }
        }

        // 4. Piece-Level Tag Code Search in Allocation Items (e.g. BSP092608, TTT092611)
        foreach ($candidates as $cand) {
            $allocItem = Database::fetchOne(
                "SELECT variant_id FROM allocation_items 
                 WHERE (selected_qr_codes LIKE ? OR selected_qr_codes LIKE ? OR selected_qr_codes = ?) 
                 ORDER BY id DESC LIMIT 1",
                ['%' . $cand . '%', '%' . $cand . ',%', $cand]
            );
            if ($allocItem && !empty($allocItem['variant_id'])) {
                $variant = Database::fetchOne(
                    $baseSql . "AND pv.id = ? LIMIT 1",
                    [(int)$allocItem['variant_id']]
                );
                if ($variant) {
                    return self::formatVariantForPOS($variant, $shopId, $cand, $parsedMrp, $parsedSellingPrice);
                }
            }
        }

        // 5. Piece-Level Tag Code Search in QC Items (e.g. TTT092611)
        foreach ($candidates as $cand) {
            $qcItem = Database::fetchOne(
                "SELECT variant_id, base_price, discount_price FROM qc_items 
                 WHERE (tag_code = ? OR tag_code LIKE ?) 
                 ORDER BY id DESC LIMIT 1",
                [$cand, '%' . $cand . '%']
            );
            if ($qcItem && !empty($qcItem['variant_id'])) {
                $variant = Database::fetchOne(
                    $baseSql . "AND pv.id = ? LIMIT 1",
                    [(int)$qcItem['variant_id']]
                );
                if ($variant) {
                    $pMrp = $parsedMrp ?? ((float)$qcItem['base_price'] > 0 ? (float)$qcItem['base_price'] : null);
                    $pSell = $parsedSellingPrice ?? ((float)$qcItem['discount_price'] > 0 ? (float)$qcItem['discount_price'] : null);
                    return self::formatVariantForPOS($variant, $shopId, $cand, $pMrp, $pSell);
                }
            }
        }

        // 6. Piece-Level Tag Code Search in Production Batch Items
        foreach ($candidates as $cand) {
            $batchItem = Database::fetchOne(
                "SELECT pbi.variant_id 
                 FROM production_batch_items pbi
                 JOIN production_batches pb ON pbi.production_batch_id = pb.id
                 WHERE pb.batch_number = ?
                 ORDER BY pbi.id DESC LIMIT 1",
                [$cand]
            );
            if ($batchItem && !empty($batchItem['variant_id'])) {
                $variant = Database::fetchOne(
                    $baseSql . "AND pv.id = ? LIMIT 1",
                    [(int)$batchItem['variant_id']]
                );
                if ($variant) {
                    return self::formatVariantForPOS($variant, $shopId, $cand, $parsedMrp, $parsedSellingPrice);
                }
            }
        }

        // 7. Style Code / Prefix / Category Code Match (e.g. MOM092605 -> MOM, BSP092608 -> BSP, TTT092611 -> TTT)
        foreach ($candidates as $cand) {
            if (preg_match('/^([A-Za-z]+)\d{4,8}$/', $cand, $m)) {
                $prefix = strtoupper($m[1]);
                $variant = Database::fetchOne(
                    $baseSql . "AND (p.code = ? OR p.code LIKE ? OR p.category_code = ? OR cat.code = ? OR p.qr_code_prefix = ? OR p.name LIKE ?) 
                     ORDER BY pv.id ASC LIMIT 1",
                    [$prefix, $prefix . '%', $prefix, $prefix, $prefix, $prefix . '%']
                );
                if ($variant) {
                    return self::formatVariantForPOS($variant, $shopId, $cand, $parsedMrp, $parsedSellingPrice);
                }
            }
        }

        return null;
    }

    private static function findDamageForPieceTag(int $variantId, string $pieceTag): ?array {
        $normalizedTag = strtoupper(trim($pieceTag));
        if ($normalizedTag === '') {
            return null;
        }

        $damageRecords = Database::fetchAll(
            "SELECT id, damage_number, tag_code
             FROM shop_damages
             WHERE variant_id = ? AND tag_code IS NOT NULL
             ORDER BY id DESC",
            [$variantId]
        );

        foreach ($damageRecords as $damage) {
            foreach (ShopDamage::resolvePieceTags($damage) as $damageTag) {
                if (strtoupper(trim($damageTag)) === $normalizedTag) {
                    return $damage;
                }
            }
        }

        return null;
    }

    private static function formatVariantForPOS(
        array $variant, 
        int $shopId, 
        ?string $scannedTag = null,
        ?float $parsedMrp = null,
        ?float $parsedSellingPrice = null
    ): array {
        // Fetch live available stock in this shop
        $stock = StockService::getBalance((int)$variant['id'], 'SHOP', $shopId, 'AVAILABLE');
        $variant['available_stock'] = $stock;
        
        // Resolve prices with rich multi-table fallback & self-healing
        self::resolveVariantPrices($variant, $scannedTag, $parsedMrp, $parsedSellingPrice);

        $isComp = ((isset($variant['warehouse_category_type']) && $variant['warehouse_category_type'] === 'COMPLEMENTARY') || !empty($variant['is_complementary']) || !empty($variant['p_is_comp']));
        $isSeries = ((isset($variant['warehouse_category_type']) && $variant['warehouse_category_type'] === 'SERIES') || ($variant['qr_type'] ?? '') === 'SERIES_BATCH' || (int)($variant['pack_quantity'] ?? 1) > 1);
        $variant['is_complementary'] = $isComp ? 1 : 0;
        $variant['is_series'] = $isSeries ? 1 : 0;
        $variant['category_type'] = $variant['warehouse_category_type'] ?? 'NORMAL';

        if ($isComp) {
            $variant['selling_price'] = 0.00;
            $variant['mrp'] = 0.00;
            $variant['price'] = 0.00;
            $variant['discount_price'] = 0.00;
            $variant['gst_percentage'] = 0.00;
        } else {
            $variant['gst_percentage'] = (float)($variant['gst_percentage'] ?? 5.00);
        }

        $variant['scanned_piece_tag'] = $scannedTag;

        $damageRecord = !empty($scannedTag)
            ? self::findDamageForPieceTag((int)$variant['id'], $scannedTag)
            : null;
        if ($damageRecord) {
            $variant['is_damaged'] = 1;
            $variant['damage_number'] = $damageRecord['damage_number'];
        }

        // Image priority: variant image -> product image -> null
        $variant['image'] = !empty($variant['variant_image']) ? $variant['variant_image'] : ($variant['product_image'] ?? null);

        // Check if this specific piece tag was already sold in a previous invoice (bypass if SERIES QR)
        if (!empty($scannedTag) && !$isSeries) {
            $alreadySold = Database::fetchOne(
                "SELECT si.id, s.invoice_number 
                 FROM sale_items si
                 JOIN sales s ON si.sale_id = s.id
                 WHERE s.shop_id = ? AND s.status != 'CANCELLED' 
                   AND (FIND_IN_SET(?, si.scanned_qr_codes) OR si.scanned_qr_codes = ? OR si.scanned_qr_codes LIKE ?)
                 LIMIT 1",
                [$shopId, $scannedTag, $scannedTag, '%' . $scannedTag . '%']
            );
            if ($alreadySold && !empty($alreadySold['invoice_number'])) {
                $variant['already_sold_invoice'] = $alreadySold['invoice_number'];
            }
        }

        return $variant;
    }

    /**
     * Process POS Retail Sale and generate invoice.
     */
    public static function processPOSSale(array $payload, int $userId): array {
        $shopId = (int)($payload['shop_id'] ?? 1);
        $items = $payload['items'] ?? [];
        $paymentMode = $payload['payment_mode'] ?? 'CASH';
        $splitCash = (float)($payload['cash_amount'] ?? 0);
        $splitUpi = (float)($payload['upi_amount'] ?? 0);
        $splitUpiRef = trim($payload['upi_reference'] ?? '');

        $customerPhone = trim($payload['customer_phone'] ?? '');
        $customerName = trim($payload['customer_name'] ?? '');
        $employeeInput = trim($payload['employee_name'] ?? $payload['employee_id'] ?? '');
        $discountAmount = (float)($payload['discount_amount'] ?? 0);
        $notes = $payload['notes'] ?? 'POS In-Store Sale';

        if ($paymentMode === 'CASH_UPI') {
            $splitSummary = "[Split Payment: Cash ₹" . number_format($splitCash, 2) . " + UPI ₹" . number_format($splitUpi, 2) . ($splitUpiRef !== '' ? " (Ref: " . $splitUpiRef . ")" : "") . "]";
            $notes = $splitSummary . (!empty($notes) && $notes !== 'POS In-Store Sale' ? " | " . $notes : "");
        }

        $billingStaffId = $userId;
        if (!empty($employeeInput)) {
            $matchedUser = Database::fetchOne(
                "SELECT id, full_name, username FROM users WHERE id = ? OR username = ? OR full_name = ? LIMIT 1",
                [$employeeInput, $employeeInput, $employeeInput]
            );
            if ($matchedUser) {
                $billingStaffId = (int)$matchedUser['id'];
            }
            if (empty($notes) || $notes === 'POS In-Store Sale') {
                $notes = "Staff: $employeeInput";
            } else {
                $notes .= " | Staff: $employeeInput";
            }
        }

        if (empty($items)) {
            throw new InvalidArgumentException("Cannot checkout with an empty cart. Please scan product QR codes.");
        }

        Database::beginTransaction();
        try {
            // 1. Resolve or Create Customer if phone or name is given
            $customerId = null;
            $customerAddress = trim($payload['customer_address'] ?? '');
            $customerGst = trim($payload['customer_gst'] ?? '');
            $customerEmail = trim($payload['customer_email'] ?? '');
            $customerCity = trim($payload['customer_city'] ?? '');

            if (!empty($customerPhone)) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
                $last10 = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;
                $cust = Database::fetchOne("SELECT id FROM customers WHERE phone = ? OR (phone IS NOT NULL AND phone != '' AND RIGHT(REGEXP_REPLACE(phone, '[^0-9]', ''), 10) = ?)", [$customerPhone, $last10]);
                if ($cust) {
                    $customerId = $cust['id'];
                    $updateFields = [];
                    $updateVals = [];
                    if (!empty($customerName)) { $updateFields[] = "name = ?"; $updateVals[] = $customerName; }
                    if (!empty($customerAddress)) { $updateFields[] = "address = ?"; $updateVals[] = $customerAddress; }
                    if (!empty($customerGst)) { $updateFields[] = "gst_number = ?"; $updateVals[] = $customerGst; }
                    if (!empty($customerEmail)) { $updateFields[] = "email = ?"; $updateVals[] = $customerEmail; }
                    if (!empty($customerCity)) { $updateFields[] = "city = ?"; $updateVals[] = $customerCity; }
                    if (!empty($updateFields)) {
                        $updateVals[] = $customerId;
                        Database::execute("UPDATE customers SET " . implode(', ', $updateFields) . " WHERE id = ?", $updateVals);
                    }
                } else {
                    Database::execute(
                        "INSERT INTO customers (name, phone, email, address, city, gst_number, source_channel, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 'SHOP', 1, NOW())",
                        [
                            !empty($customerName) ? $customerName : 'Retail Customer',
                            $customerPhone,
                            $customerEmail ?: null,
                            $customerAddress ?: null,
                            $customerCity ?: null,
                            $customerGst ?: null
                        ]
                    );
                    $customerId = (int)Database::lastInsertId();
                }
            } elseif (!empty($customerName) && strcasecmp($customerName, 'Walk-in Customer') !== 0) {
                // If customer name is provided without phone
                Database::execute(
                    "INSERT INTO customers (name, email, address, city, gst_number, source_channel, is_active, created_at) VALUES (?, ?, ?, ?, ?, 'SHOP', 1, NOW())",
                    [
                        $customerName,
                        $customerEmail ?: null,
                        $customerAddress ?: null,
                        $customerCity ?: null,
                        $customerGst ?: null
                    ]
                );
                $customerId = (int)Database::lastInsertId();
            }

            // 2. Generate unique Invoice Number (e.g. INV-20260912-0001)
            $today = date('Ymd');
            $prefix = 'INV-' . $today . '-';
            $lastInv = Database::fetchOne(
                "SELECT invoice_number FROM sales WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1",
                [$prefix . '%']
            );
            $seq = 1;
            if ($lastInv && !empty($lastInv['invoice_number'])) {
                $parts = explode('-', $lastInv['invoice_number']);
                $seq = (int)end($parts) + 1;
            }
            $invoiceNumber = sprintf('%s%04d', $prefix, $seq);

            // 3. Calculate Totals & Validate Stocks
            $subtotal = 0.0;
            $cgstTotal = 0.0;
            $sgstTotal = 0.0;
            $totalDiscountAmount = 0.0;
            $totalBilledGross = 0.0;
            $allowNegative = SettingsService::get('allow_negative_stock', false);

            $processedItems = [];
            foreach ($items as $item) {
                $variantId = (int)$item['variant_id'];
                $qty = (int)($item['quantity'] ?? 1);
                if ($qty <= 0) continue;

                $vRow = Database::fetchOne(
                    "SELECT pv.*, p.is_complementary as p_is_comp, wc.category_type as warehouse_category_type
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
                     WHERE pv.id = ?",
                    [$variantId]
                );
                $isComp = $vRow && ((isset($vRow['warehouse_category_type']) && $vRow['warehouse_category_type'] === 'COMPLEMENTARY') || !empty($vRow['is_complementary']) || !empty($vRow['p_is_comp']));
                $isSeries = $vRow && ((isset($vRow['warehouse_category_type']) && $vRow['warehouse_category_type'] === 'SERIES') || ($vRow['qr_type'] ?? '') === 'SERIES_BATCH' || (int)($vRow['pack_quantity'] ?? 1) > 1);

                $mrp = max(0.0, (float)($item['mrp'] ?? $item['original_price'] ?? $item['unit_price'] ?? 0));
                $originalUnitPrice = $mrp;
                $billedPrice = isset($item['billed_price']) ? (float)$item['billed_price'] : (float)($item['unit_price'] ?? $originalUnitPrice);
                $gstInput = $item['gst_percentage'] ?? 5.00;
                if (!is_numeric($gstInput) || !is_finite((float)$gstInput) || (float)$gstInput < 0 || (float)$gstInput > 100) {
                    throw new RuntimeException("GST percentage must be between 0 and 100 for item '{$variantId}'.");
                }
                $gstRate = (float)$gstInput;

                if ($isComp) {
                    $billedPrice = 0.00;
                    $originalUnitPrice = 0.00;
                    $mrp = 0.00;
                    $gstRate = 0.00;
                }

                $itemDiscount = (!$isComp && $billedPrice < $mrp)
                    ? round(($mrp - $billedPrice) * $qty, 2)
                    : 0.0;
                $discountPercent = (!$isComp && ($mrp * $qty) > 0)
                    ? round(($itemDiscount / ($mrp * $qty)) * 100, 2)
                    : 0.0;

                // Extract piece tags string & validate against duplicate sales (bypassed if SERIES)
                $scannedTagsStr = null;
                if (!empty($item['scanned_tags'])) {
                    $tagsArray = is_array($item['scanned_tags']) ? $item['scanned_tags'] : explode(',', $item['scanned_tags']);
                    $validTags = [];
                    foreach ($tagsArray as $t) {
                        $t = trim($t);
                        if (empty($t)) continue;

                        $damageRecord = self::findDamageForPieceTag($variantId, $t);
                        if ($damageRecord) {
                            throw new RuntimeException("Piece QR '{$t}' is recorded as damaged in Damage #{$damageRecord['damage_number']}. Damaged products cannot be billed.");
                        }
                        
                        if (!$isSeries) {
                            // Check if already sold in any past non-cancelled sale
                            $alreadySold = Database::fetchOne(
                                "SELECT s.invoice_number FROM sale_items si 
                                 JOIN sales s ON si.sale_id = s.id 
                                 WHERE s.shop_id = ? AND s.status != 'CANCELLED' 
                                   AND (FIND_IN_SET(?, si.scanned_qr_codes) OR si.scanned_qr_codes = ? OR si.scanned_qr_codes LIKE ?)
                                 LIMIT 1",
                                [$shopId, $t, $t, '%' . $t . '%']
                            );
                            if ($alreadySold && !empty($alreadySold['invoice_number'])) {
                                throw new RuntimeException("Piece Serial Tag '{$t}' was already sold in Invoice #{$alreadySold['invoice_number']}. Cannot double-bill the same physical garment.");
                            }
                        }
                        $validTags[] = $t;
                    }
                    if (!empty($validTags)) {
                        $scannedTagsStr = implode(',', array_unique($validTags));
                    }
                }

                // Check available shop stock
                $currentStock = StockService::getBalance($variantId, 'SHOP', $shopId, 'AVAILABLE');
                if (!$allowNegative && $currentStock < $qty) {
                    $variantInfo = Database::fetchOne("SELECT sku FROM product_variants WHERE id = ?", [$variantId]);
                    $sku = $variantInfo['sku'] ?? "#$variantId";
                    throw new RuntimeException("Insufficient stock in shop for item '$sku'. Available: $currentStock, Required: $qty");
                }

                $lineBilledTotal = $isComp ? 0.00 : ($qty * $billedPrice);
                // Inclusive GST Calculation on Billed Amount: Taxable Base = Billed Total / (1 + GST%)
                $taxableBase = $isComp ? 0.00 : round($lineBilledTotal / (1 + ($gstRate / 100)), 2);
                $taxAmount = $isComp ? 0.00 : round($lineBilledTotal - $taxableBase, 2);
                $cgst = $isComp ? 0.00 : round($taxAmount / 2, 2);
                $sgst = $isComp ? 0.00 : round($taxAmount - $cgst, 2);

                $subtotal += $taxableBase;
                $cgstTotal += $cgst;
                $sgstTotal += $sgst;
                $totalDiscountAmount += $itemDiscount;
                $totalBilledGross += $lineBilledTotal;

                $processedItems[] = [
                    'variant_id'        => $variantId,
                    'quantity'          => $qty,
                    'unit_price'        => $originalUnitPrice,
                    'mrp'               => $mrp,
                    'discount_percent'  => $discountPercent,
                    'discount_amount'   => $itemDiscount,
                    'gst_percentage'    => $gstRate,
                    'cgst_amount'       => $cgst,
                    'sgst_amount'       => $sgst,
                    'igst_amount'       => 0.00,
                    'total_amount'      => $lineBilledTotal,
                    'scanned_qr_codes'  => $scannedTagsStr,
                ];
            }

            $unroundedTotal = $totalBilledGross;
            $totalAmount = (float)ceil($unroundedTotal);
            $roundOff = round($totalAmount - $unroundedTotal, 2);

            // 4. Insert into `sales`
            Database::execute(
                "INSERT INTO sales (invoice_number, sale_type, shop_id, customer_id, customer_name, customer_phone, customer_address, subtotal, discount_amount, 
                                    cgst_amount, sgst_amount, igst_amount, total_amount, payment_mode, 
                                    payment_status, status, billing_staff_id, employee_name, notes, created_at)
                 VALUES (?, 'SHOP', ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, ?, 'PAID', 'PAID', ?, ?, ?, ?)",
                [
                    $invoiceNumber,
                    $shopId,
                    $customerId,
                    $customerName ?: 'Retail Customer',
                    $customerPhone,
                    $customerAddress,
                    $subtotal,
                    $totalDiscountAmount,
                    $cgstTotal,
                    $sgstTotal,
                    $totalAmount,
                    $paymentMode,
                    $billingStaffId,
                    $employeeInput,
                    $notes,
                    gmdate('Y-m-d H:i:s'),
                ]
            );
            $saleId = (int)Database::lastInsertId();

            // 5. Insert `sale_items` & Deduct Central Stock via StockService
            foreach ($processedItems as $pItem) {
                Database::execute(
                    "INSERT INTO sale_items (sale_id, variant_id, quantity, unit_price, mrp, discount_percent, discount_amount, gst_percentage, cgst_amount, sgst_amount, igst_amount, total_amount, scanned_qr_codes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $saleId,
                        $pItem['variant_id'],
                        $pItem['quantity'],
                        $pItem['unit_price'],
                        $pItem['mrp'],
                        $pItem['discount_percent'],
                        $pItem['discount_amount'],
                        $pItem['gst_percentage'],
                        $pItem['cgst_amount'],
                        $pItem['sgst_amount'],
                        $pItem['igst_amount'],
                        $pItem['total_amount'],
                        $pItem['scanned_qr_codes'],
                    ]
                );

                $tagNote = !empty($pItem['scanned_qr_codes']) ? " [Piece Tags: {$pItem['scanned_qr_codes']}]" : "";

                // Deduct stock using Central Stock Ledger
                StockService::decrease(
                    $pItem['variant_id'],
                    'SHOP',
                    $shopId,
                    'AVAILABLE',
                    (float)$pItem['quantity'],
                    'SALE',
                    'sales',
                    $saleId,
                    $userId,
                    "POS Sale: $invoiceNumber$tagNote"
                );
            }

            // 6. Record Payment(s)
            if ($paymentMode === 'CASH_UPI') {
                $splitCash = (float)($payload['cash_amount'] ?? 0);
                $splitUpi = (float)($payload['upi_amount'] ?? 0);
                $splitUpiRef = trim($payload['upi_reference'] ?? '');

                if ($splitCash <= 0 && $splitUpi <= 0) {
                    $splitCash = $totalAmount;
                    $splitUpi = 0;
                }

                if ($splitCash > 0) {
                    Database::execute(
                        "INSERT INTO payments (sale_id, payment_number, payment_mode, amount, reference_number, status, created_at)
                         VALUES (?, ?, 'CASH', ?, 'POS-CASH', 'COMPLETED', NOW())",
                        [$saleId, $invoiceNumber . '-CASH', $splitCash]
                    );
                }
                if ($splitUpi > 0) {
                    Database::execute(
                        "INSERT INTO payments (sale_id, payment_number, payment_mode, amount, reference_number, status, created_at)
                         VALUES (?, ?, 'UPI', ?, ?, 'COMPLETED', NOW())",
                        [$saleId, $invoiceNumber . '-UPI', $splitUpi, $splitUpiRef ?: $invoiceNumber]
                    );
                }
            } else {
                Database::execute(
                    "INSERT INTO payments (sale_id, payment_number, payment_mode, amount, reference_number, status, created_at)
                     VALUES (?, ?, ?, ?, ?, 'COMPLETED', NOW())",
                    [
                        $saleId,
                        $invoiceNumber . '-PAY',
                        $paymentMode,
                        $totalAmount,
                        $payload['payment_ref'] ?? $invoiceNumber,
                    ]
                );
            }

            // 7. Auto-Record in Cash Flow Ledger (Live Shift Register Sync)
            $activeReg = CashService::getActiveRegister($shopId);

            if ($paymentMode === 'CASH_UPI') {
                $splitCash = (float)($payload['cash_amount'] ?? 0);
                $splitUpi = (float)($payload['upi_amount'] ?? 0);
                $splitUpiRef = trim($payload['upi_reference'] ?? '');

                if ($splitCash <= 0 && $splitUpi <= 0) {
                    $splitCash = $totalAmount;
                }

                if ($splitCash > 0) {
                    CashService::logTransaction([
                        'shop_id'          => $shopId,
                        'register_id'      => $activeReg ? (int)$activeReg['id'] : null,
                        'transaction_type' => 'SALE_CASH',
                        'flow_direction'   => 'IN',
                        'amount'           => $splitCash,
                        'payment_mode'     => 'CASH',
                        'category'         => 'SALES',
                        'reference_id'     => $invoiceNumber,
                        'source_table'     => 'sales',
                        'source_id'        => $saleId,
                        'handled_by'       => $userId,
                        'description'      => "POS Retail Sale (Cash Split): {$invoiceNumber}"
                    ]);
                }

                if ($splitUpi > 0) {
                    CashService::logTransaction([
                        'shop_id'          => $shopId,
                        'register_id'      => $activeReg ? (int)$activeReg['id'] : null,
                        'transaction_type' => 'SALE_UPI',
                        'flow_direction'   => 'IN',
                        'amount'           => $splitUpi,
                        'payment_mode'     => 'UPI',
                        'category'         => 'SALES',
                        'reference_id'     => $invoiceNumber,
                        'source_table'     => 'sales',
                        'source_id'        => $saleId,
                        'handled_by'       => $userId,
                        'description'      => "POS Retail Sale (UPI Split" . ($splitUpiRef !== '' ? " Ref: {$splitUpiRef}" : "") . "): {$invoiceNumber}"
                    ]);
                }
            } else {
                $txType = 'SALE_CASH';
                if ($paymentMode === 'UPI') $txType = 'SALE_UPI';
                elseif ($paymentMode === 'CARD') $txType = 'SALE_CARD';

                CashService::logTransaction([
                    'shop_id'          => $shopId,
                    'register_id'      => $activeReg ? (int)$activeReg['id'] : null,
                    'transaction_type' => $txType,
                    'flow_direction'   => 'IN',
                    'amount'           => $totalAmount,
                    'payment_mode'     => in_array($paymentMode, ['CASH', 'UPI', 'CARD']) ? $paymentMode : 'CASH',
                    'category'         => 'SALES',
                    'reference_id'     => $invoiceNumber,
                    'source_table'     => 'sales',
                    'source_id'        => $saleId,
                    'handled_by'       => $userId,
                    'description'      => "POS Retail Sale ({$paymentMode}): {$invoiceNumber}"
                ]);
            }

            if ($activeReg) {
                CashService::getActiveRegister($shopId);
            }

            // 8. Audit Log
            AuditService::log('pos_sale', 'CREATE', 'sales', $saleId, null, [
                'invoice_number' => $invoiceNumber,
                'total_amount'   => $totalAmount,
                'payment_mode'   => $paymentMode,
                'items_count'    => count($processedItems),
            ]);

            Database::commit();

            return [
                'sale_id'        => $saleId,
                'invoice_number' => $invoiceNumber,
                'total_amount'   => $totalAmount,
            ];
        } catch (Exception $e) {
            Database::rollback();
            error_log("ShopService::processPOSSale error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get Complete Invoice Details for Printing.
     */
    public static function getInvoiceDetails(int $saleId): ?array {
        $sale = Database::fetchOne(
            "SELECT s.*, c.name as customer_name, c.phone as customer_phone, 
                    COALESCE(s.customer_address, c.address) as customer_address, 
                    c.gst_number as customer_gst,
                    u.full_name as staff_name, sh.name as shop_name, sh.address as shop_address, 
                    sh.phone as shop_phone, sh.gst_number as shop_gstin
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN users u ON s.billing_staff_id = u.id
             LEFT JOIN shops sh ON s.shop_id = sh.id
             WHERE s.id = ?",
            [$saleId]
        );

        if (!$sale) return null;
        $sale['created_at_display'] = formatUtcDateTime($sale['created_at'] ?? null);
        $sale['created_at_date_display'] = formatUtcDateTime($sale['created_at'] ?? null, 'd-m-Y');
        $sale['created_at_time_display'] = formatUtcDateTime($sale['created_at'] ?? null, 'h:i A');

        $items = Database::fetchAll(
            "SELECT si.*, pv.sku, pv.qr_code, pv.mrp, pv.selling_price, p.name as product_name, 
                    p.code as product_code, p.hsn_code, p.gst_percentage,
                    sz.name as size_name, cl.name as color_name
             FROM sale_items si
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             WHERE si.sale_id = ?",
            [$saleId]
        );

        $payments = Database::fetchAll("SELECT * FROM payments WHERE sale_id = ?", [$saleId]);
        $totalQty = 0;
        foreach ($items as $item) {
            $totalQty += (int)$item['quantity'];
        }

        return [
            'sale'     => $sale,
            'items'    => $items,
            'payments' => $payments,
            'totalQty' => $totalQty,
        ];
    }

    /**
     * Get Shop Stock Inventory list with full product & variant details, advanced filters, and sorting.
     */
    public static function getShopInventory(int $shopId, array $filters = []): array {
        $sql = "SELECT sb.*, 
                       pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost, 
                       pv.weight as variant_weight, pv.image_url as variant_image,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       p.category_code, p.subcategory_code, p.sub_subcategory_code, p.qr_code_prefix,
                       COALESCE(wc.category_type, 'NORMAL') as warehouse_category_type,
                       COALESCE(pv.qr_type, '') as variant_qr_type,
                       COALESCE(pv.is_complementary, p.is_complementary, 0) as is_complementary,
                       COALESCE(pv.pack_quantity, 1) as pack_quantity,
                       p.description, p.material, p.hsn_code, p.gst_percentage, p.min_stock, p.max_stock, p.source_type,
                       s.name as size_name, c.name as color_name, 
                       COALESCE(wc.id, cat.id) as category_id,
                       CASE WHEN wc.id IS NOT NULL THEN CONCAT('warehouse:', wc.id)
                            WHEN cat.id IS NOT NULL THEN CONCAT('product:', cat.id)
                            ELSE '' END as category_filter_id,
                       COALESCE(wc.name, cat.name) as category_name,
                       COALESCE(wsc.name, subcat.name) as subcategory_name,
                       COALESCE(wssc.name, subsubcat.name) as sub_subcategory_name,
                       b.name as brand_name, sel.name as seller_name,
                       sb.quantity as available_quantity,
                       (SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE variant_id = sb.variant_id AND location_type = 'SHOP' AND location_id = sb.location_id AND stock_state = 'DAMAGED') as damaged_quantity,
                       (SELECT COALESCE(SUM(si.quantity), 0) FROM sale_items si JOIN sales sl ON si.sale_id = sl.id WHERE si.variant_id = sb.variant_id AND sl.shop_id = sb.location_id AND sl.sale_type = 'SHOP' AND sl.status != 'CANCELLED') as sold_quantity,
                       (SELECT COALESCE(SUM(wri.returned_qty), 0) FROM warehouse_return_items wri JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id WHERE wri.variant_id = sb.variant_id AND wr.source_type = 'SHOP' AND wr.source_id = sb.location_id) as returned_quantity,
                       (SELECT COALESCE(SUM(ri.quantity), 0) FROM return_items ri JOIN returns r ON ri.return_id = r.id LEFT JOIN sales s ON r.sale_id = s.id WHERE ri.variant_id = sb.variant_id AND (r.source_type = 'SHOP' OR s.shop_id = sb.location_id) AND r.return_type = 'EXCHANGE') as exchanged_quantity,
                       (SELECT wa.allocation_number FROM allocation_items ai JOIN warehouse_allocations wa ON ai.allocation_id = wa.id WHERE ai.variant_id = sb.variant_id AND (wa.shop_id = sb.location_id OR (wa.destination_type = 'SHOP' AND wa.destination_id = sb.location_id)) ORDER BY wa.id DESC LIMIT 1) as latest_allocation_number
                FROM stock_balances sb
                JOIN product_variants pv ON sb.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN warehouse_sub_subcategories wssc ON p.warehouse_sub_subcategory_id = wssc.id
                LEFT JOIN warehouse_subcategories wssc_parent ON wssc.subcategory_id = wssc_parent.id
                LEFT JOIN warehouse_subcategories wsc ON COALESCE(p.warehouse_subcategory_id, wssc.subcategory_id) = wsc.id
                LEFT JOIN warehouse_categories wc ON COALESCE(p.warehouse_category_id, wsc.category_id, wssc_parent.category_id) = wc.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN sellers sel ON p.seller_id = sel.id
                LEFT JOIN sizes s ON pv.size_id = s.id
                LEFT JOIN colors c ON pv.color_id = c.id
                WHERE sb.location_type = 'SHOP' AND sb.location_id = ? AND sb.stock_state = 'AVAILABLE'";

        $params = [$shopId];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR pv.sku LIKE ? OR pv.qr_code LIKE ? OR p.code LIKE ? OR COALESCE(wc.name, cat.name) LIKE ? OR s.name LIKE ? OR c.name LIKE ?)";
            $s = '%' . trim($filters['search']) . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s]);
        }

        if (!empty($filters['category_id'])) {
            $categoryFilter = (string)$filters['category_id'];
            if (preg_match('/^warehouse:(\d+)$/', $categoryFilter, $matches)) {
                $sql .= " AND COALESCE(p.warehouse_category_id, wsc.category_id, wssc_parent.category_id) = ?";
                $params[] = (int)$matches[1];
            } elseif (preg_match('/^product:(\d+)$/', $categoryFilter, $matches)) {
                $sql .= " AND p.category_id = ?";
                $params[] = (int)$matches[1];
            } elseif (ctype_digit($categoryFilter)) {
                $sql .= " AND p.category_id = ?";
                $params[] = (int)$categoryFilter;
            }
        }

        if (!empty($filters['status'])) {
            $status = strtolower($filters['status']);
            if ($status === 'in_stock') {
                $sql .= " AND sb.quantity > COALESCE(p.min_stock, 5)";
            } elseif ($status === 'low_stock') {
                $sql .= " AND sb.quantity <= COALESCE(p.min_stock, 5) AND sb.quantity > 0";
            } elseif ($status === 'out_of_stock') {
                $sql .= " AND sb.quantity <= 0";
            } elseif ($status === 'damaged') {
                $sql .= " AND (sb.variant_id IN (SELECT variant_id FROM stock_balances WHERE location_type = 'SHOP' AND location_id = ? AND stock_state = 'DAMAGED' AND quantity > 0) OR sb.variant_id IN (SELECT variant_id FROM shop_damages WHERE shop_id = ? AND status != 'RESTOCKED'))";
                $params[] = $shopId;
                $params[] = $shopId;
            } elseif ($status === 'returned') {
                $sql .= " AND (sb.variant_id IN (SELECT wri.variant_id FROM warehouse_return_items wri JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id WHERE wr.source_type = 'SHOP' AND wr.source_id = ?))";
                $params[] = $shopId;
            } elseif ($status === 'exchanged') {
                $sql .= " AND (sb.variant_id IN (SELECT ri.variant_id FROM return_items ri JOIN returns r ON ri.return_id = r.id LEFT JOIN sales s ON r.sale_id = s.id WHERE (r.source_type = 'SHOP' OR s.shop_id = ?) AND r.return_type = 'EXCHANGE'))";
                $params[] = $shopId;
            }
        }

        // Sorting
        $sort = $filters['sort'] ?? 'name_asc';
        switch ($sort) {
            case 'stock_desc':
                $sql .= " ORDER BY sb.quantity DESC, p.name ASC";
                break;
            case 'stock_asc':
                $sql .= " ORDER BY sb.quantity ASC, p.name ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY pv.selling_price DESC, p.name ASC";
                break;
            case 'price_asc':
                $sql .= " ORDER BY pv.selling_price ASC, p.name ASC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY p.name DESC, pv.sku DESC";
                break;
            case 'sku_asc':
                $sql .= " ORDER BY pv.sku ASC";
                break;
            case 'name_asc':
            default:
                $sql .= " ORDER BY p.name ASC, pv.sku ASC";
                break;
        }

        $items = Database::fetchAll($sql, $params);
        foreach ($items as &$it) {
            if ((float)($it['selling_price'] ?? 0) <= 0 || (float)($it['mrp'] ?? 0) <= 0) {
                self::resolveVariantPrices($it);
            }
        }
        unset($it);

        return $items;
    }

    /**
     * Get summary metrics for shop inventory.
     */
    public static function getShopInventorySummary(int $shopId): array {
        $allItems = self::getShopInventory($shopId, []);
        $totalUnits = 0;
        $totalSkus = count($allItems);
        $totalValuation = 0.0;
        $totalMrpValuation = 0.0;
        $inStockCount = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $damagedSkusCount = 0;
        $returnedSkusCount = 0;
        $exchangedSkusCount = 0;
        $categoriesMap = [];

        $totalAvailableUnits = 0;
        $totalDamagedUnits = 0;
        $totalReturnedUnits = 0;
        $totalExchangedUnits = 0;

        foreach ($allItems as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            $dmg = (float)($item['damaged_quantity'] ?? 0);
            $ret = (float)($item['returned_quantity'] ?? 0);
            $exc = (float)($item['exchanged_quantity'] ?? 0);

            $minStock = (float)($item['min_stock'] ?? 5);
            $price = (float)($item['selling_price'] ?? 0);
            $mrp = (float)($item['mrp'] ?? $price);

            $totalUnits += $qty;
            $totalAvailableUnits += $qty;
            $totalDamagedUnits += $dmg;
            $totalReturnedUnits += $ret;
            $totalExchangedUnits += $exc;

            $totalValuation += ($qty * $price);
            $totalMrpValuation += ($qty * $mrp);

            if ($qty <= 0) {
                $outOfStockCount++;
            } elseif ($qty <= $minStock) {
                $lowStockCount++;
            } else {
                $inStockCount++;
            }

            if ($dmg > 0) $damagedSkusCount++;
            if ($ret > 0) $returnedSkusCount++;
            if ($exc > 0) $exchangedSkusCount++;

            if (!empty($item['category_id']) && !empty($item['category_name'])) {
                $categoriesMap[$item['category_id']] = $item['category_name'];
            }
        }

        return [
            'total_units'           => $totalUnits,
            'total_available_units' => $totalAvailableUnits,
            'total_damaged_units'   => $totalDamagedUnits,
            'total_returned_units'  => $totalReturnedUnits,
            'total_exchanged_units' => $totalExchangedUnits,
            'total_skus'            => $totalSkus,
            'total_valuation'       => $totalValuation,
            'total_mrp_valuation'   => $totalMrpValuation,
            'in_stock_count'        => $inStockCount,
            'low_stock_count'       => $lowStockCount,
            'out_of_stock_count'    => $outOfStockCount,
            'damaged_skus_count'    => $damagedSkusCount,
            'returned_skus_count'   => $returnedSkusCount,
            'exchanged_skus_count'  => $exchangedSkusCount,
            'damaged_units'         => $totalDamagedUnits,
            'categories'            => $categoriesMap,
        ];
    }

    /**
     * Get Aggregated Analytics & Chart Data for Shop Inventory.
     */
    public static function getShopInventoryChartsData(int $shopId): array {
        $allItems = self::getShopInventory($shopId, []);

        $byCategory = [];
        $bySize = [];
        $healthBreakdown = [
            'healthy' => ['count' => 0, 'units' => 0, 'label' => 'Healthy Stock (> 5 pcs)'],
            'low'     => ['count' => 0, 'units' => 0, 'label' => 'Low Stock (1-5 pcs)'],
            'out'     => ['count' => 0, 'units' => 0, 'label' => 'Out of Stock (0 pcs)'],
        ];
        $topItems = [];

        foreach ($allItems as $item) {
            $cat = !empty($item['category_name']) ? $item['category_name'] : 'Uncategorized';
            $qty = (float)($item['quantity'] ?? 0);
            $price = (float)($item['selling_price'] ?? 0);
            $mrp = (float)($item['mrp'] ?? $price);
            $minStock = (float)($item['min_stock'] ?? 5);
            $val = $qty * $price;
            $mrpVal = $qty * $mrp;

            // Category Aggregation
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = [
                    'category'      => $cat,
                    'units'         => 0,
                    'valuation'     => 0.0,
                    'mrp_valuation' => 0.0,
                    'sku_count'     => 0,
                ];
            }
            $byCategory[$cat]['units'] += $qty;
            $byCategory[$cat]['valuation'] += $val;
            $byCategory[$cat]['mrp_valuation'] += $mrpVal;
            $byCategory[$cat]['sku_count'] += 1;

            // Size Aggregation
            $size = !empty($item['size_name']) ? $item['size_name'] : 'Free';
            $bySize[$size] = ($bySize[$size] ?? 0) + $qty;

            // Health Status
            if ($qty <= 0) {
                $healthBreakdown['out']['count']++;
            } elseif ($qty <= $minStock) {
                $healthBreakdown['low']['count']++;
                $healthBreakdown['low']['units'] += $qty;
            } else {
                $healthBreakdown['healthy']['count']++;
                $healthBreakdown['healthy']['units'] += $qty;
            }

            // Top items
            $topItems[] = [
                'name'          => $item['product_name'] ?? 'Product',
                'sku'           => $item['sku'] ?? '',
                'category'      => $cat,
                'size'          => $item['size_name'] ?? '',
                'color'         => $item['color_name'] ?? '',
                'quantity'      => $qty,
                'selling_price' => $price,
                'valuation'     => $val,
            ];
        }

        // Sort Top Items by available stock descending
        usort($topItems, function($a, $b) {
            return $b['quantity'] <=> $a['quantity'];
        });
        $topItems = array_slice($topItems, 0, 8);

        // Sort Category by Units descending
        uasort($byCategory, function($a, $b) {
            return $b['units'] <=> $a['units'];
        });

        // Sort Sizes
        arsort($bySize);

        return [
            'categories'       => array_values($byCategory),
            'sizes'            => $bySize,
            'health_breakdown' => $healthBreakdown,
            'top_items'        => $topItems,
        ];
    }

    /**
     * Get Complete Single Product Variant Details for Shop Inventory.
     */
    public static function getShopInventoryItem(int $shopId, int $variantId): ?array {
        $sql = "SELECT sb.*, 
                       pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost, 
                       pv.weight as variant_weight, pv.image_url as variant_image,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       p.category_code, p.subcategory_code, p.sub_subcategory_code, p.qr_code_prefix,
                       COALESCE(wc.category_type, 'NORMAL') as warehouse_category_type,
                       COALESCE(pv.qr_type, '') as variant_qr_type,
                       COALESCE(pv.is_complementary, p.is_complementary, 0) as is_complementary,
                       COALESCE(pv.pack_quantity, 1) as pack_quantity,
                       p.description, p.material, p.hsn_code, p.gst_percentage, p.min_stock, p.max_stock, p.source_type,
                       s.name as size_name, c.name as color_name, 
                       COALESCE(wc.name, cat.name) as category_name,
                       COALESCE(wsc.name, subcat.name) as subcategory_name,
                       COALESCE(wssc.name, subsubcat.name) as sub_subcategory_name,
                       b.name as brand_name, sel.name as seller_name,
                       sb.quantity as available_quantity,
                       (SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE variant_id = sb.variant_id AND location_type = 'SHOP' AND location_id = sb.location_id AND stock_state = 'DAMAGED') as damaged_quantity,
                       (SELECT COALESCE(SUM(si.quantity), 0) FROM sale_items si JOIN sales sl ON si.sale_id = sl.id WHERE si.variant_id = sb.variant_id AND sl.shop_id = sb.location_id AND sl.sale_type = 'SHOP' AND sl.status != 'CANCELLED') as sold_quantity,
                       (SELECT COALESCE(SUM(wri.returned_qty), 0) FROM warehouse_return_items wri JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id WHERE wri.variant_id = sb.variant_id AND wr.source_type = 'SHOP' AND wr.source_id = sb.location_id) as returned_quantity,
                       (SELECT COALESCE(SUM(ri.quantity), 0) FROM return_items ri JOIN returns r ON ri.return_id = r.id LEFT JOIN sales s ON r.sale_id = s.id WHERE ri.variant_id = sb.variant_id AND (r.source_type = 'SHOP' OR s.shop_id = sb.location_id) AND r.return_type = 'EXCHANGE') as exchanged_quantity
                FROM stock_balances sb
                JOIN product_variants pv ON sb.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN warehouse_sub_subcategories wssc ON p.warehouse_sub_subcategory_id = wssc.id
                LEFT JOIN warehouse_subcategories wssc_parent ON wssc.subcategory_id = wssc_parent.id
                LEFT JOIN warehouse_subcategories wsc ON COALESCE(p.warehouse_subcategory_id, wssc.subcategory_id) = wsc.id
                LEFT JOIN warehouse_categories wc ON COALESCE(p.warehouse_category_id, wsc.category_id, wssc_parent.category_id) = wc.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN sellers sel ON p.seller_id = sel.id
                LEFT JOIN sizes s ON pv.size_id = s.id
                LEFT JOIN colors c ON pv.color_id = c.id
                WHERE sb.location_type = 'SHOP' AND sb.location_id = ? AND sb.variant_id = ? AND sb.stock_state = 'AVAILABLE'";

        $item = Database::fetchOne($sql, [$shopId, $variantId]);
        if (!$item) {
            // Fallback query if stock balance is zero or not yet initialized
            $fallbackSql = "SELECT pv.*, pv.id as variant_id, 0 as quantity, 0 as available_quantity,
                                   (SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE variant_id = pv.id AND location_type = 'SHOP' AND location_id = ? AND stock_state = 'DAMAGED') as damaged_quantity,
                                   0 as sold_quantity, 0 as returned_quantity, 0 as exchanged_quantity,
                                   pv.weight as variant_weight, pv.image_url as variant_image,
                                   p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                                   p.category_code, p.subcategory_code, p.sub_subcategory_code, p.qr_code_prefix,
                                   COALESCE(wc.category_type, 'NORMAL') as warehouse_category_type,
                                   COALESCE(pv.qr_type, '') as variant_qr_type,
                                   COALESCE(pv.is_complementary, p.is_complementary, 0) as is_complementary,
                                   COALESCE(pv.pack_quantity, 1) as pack_quantity,
                                   p.description, p.material, p.hsn_code, p.gst_percentage, p.min_stock, p.max_stock, p.source_type,
                                   s.name as size_name, c.name as color_name, 
                                   COALESCE(wc.name, cat.name) as category_name,
                                   COALESCE(wsc.name, subcat.name) as subcategory_name,
                                   COALESCE(wssc.name, subsubcat.name) as sub_subcategory_name,
                                   b.name as brand_name, sel.name as seller_name
                            FROM product_variants pv
                            JOIN products p ON pv.product_id = p.id
                            LEFT JOIN warehouse_sub_subcategories wssc ON p.warehouse_sub_subcategory_id = wssc.id
                            LEFT JOIN warehouse_subcategories wssc_parent ON wssc.subcategory_id = wssc_parent.id
                            LEFT JOIN warehouse_subcategories wsc ON COALESCE(p.warehouse_subcategory_id, wssc.subcategory_id) = wsc.id
                            LEFT JOIN warehouse_categories wc ON COALESCE(p.warehouse_category_id, wsc.category_id, wssc_parent.category_id) = wc.id
                            LEFT JOIN categories cat ON p.category_id = cat.id
                            LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                            LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
                            LEFT JOIN brands b ON p.brand_id = b.id
                            LEFT JOIN sellers sel ON p.seller_id = sel.id
                            LEFT JOIN sizes s ON pv.size_id = s.id
                            LEFT JOIN colors c ON pv.color_id = c.id
                            WHERE pv.id = ?";
            $item = Database::fetchOne($fallbackSql, [$shopId, $variantId]);
        }

        if ($item) {
            self::resolveVariantPrices($item);

            // Fetch recent stock transactions for this variant at this shop
            $item['transactions'] = Database::fetchAll(
                "SELECT st.*, u.full_name as user_name
                 FROM stock_transactions st
                 LEFT JOIN users u ON st.user_id = u.id
                 WHERE st.location_type = 'SHOP' AND st.location_id = ? AND st.variant_id = ?
                 ORDER BY st.id DESC LIMIT 15",
                [$shopId, $variantId]
            );

            // Fetch warehouse balances for this variant
            $item['warehouse_balances'] = Database::fetchAll(
                "SELECT sb.*, w.name as warehouse_name
                 FROM stock_balances sb
                 JOIN warehouses w ON sb.location_id = w.id
                 WHERE sb.location_type = 'WAREHOUSE' AND sb.variant_id = ? AND sb.stock_state = 'AVAILABLE'",
                [$variantId]
            );

            // Fetch Inward Allocations and saved piece QR tags for this variant at this shop
            $allocItems = Database::fetchAll(
                "SELECT ai.*, wa.allocation_number, wa.created_at as alloc_date, wa.status as alloc_status,
                        ao.outward_number, ao.dispatched_at
                 FROM allocation_items ai
                 JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
                 LEFT JOIN allocation_outwards ao ON ao.allocation_id = wa.id
                 WHERE ai.variant_id = ? 
                   AND (wa.shop_id = ? OR (wa.destination_type = 'SHOP' AND wa.destination_id = ?)
                        OR wa.id IN (SELECT reference_id FROM stock_transactions WHERE variant_id = ? AND location_type = 'SHOP' AND location_id = ? AND reference_type = 'warehouse_allocations'))
                 ORDER BY wa.id DESC",
                [$variantId, $shopId, $shopId, $variantId, $shopId]
            );

            $damageRecords = Database::fetchAll(
                "SELECT tag_code, quantity, damage_reason, status, damage_number, defect_type, created_at
                 FROM shop_damages
                 WHERE shop_id = ? AND variant_id = ? AND status != 'RESTOCKED'
                 ORDER BY id ASC",
                [$shopId, $variantId]
            );
            $damagedTagsMap = [];
            foreach ($damageRecords as $damageRecord) {
                foreach (explode(',', (string)($damageRecord['tag_code'] ?? '')) as $damageTag) {
                    $damageTag = strtoupper(trim($damageTag));
                    if ($damageTag !== '') {
                        $damagedTagsMap[$damageTag] = $damageRecord;
                    }
                }
            }

            $savedTags = [];
            $savedTagsByKey = [];
            $allocRef = '';
            $allocationBatches = [];
            $isSingleQrProduct = (
                in_array(strtoupper($item['warehouse_category_type'] ?? ''), ['SERIES', 'COMPLEMENTARY'], true) ||
                ($item['variant_qr_type'] ?? '') === 'SERIES_BATCH' ||
                (int)($item['pack_quantity'] ?? 1) > 1 ||
                !empty($item['is_complementary'])
            );

            foreach ($allocItems as $ai) {
                if (empty($allocRef)) $allocRef = $ai['allocation_number'];
                $batchTags = [];
                if (!empty($ai['selected_qr_codes'])) {
                    $decoded = json_decode($ai['selected_qr_codes'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $t) {
                            $tagStr = is_array($t) ? ($t['tag_code'] ?? ($t['qr_code'] ?? '')) : (string)$t;
                            $tagStr = strtoupper(trim($tagStr));
                            if ($tagStr) {
                                $batchTags[$tagStr] = $tagStr;
                            }
                        }
                    } else {
                        $tags = explode(',', $ai['selected_qr_codes']);
                        foreach ($tags as $t) {
                            $t = strtoupper(trim($t));
                            if ($t) {
                                $batchTags[$t] = $t;
                            }
                        }
                    }
                }
                $batchTags = array_values($batchTags);
                if ($isSingleQrProduct) {
                    $batchTags = array_slice($batchTags, 0, 1);
                }

                $receivedQty = max(0, (int)($ai['received_qty'] ?? 0));
                $receivedTags = [];
                if (!empty($ai['received_qr_codes'])) {
                    $decodedReceivedTags = json_decode((string)$ai['received_qr_codes'], true);
                    $rawReceivedTags = is_array($decodedReceivedTags)
                        ? $decodedReceivedTags
                        : explode(',', (string)$ai['received_qr_codes']);
                    foreach ($rawReceivedTags as $receivedTag) {
                        $receivedTag = is_array($receivedTag)
                            ? ($receivedTag['tag_code'] ?? ($receivedTag['qr_code'] ?? ''))
                            : (string)$receivedTag;
                        $receivedTag = strtoupper(trim($receivedTag));
                        if ($receivedTag !== '') {
                            $receivedTags[$receivedTag] = $receivedTag;
                        }
                    }
                    $receivedTags = array_values($receivedTags);
                }

                if ($receivedTags) {
                    $inventoryTags = array_slice($receivedTags, 0, $receivedQty ?: count($receivedTags));
                } elseif ($receivedQty > 0) {
                    $batchDamagedTags = array_values(array_intersect($batchTags, array_keys($damagedTagsMap)));
                    $batchDamagedTags = array_slice($batchDamagedTags, 0, min(
                        (int)($ai['damaged_qty'] ?? 0),
                        $receivedQty
                    ));
                    $goodTagLimit = max(0, $receivedQty - count($batchDamagedTags));
                    $batchGoodTags = array_values(array_diff($batchTags, $batchDamagedTags));
                    $inventoryTags = array_merge(array_slice($batchGoodTags, 0, $goodTagLimit), $batchDamagedTags);
                } else {
                    $inventoryTags = [];
                }

                foreach ($inventoryTags as $tag) {
                    $tagKey = strtoupper(trim($tag));
                    if ($tagKey !== '' && !isset($savedTagsByKey[$tagKey])) {
                        $savedTagsByKey[$tagKey] = $tagKey;
                    }
                }

                $batchAllocDate = !empty($ai['alloc_date']) ? date('d M Y', strtotime($ai['alloc_date'])) : 'Recent';
                $dispatchedQty = (int)($ai['dispatched_qty'] > 0 ? $ai['dispatched_qty'] : ($ai['requested_qty'] > 0 ? $ai['requested_qty'] : 1));

                $tagRange = '';
                if (!empty($batchTags)) {
                    $tagRange = count($batchTags) > 1 ? ($batchTags[0] . ' - ' . end($batchTags)) : $batchTags[0];
                }

                $allocationBatches[] = [
                    'allocation_id'     => (int)$ai['allocation_id'],
                    'allocation_number' => $ai['allocation_number'],
                    'outward_number'    => $ai['outward_number'] ?? '',
                    'alloc_date'        => $batchAllocDate,
                    'status'            => $ai['alloc_status'] ?? 'RECEIVED',
                    'quantity'          => $dispatchedQty,
                    'tag_range'         => $tagRange,
                    'tags'              => $batchTags,
                ];
            }
            $savedTags = array_values($savedTagsByKey);

            // Fetch sales / billed pieces for this shop & variant
            $salesRecords = Database::fetchAll(
                "SELECT si.id, si.sale_id, si.variant_id, si.quantity, si.scanned_qr_codes, s.invoice_number, s.customer_name, s.created_at
                 FROM sale_items si
                 JOIN sales s ON si.sale_id = s.id
                 WHERE si.variant_id = ? AND s.shop_id = ? AND s.sale_type = 'SHOP' AND s.status != 'CANCELLED'
                 ORDER BY si.id DESC",
                [$variantId, $shopId]
            );

            $soldTagsMap = [];
            $totalSoldQuantity = 0;
            foreach ($salesRecords as $sr) {
                $totalSoldQuantity += (int)($sr['quantity'] ?? 1);
                if (!empty($sr['scanned_qr_codes'])) {
                    $tags = array_map('trim', explode(',', $sr['scanned_qr_codes']));
                    foreach ($tags as $t) {
                        $tagKey = strtoupper($t);
                        if ($tagKey !== '') {
                            $soldTagsMap[$tagKey] = $sr;
                        }
                    }
                }
            }

            // Fetch returns for this shop & variant
            $returnedTagsMap = [];
            $returnRecords = Database::fetchAll(
                "SELECT ri.quantity, ri.sale_item_id, ri.item_condition, ri.reason as item_reason, r.id as return_id, r.return_number, r.return_type, r.reason, r.status, r.created_at,
                        s.invoice_number as sale_invoice, s.customer_name, si.scanned_qr_codes
                 FROM return_items ri
                 JOIN returns r ON ri.return_id = r.id
                 LEFT JOIN sales s ON r.sale_id = s.id
                 LEFT JOIN sale_items si ON ri.sale_item_id = si.id
                 WHERE ri.variant_id = ? AND r.source_type = 'SHOP' AND r.status != 'CANCELLED'
                 ORDER BY r.id DESC",
                [$variantId]
            );
            foreach ($returnRecords as $rr) {
                $rawTagString = '';
                if (!empty($rr['item_reason']) && str_contains($rr['item_reason'], 'Piece Tags:')) {
                    $rawTagString = str_replace('Piece Tags:', '', $rr['item_reason']);
                } elseif (!empty($rr['scanned_qr_codes'])) {
                    $rawTagString = $rr['scanned_qr_codes'];
                }
                if (!empty($rawTagString)) {
                    $tags = array_map('trim', explode(',', $rawTagString));
                    foreach ($tags as $t) {
                        $tagKey = strtoupper($t);
                        if ($tagKey !== '' && !str_starts_with($tagKey, 'TP-') && !str_starts_with($tagKey, 'SKU-')) {
                            $returnedTagsMap[$tagKey] = $rr;
                        }
                    }
                }
            }

            // Fetch exchanges for this shop & variant
            $exchangeDispatchedTagsMap = [];
            $exchangesHistory = Database::fetchAll(
                "SELECT ei.*, e.exchange_number, e.original_amount, e.new_amount, e.difference_amount, e.settlement_mode, e.payment_action, e.status as exchange_status, e.created_at as exchange_date,
                        s.invoice_number as sale_invoice, r.return_number,
                        COALESCE(s.customer_name, 'Retail Customer') as customer_name
                 FROM exchange_items ei
                 JOIN exchanges e ON ei.exchange_id = e.id
                 LEFT JOIN returns r ON e.return_id = r.id
                 LEFT JOIN sales s ON e.sale_id = s.id
                 WHERE ei.variant_id = ? AND e.source_type = 'SHOP' AND e.status != 'CANCELLED'
                 ORDER BY e.id DESC",
                [$variantId]
            );

            foreach ($exchangesHistory as $ex) {
                if (!empty($ex['piece_tag'])) {
                    $tags = array_map('trim', explode(',', $ex['piece_tag']));
                    foreach ($tags as $pt) {
                        $pt = strtoupper($pt);
                        if (empty($pt)) continue;
                        if ($ex['item_type'] === 'NEW') {
                            $exchangeDispatchedTagsMap[$pt] = $ex;
                        } elseif ($ex['item_type'] === 'OLD') {
                            $returnedTagsMap[$pt] = [
                                'exchange_id'     => $ex['exchange_id'] ?? $ex['id'],
                                'exchange_number' => $ex['exchange_number'],
                                'return_number'   => $ex['return_number'] ?? $ex['exchange_number'],
                                'item_condition'  => 'GOOD',
                                'reason'          => 'Product Exchange (Old Piece Returned & Restocked)',
                                'sale_invoice'    => $ex['sale_invoice'] ?? '-',
                                'customer_name'   => $ex['customer_name'] ?? 'Shop Customer',
                                'created_at'      => $ex['exchange_date'] ?? $ex['created_at'],
                            ];
                        }
                    }
                }
            }

            // Fetch warehouse return consignments
            $whReturnRecords = Database::fetchAll(
                "SELECT wri.returned_qty, wr.return_number, wr.reason, wr.status, wr.created_at
                 FROM warehouse_return_items wri
                 JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id
                 WHERE wri.variant_id = ? AND wr.source_type = 'SHOP' AND wr.source_id = ?
                 ORDER BY wr.id DESC",
                [$variantId, $shopId]
            );

            $pieces = [];
            foreach ($savedTags as $index => $tag) {
                $pieceNumber = $index + 1;
                $pieceAllocRef = $allocRef;
                foreach ($allocationBatches as $ab) {
                    if (in_array($tag, $ab['tags'], true)) {
                        $pieceAllocRef = $ab['allocation_number'];
                        break;
                    }
                }

                $isSold = false;
                $isDamaged = false;
                $isPartialDamage = false;
                $isReturned = false;
                $isExchanged = false;
                $isRestocked = false;
                $pieceStatus = 'In Stock';
                $saleInfo = null;
                $damageInfo = null;
                $returnInfo = null;
                $exchangeInfo = null;

                if ($isSingleQrProduct
                    && (float)($item['damaged_quantity'] ?? 0) > 0
                    && (float)($item['available_quantity'] ?? $item['quantity'] ?? 0) > 0
                ) {
                    // A Series/Complementary QR represents a stock pool, not one physical piece.
                    // Keep the QR partially damaged when both good and damaged quantities remain.
                    $isPartialDamage = true;
                    $pieceStatus = 'Partially Damaged';
                } elseif (isset($damagedTagsMap[$tag])) {
                    $isDamaged = true;
                    $damageInfo = $damagedTagsMap[$tag];
                    $pieceStatus = 'Damaged';
                } elseif (isset($returnedTagsMap[$tag])) {
                    // Piece was returned / restocked in Shop Return or Exchange
                    $rInfo = $returnedTagsMap[$tag];
                    if (($rInfo['item_condition'] ?? 'GOOD') === 'GOOD') {
                        $pieceStatus = 'In Stock';
                        $isRestocked = true;
                        $isReturned = false;
                        $isSold = false;
                        $returnInfo = $rInfo;
                    } else {
                        $isDamaged = true;
                        $isSold = false;
                        $pieceStatus = 'Damaged';
                        $damageInfo = [
                            'damage_number' => $rInfo['return_number'] ?? 'Return Defect',
                            'damage_reason' => 'Returned Damaged Stock',
                            'status' => 'DAMAGED'
                        ];
                    }
                } elseif (isset($exchangeDispatchedTagsMap[$tag])) {
                    // Piece was dispatched out as a replacement in an Exchange
                    $isSold = true;
                    $isExchanged = true;
                    $pieceStatus = 'Sold';
                    $exInfo = $exchangeDispatchedTagsMap[$tag];
                    $saleInfo = [
                        'invoice_number' => $exInfo['exchange_number'] . ' (Exchange)',
                        'sale_id' => $exInfo['sale_id'] ?? null,
                        'customer_name' => $exInfo['customer_name'] ?? 'Shop Customer',
                        'created_at' => $exInfo['exchange_date'] ?? $exInfo['created_at']
                    ];
                    $exchangeInfo = $exInfo;
                } elseif (isset($soldTagsMap[$tag])) {
                    $isSold = true;
                    $saleInfo = $soldTagsMap[$tag];
                    $pieceStatus = 'Sold';
                }

                $pieces[] = [
                    'piece_number'         => $pieceNumber,
                    'tag_code'             => $tag,
                    'qr_payload'           => "{$tag}|{$item['sku']}|MRP:{$item['selling_price']}",
                    'product_name'         => $item['product_name'],
                    'sku'                  => $item['sku'],
                    'product_code'         => $item['product_code'],
                    'product_image'        => $item['product_image'] ?: $item['variant_image'],
                    'category_name'        => $item['category_name'],
                    'subcategory_name'     => $item['subcategory_name'],
                    'sub_subcategory_name' => $item['sub_subcategory_name'],
                    'brand_name'           => $item['brand_name'],
                    'size_name'            => $item['size_name'] ?? 'Free',
                    'color_name'           => $item['color_name'] ?? 'Std',
                    'selling_price'        => (float)$item['selling_price'],
                    'mrp'                  => (float)$item['mrp'],
                    'status'               => $pieceStatus,
                    'is_sold'              => $isSold,
                    'is_damaged'           => $isDamaged,
                    'is_partial_damage'    => $isPartialDamage,
                    'is_returned'          => $isReturned,
                    'is_exchanged'         => $isExchanged,
                    'is_restocked'         => $isRestocked,
                    'invoice_number'       => $saleInfo['invoice_number'] ?? null,
                    'sale_id'              => $saleInfo['sale_id'] ?? null,
                    'sold_customer'        => $saleInfo['customer_name'] ?? null,
                    'sold_date'            => $saleInfo['created_at'] ?? null,
                    'damage_number'        => $damageInfo['damage_number'] ?? null,
                    'damage_reason'        => $damageInfo['damage_reason'] ?? null,
                    'damage_status'        => $damageInfo['status'] ?? null,
                    'return_number'        => $returnInfo['return_number'] ?? null,
                    'exchange_number'      => $exchangeInfo['return_number'] ?? null,
                    'inward_reference'     => $pieceAllocRef ? "Allocation #$pieceAllocRef" : 'Inward Consignment',
                ];
            }

            if (!$isSingleQrProduct) {
                $availableTagLimit = max(0, (int)floor((float)($item['available_quantity'] ?? $item['quantity'] ?? 0)));
                $availableTagIndexes = [];
                foreach ($pieces as $pieceIndex => $piece) {
                    if (in_array(strtoupper((string)$piece['status']), ['AVAILABLE', 'IN STOCK'], true)) {
                        $availableTagIndexes[] = $pieceIndex;
                    }
                }

                usort($availableTagIndexes, static function (int $left, int $right) use ($pieces): int {
                    return (int)!empty($pieces[$right]['is_restocked']) <=> (int)!empty($pieces[$left]['is_restocked'])
                        ?: $left <=> $right;
                });
                foreach (array_slice($availableTagIndexes, $availableTagLimit) as $pieceIndex) {
                    $pieces[$pieceIndex]['status'] = 'Out of Stock';
                }
            }

            $item['individual_pieces'] = $pieces;
            $item['inward_reference'] = $allocRef ? "Allocation #$allocRef" : '';
            $item['sold_quantity'] = $totalSoldQuantity;
            $item['sales'] = $salesRecords;
            $item['allocation_batches'] = $allocationBatches;
            $item['returns'] = $returnRecords;
            $item['exchanges'] = $exchangesHistory;
            $item['wh_returns'] = $whReturnRecords;
            $item['damages'] = $damageRecords;
        }

        return $item;
    }

    /**
     * Create Stock Indent Request to Warehouse.
     */
    public static function createStockIndent(int $shopId, array $items, string $notes, int $userId): string {
        if (empty($items)) {
            throw new InvalidArgumentException("Please select at least one item to request.");
        }

        Database::beginTransaction();
        try {
            $today = date('Ymd');
            $prefix = 'IND-' . $today . '-';
            $lastReq = Database::fetchOne(
                "SELECT allocation_number FROM warehouse_allocations WHERE allocation_number LIKE ? ORDER BY id DESC LIMIT 1",
                [$prefix . '%']
            );
            $seq = 1;
            if ($lastReq && !empty($lastReq['allocation_number'])) {
                $parts = explode('-', $lastReq['allocation_number']);
                $seq = (int)end($parts) + 1;
            }
            $indentNumber = sprintf('%s%04d', $prefix, $seq);

            $defaultWhId = (int)SettingsService::get('default_warehouse_id', 1);

            Database::execute(
                "INSERT INTO warehouse_allocations (allocation_number, warehouse_id, destination_type, destination_id, 
                                                   requested_by, status, notes, created_at)
                 VALUES (?, ?, 'SHOP', ?, ?, 'REQUESTED', ?, NOW())",
                [$indentNumber, $defaultWhId, $shopId, $userId, $notes]
            );
            $allocId = (int)Database::lastInsertId();

            foreach ($items as $variantId => $qty) {
                $qty = (int)$qty;
                if ($qty > 0) {
                    Database::execute(
                        "INSERT INTO allocation_items (allocation_id, variant_id, requested_qty, approved_qty)
                         VALUES (?, ?, ?, 0)",
                        [$allocId, (int)$variantId, $qty]
                    );
                }
            }

            AuditService::log('shop_inventory', 'CREATE', 'warehouse_allocations', $allocId, null, [
                'indent_number' => $indentNumber,
                'items_count'   => count($items),
            ]);

            Database::commit();
            return $indentNumber;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Update Product Selling Price & MRP from Shop Inventory.
     */
    public static function updateProductPrice(int $variantId, float $sellingPrice, ?float $mrp = null, int $userId = 1, string $notes = ''): array {
        if ($sellingPrice < 0) {
            throw new InvalidArgumentException("Selling price cannot be negative.");
        }
        
        $variant = Database::fetchOne(
            "SELECT pv.*, p.name as product_name, p.code as product_code 
             FROM product_variants pv 
             JOIN products p ON pv.product_id = p.id 
             WHERE pv.id = ?",
            [$variantId]
        );
        
        if (!$variant) {
            throw new Exception("Product variant #{$variantId} not found.");
        }

        $oldSellingPrice = (float)$variant['selling_price'];
        $oldMrp = (float)$variant['mrp'];

        if ($mrp === null || $mrp <= 0) {
            $mrp = $oldMrp > $sellingPrice ? $oldMrp : $sellingPrice;
        }

        // Never allow a discount price above the printed/base price.
        if ($mrp < $sellingPrice) {
            throw new InvalidArgumentException("Selling price cannot be greater than MRP/base price.");
        }

        Database::execute(
            "UPDATE product_variants 
             SET selling_price = ?, mrp = ?, updated_at = NOW() 
             WHERE id = ?",
            [$sellingPrice, $mrp, $variantId]
        );

        AuditService::log('SHOP', 'UPDATE_PRICE', 'product_variants', $variantId, [
            'selling_price' => $oldSellingPrice,
            'mrp'           => $oldMrp,
        ], [
            'selling_price' => $sellingPrice,
            'mrp'           => $mrp,
            'notes'         => $notes,
            'user_id'       => $userId,
            'sku'           => $variant['sku'],
            'product_name'  => $variant['product_name']
        ]);

        return [
            'variant_id'        => $variantId,
            'sku'               => $variant['sku'],
            'product_name'      => $variant['product_name'],
            'old_selling_price' => $oldSellingPrice,
            'old_mrp'           => $oldMrp,
            'new_selling_price' => $sellingPrice,
            'new_mrp'           => $mrp,
            'formatted_price'   => '₹' . number_format($sellingPrice, 2),
            'formatted_mrp'     => '₹' . number_format($mrp, 2),
        ];
    }

    /**
     * Bulk update product selling prices & MRP.
     */
    public static function updateProductPrices(array $variantIds, float $sellingPrice, ?float $mrp = null, int $userId = 1, string $notes = ''): array {
        $updated = [];
        foreach ($variantIds as $vId) {
            $vId = (int)$vId;
            if ($vId <= 0) continue;
            try {
                $updated[] = self::updateProductPrice($vId, $sellingPrice, $mrp, $userId, $notes);
            } catch (\Throwable $e) {}
        }
        return $updated;
    }

    /**
     * Look up Invoice for Returns / Exchanges.
     */
    public static function lookupInvoice(string $query, int $shopId): ?array {
        $query = trim($query);
        if (empty($query)) return null;

        if (str_starts_with($query, '{') && str_ends_with($query, '}')) {
            $json = json_decode($query, true);
            if (!empty($json['invoice_number'])) {
                $query = $json['invoice_number'];
            }
        }

        $sale = Database::fetchOne(
            "SELECT s.*, c.name as customer_name, c.phone as customer_phone
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.shop_id = ? AND (s.invoice_number = ? OR c.phone = ?)
             ORDER BY s.id DESC LIMIT 1",
            [$shopId, $query, $query]
        );

        if (!$sale) return null;

        $items = Database::fetchAll(
            "SELECT si.*, pv.sku, pv.qr_code, pv.product_id, pv.size_id, pv.color_id, pv.selling_price as variant_selling_price,
                    p.name as product_name, p.code as product_code, sz.name as size_name, cl.name as color_name
             FROM sale_items si
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             WHERE si.sale_id = ?",
            [$sale['id']]
        );

        foreach ($items as &$item) {
            $siblings = Database::fetchAll(
                "SELECT pv.id, pv.sku, pv.qr_code, pv.selling_price, pv.mrp,
                        sz.name as size_name, cl.name as color_name,
                        COALESCE((SELECT SUM(quantity) FROM stock_balances sb 
                                  WHERE sb.variant_id = pv.id 
                                    AND sb.location_type = 'SHOP' 
                                    AND sb.location_id = ? 
                                    AND sb.stock_state = 'AVAILABLE'), 0) as shop_stock
                 FROM product_variants pv
                 LEFT JOIN sizes sz ON pv.size_id = sz.id
                 LEFT JOIN colors cl ON pv.color_id = cl.id
                 WHERE pv.product_id = ? AND pv.is_active = 1
                 ORDER BY sz.name ASC, cl.name ASC",
                [$shopId, $item['product_id']]
            );
            $item['sibling_variants'] = $siblings;
        }

        return [
            'sale'  => $sale,
            'items' => $items,
        ];
    }

    /**
     * Process Customer Return / Exchange.
     * Supports:
     * - Direct Return & Refund (Cash, UPI, Credit Note)
     * - Same-Product Size/Color Exchange (In-place Stock Swap)
     * - New-Product POS Exchange (POS Credit Handover)
     * - Store Credit Note
     */
    public static function processReturn(array $payload, int $userId): array {
        $saleId = (int)$payload['sale_id'];
        $shopId = (int)$payload['shop_id'];
        $items = $payload['items'] ?? [];
        $actionType = $payload['action_type'] ?? 'RETURN'; // 'RETURN', 'EXCHANGE_SAME', 'EXCHANGE_DIFFERENT', 'CREDIT_NOTE'
        $reason = $payload['reason'] ?? 'Customer Return';
        $refundMode = $payload['refund_mode'] ?? 'CASH';

        if ($actionType === 'CREDIT_NOTE' || $actionType === 'EXCHANGE_DIFFERENT') {
            $refundMode = 'CREDIT_NOTE';
        }

        $returnType = in_array($actionType, ['EXCHANGE_SAME', 'EXCHANGE_DIFFERENT']) ? 'EXCHANGE' : 'RETURN';

        if (empty($items)) {
            throw new InvalidArgumentException("Please select items to return or exchange.");
        }

        Database::beginTransaction();
        try {
            $today = date('Ymd');
            $prefix = ($returnType === 'EXCHANGE' ? 'EXC-' : 'RET-') . $today . '-';
            $lastRet = Database::fetchOne(
                "SELECT return_number FROM returns WHERE return_number LIKE ? ORDER BY id DESC LIMIT 1",
                [$prefix . '%']
            );
            $seq = 1;
            if ($lastRet && !empty($lastRet['return_number'])) {
                $parts = explode('-', $lastRet['return_number']);
                $seq = (int)end($parts) + 1;
            }
            $returnNumber = sprintf('%s%04d', $prefix, $seq);

            $sale = Database::fetchOne(
                "SELECT s.*, c.name as customer_name, c.phone as customer_phone 
                 FROM sales s 
                 LEFT JOIN customers c ON s.customer_id = c.id 
                 WHERE s.id = ?",
                [$saleId]
            );
            $totalRefund = 0.0;
            $processedItems = [];

            foreach ($items as $it) {
                // If selected key exists and is empty/falsy, skip
                if (isset($it['selected']) && empty($it['selected'])) {
                    continue;
                }

                $qty = (int)($it['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $saleItemId = !empty($it['sale_item_id']) ? (int)$it['sale_item_id'] : null;
                $variantId = (int)($it['variant_id'] ?? 0);

                // Determine accurate unit rate from sale_items or product_variants
                $unitPrice = 0.0;
                if ($saleItemId) {
                    $si = Database::fetchOne("SELECT unit_price FROM sale_items WHERE id = ? AND sale_id = ?", [$saleItemId, $saleId]);
                    if ($si) {
                        $unitPrice = (float)$si['unit_price'];
                    }
                }
                if ($unitPrice <= 0 && !empty($it['unit_price'])) {
                    $unitPrice = (float)$it['unit_price'];
                }
                if ($unitPrice <= 0 && $variantId > 0) {
                    $pv = Database::fetchOne("SELECT selling_price FROM product_variants WHERE id = ?", [$variantId]);
                    $unitPrice = (float)($pv['selling_price'] ?? 0);
                }

                $itemRefund = $unitPrice * $qty;
                $totalRefund += $itemRefund;

                $replacementVariantId = !empty($it['replacement_variant_id']) ? (int)$it['replacement_variant_id'] : null;

                $processedItems[] = [
                    'sale_item_id'           => $saleItemId,
                    'variant_id'             => $variantId,
                    'quantity'               => $qty,
                    'condition'              => $it['condition'] ?? 'GOOD',
                    'unit_price'             => $unitPrice,
                    'refund_amount'          => $itemRefund,
                    'replacement_variant_id' => $replacementVariantId,
                ];
            }

            if (empty($processedItems)) {
                throw new InvalidArgumentException("Please select at least one item to process.");
            }

            // Notes summary
            $notes = "Action: " . $actionType;
            if ($actionType === 'EXCHANGE_SAME') {
                $notes .= " (Same-product size/color exchange)";
            } elseif ($actionType === 'EXCHANGE_DIFFERENT') {
                $notes .= " (Different-product exchange via POS)";
            }

            Database::execute(
                "INSERT INTO returns (return_number, sale_id, customer_id, return_type, reason, inspection_status, 
                                     refund_amount, refund_mode, refund_status, inspected_by, approved_by, notes, created_at)
                 VALUES (?, ?, ?, ?, ?, 'APPROVED', ?, ?, 'COMPLETED', ?, ?, ?, NOW())",
                [
                    $returnNumber,
                    $saleId,
                    $sale['customer_id'] ?? null,
                    $returnType,
                    $reason,
                    $totalRefund,
                    $refundMode,
                    $userId,
                    $userId,
                    $notes,
                ]
            );
            $returnId = (int)Database::lastInsertId();

            $totalNewExchangeAmount = 0.0;
            $hasSameProductExchange = false;

            foreach ($processedItems as $it) {
                $variantId = (int)$it['variant_id'];
                $qty = (int)$it['quantity'];
                $condition = $it['condition'] ?? 'GOOD'; // GOOD or DAMAGED

                Database::execute(
                    "INSERT INTO return_items (return_id, sale_item_id, variant_id, quantity, item_condition, destination)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$returnId, $it['sale_item_id'], $variantId, $qty, $condition, $condition === 'GOOD' ? 'AVAILABLE' : 'DAMAGE']
                );

                // If GOOD condition, restore stock into AVAILABLE shop inventory
                if ($condition === 'GOOD') {
                    StockService::increase(
                        $variantId,
                        'SHOP',
                        $shopId,
                        'AVAILABLE',
                        (float)$qty,
                        'RETURN_GOOD',
                        'returns',
                        $returnId,
                        $userId,
                        "Return Restock: $returnNumber"
                    );
                } else {
                    // Route to DAMAGED state
                    StockService::increase(
                        $variantId,
                        'SHOP',
                        $shopId,
                        'DAMAGED',
                        (float)$qty,
                        'RETURN_DAMAGED',
                        'returns',
                        $returnId,
                        $userId,
                        "Damaged Return: $returnNumber"
                    );
                }

                // If Same-Product Exchange: deduct replacement variant stock
                if ($actionType === 'EXCHANGE_SAME' && !empty($it['replacement_variant_id'])) {
                    $hasSameProductExchange = true;
                    $repVarId = (int)$it['replacement_variant_id'];
                    $repVar = Database::fetchOne("SELECT selling_price FROM product_variants WHERE id = ?", [$repVarId]);
                    $repPrice = (float)($repVar['selling_price'] ?? $it['unit_price']);
                    $repTotal = $repPrice * $qty;
                    $totalNewExchangeAmount += $repTotal;

                    // Deduct replacement stock from shop inventory
                    StockService::decrease(
                        $repVarId,
                        'SHOP',
                        $shopId,
                        'AVAILABLE',
                        (float)$qty,
                        'EXCHANGE_OUT',
                        'returns',
                        $returnId,
                        $userId,
                        "Exchange Replacement for: $returnNumber"
                    );
                }
            }

            // If Different Product Exchange with in-place selected new items:
            $newItems = $payload['new_items'] ?? [];
            $hasDifferentProductExchange = false;

            if ($actionType === 'EXCHANGE_DIFFERENT' && !empty($newItems)) {
                foreach ($newItems as $nItem) {
                    $nVarId = (int)($nItem['variant_id'] ?? 0);
                    $nQty = (int)($nItem['quantity'] ?? 0);
                    if ($nVarId <= 0 || $nQty <= 0) continue;

                    $hasDifferentProductExchange = true;
                    $nVar = Database::fetchOne("SELECT pv.*, p.name as product_name FROM product_variants pv JOIN products p ON pv.product_id = p.id WHERE pv.id = ?", [$nVarId]);
                    $nPrice = (float)($nVar['selling_price'] ?? 0);
                    $nLineTotal = $nPrice * $nQty;
                    $totalNewExchangeAmount += $nLineTotal;

                    // Deduct replacement stock from shop inventory
                    StockService::decrease(
                        $nVarId,
                        'SHOP',
                        $shopId,
                        'AVAILABLE',
                        (float)$nQty,
                        'EXCHANGE_OUT',
                        'returns',
                        $returnId,
                        $userId,
                        "Product Exchange Replacement for: $returnNumber"
                    );
                }
            }

            // If Exchange with replacement items (Same or Different Product): create entry in `exchanges` and `exchange_items`
            if (($actionType === 'EXCHANGE_SAME' && $hasSameProductExchange) || ($actionType === 'EXCHANGE_DIFFERENT' && $hasDifferentProductExchange)) {
                $diffAmount = $totalNewExchangeAmount - $totalRefund;
                $paymentAction = 'NONE';
                if ($diffAmount > 0.01) {
                    $paymentAction = 'COLLECT';
                } elseif ($diffAmount < -0.01) {
                    $paymentAction = 'REFUND';
                }

                Database::execute(
                    "INSERT INTO exchanges (return_id, sale_id, original_amount, new_amount, difference_amount, payment_action, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, 'COMPLETED', NOW())",
                    [$returnId, $saleId, $totalRefund, $totalNewExchangeAmount, abs($diffAmount), $paymentAction]
                );
                $exchangeId = (int)Database::lastInsertId();

                // Record OLD returned variants
                foreach ($processedItems as $it) {
                    Database::execute(
                        "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount)
                         VALUES (?, 'OLD', ?, ?, ?, ?)",
                        [$exchangeId, $it['variant_id'], $it['quantity'], $it['unit_price'], $it['refund_amount']]
                    );
                }

                // Record NEW variants for Same Product
                if ($actionType === 'EXCHANGE_SAME') {
                    foreach ($processedItems as $it) {
                        $repVarId = !empty($it['replacement_variant_id']) ? (int)$it['replacement_variant_id'] : null;
                        if ($repVarId) {
                            $repVar = Database::fetchOne("SELECT selling_price FROM product_variants WHERE id = ?", [$repVarId]);
                            $repPrice = (float)($repVar['selling_price'] ?? $it['unit_price']);
                            Database::execute(
                                "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount)
                                 VALUES (?, 'NEW', ?, ?, ?, ?)",
                                [$exchangeId, $repVarId, $it['quantity'], $repPrice, $repPrice * $it['quantity']]
                            );
                        }
                    }
                }

                // Record NEW variants for Different Product
                if ($actionType === 'EXCHANGE_DIFFERENT' && !empty($newItems)) {
                    foreach ($newItems as $nItem) {
                        $nVarId = (int)($nItem['variant_id'] ?? 0);
                        $nQty = (int)($nItem['quantity'] ?? 0);
                        if ($nVarId <= 0 || $nQty <= 0) continue;
                        $nVar = Database::fetchOne("SELECT selling_price FROM product_variants WHERE id = ?", [$nVarId]);
                        $nPrice = (float)($nVar['selling_price'] ?? 0);
                        Database::execute(
                            "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount)
                             VALUES (?, 'NEW', ?, ?, ?, ?)",
                            [$exchangeId, $nVarId, $nQty, $nPrice, $nPrice * $nQty]
                        );
                    }
                }
            }

            // If cash refund issued, log in cash flow ledger (deducts from active till)
            if ($refundMode === 'CASH' && $totalRefund > 0) {
                $activeReg = CashService::getActiveRegister($shopId);
                CashService::logTransaction([
                    'shop_id'          => $shopId,
                    'register_id'      => $activeReg ? (int)$activeReg['id'] : null,
                    'transaction_type' => 'REFUND_CASH',
                    'flow_direction'   => 'OUT',
                    'amount'           => $totalRefund,
                    'payment_mode'     => 'CASH',
                    'category'         => 'RETURN_REFUND',
                    'reference_id'     => $returnNumber,
                    'source_table'     => 'returns',
                    'source_id'        => $returnId,
                    'handled_by'       => $userId,
                    'description'      => "Cash Refund for Return: {$returnNumber} (Invoice: " . ($sale['invoice_number'] ?? '') . ")"
                ]);

                if ($activeReg) {
                    CashService::getActiveRegister($shopId);
                }
            }

            AuditService::log('shop_returns', 'CREATE', 'returns', $returnId, null, [
                'return_number' => $returnNumber,
                'return_type'   => $returnType,
                'action_type'   => $actionType,
                'refund_amount' => $totalRefund,
            ]);

            Database::commit();

            return [
                'return_number'   => $returnNumber,
                'return_id'       => $returnId,
                'return_type'     => $returnType,
                'action_type'     => $actionType,
                'refund_amount'   => $totalRefund,
                'customer_name'   => $sale['customer_name'] ?? '',
                'customer_phone'  => $sale['customer_phone'] ?? '',
                'sale_id'         => $saleId,
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Get Complete Details of a Customer Return and its Returned Items.
     */
    public static function getReturnDetails(int $returnId): ?array {
        $return = Database::fetchOne(
            "SELECT r.*, s.invoice_number, s.created_at as sale_date, s.payment_mode as sale_payment_mode,
                    s.subtotal as sale_subtotal, s.total_amount as sale_total_amount,
                    c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address,
                    u.full_name as staff_name, sh.name as shop_name, sh.address as shop_address, sh.phone as shop_phone
             FROM returns r
             JOIN sales s ON r.sale_id = s.id
             LEFT JOIN customers c ON r.customer_id = c.id
             LEFT JOIN users u ON r.inspected_by = u.id
             LEFT JOIN shops sh ON s.shop_id = sh.id
             WHERE r.id = ? OR r.return_number = ?",
            [$returnId, (string)$returnId]
        );

        if (!$return) return null;

        $items = Database::fetchAll(
            "SELECT ri.*, si.unit_price, si.total_amount as original_line_amount, si.scanned_qr_codes,
                    pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.image_url as variant_image,
                    p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                    p.category_code, p.subcategory_code, p.hsn_code,
                    sz.name as size_name, cl.name as color_name, cat.name as category_name
             FROM return_items ri
             LEFT JOIN sale_items si ON ri.sale_item_id = si.id
             JOIN product_variants pv ON ri.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             WHERE ri.return_id = ?",
            [$return['id']]
        );

        $totalReturnedQty = 0;
        $calculatedGrandRefund = 0.0;
        foreach ($items as &$it) {
            $totalReturnedQty += (int)$it['quantity'];
            $it['image'] = !empty($it['variant_image']) ? $it['variant_image'] : ($it['product_image'] ?? null);
            $it['unit_price'] = (float)($it['unit_price'] ?? ($it['selling_price'] ?? 0));
            $it['calculated_refund'] = $it['unit_price'] * (int)$it['quantity'];
            $calculatedGrandRefund += $it['calculated_refund'];
        }

        // Synchronize and auto-repair refund_amount if stored as 0
        if (((float)($return['refund_amount'] ?? 0)) <= 0 && $calculatedGrandRefund > 0) {
            $return['refund_amount'] = $calculatedGrandRefund;
            Database::execute("UPDATE returns SET refund_amount = ? WHERE id = ?", [$calculatedGrandRefund, $return['id']]);
        }

        // Fetch associated exchange details if any
        $exchange = Database::fetchOne(
            "SELECT e.*, 
                    (SELECT COUNT(*) FROM exchange_items WHERE exchange_id = e.id) as item_count
             FROM exchanges e 
             WHERE e.return_id = ?",
            [$return['id']]
        );
        $exchangeItems = [];
        if ($exchange) {
            $exchangeItems = Database::fetchAll(
                "SELECT ei.*, pv.sku, p.name as product_name, sz.name as size_name, cl.name as color_name
                 FROM exchange_items ei
                 JOIN product_variants pv ON ei.variant_id = pv.id
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN sizes sz ON pv.size_id = sz.id
                 LEFT JOIN colors cl ON pv.color_id = cl.id
                 WHERE ei.exchange_id = ?",
                [$exchange['id']]
            );
        }

        return [
            'return'           => $return,
            'items'            => $items,
            'exchange'         => $exchange,
            'exchangeItems'    => $exchangeItems,
            'totalReturnedQty' => $totalReturnedQty,
        ];
    }

    /**
     * Get Comprehensive Summary KPI Metrics for Customer Returns & Exchanges at a Shop.
     */
    public static function getReturnsSummary(int $shopId): array {
        $today = date('Y-m-d');

        // Aggregated transaction totals
        $stats = Database::fetchOne(
            "SELECT 
                COUNT(r.id) as total_transactions,
                SUM(CASE WHEN DATE(r.created_at) = ? THEN 1 ELSE 0 END) as today_transactions,
                
                -- Returns & Direct Refunds
                SUM(CASE WHEN r.return_type != 'EXCHANGE' AND r.refund_mode != 'CREDIT_NOTE' THEN 1 ELSE 0 END) as total_returns_count,
                COALESCE(SUM(CASE WHEN r.return_type != 'EXCHANGE' AND r.refund_mode != 'CREDIT_NOTE' THEN r.refund_amount ELSE 0 END), 0) as total_refund_amount,
                
                -- Exchanges
                SUM(CASE WHEN r.return_type = 'EXCHANGE' THEN 1 ELSE 0 END) as total_exchanges_count,
                COALESCE(SUM(CASE WHEN r.return_type = 'EXCHANGE' THEN r.refund_amount ELSE 0 END), 0) as total_exchanges_value,
                
                -- Store Credit Notes
                SUM(CASE WHEN r.refund_mode = 'CREDIT_NOTE' THEN 1 ELSE 0 END) as total_credit_notes_count,
                COALESCE(SUM(CASE WHEN r.refund_mode = 'CREDIT_NOTE' THEN r.refund_amount ELSE 0 END), 0) as total_credit_notes_amount
             FROM returns r
             JOIN sales s ON r.sale_id = s.id
             WHERE s.shop_id = ?",
            [$today, $shopId]
        );

        // Item level condition breakdown (Restocked Good vs Damaged)
        $itemStats = Database::fetchOne(
            "SELECT 
                COALESCE(SUM(ri.quantity), 0) as total_items_returned,
                COALESCE(SUM(CASE WHEN ri.item_condition = 'GOOD' THEN ri.quantity ELSE 0 END), 0) as restocked_good_qty,
                COALESCE(SUM(CASE WHEN ri.item_condition != 'GOOD' THEN ri.quantity ELSE 0 END), 0) as restocked_damaged_qty
             FROM return_items ri
             JOIN returns r ON ri.return_id = r.id
             JOIN sales s ON r.sale_id = s.id
             WHERE s.shop_id = ?",
            [$shopId]
        );

        $totalPcs = (int)($itemStats['total_items_returned'] ?? 0);
        $goodPcs = (int)($itemStats['restocked_good_qty'] ?? 0);
        $damagedPcs = (int)($itemStats['restocked_damaged_qty'] ?? 0);
        $goodRate = $totalPcs > 0 ? round(($goodPcs / $totalPcs) * 100) : 100;

        return [
            'total_transactions'        => (int)($stats['total_transactions'] ?? 0),
            'today_transactions'        => (int)($stats['today_transactions'] ?? 0),
            'total_returns_count'       => (int)($stats['total_returns_count'] ?? 0),
            'total_refund_amount'       => (float)($stats['total_refund_amount'] ?? 0),
            'total_exchanges_count'     => (int)($stats['total_exchanges_count'] ?? 0),
            'total_exchanges_value'     => (float)($stats['total_exchanges_value'] ?? 0),
            'total_credit_notes_count'  => (int)($stats['total_credit_notes_count'] ?? 0),
            'total_credit_notes_amount' => (float)($stats['total_credit_notes_amount'] ?? 0),
            'total_items_returned'      => $totalPcs,
            'restocked_good_qty'        => $goodPcs,
            'restocked_damaged_qty'     => $damagedPcs,
            'restock_good_rate'         => $goodRate,
        ];
    }


    /**
     * Get complete details of an allocation inward consignment for shop receiving.
     */
    public static function getInwardAllocationDetails(int $allocationId): ?array {
        $allocation = WarehouseAllocation::find($allocationId);
        if (!$allocation) {
            return null;
        }

        // Outward Gate Pass details
        $outward = Database::fetchOne(
            "SELECT * FROM allocation_outwards WHERE allocation_id = ?",
            [$allocationId]
        );

        // Shop details
        $shopId = (int)($outward['shop_id'] ?? 0);
        $destinationType = strtoupper(trim((string)($outward['destination_type'] ?? '')));
        $destinationId = (int)($outward['destination_id'] ?? 0);
        if ($shopId <= 0 && $destinationType === 'SHOP') {
            $shopId = $destinationId;
        } elseif ($shopId <= 0 && str_starts_with($destinationType, 'SHOP_')) {
            $shopId = (int)substr($destinationType, 5);
        }
        if ($shopId <= 0) {
            $shopId = (int)($allocation['shop_id'] ?? 0);
            $destinationType = strtoupper(trim((string)($allocation['destination_type'] ?? '')));
            $destinationId = (int)($allocation['destination_id'] ?? 0);
            if ($shopId <= 0 && $destinationType === 'SHOP') {
                $shopId = $destinationId;
            } elseif ($shopId <= 0 && str_starts_with($destinationType, 'SHOP_')) {
                $shopId = (int)substr($destinationType, 5);
            }
        }
        if ($shopId <= 0) {
            throw new RuntimeException('The allocation does not reference a shop in the active concern.');
        }
        $shop = Shop::find($shopId);
        if (!$shop) {
            throw new RuntimeException('The allocation shop is not available in the active concern.');
        }

        // Warehouse details
        $warehouse = Warehouse::find((int)$allocation['warehouse_id']);
        if (!$warehouse) {
            throw new RuntimeException('The allocation warehouse is not available in the active concern.');
        }

        // Enhanced Item breakdown with variants, product codes, sizes, colors, and prices
        $rawItems = Database::fetchAll(
            "SELECT ai.*, pv.sku, pv.qr_code, pv.mrp, pv.selling_price,
                    pv.qr_type, pv.pack_quantity, pv.series_start_code, pv.series_end_code,
                    COALESCE(pv.is_complementary, p.is_complementary, 0) as is_complementary,
                    COALESCE(wc.category_type, 'NORMAL') as category_type,
                    p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                    c.name as category_name, s.name as size_name, col.name as color_name, col.hex_code as color_hex
             FROM allocation_items ai
             JOIN product_variants pv ON ai.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors col ON pv.color_id = col.id
             WHERE ai.allocation_id = ?
             ORDER BY p.name ASC, pv.sku ASC",
            [$allocationId]
        );

        $parseQrTags = static function ($rawQrStr): array {
            $rawQrStr = trim((string)$rawQrStr);
            if ($rawQrStr === '') {
                return [];
            }

            if (str_starts_with($rawQrStr, '[') && str_ends_with($rawQrStr, ']')) {
                $jsonTags = json_decode($rawQrStr, true);
                $tags = [];
                if (is_array($jsonTags)) {
                    foreach ($jsonTags as $jsonTag) {
                        $tags[] = is_array($jsonTag)
                            ? ($jsonTag['tag_code'] ?? ($jsonTag['qr_code'] ?? ''))
                            : (string)$jsonTag;
                    }
                }
            } else {
                $tags = explode(',', $rawQrStr);
            }

            $uniqueTags = [];
            foreach ($tags as $tag) {
                $tag = strtoupper(trim((string)$tag));
                if ($tag !== '') {
                    $uniqueTags[$tag] = $tag;
                }
            }
            return array_values($uniqueTags);
        };

        // Consolidate duplicate variant lines if any
        $consolidatedItems = [];
        foreach ($rawItems as $item) {
            $vid = (int)$item['variant_id'];
            if (isset($consolidatedItems[$vid])) {
                $consolidatedItems[$vid]['requested_qty'] += (int)$item['requested_qty'];
                $consolidatedItems[$vid]['approved_qty'] += (int)$item['approved_qty'];
                $consolidatedItems[$vid]['dispatched_qty'] += (int)$item['dispatched_qty'];
                $consolidatedItems[$vid]['picked_qty'] = (int)($consolidatedItems[$vid]['picked_qty'] ?? 0) + (int)($item['picked_qty'] ?? 0);
                $consolidatedItems[$vid]['packed_qty'] = (int)($consolidatedItems[$vid]['packed_qty'] ?? 0) + (int)($item['packed_qty'] ?? 0);
                $tagLists = array_merge(
                    $parseQrTags($consolidatedItems[$vid]['selected_qr_codes'] ?? ''),
                    $parseQrTags($item['selected_qr_codes'] ?? '')
                );
                $consolidatedItems[$vid]['selected_qr_codes'] = implode(',', array_values(array_unique($tagLists)));
            } else {
                $consolidatedItems[$vid] = $item;
            }
        }
        $items = array_values($consolidatedItems);

        foreach ($items as &$item) {
            $isSeries = (
                ($item['qr_type'] ?? '') === 'SERIES_BATCH' ||
                strtoupper($item['category_type'] ?? '') === 'SERIES' ||
                (int)($item['pack_quantity'] ?? 0) > 1 ||
                !empty($item['is_complementary'])
            );
            $item['is_series'] = $isSeries;

            $parsedTags = $parseQrTags($item['selected_qr_codes'] ?? '');
            $item['saved_tags'] = $parsedTags;

            if ($isSeries) {
                $seriesQr = !empty($parsedTags[0]) ? $parsedTags[0] : (!empty($item['series_start_code']) ? $item['series_start_code'] : (!empty($item['qr_code']) ? $item['qr_code'] : $item['sku']));
                $item['series_qr_code'] = strtoupper($seriesQr);
            } elseif (
                !empty($parsedTags) &&
                (int)($item['received_qty'] ?? 0) === 0 &&
                (int)($item['damaged_qty'] ?? 0) === 0
            ) {
                // Older inward receipts may have replaced outward tags with only received tags.
                $outwardQty = (int)$item['dispatched_qty'];
                $quantityLimit = $outwardQty > 0 ? $outwardQty : (int)$item['requested_qty'];
                $item['outward_dispatched_qty'] = $outwardQty;
                $item['dispatched_qty'] = min($quantityLimit, count($parsedTags));
                $item['saved_tags'] = array_slice($parsedTags, 0, $item['dispatched_qty']);
            }
        }
        unset($item);

        // Parse piece-level QR codes (for series product, all units share the single series QR code)
        $pieceQRs = [];
        $pieceCounter = 1;
        foreach ($items as $item) {
            $isSeries = !empty($item['is_series']);
            $allocatedQty = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : $item['requested_qty']);

            if ($isSeries) {
                // A series product has ONLY ONE QR code representing the entire allocated quantity
                $singleTag = !empty($item['series_qr_code']) ? $item['series_qr_code'] : (!empty($item['saved_tags'][0]) ? $item['saved_tags'][0] : $item['sku']);
                for ($p = 1; $p <= $allocatedQty; $p++) {
                    $pieceQRs[] = [
                        'piece_no'      => $pieceCounter++,
                        'tag_code'      => $singleTag,
                        'variant_id'    => $item['variant_id'],
                        'sku'           => $item['sku'],
                        'product_name'  => $item['product_name'],
                        'size_name'     => $item['size_name'] ?? 'Free',
                        'color_name'    => $item['color_name'] ?? 'Std',
                        'selling_price' => (float)($item['selling_price'] ?? 0),
                        'is_series'     => true,
                        'series_qr'     => $singleTag,
                        'pack_quantity' => $allocatedQty,
                    ];
                }
            } else {
                if (!empty($item['saved_tags'])) {
                    foreach (array_slice($item['saved_tags'], 0, $allocatedQty) as $tag) {
                        $pieceQRs[] = [
                            'piece_no'      => $pieceCounter++,
                            'tag_code'      => $tag,
                            'variant_id'    => $item['variant_id'],
                            'sku'           => $item['sku'],
                            'product_name'  => $item['product_name'],
                            'size_name'     => $item['size_name'] ?? 'Free',
                            'color_name'    => $item['color_name'] ?? 'Std',
                            'selling_price' => (float)($item['selling_price'] ?? 0),
                            'is_series'     => false,
                        ];
                    }
                }
            }
        }

        return [
            'allocation' => $allocation,
            'outward'    => $outward,
            'shop'       => $shop,
            'warehouse'  => $warehouse,
            'items'      => $items,
            'pieceQRs'   => $pieceQRs,
        ];
    }

    /**
     * Process real-time QR / SKU scan during Shop Inward Cross-Checking.
     */
    public static function processShopCrossCheckScan(int $allocationId, string $scannedCode, array $scannedMap = []): array {
        $details = self::getInwardAllocationDetails($allocationId);
        if (!$details) {
            throw new Exception("Stock Allocation Pass #$allocationId not found.");
        }

        $allocation = $details['allocation'];
        $items = $details['items'];
        $scannedCode = trim($scannedCode);

        // Check if master consignment QR or outward pass QR scanned
        $isMasterCarton = (
            strcasecmp($scannedCode, $allocation['allocation_number']) === 0 ||
            (!empty($details['outward']) && strcasecmp($scannedCode, $details['outward']['outward_number']) === 0)
        );

        $matchedVariantId = null;
        $matchedItem = null;

        if ($isMasterCarton) {
            // Master QR scanned: bulk populate all variants to expected quantities
            foreach ($items as $item) {
                $vid = (int)$item['variant_id'];
                $expected = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : $item['requested_qty']);
                $scannedMap[$vid] = $expected;
            }
        } else {
            // Check piece QR tags or SKU variants
            foreach ($items as $item) {
                if (
                    strcasecmp($item['sku'], $scannedCode) === 0 ||
                    (!empty($item['qr_code']) && strcasecmp($item['qr_code'], $scannedCode) === 0) ||
                    (!empty($item['series_qr_code']) && strcasecmp($item['series_qr_code'], $scannedCode) === 0) ||
                    (!empty($item['saved_tags']) && in_array(strtoupper($scannedCode), array_map('strtoupper', $item['saved_tags']))) ||
                    (string)$item['variant_id'] === $scannedCode ||
                    str_contains($scannedCode, $item['sku'])
                ) {
                    $matchedVariantId = (int)$item['variant_id'];
                    $matchedItem = $item;
                    break;
                }
            }

            // Also check individual piece tags list
            if (!$matchedVariantId && !empty($details['pieceQRs'])) {
                foreach ($details['pieceQRs'] as $pqr) {
                    if (strcasecmp($pqr['tag_code'], $scannedCode) === 0) {
                        $matchedVariantId = (int)$pqr['variant_id'];
                        foreach ($items as $it) {
                            if ((int)$it['variant_id'] === $matchedVariantId) {
                                $matchedItem = $it;
                                break;
                            }
                        }
                        break;
                    }
                }
            }

            if (!$matchedVariantId) {
                return [
                    'success'       => false,
                    'message'       => "Scanned code '$scannedCode' does not match any product in Inward Pass #{$allocation['allocation_number']}.",
                    'matched_item'  => null,
                    'scanned_map'   => $scannedMap,
                ];
            }

            $currentCount = (int)($scannedMap[$matchedVariantId] ?? 0);
            if (!empty($matchedItem['is_series'])) {
                $expQty = (int)($matchedItem['dispatched_qty'] > 0 ? $matchedItem['dispatched_qty'] : $matchedItem['requested_qty']);
                $scannedMap[$matchedVariantId] = $expQty;
            } else {
                $scannedMap[$matchedVariantId] = $currentCount + 1;
            }
        }

        // Build comparison breakdown and shortage statistics
        $totalExpected = 0;
        $totalScanned = 0;
        $comparison = [];
        $missingItems = [];

        foreach ($items as $item) {
            $vid = (int)$item['variant_id'];
            $expected = (int)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : $item['requested_qty']);
            $scanned = (int)($scannedMap[$vid] ?? 0);
            $diff = $scanned - $expected;

            $totalExpected += $expected;
            $totalScanned += $scanned;

            $status = 'MATCHED';
            if ($diff < 0) {
                $status = 'SHORTAGE';
                $missingItems[] = [
                    'variant_id'   => $vid,
                    'sku'          => $item['sku'],
                    'product_name' => $item['product_name'],
                    'size_name'    => $item['size_name'] ?? 'Free Size',
                    'color_name'   => $item['color_name'] ?? 'Std',
                    'expected_qty' => $expected,
                    'scanned_qty'  => $scanned,
                    'missing_qty'  => abs($diff),
                ];
            } elseif ($diff > 0) {
                $status = 'EXCESS';
            }

            $comparison[] = [
                'variant_id'   => $vid,
                'sku'          => $item['sku'],
                'product_name' => $item['product_name'],
                'size_name'    => $item['size_name'] ?? 'Free Size',
                'color_name'   => $item['color_name'] ?? 'Std',
                'color_hex'    => $item['color_hex'] ?? '#6c757d',
                'selling_price'=> (float)($item['selling_price'] ?? 0),
                'expected_qty' => $expected,
                'scanned_qty'  => $scanned,
                'diff'         => $diff,
                'status'       => $status,
            ];
        }

        $allMatched = ($totalExpected > 0 && $totalScanned === $totalExpected && empty($missingItems));
        $progressPct = $totalExpected > 0 ? min(100, round(($totalScanned / $totalExpected) * 100)) : 100;

        return [
            'success'          => true,
            'is_master'        => $isMasterCarton,
            'message'          => $isMasterCarton 
                ? "Master Consignment QR verified! All $totalExpected pieces loaded." 
                : "Scanned {$matchedItem['sku']} ({$matchedItem['size_name']} - {$matchedItem['color_name']}). Scanned: {$scannedMap[$matchedVariantId]}/{$expected}",
            'matched_item'     => $matchedItem,
            'scanned_map'      => $scannedMap,
            'total_expected'   => $totalExpected,
            'total_scanned'    => $totalScanned,
            'progress_percent' => $progressPct,
            'all_matched'      => $allMatched,
            'missing_items'    => $missingItems,
            'comparison'       => $comparison,
        ];
    }

    /**
     * Complete receipt of a shop allocation inward consignment.
     */
    public static function receiveShopAllocation(int $allocationId, int $shopId, int $userId, string $notes = '', ?array $receivedItems = null): void {
        $details = self::getInwardAllocationDetails($allocationId);
        if (!$details) {
            throw new Exception("Allocation Pass #$allocationId not found.");
        }

        $allocation = $details['allocation'];
        $items = $details['items'];

        Database::beginTransaction();
        try {
            $totalExpected = 0;
            $totalAccounted = 0;
            $totalAccepted = 0;
            $totalDamaged = 0;
            $totalMissing = 0;

            // 1. Process each item variant
            foreach ($items as $item) {
                $vid = (int)$item['variant_id'];
                $dispatchedQty = (float)($item['dispatched_qty'] > 0 ? $item['dispatched_qty'] : ($item['requested_qty'] > 0 ? $item['requested_qty'] : 1));
                $totalExpected += $dispatchedQty;

                $prevRecv = (float)($item['received_qty'] ?? 0);
                $prevDam  = (float)($item['damaged_qty'] ?? 0);
                $prevAccepted = max(0.0, $prevRecv - $prevDam);

                if ($receivedItems !== null && isset($receivedItems[$vid])) {
                    $recv = max(0.0, (float)($receivedItems[$vid]['received_qty'] ?? 0));
                    $dam  = max(0.0, (float)($receivedItems[$vid]['damaged_qty'] ?? 0));
                    if ($recv > $dispatchedQty) {
                        throw new Exception("Received quantity for {$item['sku']} exceeds the dispatched quantity.");
                    }
                    if ($dam > $recv) {
                        throw new Exception("Damaged quantity for {$item['sku']} cannot exceed its received quantity.");
                    }
                    $itemNotes = trim($receivedItems[$vid]['notes'] ?? '');
                    $scannedTagsList = !empty($receivedItems[$vid]['scanned_tags']) ? (is_array($receivedItems[$vid]['scanned_tags']) ? $receivedItems[$vid]['scanned_tags'] : array_filter(array_map('trim', explode(',', (string)$receivedItems[$vid]['scanned_tags'])))) : [];
                    $damagedTagsList = !empty($receivedItems[$vid]['damage_tags']) ? (is_array($receivedItems[$vid]['damage_tags']) ? $receivedItems[$vid]['damage_tags'] : array_filter(array_map('trim', explode(',', (string)$receivedItems[$vid]['damage_tags'])))) : [];
                } else {
                    // Default to 100% full inward
                    $recv = $dispatchedQty;
                    $dam = 0.0;
                    $itemNotes = '';
                    $scannedTagsList = [];
                    $damagedTagsList = [];
                }

                $receivedTagMap = [];
                $existingReceivedTags = json_decode((string)($item['received_qr_codes'] ?? ''), true);
                if (is_array($existingReceivedTags)) {
                    foreach ($existingReceivedTags as $existingTag) {
                        $existingTag = strtoupper(trim((string)$existingTag));
                        if ($existingTag !== '') {
                            $receivedTagMap[$existingTag] = $existingTag;
                        }
                    }
                }
                foreach (array_merge($scannedTagsList, $damagedTagsList) as $receivedTag) {
                    $receivedTag = strtoupper(trim((string)$receivedTag));
                    if ($receivedTag !== '') {
                        $receivedTagMap[$receivedTag] = $receivedTag;
                    }
                }
                if (!$receivedTagMap && $recv > 0) {
                    $allocatedTags = $item['saved_tags'] ?? [];
                    if (!$allocatedTags && !empty($item['selected_qr_codes'])) {
                        $allocatedTags = json_decode((string)$item['selected_qr_codes'], true);
                        if (!is_array($allocatedTags)) {
                            $allocatedTags = explode(',', (string)$item['selected_qr_codes']);
                        }
                    }
                    foreach (array_slice((array)$allocatedTags, 0, (int)$recv) as $allocatedTag) {
                        $allocatedTag = is_array($allocatedTag)
                            ? ($allocatedTag['tag_code'] ?? ($allocatedTag['qr_code'] ?? ''))
                            : (string)$allocatedTag;
                        $allocatedTag = strtoupper(trim($allocatedTag));
                        if ($allocatedTag !== '') {
                            $receivedTagMap[$allocatedTag] = $allocatedTag;
                        }
                    }
                }

                $accepted = max(0.0, $recv - $dam);
                $missing = max(0.0, $dispatchedQty - $recv);

                $totalAccounted += $recv;
                $totalAccepted += $accepted;
                $totalDamaged += $dam;
                $totalMissing += $missing;

                // Persist variant-level received_qty, damaged_qty, missing_qty in allocation_items
                Database::execute(
                    "UPDATE allocation_items 
                     SET received_qty = ?, damaged_qty = ?, missing_qty = ?, received_qr_codes = ?
                     WHERE allocation_id = ? AND variant_id = ?",
                    [(int)$recv, (int)$dam, (int)$missing, json_encode(array_values($receivedTagMap)), $allocationId, $vid]
                );

                // Incremental deltas for AVAILABLE shop stock
                $deltaAccepted = $accepted - $prevAccepted;
                if ($deltaAccepted > 0) {
                    StockService::increase(
                        variantId: $vid,
                        locationType: 'SHOP',
                        locationId: $shopId,
                        stockState: 'AVAILABLE',
                        quantity: $deltaAccepted,
                        transactionType: 'SHOP_ALLOCATION_INWARD',
                        referenceType: 'warehouse_allocations',
                        referenceId: $allocationId,
                        userId: $userId,
                        notes: "Allocation #{$allocation['allocation_number']} received into Shop Inventory" . ($notes ? " ({$notes})" : "") . ($prevAccepted > 0 ? " [Incremental Inward: +{$deltaAccepted} pcs]" : "")
                    );
                } elseif ($deltaAccepted < 0) {
                    $decrementQty = abs($deltaAccepted);
                    StockService::decrease(
                        variantId: $vid,
                        locationType: 'SHOP',
                        locationId: $shopId,
                        stockState: 'AVAILABLE',
                        quantity: $decrementQty,
                        transactionType: 'SHOP_ALLOCATION_INWARD',
                        referenceType: 'warehouse_allocations',
                        referenceId: $allocationId,
                        userId: $userId,
                        notes: "Allocation #{$allocation['allocation_number']} inward adjustment: -{$decrementQty} pcs"
                    );
                }

                // Incremental deltas for DAMAGED shop stock
                $deltaDamaged = $dam - $prevDam;
                if ($deltaDamaged > 0) {
                    $baseCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM shop_damages WHERE shop_id = ?", [$shopId])['c'] ?? 0);
                    $damageNumber = 'SH-DMG-' . date('Ymd') . '-' . str_pad((string)($baseCount + 1), 4, '0', STR_PAD_LEFT);
                    $costPerUnit = (float)($item['production_cost'] ?? $item['purchase_price'] ?? $item['selling_price'] ?? 0.0);
                    $totalLoss = $deltaDamaged * $costPerUnit;

                    $catInitial = !empty($item['category_name']) ? strtoupper(substr(trim($item['category_name']), 0, 1)) : 'M';
                    $subInitial = !empty($item['subcategory_name']) ? strtoupper(substr(trim($item['subcategory_name']), 0, 1)) : 'T';
                    $subSubInitial = !empty($item['sub_subcategory_name']) ? strtoupper(substr(trim($item['sub_subcategory_name']), 0, 1)) : 'P';
                    $datePart = date('my');
                    $generatedDamagePrefix = $catInitial . $subInitial . $subSubInitial;

                    // Collect all damaged piece tags
                    $damTags = $damagedTagsList;
                    if (empty($damTags) && !empty($receivedItems[$vid]['damage_tag'])) {
                        $damTags = array_filter(array_map('trim', explode(',', (string)$receivedItems[$vid]['damage_tag'])));
                    }
                    if (empty($damTags)) {
                        for ($dIdx = 1; $dIdx <= (int)$deltaDamaged; $dIdx++) {
                            $damTags[] = sprintf('%s%s%02d', $generatedDamagePrefix, $datePart, $dIdx);
                        }
                    }
                    $tagCode = implode(', ', $damTags);
                    $damageReason = !empty($itemNotes) ? $itemNotes : 'Transit / Inward Verification Damage';

                    $damageId = ShopDamage::create([
                        'damage_number' => $damageNumber,
                        'shop_id'       => $shopId,
                        'variant_id'    => $vid,
                        'tag_code'      => $tagCode,
                        'source_type'   => 'INWARD_TRANSIT',
                        'reference_id'  => $allocationId,
                        'allocation_id' => $allocationId,
                        'defect_type'   => 'TRANSIT_DAMAGE',
                        'quantity'      => (int)$deltaDamaged,
                        'cost_per_unit' => $costPerUnit,
                        'estimated_cost'=> $costPerUnit,
                        'total_loss'    => $totalLoss,
                        'damage_reason' => $damageReason,
                        'defect_notes'  => "Quarantined during Shop Inward for Allocation #{$allocation['allocation_number']}" . ($itemNotes ? " - Notes: {$itemNotes}" : "") . " (Damaged Piece Tags: {$tagCode})",
                        'status'        => 'LOGGED',
                        'reported_by'   => $userId,
                    ]);

                    StockService::increase(
                        variantId: $vid,
                        locationType: 'SHOP',
                        locationId: $shopId,
                        stockState: 'DAMAGED',
                        quantity: $deltaDamaged,
                        transactionType: 'RETURN_DAMAGED',
                        referenceType: 'shop_damages',
                        referenceId: $damageId,
                        userId: $userId,
                        notes: "Transit Damaged from Allocation #{$allocation['allocation_number']} (Case #{$damageNumber}). " . $itemNotes
                    );

                    AuditService::log('SHOP', 'INWARD_DAMAGE_LOGGED', 'shop_damages', $damageId, null, [
                        'damage_number'     => $damageNumber,
                        'allocation_id'     => $allocationId,
                        'allocation_number' => $allocation['allocation_number'],
                        'variant_id'        => $vid,
                        'damaged_qty'       => (int)$deltaDamaged,
                        'tag_code'          => $tagCode,
                    ], $userId);
                }
            }

            // 2. Set allocation status: RECEIVED if 100% matched, PARTIAL_RECEIVED if partial
            $newStatus = ($totalAccounted >= $totalExpected) ? 'RECEIVED' : 'PARTIAL_RECEIVED';
            WarehouseAllocation::updateStatus($allocationId, $newStatus, $userId);

            // 3. Update outward pass status to DELIVERED
            Database::execute(
                "UPDATE allocation_outwards SET status = 'DELIVERED', updated_at = NOW() WHERE allocation_id = ?",
                [$allocationId]
            );

            AuditService::log('SHOP', 'ALLOCATION_INWARD_RECEIVE', 'warehouse_allocations', $allocationId, null, [
                'number' => $allocation['allocation_number'], 'shop_id' => $shopId, 'notes' => $notes,
                'status' => $newStatus, 'received_pcs' => $totalAccounted, 'expected_pcs' => $totalExpected
            ]);

            Database::commit();
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * =========================================================================
     * SELLED PRODUCTS (SOLD PRODUCTS / SALES REGISTER)
     * =========================================================================
     */

    /**
     * Get itemized list of all sold products in the shop with rich metadata and filters.
     */
    public static function getSoldProducts(int $shopId, array $filters = []): array {
        $sql = "SELECT si.*, 
                       s.invoice_number, s.payment_mode, s.payment_status, s.created_at as sale_date,
                       s.notes as sale_notes, s.subtotal as invoice_subtotal, s.total_amount as invoice_total_amount,
                       COALESCE(payment_totals.cash_paid, 0) as cash_paid,
                       COALESCE(payment_totals.online_paid, 0) as online_paid,
                       c.id as customer_id, c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
                       u.full_name as cashier_name,
                       pv.sku, pv.mrp, pv.selling_price, pv.image_url as variant_image,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       p.category_id as category_id, p.category_code, p.subcategory_code, p.hsn_code,
                       sz.name as size_name, cl.name as color_name, cat.name as category_name
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN product_variants pv ON si.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN sizes sz ON pv.size_id = sz.id
                LEFT JOIN colors cl ON pv.color_id = cl.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN users u ON s.billing_staff_id = u.id
                LEFT JOIN (
                    SELECT sale_id,
                           SUM(CASE WHEN payment_mode = 'CASH' THEN amount ELSE 0 END) as cash_paid,
                           SUM(CASE WHEN payment_mode <> 'CASH' THEN amount ELSE 0 END) as online_paid
                    FROM payments
                    WHERE status = 'COMPLETED'
                    GROUP BY sale_id
                ) payment_totals ON payment_totals.sale_id = s.id
                WHERE s.shop_id = ? AND s.sale_type = 'SHOP'";
        
        $params = [$shopId];

        // Filter by Search Query (Product, SKU, Invoice, Customer, Piece Tag)
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (p.name LIKE ? OR pv.sku LIKE ? OR s.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR si.scanned_qr_codes LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search, $search, $search]);
        }

        // Filter by Date Range
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(s.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(s.created_at) <= ?";
            $params[] = $filters['end_date'];
        }

        // Filter by Category
        if (!empty($filters['category_id']) && $filters['category_id'] !== 'ALL') {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        $sql .= " ORDER BY s.created_at DESC, si.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int)$filters['offset'];
            }
        }

        $items = Database::fetchAll($sql, $params);

        // Calculate KPI summaries across the shop
        $kpiSql = "SELECT COUNT(DISTINCT s.id) as total_invoices,
                          COALESCE(SUM(si.quantity), 0) as total_units_sold,
                          COALESCE(SUM(si.total_amount), 0) as total_revenue,
                          COALESCE(SUM(si.cgst_amount + si.sgst_amount + si.igst_amount), 0) as total_tax,
                          COALESCE(SUM(si.discount_amount), 0) as total_discount
                   FROM sale_items si
                   JOIN sales s ON si.sale_id = s.id
                   WHERE s.shop_id = ? AND s.sale_type = 'SHOP'";
        $kpiParams = [$shopId];

        if (!empty($filters['start_date'])) {
            $kpiSql .= " AND DATE(s.created_at) >= ?";
            $kpiParams[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $kpiSql .= " AND DATE(s.created_at) <= ?";
            $kpiParams[] = $filters['end_date'];
        }

        $kpis = Database::fetchOne($kpiSql, $kpiParams) ?: [
            'total_invoices'   => 0,
            'total_units_sold' => 0,
            'total_revenue'    => 0,
            'total_tax'        => 0,
            'total_discount'   => 0,
        ];

        foreach ($items as &$it) {
            $it['image'] = !empty($it['variant_image']) ? $it['variant_image'] : ($it['product_image'] ?? null);
            $it['sale_date_display'] = formatUtcDateTime($it['sale_date'] ?? null);
            $it['tax_total'] = (float)$it['cgst_amount'] + (float)$it['sgst_amount'] + (float)$it['igst_amount'];

            $qty = max(1, (int)($it['quantity'] ?? 1));
            $originalUnitPrice = (float)($it['unit_price'] > 0 ? $it['unit_price'] : ($it['mrp'] > 0 ? $it['mrp'] : ($it['selling_price'] ?? 0)));
            $totalPaid = (float)($it['total_amount'] ?? 0);
            $billedUnitPrice = round($totalPaid / $qty, 2);
            $grossTotal = round($originalUnitPrice * $qty, 2);

            $discAmt = (float)($it['discount_amount'] ?? 0);
            if ($discAmt <= 0 && $grossTotal > $totalPaid) {
                $discAmt = round($grossTotal - $totalPaid, 2);
            }

            $discPercent = (float)($it['discount_percent'] ?? 0);
            if ($discPercent <= 0 && $grossTotal > 0 && $discAmt > 0) {
                $discPercent = round(($discAmt / $grossTotal) * 100, 2);
            }

            $cashPaid = (float)$it['cash_paid'];
            $onlinePaid = (float)$it['online_paid'];
            if ($cashPaid + $onlinePaid <= 0) {
                if (($it['payment_mode'] ?? '') === 'CASH_UPI'
                    && preg_match('/\[Split Payment:\s*Cash\s*₹([\d,.]+)\s*\+\s*UPI\s*₹([\d,.]+)/i', (string)($it['sale_notes'] ?? ''), $splitPayment)) {
                    $cashPaid = (float)str_replace(',', '', $splitPayment[1]);
                    $onlinePaid = (float)str_replace(',', '', $splitPayment[2]);
                } elseif (($it['payment_mode'] ?? '') === 'CASH') {
                    $cashPaid = (float)($it['invoice_total_amount'] ?? 0);
                } else {
                    $onlinePaid = (float)($it['invoice_total_amount'] ?? 0);
                }
            }
            $paymentTotal = $cashPaid + $onlinePaid;
            $cashRevenue = $paymentTotal > 0
                ? round($totalPaid * $cashPaid / $paymentTotal, 2)
                : 0.0;
            $it['cash_revenue'] = $cashRevenue;
            $it['online_revenue'] = round($totalPaid - $cashRevenue, 2);

            $it['original_unit_price'] = $originalUnitPrice;
            $it['billed_unit_price']   = $billedUnitPrice;
            $it['gross_total']         = $grossTotal;
            $it['discount_amount']     = $discAmt;
            $it['discount_percent']    = $discPercent;
            $it['unit_discount']       = round($discAmt / $qty, 2);
            $it['has_discount']        = ($discAmt > 0 || $billedUnitPrice < $originalUnitPrice);
            $it['taxable_base']        = max(0, round($totalPaid - $it['tax_total'], 2));
        }

        return [
            'items' => $items,
            'kpis'  => $kpis,
        ];
    }

    /**
     * Get single sold product line detail.
     */
    public static function getSoldProductDetails(int $saleItemId): ?array {
        $sql = "SELECT si.*, 
                       s.invoice_number, s.payment_mode, s.payment_status, s.created_at as sale_date,
                       s.subtotal as invoice_subtotal, s.total_amount as invoice_total_amount,
                       s.discount_amount as invoice_discount_total, s.employee_name as billed_employee,
                       s.notes as sale_notes,
                       c.id as customer_id, c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
                       u.full_name as cashier_name, sh.name as shop_name,
                       pv.sku, pv.mrp, pv.selling_price, pv.image_url as variant_image,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       p.category_code, p.subcategory_code, p.hsn_code,
                       sz.name as size_name, cl.name as color_name, cat.name as category_name
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN product_variants pv ON si.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN shops sh ON s.shop_id = sh.id
                LEFT JOIN sizes sz ON pv.size_id = sz.id
                LEFT JOIN colors cl ON pv.color_id = cl.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN users u ON s.billing_staff_id = u.id
                WHERE si.id = ?";
        
        $item = Database::fetchOne($sql, [$saleItemId]);
        if (!$item) return null;

        $item['image'] = !empty($item['variant_image']) ? $item['variant_image'] : ($item['product_image'] ?? null);
        $item['tax_total'] = (float)$item['cgst_amount'] + (float)$item['sgst_amount'] + (float)$item['igst_amount'];

        $qty = max(1, (int)($item['quantity'] ?? 1));
        $originalUnitPrice = (float)($item['unit_price'] > 0 ? $item['unit_price'] : ($item['mrp'] > 0 ? $item['mrp'] : ($item['selling_price'] ?? 0)));
        $totalPaid = (float)($item['total_amount'] ?? 0);
        $billedUnitPrice = round($totalPaid / $qty, 2);
        $grossTotal = round($originalUnitPrice * $qty, 2);

        $discAmt = (float)($item['discount_amount'] ?? 0);
        if ($discAmt <= 0 && $grossTotal > $totalPaid) {
            $discAmt = round($grossTotal - $totalPaid, 2);
        }

        $discPercent = (float)($item['discount_percent'] ?? 0);
        if ($discPercent <= 0 && $grossTotal > 0 && $discAmt > 0) {
            $discPercent = round(($discAmt / $grossTotal) * 100, 2);
        }

        $item['original_unit_price'] = $originalUnitPrice;
        $item['billed_unit_price']   = $billedUnitPrice;
        $item['gross_total']         = $grossTotal;
        $item['discount_amount']     = $discAmt;
        $item['discount_percent']    = $discPercent;
        $item['unit_discount']       = round($discAmt / $qty, 2);
        $item['has_discount']        = ($discAmt > 0 || $billedUnitPrice < $originalUnitPrice);
        $item['taxable_base']        = max(0, round($totalPaid - $item['tax_total'], 2));

        return $item;
    }

    /**
     * =========================================================================
     * RETURN TO WAREHOUSE (SHOP -> CENTRAL WAREHOUSE DISPATCH)
     * =========================================================================
     */

    /**
     * Get list of all Return-to-Warehouse dispatches created by this Shop.
     */
    public static function getShopWarehouseReturns(int $shopId, array $filters = []): array {
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
                WHERE wr.source_type = 'SHOP' AND wr.source_id = ?";
        
        $params = [$shopId];

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

        // Summaries
        $kpiSql = "SELECT COUNT(*) as total_dispatches,
                          COALESCE(SUM((SELECT SUM(returned_qty) FROM warehouse_return_items WHERE warehouse_return_id = wr.id)), 0) as total_units_returned,
                          SUM(CASE WHEN wr.status IN ('RECEIVED', 'INSPECTING') THEN 1 ELSE 0 END) as in_transit_count,
                          SUM(CASE WHEN wr.status IN ('CLASSIFIED', 'COMPLETED') THEN 1 ELSE 0 END) as completed_count
                   FROM warehouse_returns wr
                   WHERE wr.source_type = 'SHOP' AND wr.source_id = ?";
        $kpis = Database::fetchOne($kpiSql, [$shopId]) ?: [
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
     * Create a new Return to Warehouse from Shop Inventory and deduct stock.
     */
    public static function createShopWarehouseReturn(int $shopId, array $payload, int $userId): array {
        if ($shopId <= 0 || !Shop::find($shopId)) {
            throw new InvalidArgumentException("Invalid shop store context.");
        }

        $warehouseId = (int)($payload['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            throw new InvalidArgumentException("Please select a valid destination warehouse.");
        }
        if (!Warehouse::find($warehouseId)) {
            throw new InvalidArgumentException("Selected destination warehouse does not belong to the active concern.");
        }

        $reason = trim($payload['reason'] ?? '');
        if (empty($reason)) {
            $reason = 'Damaged & Defective Stock Return';
        }

        $carrierName = trim($payload['carrier_name'] ?? '');
        if (empty($carrierName)) {
            throw new InvalidArgumentException("Carrier / Transporter name is required for outward dispatch.");
        }

        $cartonCount = (int)($payload['carton_count'] ?? 1);
        if ($cartonCount < 1) {
            throw new InvalidArgumentException("Carton / Box count must be at least 1.");
        }

        $driverPhone = trim($payload['driver_phone'] ?? '');
        if (!empty($driverPhone)) {
            $driverPhoneDigits = preg_replace('/\D/', '', $driverPhone);
            if (!preg_match('/^[6-9][0-9]{9}$/', $driverPhoneDigits)) {
                throw new InvalidArgumentException("Invalid driver phone number. Must be a valid 10-digit mobile number starting with 6-9.");
            }
            $driverPhone = $driverPhoneDigits;
        }

        $vehicleNo    = strtoupper(trim($payload['vehicle_no'] ?? ''));
        $driverName   = trim($payload['driver_name'] ?? '');
        $trackingNo   = trim($payload['tracking_no'] ?? '');
        $dispatchDate = trim($payload['dispatch_date'] ?? date('Y-m-d H:i:s'));
        $remarks      = trim($payload['remarks'] ?? ($payload['notes'] ?? ''));

        $items = $payload['items'] ?? [];
        if (empty($items)) {
            throw new InvalidArgumentException("Please select at least one product item to return to the warehouse.");
        }

        Database::beginTransaction();
        try {
            // Generate Return Number: RET-YYYYMMDD-XXXX
            $today = date('Ymd');
            $prefix = 'RET-' . $today . '-';
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
                'carrier_name'  => $carrierName,
                'vehicle_no'    => $vehicleNo,
                'driver_name'   => $driverName,
                'driver_phone'  => $driverPhone,
                'tracking_no'   => $trackingNo,
                'carton_count'  => max(1, $cartonCount),
                'dispatch_date' => $dispatchDate,
                'remarks'       => $remarks
            ];
            $serializedNotes = json_encode($notesData);

            // Insert into warehouse_returns
            Database::execute(
                "INSERT INTO warehouse_returns (return_number, source_type, source_id, warehouse_id, destination_warehouse_id, reason, status, notes, created_at, updated_at)
                 VALUES (?, 'SHOP', ?, ?, ?, ?, 'RECEIVED', ?, NOW(), NOW())",
                [$returnNumber, $shopId, $warehouseId, $warehouseId, $reason, $serializedNotes]
            );
            $returnId = (int)Database::lastInsertId();

            $processedCount = 0;
            foreach ($items as $it) {
                $variantId = (int)($it['variant_id'] ?? 0);
                $qty = (int)($it['quantity'] ?? 0);
                $stockState = strtoupper($it['stock_state'] ?? 'AVAILABLE'); // AVAILABLE or DAMAGED
                $classification = strtoupper($it['classification'] ?? ($stockState === 'DAMAGED' ? 'DAMAGED' : 'GOOD'));

                if ($variantId <= 0 || $qty <= 0) continue;
                if (!in_array($stockState, ['AVAILABLE', 'DAMAGED'], true)) {
                    throw new InvalidArgumentException("Invalid shop stock pool for warehouse return.");
                }

                // Normal garments are individual QR pieces and may only be returned one at a time.
                // Series and complementary products represent multiple pieces and may use a quantity.
                $variantType = Database::fetchOne(
                    "SELECT COALESCE(wc.category_type, 'NORMAL') as category_type,
                            COALESCE(pv.is_complementary, 0) as is_complementary,
                            pv.qr_type, pv.pack_quantity
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
                     WHERE pv.id = ? LIMIT 1",
                    [$variantId]
                );
                $variantCategory = strtoupper(trim((string)($variantType['category_type'] ?? 'NORMAL')));
                $allowsMultiple = $variantCategory === 'SERIES'
                    || !empty($variantType['is_complementary'])
                    || strtoupper((string)($variantType['qr_type'] ?? '')) === 'SERIES_BATCH'
                    || (int)($variantType['pack_quantity'] ?? 1) > 1;
                if (!$allowsMultiple && $qty > 1) {
                    throw new RuntimeException("Normal product returns are limited to one individual QR piece per dispatch.");
                }

                // Validate shop stock
                $balanceRec = StockBalance::findByKey($variantId, 'SHOP', $shopId, $stockState);
                $currentStock = (float)($balanceRec['quantity'] ?? 0);
                if ($currentStock < $qty) {
                    $pv = Database::fetchOne("SELECT sku FROM product_variants WHERE id = ?", [$variantId]);
                    $sku = $pv['sku'] ?? "#$variantId";
                    throw new RuntimeException("Insufficient {$stockState} stock for SKU '{$sku}'. Requested: {$qty}, Available: {$currentStock}");
                }

                $pieceTags = $it['piece_tags'] ?? [];
                if (!is_array($pieceTags)) {
                    $pieceTags = preg_split('/[,|]/', (string)$pieceTags) ?: [];
                }
                if (!empty($pieceTags)) {
                    $damageCases = Database::fetchAll(
                        "SELECT tag_code
                         FROM shop_damages
                         WHERE shop_id = ? AND variant_id = ? AND status <> 'RESTOCKED'
                           AND tag_code IS NOT NULL AND tag_code <> ''",
                        [$shopId, $variantId]
                    );
                    $damagedTagSet = [];
                    foreach ($damageCases as $damageCase) {
                        foreach (ShopDamage::resolvePieceTags($damageCase) as $damageTag) {
                            $damagedTagSet[strtoupper(trim($damageTag))] = true;
                        }
                    }

                    foreach ($pieceTags as $pieceTag) {
                        $normalizedPieceTag = strtoupper(trim((string)$pieceTag, " \t\n\r\0\x0B\"'"));
                        if ($normalizedPieceTag === '') {
                            continue;
                        }
                        if ($stockState === 'DAMAGED' && empty($damagedTagSet[$normalizedPieceTag])) {
                            throw new RuntimeException("Piece QR '{$pieceTag}' is not allocated to Damage Management for this product.");
                        }
                        if ($stockState === 'AVAILABLE' && !empty($damagedTagSet[$normalizedPieceTag])) {
                            throw new RuntimeException("Piece QR '{$pieceTag}' is assigned to Damage Management and cannot be returned from Available Stock.");
                        }
                    }
                }

                // Insert into warehouse_return_items
                $goodQty = ($classification === 'GOOD') ? $qty : 0;
                $damQty  = ($classification === 'DAMAGED') ? $qty : 0;
                $defQty  = ($classification === 'DEFECTIVE') ? $qty : 0;

                Database::execute(
                    "INSERT INTO warehouse_return_items (warehouse_return_id, variant_id, returned_qty, good_qty, damaged_qty, defective_qty, classification)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$returnId, $variantId, $qty, $goodQty, $damQty, $defQty, $classification]
                );

                // Deduct stock from Shop Inventory
                StockService::decrease(
                    variantId: $variantId,
                    locationType: 'SHOP',
                    locationId: $shopId,
                    stockState: $stockState,
                    quantity: (float)$qty,
                    transactionType: 'TRANSFER_OUT',
                    referenceType: 'warehouse_returns',
                    referenceId: $returnId,
                    userId: $userId,
                    notes: "Shop Return to Warehouse #{$returnNumber} ({$classification})"
                );

                $processedCount++;
            }

            if ($processedCount === 0) {
                throw new InvalidArgumentException("No valid items with quantity > 0 were provided.");
            }

            AuditService::log('SHOP', 'RETURN_TO_WAREHOUSE_DISPATCH', 'warehouse_returns', $returnId, null, [
                'return_number' => $returnNumber,
                'shop_id'       => $shopId,
                'warehouse_id'  => $warehouseId,
                'items_count'   => $processedCount,
                'outward'       => $notesData
            ]);

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
     * Get Complete Details of a Return to Warehouse Consignment for view, gatepass, and inspection.
     */
    public static function getShopWarehouseReturnDetails(int $returnId): ?array {
        $ret = Database::fetchOne(
            "SELECT wr.*, 
                    w.name as warehouse_name, w.code as warehouse_code, w.address as warehouse_address, w.phone as warehouse_phone,
                    s.name as shop_name, s.code as shop_code, s.address as shop_address, s.phone as shop_phone,
                    u.full_name as inspected_by_name
             FROM warehouse_returns wr
             JOIN warehouses w ON wr.warehouse_id = w.id
             LEFT JOIN shops s ON (wr.source_type = 'SHOP' AND wr.source_id = s.id)
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
        if (!empty($ret['notes'])) {
            $decoded = json_decode($ret['notes'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $outward = array_merge($outward, $decoded);
            } else {
                $outward['remarks'] = $ret['notes'];
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

    // ==========================================
    // SHOP CUSTOMER MANAGEMENT & 360 CRM
    // ==========================================

    /**
     * Get Customers List with Aggregated Sales & Spend Metrics
     */
    public static function getCustomersList(array $filters = []): array {
        $q = trim($filters['q'] ?? '');
        $status = $filters['status'] ?? 'all';
        $tier = $filters['tier'] ?? 'all';
        $city = trim($filters['city'] ?? '');
        $sort = $filters['sort'] ?? 'recent';
        $channel = $filters['channel'] ?? 'all';

        $where = ["1=1"];
        $params = [];

        if (!empty($q)) {
            $where[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR c.city LIKE ? OR c.gst_number LIKE ?)";
            $searchTerm = "%{$q}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($status === 'active') {
            $where[] = "c.is_active = 1";
        } elseif ($status === 'inactive') {
            $where[] = "c.is_active = 0";
        }

        if (!empty($city)) {
            $where[] = "c.city = ?";
            $params[] = $city;
        }

        if (!empty($channel) && $channel === 'SHOP') {
            $where[] = "(c.source_channel = 'SHOP' OR c.id IN (SELECT DISTINCT s_sub.customer_id FROM sales s_sub WHERE s_sub.sale_type = 'SHOP' AND s_sub.customer_id IS NOT NULL))";
        } elseif (!empty($channel) && $channel !== 'all') {
            $where[] = "c.source_channel = ?";
            $params[] = $channel;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "
            SELECT c.*,
                   (SELECT COUNT(s.id) FROM sales s WHERE (s.customer_id = c.id OR (s.customer_phone IS NOT NULL AND s.customer_phone != '' AND c.phone IS NOT NULL AND c.phone != '' AND RIGHT(REGEXP_REPLACE(s.customer_phone, '[^0-9]', ''), 10) = RIGHT(REGEXP_REPLACE(c.phone, '[^0-9]', ''), 10))) AND s.sale_type = 'SHOP' AND s.status != 'CANCELLED') as total_orders,
                   (SELECT COALESCE(SUM(s.total_amount), 0) FROM sales s WHERE (s.customer_id = c.id OR (s.customer_phone IS NOT NULL AND s.customer_phone != '' AND c.phone IS NOT NULL AND c.phone != '' AND RIGHT(REGEXP_REPLACE(s.customer_phone, '[^0-9]', ''), 10) = RIGHT(REGEXP_REPLACE(c.phone, '[^0-9]', ''), 10))) AND s.sale_type = 'SHOP' AND s.status != 'CANCELLED') as total_spend,
                   (SELECT COALESCE(SUM(si.quantity), 0) FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE (s.customer_id = c.id OR (s.customer_phone IS NOT NULL AND s.customer_phone != '' AND c.phone IS NOT NULL AND c.phone != '' AND RIGHT(REGEXP_REPLACE(s.customer_phone, '[^0-9]', ''), 10) = RIGHT(REGEXP_REPLACE(c.phone, '[^0-9]', ''), 10))) AND s.sale_type = 'SHOP' AND s.status != 'CANCELLED') as total_items_bought,
                   (SELECT MAX(s.created_at) FROM sales s WHERE (s.customer_id = c.id OR (s.customer_phone IS NOT NULL AND s.customer_phone != '' AND c.phone IS NOT NULL AND c.phone != '' AND RIGHT(REGEXP_REPLACE(s.customer_phone, '[^0-9]', ''), 10) = RIGHT(REGEXP_REPLACE(c.phone, '[^0-9]', ''), 10))) AND s.sale_type = 'SHOP' AND s.status != 'CANCELLED') as last_visit_date,
                   (SELECT s2.invoice_number FROM sales s2 WHERE (s2.customer_id = c.id OR (s2.customer_phone IS NOT NULL AND s2.customer_phone != '' AND c.phone IS NOT NULL AND c.phone != '' AND RIGHT(REGEXP_REPLACE(s2.customer_phone, '[^0-9]', ''), 10) = RIGHT(REGEXP_REPLACE(c.phone, '[^0-9]', ''), 10))) AND s2.sale_type = 'SHOP' AND s2.status != 'CANCELLED' ORDER BY s2.created_at DESC LIMIT 1) as last_invoice_number,
                   (SELECT s2.id FROM sales s2 WHERE (s2.customer_id = c.id OR (s2.customer_phone IS NOT NULL AND s2.customer_phone != '' AND c.phone IS NOT NULL AND c.phone != '' AND RIGHT(REGEXP_REPLACE(s2.customer_phone, '[^0-9]', ''), 10) = RIGHT(REGEXP_REPLACE(c.phone, '[^0-9]', ''), 10))) AND s2.sale_type = 'SHOP' AND s2.status != 'CANCELLED' ORDER BY s2.created_at DESC LIMIT 1) as last_sale_id,
                   (SELECT COUNT(r.id) FROM returns r WHERE r.customer_id = c.id AND r.source_type = 'SHOP') as total_returns
            FROM customers c
            WHERE {$whereSql}
        ";

        if ($tier === 'vip') {
            $sql .= " HAVING total_spend >= 10000";
        } elseif ($tier === 'regular') {
            $sql .= " HAVING total_spend >= 3000 AND total_spend < 10000";
        } elseif ($tier === 'new') {
            $sql .= " HAVING total_spend < 3000";
        }

        switch ($sort) {
            case 'spend_desc':
                $sql .= " ORDER BY total_spend DESC, c.name ASC";
                break;
            case 'orders_desc':
                $sql .= " ORDER BY total_orders DESC, c.name ASC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY c.name ASC";
                break;
            case 'oldest':
                $sql .= " ORDER BY c.created_at ASC";
                break;
            case 'recent':
            default:
                $sql .= " ORDER BY COALESCE(last_visit_date, c.created_at) DESC, c.id DESC";
                break;
        }

        $customers = Database::fetchAll($sql, $params);

        foreach ($customers as &$c) {
            $orders = (int)$c['total_orders'];
            $spend = (float)$c['total_spend'];
            $c['avg_order_value'] = $orders > 0 ? round($spend / $orders, 2) : 0.00;

            if ($spend >= 10000) {
                $c['tier_label'] = 'VIP Customer';
                $c['tier_badge'] = 'bg-warning-subtle text-dark border border-warning';
                $c['tier_icon'] = 'bi-star-fill text-warning';
            } elseif ($spend >= 3000) {
                $c['tier_label'] = 'Regular';
                $c['tier_badge'] = 'bg-primary-subtle text-primary border border-primary';
                $c['tier_icon'] = 'bi-person-check-fill text-primary';
            } else {
                $c['tier_label'] = 'New / Walk-in';
                $c['tier_badge'] = 'bg-secondary-subtle text-secondary border';
                $c['tier_icon'] = 'bi-person text-secondary';
            }
        }
        unset($c);

        return $customers;
    }

    /**
     * Get Customer Summary KPI Metrics (Isolated by Channel)
     */
    public static function getCustomerSummaryMetrics(?string $channel = 'SHOP'): array {
        if ($channel === 'SHOP') {
            $chanWhere = " WHERE (source_channel = 'SHOP' OR id IN (SELECT DISTINCT customer_id FROM sales WHERE sale_type = 'SHOP' AND customer_id IS NOT NULL))";
            $chanParams = [];
        } elseif (!empty($channel) && $channel !== 'all') {
            $chanWhere = " WHERE source_channel = ?";
            $chanParams = [$channel];
        } else {
            $chanWhere = '';
            $chanParams = [];
        }

        $totalCust = (int)(Database::fetchOne("SELECT COUNT(id) as cnt FROM customers" . $chanWhere, $chanParams)['cnt'] ?? 0);
        $activeCust = (int)(Database::fetchOne("SELECT COUNT(id) as cnt FROM customers WHERE is_active = 1" . (!empty($chanWhere) ? " AND (" . substr($chanWhere, 7) . ")" : ""), $chanParams)['cnt'] ?? 0);

        $salesStats = Database::fetchOne(
            "SELECT COALESCE(SUM(s.total_amount), 0) as total_rev,
                    COUNT(DISTINCT s.customer_id) as buying_customers
             FROM sales s
             WHERE s.sale_type = 'SHOP' AND s.status != 'CANCELLED'"
        );

        $repeatCust = (int)(Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM (
                SELECT s.customer_id FROM sales s 
                WHERE s.sale_type = 'SHOP' AND s.status != 'CANCELLED' AND s.customer_id IS NOT NULL 
                GROUP BY s.customer_id HAVING COUNT(s.id) > 1
             ) as t"
        )['cnt'] ?? 0);

        $totalRev = (float)($salesStats['total_rev'] ?? 0);
        $buyingCust = (int)($salesStats['buying_customers'] ?? 0);
        $avgLtv = $buyingCust > 0 ? round($totalRev / $buyingCust, 2) : 0;
        $repeatRate = $buyingCust > 0 ? round(($repeatCust / $buyingCust) * 100, 1) : 0;

        $cities = Database::fetchAll("SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != ''" . (!empty($chanWhere) ? " AND (" . substr($chanWhere, 7) . ")" : "") . " ORDER BY city ASC", $chanParams);
        $citiesList = array_column($cities, 'city');

        return [
            'total_customers'       => $totalCust,
            'active_customers'      => $activeCust,
            'inactive_customers'    => max(0, $totalCust - $activeCust),
            'total_revenue'         => $totalRev,
            'avg_ltv'               => $avgLtv,
            'repeat_customers'      => $repeatCust,
            'repeat_rate'           => $repeatRate,
            'cities'                => $citiesList,
        ];
    }

    /**
     * Get 360-Degree Customer Details with Invoices, Items Bought, and Returns History (Isolated to Shop)
     */
    public static function getCustomerDetails(int $customerId): ?array {
        $customer = Database::fetchOne("SELECT * FROM customers WHERE id = ?", [$customerId]);
        if (!$customer) return null;

        // Clean 10-digit phone for cross-channel / duplicate aggregation
        $phoneDigits = preg_replace('/[^0-9]/', '', $customer['phone'] ?? '');
        $last10 = strlen($phoneDigits) >= 10 ? substr($phoneDigits, -10) : $phoneDigits;

        // Find all matching customer IDs
        $matchingIds = [$customerId];
        if (!empty($last10)) {
            $otherCusts = Database::fetchAll(
                "SELECT * FROM customers WHERE RIGHT(REGEXP_REPLACE(phone, '[^0-9]', ''), 10) = ?",
                [$last10]
            );
            foreach ($otherCusts as $oc) {
                $matchingIds[] = (int)$oc['id'];
                // Backfill any missing fields on primary customer from other records
                if (empty($customer['city']) && !empty($oc['city'])) $customer['city'] = $oc['city'];
                if (empty($customer['state']) && !empty($oc['state'])) $customer['state'] = $oc['state'];
                if (empty($customer['email']) && !empty($oc['email'])) $customer['email'] = $oc['email'];
                if ((empty($customer['address']) || strtolower(trim($customer['address'])) === 'test') && !empty($oc['address']) && strtolower(trim($oc['address'])) !== 'test') {
                    $customer['address'] = $oc['address'];
                }
            }
        }
        $matchingIds = array_values(array_unique($matchingIds));
        $inPlaceholders = implode(',', array_fill(0, count($matchingIds), '?'));

        // Fetch all sales for matching customer IDs or matching phone (strictly SHOP sales)
        $saleParams = $matchingIds;
        $saleWhere = "s.customer_id IN ($inPlaceholders)";
        if (!empty($last10)) {
            $saleWhere .= " OR (s.customer_phone IS NOT NULL AND s.customer_phone != '' AND RIGHT(REGEXP_REPLACE(s.customer_phone, '[^0-9]', ''), 10) = ?)";
            $saleParams[] = $last10;
        }
        $saleWhere = "($saleWhere) AND s.sale_type = 'SHOP'";

        $sales = Database::fetchAll(
            "SELECT s.*, 
                    u.full_name as staff_name,
                    sh.name as shop_name,
                    sh.address as shop_address,
                    sh.phone as shop_phone,
                    sh.gst_number as shop_gstin,
                    (SELECT COUNT(id) FROM sale_items WHERE sale_id = s.id) as items_count,
                    (SELECT SUM(quantity) FROM sale_items WHERE sale_id = s.id) as total_qty
              FROM sales s
              LEFT JOIN users u ON s.billing_staff_id = u.id
              LEFT JOIN shops sh ON s.shop_id = sh.id
              WHERE $saleWhere
              ORDER BY s.created_at DESC",
            $saleParams
        );

        $saleIds = !empty($sales) ? array_column($sales, 'id') : [0];
        $saleInPlaceholders = implode(',', array_fill(0, count($saleIds), '?'));

        $itemsBought = Database::fetchAll(
            "SELECT si.*, pv.sku, pv.qr_code, pv.mrp as variant_mrp, p.name as product_name, p.code as product_code,
                    p.hsn_code, p.gst_percentage,
                    sz.name as size_name, cl.name as color_name, s.invoice_number, s.created_at as sale_date,
                    s.id as sale_id
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             WHERE s.id IN ($saleInPlaceholders) AND s.sale_type = 'SHOP' AND s.status != 'CANCELLED'
             ORDER BY s.created_at DESC, si.id ASC",
            $saleIds
        );

        // Fetch returns (strictly SHOP returns)
        $retParams = array_merge($matchingIds, $saleIds);
        $retInCust = implode(',', array_fill(0, count($matchingIds), '?'));
        $retInSale = implode(',', array_fill(0, count($saleIds), '?'));

        $returns = Database::fetchAll(
            "SELECT r.*, s.invoice_number, u.full_name as processed_by_name
             FROM returns r
             JOIN sales s ON r.sale_id = s.id
             LEFT JOIN users u ON COALESCE(r.approved_by, r.inspected_by) = u.id
             WHERE (r.customer_id IN ($retInCust) OR r.sale_id IN ($retInSale)) AND r.source_type = 'SHOP'
             ORDER BY r.created_at DESC",
            $retParams
        );

        // Fetch return items to attach return status to itemsBought and sales
        $returnIds = !empty($returns) ? array_column($returns, 'id') : [0];
        $retItemPlaceholders = implode(',', array_fill(0, count($returnIds), '?'));
        $returnItems = Database::fetchAll(
            "SELECT ri.*, r.return_number, r.return_type, r.status as return_status, r.sale_id
             FROM return_items ri
             JOIN returns r ON ri.return_id = r.id
             WHERE ri.return_id IN ($retItemPlaceholders)",
            $returnIds
        );

        $returnedSaleItemIds = [];
        $totalRefunds = 0.0;
        $totalItemsReturned = 0;

        foreach ($returnItems as $ri) {
            if (!empty($ri['sale_item_id'])) {
                $returnedSaleItemIds[$ri['sale_item_id']] = $ri;
            }
            $totalItemsReturned += (int)($ri['quantity'] ?? 1);
        }

        foreach ($returns as $r) {
            $totalRefunds += (float)($r['refund_amount'] ?? 0);
        }

        // Attach return flag to itemsBought
        foreach ($itemsBought as &$it) {
            if (isset($returnedSaleItemIds[$it['id']])) {
                $ri = $returnedSaleItemIds[$it['id']];
                $it['is_returned'] = true;
                $it['return_number'] = $ri['return_number'];
                $it['return_type'] = $ri['return_type'];
                $it['return_status'] = $ri['return_status'];
            } else {
                $it['is_returned'] = false;
                $it['return_number'] = null;
                $it['return_type'] = null;
                $it['return_status'] = null;
            }
        }
        unset($it);

        // Group item details by sale_id so each sale has its items pre-attached
        $saleItemsMap = [];
        foreach ($itemsBought as $it) {
            $saleItemsMap[$it['sale_id']][] = $it;
        }

        // Attach items and return info to each sale
        foreach ($sales as &$sl) {
            $sl['items'] = $saleItemsMap[$sl['id']] ?? [];
            $sRets = array_filter($returns, fn($r) => (int)$r['sale_id'] === (int)$sl['id']);
            if (!empty($sRets)) {
                $firstRet = reset($sRets);
                $sl['has_return'] = true;
                $sl['return_number'] = $firstRet['return_number'];
                $sl['return_type'] = $firstRet['return_type'];
                $sl['refund_amount'] = (float)$firstRet['refund_amount'];
            } else {
                $sl['has_return'] = false;
                $sl['return_number'] = null;
                $sl['return_type'] = null;
                $sl['refund_amount'] = 0.00;
            }
        }
        unset($sl);

        $totalSpend = 0.0;
        $totalQty = 0;
        foreach ($sales as $sl) {
            if ($sl['status'] !== 'CANCELLED') {
                $totalSpend += (float)$sl['total_amount'];
                $totalQty += (int)($sl['total_qty'] ?? $sl['items_count'] ?? 0);
            }
        }

        $orderCount = count(array_filter($sales, fn($s) => $s['status'] !== 'CANCELLED'));
        $avgOrder = $orderCount > 0 ? round($totalSpend / $orderCount, 2) : 0.00;
        $netSpend = max(0, $totalSpend - $totalRefunds);
        $netItems = max(0, $totalQty - $totalItemsReturned);

        return [
            'customer'          => $customer,
            'metrics'           => [
                'total_orders'          => $orderCount,
                'total_spend'           => $totalSpend,
                'net_spend'             => $netSpend,
                'total_refunds'         => $totalRefunds,
                'total_items_bought'    => $totalQty,
                'total_items_returned'  => $totalItemsReturned,
                'net_items_kept'        => $netItems,
                'avg_order_value'       => $avgOrder,
                'total_returns'         => count($returns),
                'first_visit'           => !empty($sales) ? end($sales)['created_at'] : $customer['created_at'],
                'last_visit'            => !empty($sales) ? reset($sales)['created_at'] : null,
            ],
            'sales'             => $sales,
            'items_bought'      => $itemsBought,
            'returns'           => $returns,
        ];
    }

    /**
     * Create or Update Customer Profile
     */
    public static function saveCustomer(array $data, ?int $id = null): array {
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $address = trim($data['address'] ?? '');
        $city = trim($data['city'] ?? '');
        $state = trim($data['state'] ?? 'Tamil Nadu');
        $gstNumber = trim($data['gst_number'] ?? '');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if (empty($name)) {
            throw new InvalidArgumentException("Customer Name is required.");
        }
        if (empty($phone)) {
            throw new InvalidArgumentException("Customer Phone / Mobile number is required.");
        }

        if ($id) {
            $existing = Database::fetchOne("SELECT id FROM customers WHERE phone = ? AND id != ?", [$phone, $id]);
        } else {
            $existing = Database::fetchOne("SELECT id FROM customers WHERE phone = ?", [$phone]);
        }
        if ($existing) {
            throw new InvalidArgumentException("A customer with phone number '{$phone}' already exists.");
        }

        if ($id) {
            Database::execute(
                "UPDATE customers 
                 SET name = ?, phone = ?, email = ?, address = ?, city = ?, state = ?, 
                     gst_number = ?, is_active = ?, updated_at = NOW()
                 WHERE id = ?",
                [
                    $name,
                    $phone,
                    $email ?: null,
                    $address ?: null,
                    $city ?: null,
                    $state ?: null,
                    $gstNumber ?: null,
                    $isActive,
                    $id
                ]
            );
            $customerId = $id;
            AuditService::log('SHOP', 'UPDATE_CUSTOMER', 'customers', $customerId, null, ['name' => $name, 'phone' => $phone]);
        } else {
            $sourceChannel = $data['source_channel'] ?? 'SHOP';
            Database::execute(
                "INSERT INTO customers (name, phone, email, address, city, state, gst_number, source_channel, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $name,
                    $phone,
                    $email ?: null,
                    $address ?: null,
                    $city ?: null,
                    $state ?: null,
                    $gstNumber ?: null,
                    $sourceChannel,
                    $isActive
                ]
            );
            $customerId = (int)Database::lastInsertId();
            AuditService::log('SHOP', 'CREATE_CUSTOMER', 'customers', $customerId, null, ['name' => $name, 'phone' => $phone]);
        }

        return Database::fetchOne("SELECT * FROM customers WHERE id = ?", [$customerId]);
    }

    /**
     * Toggle Customer Active Status
     */
    public static function toggleCustomerStatus(int $customerId): bool {
        $cust = Database::fetchOne("SELECT is_active FROM customers WHERE id = ?", [$customerId]);
        if (!$cust) return false;
        $newStatus = $cust['is_active'] ? 0 : 1;
        Database::execute("UPDATE customers SET is_active = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $customerId]);
        AuditService::log('SHOP', 'TOGGLE_CUSTOMER_STATUS', 'customers', $customerId, null, ['new_status' => $newStatus]);
        return (bool)$newStatus;
    }
}
