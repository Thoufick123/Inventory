<?php
/**
 * Asset Service
 * Central domain logic engine for Enterprise Asset Management:
 * Production Units, Warehouses, Retail Shops, and Office fleet equipment.
 */

require_once BASE_PATH . '/app/Helpers/Database.php';

class AssetService {

    /**
     * Generate unique sequence number with prefix.
     */
    public static function generateSequenceNumber(string $table, string $column, string $prefix): string {
        $today = date('Ymd');
        $pattern = $prefix . '-' . $today . '-%';
        
        $lastRecord = Database::fetchOne(
            "SELECT $column FROM $table WHERE $column LIKE ? ORDER BY id DESC LIMIT 1",
            [$pattern]
        );

        if ($lastRecord && !empty($lastRecord[$column])) {
            $parts = explode('-', $lastRecord[$column]);
            $sequence = (int)end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $today, $sequence);
    }

    /**
     * Generate standard Asset Code (e.g. AST-PRD-0012, AST-WHS-0005, AST-SHP-0003, AST-OFF-0001).
     */
    public static function generateAssetCode(string $locationType): string {
        $prefixMap = [
            'PRODUCTION' => 'AST-PRD',
            'WAREHOUSE'  => 'AST-WHS',
            'SHOP'       => 'AST-SHP',
            'OFFICE'     => 'AST-OFF',
        ];
        $prefix = $prefixMap[$locationType] ?? 'AST-GEN';
        
        $last = Database::fetchOne(
            "SELECT asset_code FROM assets WHERE asset_code LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '-%']
        );

        if ($last && !empty($last['asset_code'])) {
            $parts = explode('-', $last['asset_code']);
            $num = (int)end($parts) + 1;
        } else {
            $num = 1;
        }

        return sprintf('%s-%04d', $prefix, $num);
    }

    /**
     * Get all active asset categories.
     */
    public static function getCategories(): array {
        return Database::fetchAll("SELECT * FROM asset_categories WHERE is_active = 1 ORDER BY name ASC");
    }

    /**
     * Get structured map of all location entities across the enterprise.
     */
    public static function getLocationUnitsMap(): array {
        $productionUnits = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : [];
        $productionUnits = array_map(
            static fn(array $unit): array => array_merge($unit, ['manager' => $unit['manager_name'] ?? '']),
            $productionUnits
        );
        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];
        $shops = function_exists('getScopedShops') ? getScopedShops() : [];

