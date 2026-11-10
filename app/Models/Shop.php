<?php
/**
 * Shop Model
 * Retail store locations, POS counters, managers, and live sales metrics.
 */

class Shop {

    private static bool $schemaChecked = false;

    /**
     * Ensure database table schema has required columns.
     */
    public static function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;
        try {
            $col = Database::fetchOne("SHOW COLUMNS FROM shops LIKE 'manager_name'");
            if (!$col) {
                Database::execute("ALTER TABLE shops ADD COLUMN manager_name VARCHAR(150) NULL AFTER manager_id");
            }
        } catch (Throwable $e) {}
    }

    /**
     * Find shop by ID.
     */
    public static function find(int $id): ?array {
        self::ensureSchema();
        $concernId = self::currentConcernScopeId();
        if ($concernId === null) {
            return null;
        }
        $assignedId = function_exists('currentShopId') ? currentShopId() : null;
        if ($assignedId !== null && $assignedId !== $id) {
            return null;
        }
        return Database::fetchOne(
            "SELECT s.*, COALESCE(NULLIF(s.manager_name, ''), u.full_name) as manager_name, u.email as manager_email, u.phone as manager_phone
             FROM shops s 
             LEFT JOIN users u ON s.manager_id = u.id 
             WHERE s.id = ? AND s.concern_id = ?",
            [$id, $concernId]
        );
    }

    /**
     * Find shop by code.
     */
    public static function findByCode(string $code): ?array {
        self::ensureSchema();
        $concernId = self::currentConcernScopeId();
        if ($concernId === null) {
            return null;
        }
        $assignedId = function_exists('currentShopId') ? currentShopId() : null;
        if ($assignedId === 0) {
            return null;
        }
        if ($assignedId !== null && !Database::fetchOne(
            "SELECT id FROM shops WHERE code = ? AND concern_id = ? AND id = ?",
            [$code, $concernId, $assignedId]
        )) {
            return null;
        }
        return Database::fetchOne(
            "SELECT s.*, COALESCE(NULLIF(s.manager_name, ''), u.full_name) as manager_name 
             FROM shops s 
             LEFT JOIN users u ON s.manager_id = u.id 
             WHERE s.code = ? AND s.concern_id = ?",
            [$code, $concernId]
        );
    }

    /**
     * Get all shops with optional filtering.
     */
    public static function getAll(array $filters = []): array {
        self::ensureSchema();
        $concernId = self::currentConcernScopeId();
        if ($concernId === null) {
            return [];
        }
        $sql = "SELECT s.*, COALESCE(NULLIF(s.manager_name, ''), u.full_name) as manager_name 
                FROM shops s 
                LEFT JOIN users u ON s.manager_id = u.id 
                WHERE s.concern_id = ?";
        $params = [$concernId];
        $assignedId = function_exists('currentShopId') ? currentShopId() : null;
        if ($assignedId === 0) {
            return [];
        }
        if ($assignedId !== null) {
            $sql .= " AND s.id = ?";
            $params[] = $assignedId;
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND s.is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.name LIKE ? OR s.code LIKE ? OR s.manager_name LIKE ? OR s.phone LIKE ? OR s.address LIKE ? OR s.gst_number LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search, $search, $search]);
        }

        $sql .= " ORDER BY s.name ASC";
        $rows = Database::fetchAll($sql, $params);

        return $rows;
    }

    /**
     * Get all shops with operational metrics (stock counts, today's sales, month's sales).
     */
    public static function getWithMetrics(): array {
        $shops = self::getAll();

        $today = date('Y-m-d');
        foreach ($shops as &$shop) {
            $shopId = (int)$shop['id'];

            // Shop stock counts
            $stockStats = Database::fetchOne(
                "SELECT COALESCE(SUM(CASE WHEN sb.stock_state = 'AVAILABLE' THEN sb.quantity ELSE 0 END), 0) as available_units,
                        COALESCE(SUM(sb.quantity), 0) as total_units,
                        COUNT(DISTINCT sb.variant_id) as total_skus
                 FROM stock_balances sb
                 WHERE sb.location_type = 'SHOP' AND sb.location_id = ?",
                [$shopId]
            );

            // Today's Sales
            $todayStats = Database::fetchOne(
                "SELECT COUNT(id) as today_invoices,
                        COALESCE(SUM(total_amount), 0) as today_revenue
                 FROM sales
                 WHERE shop_id = ? AND sale_type = 'SHOP' AND DATE(created_at) = ? AND status != 'CANCELLED'",
                [$shopId, $today]
            );

            // This Month's Sales
            $monthStats = Database::fetchOne(
                "SELECT COUNT(id) as month_invoices,
                        COALESCE(SUM(total_amount), 0) as month_revenue
                 FROM sales
                 WHERE shop_id = ? AND sale_type = 'SHOP' 
                   AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                   AND YEAR(created_at) = YEAR(CURRENT_DATE()) 
                   AND status != 'CANCELLED'",
                [$shopId]
            );

            $shop['available_units'] = (float)($stockStats['available_units'] ?? 0);
            $shop['total_units']     = (float)($stockStats['total_units'] ?? 0);
            $shop['total_skus']      = (int)($stockStats['total_skus'] ?? 0);
            $shop['today_invoices']  = (int)($todayStats['today_invoices'] ?? 0);
            $shop['today_revenue']   = (float)($todayStats['today_revenue'] ?? 0);
            $shop['month_invoices']  = (int)($monthStats['month_invoices'] ?? 0);
            $shop['month_revenue']   = (float)($monthStats['month_revenue'] ?? 0);
        }
        unset($shop);

        return $shops;
    }

    /**
     * Create shop.
     */
    public static function create(array $data): int {
        self::ensureSchema();
        $concernId = (int)($data['concern_id'] ?? 0);
        $activeConcernId = self::currentConcernScopeId();
        if ($concernId <= 0 || $activeConcernId === null || $concernId !== $activeConcernId || !Concern::hasFacilityModules($concernId, 'SHOP') || !Database::fetchOne(
            "SELECT id FROM concerns WHERE id = ? AND is_active = 1",
            [$concernId]
        )) {
            throw new InvalidArgumentException('Choose an active concern before creating a shop.');
        }
        $code = strtoupper(trim($data['code'] ?? ''));
        if ($code === '') {
            $num = 1;
            do {
                $code = 'SH-C' . $concernId . '-' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
                $exists = Database::fetchOne("SELECT id FROM shops WHERE code = ?", [$code]);
                $num++;
            } while ($exists);
        }
        Database::execute(
            "INSERT INTO shops (name, code, manager_name, address, phone, manager_id, gst_number, is_active, concern_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                trim($data['name']),
                $code,
                trim($data['manager_name'] ?? ''),
                $data['address'] ?? null,
                trim($data['phone'] ?? ''),
                !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                $data['gst_number'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $concernId
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update shop.
     */
    public static function update(int $id, array $data): bool {
        self::ensureSchema();
        $concernId = self::currentConcernScopeId();
        if ($concernId === null || !self::find($id)) {
            return false;
        }
        return Database::execute(
            "UPDATE shops 
             SET name = ?, code = ?, manager_name = ?, address = ?, phone = ?, manager_id = ?, gst_number = ?, is_active = ?, updated_at = NOW()
             WHERE id = ? AND concern_id = ?",
            [
                trim($data['name']),
                strtoupper(trim($data['code'])),
                trim($data['manager_name'] ?? ''),
                $data['address'] ?? null,
                trim($data['phone'] ?? ''),
                !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                $data['gst_number'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id,
                $concernId
            ]
        ) >= 0;
    }

    /**
     * Delete shop.
     */
    public static function delete(int $id): bool {
        $concernId = self::currentConcernScopeId();
        return $concernId !== null && self::find($id) !== null
            && Database::execute("DELETE FROM shops WHERE id = ? AND concern_id = ?", [$id, $concernId]) > 0;
    }

    /**
     * Toggle active status.
     */
    public static function toggleActive(int $id): bool {
        $concernId = self::currentConcernScopeId();
        if ($concernId === null || self::find($id) === null) {
            return false;
        }
        return Database::execute(
            "UPDATE shops SET is_active = NOT is_active, updated_at = NOW() WHERE id = ? AND concern_id = ?",
            [$id, $concernId]
        ) > 0;
    }

    /**
     * Get active shops.
     */
    public static function getActive(): array {
        return self::getAll(['is_active' => 1]);
    }

    /**
     * Count shops.
     */
    public static function count(array $filters = []): int {
        $concernId = self::currentConcernScopeId();
        if ($concernId === null) {
            return 0;
        }
        $sql = "SELECT COUNT(*) as cnt FROM shops WHERE concern_id = ?";
        $params = [$concernId];
        $assignedId = function_exists('currentShopId') ? currentShopId() : null;
        if ($assignedId === 0) {
            return 0;
        }
        if ($assignedId !== null) {
            $sql .= " AND id = ?";
            $params[] = $assignedId;
        }
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }
        $row = Database::fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }

    private static function currentConcernScopeId(): ?int {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        return $concernId !== null && $concernId > 0 ? $concernId : null;
    }
}
