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
        return Database::fetchOne(
            "SELECT s.*, COALESCE(NULLIF(s.manager_name, ''), u.full_name) as manager_name, u.email as manager_email, u.phone as manager_phone
             FROM shops s 
             LEFT JOIN users u ON s.manager_id = u.id 
             WHERE s.id = ?",
            [$id]
        );
    }

    /**
     * Find shop by code.
     */
    public static function findByCode(string $code): ?array {
        self::ensureSchema();
        return Database::fetchOne(
            "SELECT s.*, COALESCE(NULLIF(s.manager_name, ''), u.full_name) as manager_name 
             FROM shops s 
             LEFT JOIN users u ON s.manager_id = u.id 
             WHERE s.code = ?",
            [$code]
        );
    }

    /**
     * Get all shops with optional filtering.
     */
    public static function getAll(array $filters = []): array {
        self::ensureSchema();
        $sql = "SELECT s.*, COALESCE(NULLIF(s.manager_name, ''), u.full_name) as manager_name 
                FROM shops s 
                LEFT JOIN users u ON s.manager_id = u.id 
                WHERE 1=1";
        $params = [];

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
        $code = strtoupper(trim($data['code'] ?? ''));
        if (empty($code)) {
            $num = 1;
            do {
                $code = 'SH-' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
                $exists = self::findByCode($code);
                if ($exists) {
                    $num++;
                } else {
                    break;
                }
            } while (true);
        }

        Database::execute(
            "INSERT INTO shops (name, code, manager_name, address, phone, manager_id, gst_number, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                trim($data['name']),
                $code,
                trim($data['manager_name'] ?? ''),
                $data['address'] ?? null,
                trim($data['phone'] ?? ''),
                !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                $data['gst_number'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update shop.
     */
    public static function update(int $id, array $data): bool {
        self::ensureSchema();
        return Database::execute(
            "UPDATE shops 
             SET name = ?, code = ?, manager_name = ?, address = ?, phone = ?, manager_id = ?, gst_number = ?, is_active = ?, updated_at = NOW()
             WHERE id = ?",
            [
                trim($data['name']),
                strtoupper(trim($data['code'])),
                trim($data['manager_name'] ?? ''),
                $data['address'] ?? null,
                trim($data['phone'] ?? ''),
                !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                $data['gst_number'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id
            ]
        ) >= 0;
    }

    /**
     * Delete shop.
     */
    public static function delete(int $id): bool {
        return Database::execute("DELETE FROM shops WHERE id = ?", [$id]) > 0;
    }

    /**
     * Toggle active status.
     */
    public static function toggleActive(int $id): bool {
        return Database::execute(
            "UPDATE shops SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?",
            [$id]
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
        $sql = "SELECT COUNT(*) as cnt FROM shops WHERE 1=1";
        $params = [];
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }
        $row = Database::fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }
}
