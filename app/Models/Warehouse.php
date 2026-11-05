<?php
/**
 * Warehouse Model
 * Handles warehouse locations, lookups, managers, and inventory metadata.
 */

class Warehouse {

    private static bool $schemaChecked = false;

    /**
     * Ensure database table schema has required columns.
     */
    public static function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;
        try {
            $col = Database::fetchOne("SHOW COLUMNS FROM warehouses LIKE 'manager_name'");
            if (!$col) {
                Database::execute("ALTER TABLE warehouses ADD COLUMN manager_name VARCHAR(150) NULL AFTER manager_id");
            }
        } catch (Throwable $e) {}
    }

    /**
     * Find warehouse by ID.
     */
    public static function find(int $id): ?array {
        self::ensureSchema();
        return Database::fetchOne(
            "SELECT w.*, COALESCE(NULLIF(w.manager_name, ''), u.full_name) as manager_name, u.email as manager_email, u.phone as manager_phone
             FROM warehouses w
             LEFT JOIN users u ON w.manager_id = u.id
             WHERE w.id = ?",
            [$id]
        );
    }

    /**
     * Find warehouse by code.
     */
    public static function findByCode(string $code): ?array {
        self::ensureSchema();
        return Database::fetchOne(
            "SELECT w.*, COALESCE(NULLIF(w.manager_name, ''), u.full_name) as manager_name
             FROM warehouses w
             LEFT JOIN users u ON w.manager_id = u.id
             WHERE w.code = ?",
            [$code]
        );
    }

    /**
     * Get all active warehouses.
     */
    public static function getAllActive(): array {
        return self::getAll(['is_active' => 1]);
    }

    /**
     * Get all warehouses.
     */
    public static function getAll(array $filters = []): array {
        self::ensureSchema();
        $sql = "SELECT w.*, COALESCE(NULLIF(w.manager_name, ''), u.full_name) as manager_name
                FROM warehouses w
                LEFT JOIN users u ON w.manager_id = u.id
                WHERE 1=1";
        $params = [];

        if (isset($filters['is_active'])) {
            $sql .= " AND w.is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }

        // Automatic warehouse scoping for assigned warehouse staff
        if (empty($filters['unscoped']) && function_exists('currentWarehouseId')) {
            $scopedId = currentWarehouseId();
            if ($scopedId !== null) {
                $sql .= " AND w.id = ?";
                $params[] = $scopedId;
            } elseif (!empty($filters['warehouse_id'])) {
                $sql .= " AND w.id = ?";
                $params[] = (int)$filters['warehouse_id'];
            }
        } elseif (!empty($filters['warehouse_id'])) {
            $sql .= " AND w.id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (w.name LIKE ? OR w.code LIKE ? OR w.manager_name LIKE ? OR w.address LIKE ? OR w.phone LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY w.name ASC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Create warehouse.
     */
    public static function create(array $data): int {
        self::ensureSchema();
        Database::execute(
            "INSERT INTO warehouses (name, code, manager_name, address, phone, manager_id, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                trim($data['name']),
                strtoupper(trim($data['code'])),
                trim($data['manager_name'] ?? ''),
                $data['address'] ?? null,
                trim($data['phone'] ?? ''),
                !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                $data['is_active'] ?? 1
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update warehouse.
     */
    public static function update(int $id, array $data): bool {
        self::ensureSchema();
        return Database::execute(
            "UPDATE warehouses 
             SET name = ?, code = ?, manager_name = ?, address = ?, phone = ?, manager_id = ?, is_active = ?, updated_at = NOW()
             WHERE id = ?",
            [
                trim($data['name']),
                strtoupper(trim($data['code'])),
                trim($data['manager_name'] ?? ''),
                $data['address'] ?? null,
                trim($data['phone'] ?? ''),
                !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id
            ]
        ) >= 0;
    }

    /**
     * Get all warehouses with rich operational metrics (stock units, valuation, storage locations).
     */
    public static function getWithMetrics(bool $unscoped = false): array {
        $warehouses = self::getAll($unscoped ? ['unscoped' => true] : []);

        foreach ($warehouses as &$wh) {
            $whId = (int)$wh['id'];

            // Available stock and total stock count
            $stockStats = Database::fetchOne(
                "SELECT COALESCE(SUM(CASE WHEN sb.stock_state = 'AVAILABLE' THEN sb.quantity ELSE 0 END), 0) as available_units,
                        COALESCE(SUM(sb.quantity), 0) as total_units,
                        COALESCE(SUM(CASE WHEN sb.stock_state = 'AVAILABLE' THEN sb.quantity * pv.selling_price ELSE 0 END), 0) as available_valuation,
                        COUNT(DISTINCT sb.variant_id) as total_skus
                 FROM stock_balances sb
                 JOIN product_variants pv ON sb.variant_id = pv.id
                 WHERE sb.location_type = 'WAREHOUSE' AND sb.location_id = ?",
                [$whId]
            );

            // Storage locations (racks/bins)
            $locCount = (int)(Database::fetchOne(
                "SELECT COUNT(id) as cnt FROM locations WHERE warehouse_id = ? AND is_active = 1",
                [$whId]
            )['cnt'] ?? 0);

            // Active allocations count
            $allocCount = (int)(Database::fetchOne(
                "SELECT COUNT(id) as cnt FROM warehouse_allocations 
                 WHERE warehouse_id = ? AND status IN ('REQUESTED', 'STOCK_CHECK', 'RESERVED', 'APPROVED', 'PICKING', 'PACKING')",
                [$whId]
            )['cnt'] ?? 0);

            $wh['available_units']     = (float)($stockStats['available_units'] ?? 0);
            $wh['total_units']         = (float)($stockStats['total_units'] ?? 0);
            $wh['available_valuation'] = (float)($stockStats['available_valuation'] ?? 0);
            $wh['total_skus']          = (int)($stockStats['total_skus'] ?? 0);
            $wh['locations_count']     = $locCount;
            $wh['active_allocations']  = $allocCount;
        }
        unset($wh);

        return $warehouses;
    }

    /**
     * Delete warehouse.
     */
    public static function delete(int $id): bool {
        return Database::execute("DELETE FROM warehouses WHERE id = ?", [$id]) > 0;
    }

    /**
     * Toggle active status.
     */
    public static function toggleActive(int $id): bool {
        return Database::execute(
            "UPDATE warehouses SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Count warehouses.
     */
    public static function count(array $filters = []): int {
        $sql = "SELECT COUNT(*) as cnt FROM warehouses WHERE 1=1";
        $params = [];
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }
        $row = Database::fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }
}
