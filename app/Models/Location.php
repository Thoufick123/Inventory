<?php
/**
 * Location Model
 * Handles physical warehouse storage placements: racks, shelves, and bins.
 */

class Location {

    /**
     * Find location by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT l.*, w.name as warehouse_name, w.code as warehouse_code
             FROM locations l
             JOIN warehouses w ON l.warehouse_id = w.id
             WHERE l.id = ?",
            [$id]
        );
    }

    /**
     * Get all locations with optional filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT l.*, w.name as warehouse_name, w.code as warehouse_code
                FROM locations l
                JOIN warehouses w ON l.warehouse_id = w.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND l.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND l.is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (l.rack LIKE ? OR l.shelf LIKE ? OR l.bin LIKE ? OR l.label LIKE ? OR w.name LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY w.name ASC, l.rack ASC, l.shelf ASC, l.bin ASC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Create a storage location.
     */
    public static function create(array $data): int {
        $rack = trim($data['rack'] ?? '');
        $shelf = trim($data['shelf'] ?? '');
        $bin = trim($data['bin'] ?? '');
        $label = !empty($data['label']) ? trim($data['label']) : "R-$rack / S-$shelf / B-$bin";

        Database::execute(
            "INSERT INTO locations (warehouse_id, rack, shelf, bin, label, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                (int)$data['warehouse_id'],
                $rack,
                $shelf,
                $bin,
                $label,
                $data['is_active'] ?? 1
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update location.
     */
    public static function update(int $id, array $data): bool {
        $rack = trim($data['rack'] ?? '');
        $shelf = trim($data['shelf'] ?? '');
        $bin = trim($data['bin'] ?? '');
        $label = !empty($data['label']) ? trim($data['label']) : "R-$rack / S-$shelf / B-$bin";

        return Database::execute(
            "UPDATE locations 
             SET warehouse_id = ?, rack = ?, shelf = ?, bin = ?, label = ?, is_active = ?
             WHERE id = ?",
            [
                (int)$data['warehouse_id'],
                $rack,
                $shelf,
                $bin,
                $label,
                $data['is_active'] ?? 1,
                $id
            ]
        ) >= 0;
    }
}
