<?php
/**
 * ProductionUnit Model
 * Handles production unit facilities, managers, programs, and operational capacity.
 */

class ProductionUnit {

    private static bool $schemaChecked = false;

    /**
     * Ensure database table schema has required columns.
     */
    public static function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;
        try {
            $col = Database::fetchOne("SHOW COLUMNS FROM production_units LIKE 'manager_name'");
            if (!$col) {
                Database::execute("ALTER TABLE production_units ADD COLUMN manager_name VARCHAR(150) NULL AFTER manager_id");
            }
        } catch (Throwable $e) {}
    }

    /**
     * Find a production unit by ID.
     */
    public static function find(int $id): ?array {
        self::ensureSchema();
        return Database::fetchOne(
            "SELECT pu.*, COALESCE(NULLIF(pu.manager_name, ''), u.full_name) as manager_name, u.email as manager_email, u.phone as manager_phone
             FROM production_units pu
             LEFT JOIN users u ON pu.manager_id = u.id
             WHERE pu.id = ?",
            [$id]
        );
    }

    /**
     * Find a production unit by unique code.
     */
    public static function findByCode(string $code): ?array {
        self::ensureSchema();
        return Database::fetchOne(
            "SELECT pu.*, COALESCE(NULLIF(pu.manager_name, ''), u.full_name) as manager_name
             FROM production_units pu
             LEFT JOIN users u ON pu.manager_id = u.id
             WHERE pu.code = ?",
            [$code]
        );
    }

    /**
     * Get all production units with optional filtering.
     */
    public static function getAll(array $filters = []): array {
        self::ensureSchema();
        $sql = "SELECT pu.*, COALESCE(NULLIF(pu.manager_name, ''), u.full_name) as manager_name
                FROM production_units pu
                LEFT JOIN users u ON pu.manager_id = u.id
                WHERE 1=1";
        $params = [];

        if (isset($filters['is_active'])) {
            $sql .= " AND pu.is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (pu.name LIKE ? OR pu.code LIKE ? OR pu.manager_name LIKE ? OR pu.address LIKE ? OR pu.phone LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY pu.name ASC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Get all production units with rich operational metrics for dashboard display.
     */
    public static function getWithMetrics(): array {
        $units = self::getAll();

        foreach ($units as &$unit) {
            $unitId = (int)$unit['id'];

            // Active programs count
            $progStats = Database::fetchOne(
                "SELECT COUNT(id) as total_programs,
                        SUM(CASE WHEN status IN ('DRAFT', 'APPROVED', 'SCHEDULED', 'IN_PROGRESS') THEN 1 ELSE 0 END) as active_programs,
                        COALESCE(SUM(planned_quantity), 0) as total_planned_qty,
                        COALESCE(SUM(completed_quantity), 0) as total_completed_qty
                 FROM production_programs
                 WHERE production_unit_id = ?",
                [$unitId]
            );

            // Cutting batches count
            $cutStats = Database::fetchOne(
                "SELECT COUNT(id) as total_cutting,
                        COALESCE(SUM(cut_qty), 0) as total_cut_qty,
                        COALESCE(SUM(wastage_qty), 0) as total_wastage_qty
                 FROM cutting_batches
                 WHERE production_unit_id = ?",
                [$unitId]
            );

            $unit['total_programs']     = (int)($progStats['total_programs'] ?? 0);
            $unit['active_programs']    = (int)($progStats['active_programs'] ?? 0);
            $unit['total_planned_qty']  = (int)($progStats['total_planned_qty'] ?? 0);
            $unit['total_completed_qty']= (int)($progStats['total_completed_qty'] ?? 0);
            $unit['total_cutting']      = (int)($cutStats['total_cutting'] ?? 0);
            $unit['total_cut_qty']      = (int)($cutStats['total_cut_qty'] ?? 0);
        }
        unset($unit);

        return $units;
    }

    /**
     * Create a new production unit.
     */
    public static function create(array $data): int {
        self::ensureSchema();
        $code = strtoupper(trim($data['code'] ?? ''));
        if (empty($code)) {
            $num = 1;
            do {
                $code = 'PU-' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
                $exists = self::findByCode($code);
                if ($exists) {
                    $num++;
                } else {
                    break;
                }
            } while (true);
        }

        Database::execute(
            "INSERT INTO production_units (name, code, manager_name, address, phone, manager_id, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                trim($data['name']),
                $code,
                trim($data['manager_name'] ?? ''),
                $data['address'] ?? null,
                trim($data['phone'] ?? ''),
                !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update an existing production unit.
     */
    public static function update(int $id, array $data): bool {
        self::ensureSchema();
        return Database::execute(
            "UPDATE production_units 
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
     * Delete a production unit.
     */
    public static function delete(int $id): bool {
        return Database::execute("DELETE FROM production_units WHERE id = ?", [$id]) > 0;
    }

    /**
     * Toggle active status.
     */
    public static function toggleActive(int $id): bool {
        return Database::execute(
            "UPDATE production_units SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Count production units.
     */
    public static function count(array $filters = []): int {
        $sql = "SELECT COUNT(*) as cnt FROM production_units WHERE 1=1";
        $params = [];
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }
        $row = Database::fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }
}
