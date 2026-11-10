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
        $concernId = self::currentConcernScopeId();
        if ($concernId === null) {
            return null;
        }
        $assignedId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($assignedId !== null && $assignedId !== $id) {
            return null;
        }
        return Database::fetchOne(
            "SELECT pu.*, COALESCE(NULLIF(pu.manager_name, ''), u.full_name) as manager_name, u.email as manager_email, u.phone as manager_phone
             FROM production_units pu
             LEFT JOIN users u ON pu.manager_id = u.id
             WHERE pu.id = ? AND pu.concern_id = ?",
            [$id, $concernId]
        );
    }

    /**
     * Find a production unit by unique code.
     */
    public static function findByCode(string $code): ?array {
        self::ensureSchema();
        $concernId = self::currentConcernScopeId();
        if ($concernId === null) {
            return null;
        }
        $assignedId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($assignedId === 0 || ($assignedId !== null && !Database::fetchOne(
            "SELECT id FROM production_units WHERE code = ? AND concern_id = ? AND id = ?",
            [$code, $concernId, $assignedId]
        ))) {
            return null;
        }
        return Database::fetchOne(
            "SELECT pu.*, COALESCE(NULLIF(pu.manager_name, ''), u.full_name) as manager_name
             FROM production_units pu
             LEFT JOIN users u ON pu.manager_id = u.id
             WHERE pu.code = ? AND pu.concern_id = ?",
            [$code, $concernId]
        );
    }

    /**
     * Get all production units with optional filtering.
     */
    public static function getAll(array $filters = []): array {
        self::ensureSchema();
        $concernId = self::currentConcernScopeId();
        if ($concernId === null) {
            return [];
        }
        $sql = "SELECT pu.*, COALESCE(NULLIF(pu.manager_name, ''), u.full_name) as manager_name
                FROM production_units pu
                LEFT JOIN users u ON pu.manager_id = u.id
                WHERE pu.concern_id = ?";
        $params = [$concernId];
        $assignedId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($assignedId === 0) {
            return [];
        }
        if ($assignedId !== null) {
            $sql .= " AND pu.id = ?";
            $params[] = $assignedId;
        }

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
        $concernId = (int)($data['concern_id'] ?? 0);
        $activeConcernId = self::currentConcernScopeId();
        if ($concernId <= 0 || $activeConcernId === null || $concernId !== $activeConcernId || !Concern::hasFacilityModules($concernId, 'PRODUCTION') || !Database::fetchOne(
            "SELECT id FROM concerns WHERE id = ? AND is_active = 1",
            [$concernId]
        )) {
            throw new InvalidArgumentException('Choose an active concern before creating a production unit.');
        }
        $code = strtoupper(trim($data['code'] ?? ''));
        if (empty($code)) {
            $num = 1;
            do {
                $code = 'PU-C' . $concernId . '-' . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
                $exists = Database::fetchOne(
                    "SELECT id FROM production_units WHERE code = ? AND concern_id = ?",
                    [$code, $concernId]
                );
                if ($exists) {
                    $num++;
                } else {
                    break;
                }
            } while (true);
        }

        Database::execute(
            "INSERT INTO production_units (name, code, manager_name, address, phone, gst_number, manager_id, is_active, concern_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                trim($data['name']),
                $code,
                trim($data['manager_name'] ?? ''),
                $data['address'] ?? null,
                trim($data['phone'] ?? ''),
                $data['gst_number'] ?? null,
                !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $concernId
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update an existing production unit.
     */
    public static function update(int $id, array $data): bool {
        self::ensureSchema();
        $concernId = self::currentConcernScopeId();
        if ($concernId === null || !self::find($id)) {
            return false;
        }
        return Database::execute(
            "UPDATE production_units 
             SET name = ?, code = ?, manager_name = ?, address = ?, phone = ?, gst_number = ?, manager_id = ?, is_active = ?, updated_at = NOW()
             WHERE id = ? AND concern_id = ?",
            [
                trim($data['name']),
                strtoupper(trim($data['code'])),
                trim($data['manager_name'] ?? ''),
                $data['address'] ?? null,
                trim($data['phone'] ?? ''),
                $data['gst_number'] ?? null,
                !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id,
                $concernId
            ]
        ) >= 0;
    }

    /**
     * Delete a production unit.
     */
    public static function delete(int $id): bool {
        $concernId = self::currentConcernScopeId();
        return $concernId !== null && self::find($id) !== null
            && Database::execute("DELETE FROM production_units WHERE id = ? AND concern_id = ?", [$id, $concernId]) > 0;
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
            "UPDATE production_units SET is_active = NOT is_active, updated_at = NOW() WHERE id = ? AND concern_id = ?",
            [$id, $concernId]
        ) > 0;
    }

    /**
     * Count production units.
     */
    public static function count(array $filters = []): int {
        $concernId = self::currentConcernScopeId();
        if ($concernId === null) {
            return 0;
        }
        $sql = "SELECT COUNT(*) as cnt FROM production_units WHERE concern_id = ?";
        $params = [$concernId];
        $assignedId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
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