        return [
            'production' => $productionUnits,
            'warehouses'  => $warehouses,
            'shops'       => $shops,
            'office'      => isSuperAdmin()
                ? [['id' => 1, 'name' => 'GRM Head Office & Admin', 'code' => 'HQ']]
                : [],
        ];
    }

    /**
     * Resolve human-readable location label for a location_type and location_id.
     */
    public static function resolveLocationName(string $locationType, int $locationId): string {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return self::fallbackLocationName($locationType, $locationId);
        }
        switch ($locationType) {
            case 'PRODUCTION':
                $pu = Database::fetchOne("SELECT name, code FROM production_units WHERE id = ? AND concern_id = ?", [$locationId, $concernId]);
                return $pu ? "🏭 " . ($pu['code'] ? "[{$pu['code']}] " : "") . $pu['name'] : "🏭 Production Unit #{$locationId}";
            case 'WAREHOUSE':
                $wh = Database::fetchOne("SELECT name, code FROM warehouses WHERE id = ? AND concern_id = ?", [$locationId, $concernId]);
                return $wh ? "🏢 " . ($wh['code'] ? "[{$wh['code']}] " : "") . $wh['name'] : "🏢 Warehouse #{$locationId}";
            case 'SHOP':
                $shp = Database::fetchOne("SELECT name, code FROM shops WHERE id = ? AND concern_id = ?", [$locationId, $concernId]);
                return $shp ? "🛍️ " . ($shp['code'] ? "[{$shp['code']}] " : "") . $shp['name'] : "🛍️ Shop #{$locationId}";
            case 'OFFICE':
            default:
                return "🏛️ Head Office & Logistics";
        }
    }

    private static function fallbackLocationName(string $locationType, int $locationId): string {
        return match ($locationType) {
            'PRODUCTION' => "🏭 Production Unit #{$locationId}",
            'WAREHOUSE' => "🏢 Warehouse #{$locationId}",
            'SHOP' => "🛍️ Shop #{$locationId}",
            default => "🏛️ Head Office & Logistics",
        };
    }

    /**
     * Calculate current written down book value.
     */
    public static function calculateCurrentBookValue(float $purchaseCost, float $salvageValue, float $annualRate, ?string $purchaseDate): float {
        if ($purchaseCost <= 0) return 0.00;
        if (empty($purchaseDate)) return $purchaseCost;

        $purchaseTs = strtotime($purchaseDate);
        if (!$purchaseTs) return $purchaseCost;

        $daysElapsed = max(0, (time() - $purchaseTs) / 86400);
        $yearsElapsed = $daysElapsed / 365.25;

        // Reducing balance / Straight-line approximation with salvage floor
        $depreciationFraction = ($annualRate / 100.0) * $yearsElapsed;
        $depreciableAmount = max(0, $purchaseCost - $salvageValue);
        $depreciationAmount = min($depreciableAmount, $depreciableAmount * $depreciationFraction);

        $bookValue = max($salvageValue, $purchaseCost - $depreciationAmount);
        return round($bookValue, 2);
    }

    /**
     * Get Dashboard KPI Metrics with optional Unit Filter.
     */
    public static function getDashboardMetrics(?string $locationType = null, ?int $locationId = null): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        [$scopeSql, $params] = self::assetConcernScope('a');
        $where = "WHERE {$scopeSql}";

        if (!empty($locationType)) {
            $where .= " AND a.location_type = ?";
            $params[] = $locationType;
            if (!empty($locationId)) {
                $where .= " AND a.location_id = ?";
                $params[] = $locationId;
            }
        }

        // Summary Counts & Capital Value
        $summary = Database::fetchOne("
            SELECT 
                COUNT(*) as total_assets,
                COALESCE(SUM(a.purchase_cost), 0) as total_purchase_cost,
                COALESCE(SUM(a.current_book_value), 0) as total_book_value,
                COALESCE(SUM(CASE WHEN a.status = 'IN_USE' THEN 1 ELSE 0 END), 0) as in_use_count,
                COALESCE(SUM(CASE WHEN a.status = 'IN_MAINTENANCE' THEN 1 ELSE 0 END), 0) as in_maintenance_count,
                COALESCE(SUM(CASE WHEN a.status = 'IDLE' THEN 1 ELSE 0 END), 0) as idle_count,
                COALESCE(SUM(CASE WHEN a.status = 'DAMAGED' THEN 1 ELSE 0 END), 0) as damaged_count,
                COALESCE(SUM(CASE WHEN a.condition_state IN ('POOR', 'CRITICAL') THEN 1 ELSE 0 END), 0) as critical_condition_count,
                COALESCE(SUM(CASE WHEN a.warranty_expiry IS NOT NULL AND a.warranty_expiry >= CURDATE() AND a.warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END), 0) as warranty_expiring_soon_count
            FROM assets a
            $where
        ", $params) ?: [];

        // Category Breakdown
        $categoryBreakdown = Database::fetchAll("
            SELECT 
                c.id, c.name, c.code, c.icon,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.current_book_value), 0) as total_value
            FROM asset_categories c
            LEFT JOIN assets a ON a.category_id = c.id $where
            GROUP BY c.id, c.name, c.code, c.icon
            ORDER BY asset_count DESC, c.name ASC
        ", $params);

        // Location Pillar Breakdown (Always global overview)
        $pillarStats = Database::fetchAll("
            SELECT 
                location_type,
                COUNT(*) as asset_count,
                COALESCE(SUM(current_book_value), 0) as total_value,
                COALESCE(SUM(CASE WHEN status = 'IN_USE' THEN 1 ELSE 0 END), 0) as active_count,
                COALESCE(SUM(CASE WHEN status = 'IN_MAINTENANCE' THEN 1 ELSE 0 END), 0) as maintenance_count
            FROM assets a
            $where
            GROUP BY location_type
        ", $params);

        // Specific Facility-by-Facility Breakdown across all units, warehouses, shops, HQ
        $facilityBreakdown = [];
        $productionUnitIds = array_map('intval', array_column(
            function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : [],
            'id'
        ));
        $warehouseIds = array_map('intval', array_column(
            function_exists('getScopedWarehouses') ? getScopedWarehouses() : [],
            'id'
        ));
        $shopIds = array_map('intval', array_column(
            function_exists('getScopedShops') ? getScopedShops() : [],
            'id'
        ));

        // 1. Production Units
        $productionUnitFilter = $productionUnitIds
            ? ' AND pu.id IN (' . implode(',', array_fill(0, count($productionUnitIds), '?')) . ')'
            : ' AND 1 = 0';
        $pus = Database::fetchAll("
            SELECT 
                pu.id, pu.name, pu.code, 'PRODUCTION' as location_type,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.current_book_value), 0) as total_value,
                COALESCE(SUM(CASE WHEN a.status = 'IN_USE' THEN 1 ELSE 0 END), 0) as in_use_count,
                COALESCE(SUM(CASE WHEN a.status = 'IN_MAINTENANCE' THEN 1 ELSE 0 END), 0) as in_maintenance_count,
                COALESCE(SUM(CASE WHEN a.status = 'DAMAGED' THEN 1 ELSE 0 END), 0) as damaged_count
            FROM production_units pu
            LEFT JOIN assets a ON a.location_type = 'PRODUCTION' AND a.location_id = pu.id
            WHERE pu.is_active = 1 AND pu.concern_id = ?{$productionUnitFilter}
            GROUP BY pu.id, pu.name, pu.code
            ORDER BY pu.id ASC
        ", array_merge([$concernId ?? 0], $productionUnitIds));
        foreach ($pus as $p) {
            $facilityBreakdown[] = [
                'type'             => 'PRODUCTION',
                'id'               => (int)$p['id'],
                'label'            => "🏭 " . ($p['code'] ? "[{$p['code']}] " : "") . $p['name'],
                'asset_count'      => (int)$p['asset_count'],
                'total_value'      => (float)$p['total_value'],
                'in_use_count'     => (int)$p['in_use_count'],
                'in_maintenance'   => (int)$p['in_maintenance_count'],
                'damaged_count'    => (int)$p['damaged_count'],
            ];
        }

        // 2. Warehouses
        $warehouseFilter = $warehouseIds
            ? ' AND w.id IN (' . implode(',', array_fill(0, count($warehouseIds), '?')) . ')'
            : ' AND 1 = 0';
        $whs = Database::fetchAll("
            SELECT 
                w.id, w.name, w.code, 'WAREHOUSE' as location_type,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.current_book_value), 0) as total_value,
                COALESCE(SUM(CASE WHEN a.status = 'IN_USE' THEN 1 ELSE 0 END), 0) as in_use_count,
                COALESCE(SUM(CASE WHEN a.status = 'IN_MAINTENANCE' THEN 1 ELSE 0 END), 0) as in_maintenance_count,
                COALESCE(SUM(CASE WHEN a.status = 'DAMAGED' THEN 1 ELSE 0 END), 0) as damaged_count
            FROM warehouses w
            LEFT JOIN assets a ON a.location_type = 'WAREHOUSE' AND a.location_id = w.id
            WHERE w.is_active = 1 AND w.concern_id = ?{$warehouseFilter}
            GROUP BY w.id, w.name, w.code
            ORDER BY w.id ASC
        ", array_merge([$concernId ?? 0], $warehouseIds));
        foreach ($whs as $w) {
            $facilityBreakdown[] = [
                'type'             => 'WAREHOUSE',
                'id'               => (int)$w['id'],
                'label'            => "🏢 " . ($w['code'] ? "[{$w['code']}] " : "") . $w['name'],
                'asset_count'      => (int)$w['asset_count'],
                'total_value'      => (float)$w['total_value'],
                'in_use_count'     => (int)$w['in_use_count'],
                'in_maintenance'   => (int)$w['in_maintenance_count'],
                'damaged_count'    => (int)$w['damaged_count'],
            ];
        }

        // 3. Shops
        $shopFilter = $shopIds
            ? ' AND s.id IN (' . implode(',', array_fill(0, count($shopIds), '?')) . ')'
            : ' AND 1 = 0';
        $shs = Database::fetchAll("
            SELECT 
                s.id, s.name, s.code, 'SHOP' as location_type,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.current_book_value), 0) as total_value,
                COALESCE(SUM(CASE WHEN a.status = 'IN_USE' THEN 1 ELSE 0 END), 0) as in_use_count,
                COALESCE(SUM(CASE WHEN a.status = 'IN_MAINTENANCE' THEN 1 ELSE 0 END), 0) as in_maintenance_count,
                COALESCE(SUM(CASE WHEN a.status = 'DAMAGED' THEN 1 ELSE 0 END), 0) as damaged_count
            FROM shops s
            LEFT JOIN assets a ON a.location_type = 'SHOP' AND a.location_id = s.id
            WHERE s.is_active = 1 AND s.concern_id = ?{$shopFilter}
            GROUP BY s.id, s.name, s.code
            ORDER BY s.id ASC
        ", array_merge([$concernId ?? 0], $shopIds));
        foreach ($shs as $s) {
            $facilityBreakdown[] = [
                'type'             => 'SHOP',
                'id'               => (int)$s['id'],
                'label'            => "🛍️ " . ($s['code'] ? "[{$s['code']}] " : "") . $s['name'],
                'asset_count'      => (int)$s['asset_count'],
                'total_value'      => (float)$s['total_value'],
                'in_use_count'     => (int)$s['in_use_count'],
                'in_maintenance'   => (int)$s['in_maintenance_count'],
                'damaged_count'    => (int)$s['damaged_count'],
            ];
        }

        // 4. Head Office assets are visible only to Super Admin.
        $hq = isSuperAdmin() ? Database::fetchOne("
            SELECT 
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.current_book_value), 0) as total_value,
                COALESCE(SUM(CASE WHEN a.status = 'IN_USE' THEN 1 ELSE 0 END), 0) as in_use_count,
                COALESCE(SUM(CASE WHEN a.status = 'IN_MAINTENANCE' THEN 1 ELSE 0 END), 0) as in_maintenance_count,
                COALESCE(SUM(CASE WHEN a.status = 'DAMAGED' THEN 1 ELSE 0 END), 0) as damaged_count
            FROM assets a
            WHERE a.location_type = 'OFFICE'
        ") : [];
        $facilityBreakdown[] = [
            'type'             => 'OFFICE',
            'id'               => 1,
            'label'            => "🏛️ [HQ] GRM Head Office & Admin",
            'asset_count'      => (int)($hq['asset_count'] ?? 0),
            'total_value'      => (float)($hq['total_value'] ?? 0),
            'in_use_count'     => (int)($hq['in_use_count'] ?? 0),
            'in_maintenance'   => (int)($hq['in_maintenance_count'] ?? 0),
            'damaged_count'    => (int)($hq['damaged_count'] ?? 0),
        ];

        // Monthly Maintenance Spending Trends (Last 6 Months)
        [$assetScopeSql, $assetScopeParams] = self::assetConcernScope('a');
        $monthlyTrend = Database::fetchAll("
            SELECT 
                DATE_FORMAT(m.service_date, '%b %Y') as month_label,
                DATE_FORMAT(m.service_date, '%Y-%m') as ym_code,
                COUNT(m.id) as tickets_count,
                COALESCE(SUM(m.service_cost), 0) as total_cost,
                COALESCE(SUM(m.downtime_hours), 0) as total_downtime
            FROM asset_maintenance_logs m
            JOIN assets a ON m.asset_id = a.id
            WHERE {$assetScopeSql}
              AND m.service_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY ym_code, month_label
            ORDER BY ym_code ASC
        ", $assetScopeParams);

        // Service Due Soon
        $serviceDue = Database::fetchAll("
            SELECT m.*, a.name as asset_name, a.asset_code, a.location_type, a.location_id
            FROM asset_maintenance_logs m
            JOIN assets a ON m.asset_id = a.id
            WHERE {$assetScopeSql}
              AND m.next_due_date IS NOT NULL
              AND m.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY m.next_due_date ASC
            LIMIT 5
        ", $assetScopeParams);

        // Recent Inter-Unit Transfers
        $recentTransfers = Database::fetchAll("
            SELECT t.*, a.name as asset_name, a.asset_code, a.barcode_qr
            FROM asset_transfers t
            JOIN assets a ON t.asset_id = a.id
            WHERE {$assetScopeSql}
            ORDER BY t.transfer_date DESC
            LIMIT 5
        ", $assetScopeParams);

        return [
            'summary'           => $summary,
            'categoryBreakdown' => $categoryBreakdown,
            'pillarStats'       => $pillarStats,
            'facilityBreakdown' => $facilityBreakdown,
            'monthlyTrend'      => $monthlyTrend,
            'serviceDue'        => $serviceDue,
            'recentTransfers'   => $recentTransfers,
        ];
    }

    /**
     * Get Filtered Asset List.
     */
    public static function getAssetsList(array $filters = []): array {
        [$scopeSql, $params] = self::assetConcernScope('a');
        $where = "WHERE {$scopeSql}";

        if (!empty($filters['location_type'])) {
            $where .= " AND a.location_type = ?";
            $params[] = $filters['location_type'];
        }

        if (!empty($filters['location_id'])) {
            $where .= " AND a.location_id = ?";
            $params[] = (int)$filters['location_id'];
        }

        if (!empty($filters['category_id'])) {
            $where .= " AND a.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $where .= " AND a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['condition_state'])) {
            $where .= " AND a.condition_state = ?";
            $params[] = $filters['condition_state'];
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $where .= " AND (a.name LIKE ? OR a.asset_code LIKE ? OR a.barcode_qr LIKE ? OR a.serial_number LIKE ? OR a.brand LIKE ? OR a.model_number LIKE ? OR a.floor_area LIKE ?)";
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s]);
        }

        $sql = "
            SELECT 
                a.*,
                c.name as category_name,
                c.code as category_code,
                c.icon as category_icon,
                c.depreciation_rate_annual
            FROM assets a
            JOIN asset_categories c ON a.category_id = c.id
            $where
            ORDER BY a.id DESC
        ";

        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            $sql .= " LIMIT $offset, $limit";
        }

        $assets = Database::fetchAll($sql, $params);

        // Enhance with resolved location names
        foreach ($assets as &$asset) {
            $asset['location_display'] = self::resolveLocationName($asset['location_type'], (int)$asset['location_id']);
        }

        return $assets;
    }

    /**
     * Get Single Asset by ID with full 360 profile.
     */
    public static function getAssetById(int $id): ?array {
        [$scopeSql, $scopeParams] = self::assetConcernScope('a');
        $asset = Database::fetchOne("
            SELECT 
                a.*,
                c.name as category_name,
                c.code as category_code,
                c.icon as category_icon,
                c.depreciation_rate_annual,
                u.full_name as created_by_name
            FROM assets a
            JOIN asset_categories c ON a.category_id = c.id
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.id = ? AND {$scopeSql}
        ", array_merge([$id], $scopeParams));

        if (!$asset) return null;

        $asset['location_display'] = self::resolveLocationName($asset['location_type'], (int)$asset['location_id']);

        // Transfer history
        $transfers = Database::fetchAll("
            SELECT t.*, u.full_name as transferred_by_name
            FROM asset_transfers t
            JOIN assets a ON t.asset_id = a.id
            LEFT JOIN users u ON t.transferred_by = u.id
            WHERE t.asset_id = ? AND {$scopeSql}
            ORDER BY t.transfer_date DESC
        ", array_merge([$id], $scopeParams));

        foreach ($transfers as &$trf) {
            $trf['from_display'] = self::resolveLocationName($trf['from_location_type'], (int)$trf['from_location_id']);
            $trf['to_display']   = self::resolveLocationName($trf['to_location_type'], (int)$trf['to_location_id']);
        }

        // Maintenance history
        $maintenanceLogs = Database::fetchAll("
            SELECT m.*, u.full_name as logged_by_name
            FROM asset_maintenance_logs m
            JOIN assets a ON m.asset_id = a.id
            LEFT JOIN users u ON m.created_by = u.id
            WHERE m.asset_id = ? AND {$scopeSql}
            ORDER BY m.service_date DESC, m.id DESC
        ", array_merge([$id], $scopeParams));

        $asset['transfers'] = $transfers;
        $asset['maintenance_logs'] = $maintenanceLogs;

        return $asset;
    }

    /**
     * Find asset by QR code, Asset Tag Code, Serial Number, Model Number, or Invoice Number.
     */
    public static function findByCodeOrQR(string $code): ?array {
        $clean = trim($code);
        if ($clean === '') return null;

        [$scopeSql, $scopeParams] = self::assetConcernScope('a');
        // 1. Exact match across all identification tags
        $asset = Database::fetchOne("
            SELECT a.id FROM assets a
            WHERE {$scopeSql}
              AND (a.asset_code = ?
               OR a.barcode_qr = ?
               OR a.serial_number = ?
               OR a.model_number = ?
               OR a.invoice_number = ?)
            LIMIT 1
        ", array_merge($scopeParams, [$clean, $clean, $clean, $clean, $clean]));

        if ($asset) {
            return self::getAssetById((int)$asset['id']);
        }

        // 2. Fallback prefix/substring match if exact was not found
        $like = '%' . $clean . '%';
        $asset = Database::fetchOne("
            SELECT a.id FROM assets a
            WHERE {$scopeSql}
              AND (a.asset_code LIKE ?
               OR a.barcode_qr LIKE ?
               OR a.serial_number LIKE ?
               OR a.model_number LIKE ?
               OR a.invoice_number LIKE ?
               OR a.name LIKE ?
               OR a.brand LIKE ?)
            ORDER BY 
                CASE
                    WHEN a.asset_code = ? THEN 1
                    WHEN a.barcode_qr = ? THEN 2
                    WHEN a.serial_number = ? THEN 3
                    WHEN a.asset_code LIKE ? THEN 4
                    ELSE 5
                END, a.id DESC
            LIMIT 1
        ", array_merge($scopeParams, [$like, $like, $like, $like, $like, $like, $like, $clean, $clean, $clean, $clean . '%']));

        return $asset ? self::getAssetById((int)$asset['id']) : null;
    }

    /**
     * Search multiple assets matching tag, QR, serial, model, or keyword (returns list).
     */
    public static function searchMultiTags(string $query, int $limit = 8): array {
        $clean = trim($query);
        if ($clean === '') return [];

        $like = '%' . $clean . '%';
        [$scopeSql, $scopeParams] = self::assetConcernScope('a');
        $sql = "
            SELECT 
                a.*,
                c.name as category_name,
                c.icon as category_icon,
                c.depreciation_rate_annual
            FROM assets a
            JOIN asset_categories c ON a.category_id = c.id
            WHERE {$scopeSql} AND (
               a.asset_code LIKE ?
               OR a.barcode_qr LIKE ? 
               OR a.serial_number LIKE ? 
               OR a.model_number LIKE ?
               OR a.invoice_number LIKE ?
               OR a.name LIKE ?
               OR a.brand LIKE ?
               OR a.custodian_name LIKE ?
               OR a.floor_area LIKE ?
            )
            ORDER BY 
                CASE 
                    WHEN a.asset_code = ? THEN 1
                    WHEN a.barcode_qr = ? THEN 2
                    WHEN a.serial_number = ? THEN 3
                    WHEN a.asset_code LIKE ? THEN 4
                    ELSE 5
                END, a.id DESC
            LIMIT ?
        ";

        $rows = Database::fetchAll($sql, array_merge($scopeParams, [
            $like, $like, $like, $like, $like, $like, $like, $like, $like,
            $clean, $clean, $clean, $clean . '%',
            $limit
        ]));

        foreach ($rows as &$r) {
            $r['location_display'] = self::resolveLocationName($r['location_type'], (int)$r['location_id']);
        }

        return $rows;
    }

    /**
     * Create New Asset.
     */
    public static function createAsset(array $data, ?int $userId = null): int {
        $locationType = $data['location_type'] ?? 'PRODUCTION';
        $locationId = (int)($data['location_id'] ?? 0);
        self::assertLocationBelongsToCurrentConcern((string)$locationType, $locationId, true);
        $categoryId = (int)($data['category_id'] ?? 1);

        $assetCode = !empty($data['asset_code']) ? trim($data['asset_code']) : self::generateAssetCode($locationType);
        $barcodeQr = !empty($data['barcode_qr']) ? trim($data['barcode_qr']) : $assetCode;

        // Fetch category depreciation
        $cat = Database::fetchOne("SELECT depreciation_rate_annual FROM asset_categories WHERE id = ?", [$categoryId]);
        $depRate = (float)($cat['depreciation_rate_annual'] ?? 15.00);

        $purchaseCost = (float)($data['purchase_cost'] ?? 0.00);
        $salvageValue = (float)($data['salvage_value'] ?? 0.00);
        $purchaseDate = !empty($data['purchase_date']) ? $data['purchase_date'] : null;

        $currentBookValue = self::calculateCurrentBookValue($purchaseCost, $salvageValue, $depRate, $purchaseDate);

        $sql = "
            INSERT INTO assets (
                asset_code, name, category_id, location_type, location_id, floor_area,
                brand, model_number, serial_number, barcode_qr, specifications,
                purchase_date, purchase_cost, vendor_name, vendor_contact, invoice_number,
                warranty_expiry, useful_life_years, salvage_value, current_book_value,
                last_depreciation_date, status, condition_state, custodian_name,
                photo_url, invoice_file_url, notes, created_by, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                CURDATE(), ?, ?, ?,
                ?, ?, ?, ?, NOW(), NOW()
            )
        ";

        Database::execute($sql, [
            $assetCode,
            trim($data['name'] ?? 'New Asset'),
            $categoryId,
            $locationType,
            $locationId,
            trim($data['floor_area'] ?? ''),
            trim($data['brand'] ?? ''),
            trim($data['model_number'] ?? ''),
            trim($data['serial_number'] ?? ''),
            $barcodeQr,
            trim($data['specifications'] ?? ''),
            $purchaseDate,
            $purchaseCost,
            trim($data['vendor_name'] ?? ''),
            trim($data['vendor_contact'] ?? ''),
            trim($data['invoice_number'] ?? ''),
            !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null,
            (int)($data['useful_life_years'] ?? 5),
            $salvageValue,
            $currentBookValue,
            $data['status'] ?? 'IN_USE',
            $data['condition_state'] ?? 'GOOD',
            trim($data['custodian_name'] ?? ''),
            trim($data['photo_url'] ?? ''),
            trim($data['invoice_file_url'] ?? ''),
            trim($data['notes'] ?? ''),
            $userId
        ]);

        return (int)Database::getInstance()->lastInsertId();
    }

    /**
     * Update Asset.
     */
    public static function updateAsset(int $id, array $data, ?int $userId = null): bool {
        $asset = self::getAssetById($id);
        if (!$asset) return false;
        self::assertLocationBelongsToCurrentConcern(
            (string)($data['location_type'] ?? $asset['location_type']),
            (int)($data['location_id'] ?? $asset['location_id']),
            true
        );

        $categoryId = (int)($data['category_id'] ?? $asset['category_id']);
        $cat = Database::fetchOne("SELECT depreciation_rate_annual FROM asset_categories WHERE id = ?", [$categoryId]);
        $depRate = (float)($cat['depreciation_rate_annual'] ?? 15.00);

        $purchaseCost = isset($data['purchase_cost']) ? (float)$data['purchase_cost'] : (float)$asset['purchase_cost'];
        $salvageValue = isset($data['salvage_value']) ? (float)$data['salvage_value'] : (float)$asset['salvage_value'];
        $purchaseDate = !empty($data['purchase_date']) ? $data['purchase_date'] : $asset['purchase_date'];

        $currentBookValue = self::calculateCurrentBookValue($purchaseCost, $salvageValue, $depRate, $purchaseDate);

        $sql = "
            UPDATE assets SET
                name = ?,
                category_id = ?,
                location_type = ?,
                location_id = ?,
                floor_area = ?,
                brand = ?,
                model_number = ?,
                serial_number = ?,
                specifications = ?,
                purchase_date = ?,
                purchase_cost = ?,
                vendor_name = ?,
                vendor_contact = ?,
                invoice_number = ?,
                warranty_expiry = ?,
                useful_life_years = ?,
                salvage_value = ?,
                current_book_value = ?,
                status = ?,
                condition_state = ?,
                custodian_name = ?,
                photo_url = COALESCE(NULLIF(?, ''), photo_url),
                invoice_file_url = COALESCE(NULLIF(?, ''), invoice_file_url),
                notes = ?,
                updated_at = NOW()
            WHERE id = ?
        ";

        return Database::execute($sql, [
            trim($data['name'] ?? $asset['name']),
            $categoryId,
            $data['location_type'] ?? $asset['location_type'],
            (int)($data['location_id'] ?? $asset['location_id']),
            trim($data['floor_area'] ?? $asset['floor_area']),
            trim($data['brand'] ?? $asset['brand']),
            trim($data['model_number'] ?? $asset['model_number']),
            trim($data['serial_number'] ?? $asset['serial_number']),
            trim($data['specifications'] ?? $asset['specifications']),
            $purchaseDate,
            $purchaseCost,
            trim($data['vendor_name'] ?? $asset['vendor_name']),
            trim($data['vendor_contact'] ?? $asset['vendor_contact']),
            trim($data['invoice_number'] ?? $asset['invoice_number']),
            !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null,
            (int)($data['useful_life_years'] ?? $asset['useful_life_years']),
            $salvageValue,
            $currentBookValue,
            $data['status'] ?? $asset['status'],
            $data['condition_state'] ?? $asset['condition_state'],
            trim($data['custodian_name'] ?? $asset['custodian_name']),
            trim($data['photo_url'] ?? ''),
            trim($data['invoice_file_url'] ?? ''),
            trim($data['notes'] ?? $asset['notes']),
            $id
        ]);
    }

    /**
     * Delete Asset.
     */
    public static function deleteAsset(int $id): bool {
        if (!self::getAssetById($id)) {
            return false;
        }
        return Database::execute("DELETE FROM assets WHERE id = ?", [$id]);
    }

    /**
     * Inter-Unit Asset Transfer Execution.
     */
    public static function transferAsset(array $data, ?int $userId = null): int {
        $assetId = (int)$data['asset_id'];
        $asset = self::getAssetById($assetId);
        if (!$asset) {
            throw new Exception("Asset #{$assetId} not found.");
        }

        $toLocationType = $data['to_location_type'];
        $toLocationId   = (int)$data['to_location_id'];
        self::assertLocationBelongsToCurrentConcern((string)$toLocationType, $toLocationId, true);
        $toFloorArea    = trim($data['to_floor_area'] ?? '');
        $reason         = trim($data['reason'] ?? '');
        $notes          = trim($data['notes'] ?? '');

        $transferNumber = self::generateSequenceNumber('asset_transfers', 'transfer_number', 'TRF-AST');

        Database::beginTransaction();
        try {
            // 1. Record transfer log
            $sql = "
                INSERT INTO asset_transfers (
                    transfer_number, asset_id, from_location_type, from_location_id, from_floor_area,
                    to_location_type, to_location_id, to_floor_area, transfer_date, status,
                    transferred_by, reason, challan_notes, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, NOW(), 'COMPLETED',
                    ?, ?, ?, NOW(), NOW()
                )
            ";

            Database::execute($sql, [
                $transferNumber,
                $assetId,
                $asset['location_type'],
                $asset['location_id'],
                $asset['floor_area'],
                $toLocationType,
                $toLocationId,
                $toFloorArea,
                $userId,
                $reason,
                $notes
            ]);

            $transferId = (int)Database::getInstance()->lastInsertId();

            // 2. Update asset current location
            Database::execute("
                UPDATE assets SET
                    location_type = ?,
                    location_id = ?,
                    floor_area = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $toLocationType,
                $toLocationId,
                $toFloorArea,
                $assetId
            ]);

            Database::commit();
            return $transferId;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Record Maintenance / Repair Service Log.
     */
    public static function recordMaintenance(array $data, ?int $userId = null): int {
        $assetId = (int)$data['asset_id'];
        $asset = self::getAssetById($assetId);
        if (!$asset) {
            throw new Exception("Asset #{$assetId} not found.");
        }

        $logNumber = self::generateSequenceNumber('asset_maintenance_logs', 'log_number', 'MNT');
        $serviceType = $data['service_type'] ?? 'ROUTINE_PREVENTIVE';
        $status = $data['status'] ?? 'COMPLETED';

        Database::beginTransaction();
        try {
            $sql = "
                INSERT INTO asset_maintenance_logs (
                    log_number, asset_id, service_type, issue_description, action_taken,
                    serviced_by_vendor, technician_name, technician_contact, service_cost,
                    parts_replaced, service_date, next_due_date, downtime_hours, status,
                    created_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, NOW(), NOW()
                )
            ";

            Database::execute($sql, [
                $logNumber,
                $assetId,
                $serviceType,
                trim($data['issue_description'] ?? ''),
                trim($data['action_taken'] ?? ''),
                trim($data['serviced_by_vendor'] ?? ''),
                trim($data['technician_name'] ?? ''),
                trim($data['technician_contact'] ?? ''),
                (float)($data['service_cost'] ?? 0.00),
                trim($data['parts_replaced'] ?? ''),
                !empty($data['service_date']) ? $data['service_date'] : date('Y-m-d'),
                !empty($data['next_due_date']) ? $data['next_due_date'] : null,
                (float)($data['downtime_hours'] ?? 0.0),
                $status,
                $userId
            ]);

            $logId = (int)Database::getInstance()->lastInsertId();

            // Update Asset status & condition if specified
            $newAssetStatus = $data['asset_status'] ?? ($status === 'IN_PROGRESS' ? 'IN_MAINTENANCE' : 'IN_USE');
            $newCondition = $data['condition_state'] ?? $asset['condition_state'];

            Database::execute("
                UPDATE assets SET
                    status = ?,
                    condition_state = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [$newAssetStatus, $newCondition, $assetId]);

            Database::commit();
            return $logId;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Get All Inter-Unit Transfers Manifest List.
     */
    public static function getAllTransfers(array $filters = []): array {
        [$scopeSql, $params] = self::assetConcernScope('a');
        $where = "WHERE {$scopeSql}";

        if (!empty($filters['status'])) {
            $where .= " AND t.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $where .= " AND (t.transfer_number LIKE ? OR a.name LIKE ? OR a.asset_code LIKE ? OR t.reason LIKE ?)";
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        $sql = "
            SELECT 
                t.*,
                a.name as asset_name,
                a.asset_code,
                a.barcode_qr,
                c.name as category_name,
                c.icon as category_icon,
                u.full_name as transferred_by_name
            FROM asset_transfers t
            JOIN assets a ON t.asset_id = a.id
            JOIN asset_categories c ON a.category_id = c.id
            LEFT JOIN users u ON t.transferred_by = u.id
            $where
            ORDER BY t.transfer_date DESC, t.id DESC
        ";

        $transfers = Database::fetchAll($sql, $params);
        foreach ($transfers as &$trf) {
            $trf['from_display'] = self::resolveLocationName($trf['from_location_type'], (int)$trf['from_location_id']);
            $trf['to_display']   = self::resolveLocationName($trf['to_location_type'], (int)$trf['to_location_id']);
        }

        return $transfers;
    }

    /**
     * Get All Maintenance Service Tickets List.
     */
    public static function getAllMaintenanceLogs(array $filters = []): array {
        [$scopeSql, $params] = self::assetConcernScope('a');
        $where = "WHERE {$scopeSql}";

        if (!empty($filters['status'])) {
            $where .= " AND m.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['service_type'])) {
            $where .= " AND m.service_type = ?";
            $params[] = $filters['service_type'];
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $where .= " AND (m.log_number LIKE ? OR a.name LIKE ? OR a.asset_code LIKE ? OR m.technician_name LIKE ? OR m.serviced_by_vendor LIKE ? OR m.issue_description LIKE ?)";
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s]);
        }

        $sql = "
            SELECT 
                m.*,
                a.name as asset_name,
                a.asset_code,
                a.location_type,
                a.location_id,
                a.floor_area,
                c.name as category_name,
                c.icon as category_icon,
                u.full_name as logged_by_name
            FROM asset_maintenance_logs m
            JOIN assets a ON m.asset_id = a.id
            JOIN asset_categories c ON a.category_id = c.id
            LEFT JOIN users u ON m.created_by = u.id
            $where
            ORDER BY m.service_date DESC, m.id DESC
        ";

        $logs = Database::fetchAll($sql, $params);
        foreach ($logs as &$log) {
            $log['location_display'] = self::resolveLocationName($log['location_type'], (int)$log['location_id']);
        }

        return $logs;
    }

    /**
     * Create New Asset Category Dynamically.
     */
    public static function createCategory(array $data): array {
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new InvalidArgumentException("Category name is required.");
        }

        $code = trim($data['code'] ?? '');
        if (empty($code)) {
            $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 10));
            if (empty($code)) $code = 'CAT_' . time();
        }

        // Check duplicate code
        $existing = Database::fetchOne("SELECT id FROM asset_categories WHERE code = ?", [$code]);
        if ($existing) {
            $code = $code . '_' . rand(10, 99);
        }

        $depRate = isset($data['depreciation_rate_annual']) ? (float)$data['depreciation_rate_annual'] : 15.00;
        $icon = !empty($data['icon']) ? trim($data['icon']) : 'bi-cpu';
        $description = trim($data['description'] ?? '');

        Database::query("
            INSERT INTO asset_categories (name, code, icon, description, depreciation_rate_annual, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ", [$name, $code, $icon, $description, $depRate]);

        $catId = (int)Database::lastInsertId();

        return [
            'id'                       => $catId,
            'name'                     => $name,
            'code'                     => $code,
            'icon'                     => $icon,
            'depreciation_rate_annual' => $depRate,
            'description'              => $description,
        ];
    }

    /**
     * Create New Facility / Operational Unit Dynamically.
     */
    public static function createFacilityUnit(string $type, array $data): array {
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new InvalidArgumentException("Facility / Unit name is required.");
        }

        $code = trim($data['code'] ?? '');
        $address = trim($data['address'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $managerName = trim($data['manager_name'] ?? '');

        $type = strtoupper($type);
        if (!in_array($type, ['PRODUCTION', 'WAREHOUSE', 'SHOP'], true)) {
            throw new InvalidArgumentException("Invalid facility type: {$type}. Must be PRODUCTION, WAREHOUSE, or SHOP.");
        }
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null || !Concern::hasFacilityModules($concernId, $type)) {
            throw new InvalidArgumentException('Choose an active concern with a compatible module before creating a facility.');
        }

        $table = ['PRODUCTION' => 'production_units', 'WAREHOUSE' => 'warehouses', 'SHOP' => 'shops'][$type];
        $existing = $code === '' ? null : Database::fetchOne("SELECT id FROM {$table} WHERE code = ? AND concern_id = ?", [$code, $concernId]);
        if ($existing) {
            throw new InvalidArgumentException('A facility with this code already exists in the active concern.');
        }

        $facilityData = [
            'name' => $name,
            'code' => $code,
            'address' => $address,
            'phone' => $phone,
            'manager_name' => $managerName,
            'is_active' => 1,
            'concern_id' => $concernId,
        ];
        $unitId = match ($type) {
            'PRODUCTION' => ProductionUnit::create($facilityData),
            'WAREHOUSE' => Warehouse::create($facilityData),
            'SHOP' => Shop::create($facilityData),
        };

        return [
            'id'       => $unitId,
            'name'     => $name,
            'code'     => $code,
            'type'     => $type,
            'display'  => self::resolveLocationName($type, $unitId)
        ];
    }

    private static function assetConcernScope(string $alias): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return ['1 = 0', []];
        }
        $sql = "(
                ({$alias}.location_type = 'PRODUCTION' AND EXISTS (SELECT 1 FROM production_units pu_scope WHERE pu_scope.id = {$alias}.location_id AND pu_scope.concern_id = ?))
                OR ({$alias}.location_type = 'WAREHOUSE' AND EXISTS (SELECT 1 FROM warehouses w_scope WHERE w_scope.id = {$alias}.location_id AND w_scope.concern_id = ?))
                OR ({$alias}.location_type = 'SHOP' AND EXISTS (SELECT 1 FROM shops s_scope WHERE s_scope.id = {$alias}.location_id AND s_scope.concern_id = ?))
                OR ({$alias}.location_type = 'OFFICE' AND ? = 1)
            )";
        $params = [$concernId, $concernId, $concernId, isSuperAdmin() ? 1 : 0];
        foreach ([
            'PRODUCTION' => 'currentProductionUnitId',
            'WAREHOUSE' => 'currentWarehouseId',
            'SHOP' => 'currentShopId',
        ] as $locationType => $helper) {
            $assignedId = function_exists($helper) ? $helper() : null;
            if ($assignedId === 0) {
                $sql .= " AND {$alias}.location_type != '{$locationType}'";
            } elseif ($assignedId !== null) {
                $sql .= " AND ({$alias}.location_type != '{$locationType}' OR {$alias}.location_id = ?)";
                $params[] = $assignedId;
            }
        }
        return [$sql, $params];
    }

    private static function locationBelongsToCurrentConcern(string $locationType, int $locationId, bool $activeOnly = false): bool {
        if ($locationType === 'OFFICE') {
            return isSuperAdmin();
        }
        $tables = [
            'PRODUCTION' => ['production_units', 'currentProductionUnitId'],
            'WAREHOUSE' => ['warehouses', 'currentWarehouseId'],
            'SHOP' => ['shops', 'currentShopId'],
        ];
        $locationType = strtoupper($locationType);
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null || $locationId <= 0 || !isset($tables[$locationType])) {
            return false;
        }
        [$table, $assignmentHelper] = $tables[$locationType];
        $assignedId = function_exists($assignmentHelper) ? $assignmentHelper() : null;
        if ($assignedId === 0 || ($assignedId !== null && $assignedId !== $locationId)) {
            return false;
        }
        $activeClause = $activeOnly ? ' AND is_active = 1' : '';
        return (bool)Database::fetchOne(
            "SELECT id FROM {$table} WHERE id = ? AND concern_id = ?{$activeClause}",
            [$locationId, $concernId]
        );
    }

    private static function assertLocationBelongsToCurrentConcern(string $locationType, int $locationId, bool $activeOnly = false): void {
        if (!self::locationBelongsToCurrentConcern($locationType, $locationId, $activeOnly)) {
            throw new InvalidArgumentException('The selected asset location must be active and belong to the active concern.');
        }
    }
}
