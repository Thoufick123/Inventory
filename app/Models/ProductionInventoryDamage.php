<?php

class ProductionInventoryDamage
{
    public static function getAll(array $filters = []): array
    {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null || $concernId <= 0) {
            return [];
        }
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId === 0) {
            return [];
        }
        $sql = "SELECT d.*,
                       COALESCE(NULLIF(qi.size_name, ''), sz.name, 'Std') AS size_name,
                       COALESCE(NULLIF(qi.color_name, ''), col.name, 'Std') AS color_name,
                       qi.catalog_hierarchy,
                       qb.batch_number AS qc_batch_number,
                       pp.program_number, pp.name AS program_name,
                       pv.sku,
                       COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                       p.code AS product_code,
                       COALESCE(pu.name, CASE WHEN d.stock_location_type = 'CONCERN' THEN CONCAT(COALESCE(dc.name, 'Concern'), ' (Concern Inventory)') ELSE 'Concern Inventory' END) AS production_unit_name,
                       dc.name AS concern_name,
                       reporter.full_name AS reported_by_name,
                       resolver.full_name AS resolved_by_name
                FROM production_inventory_damages d
                LEFT JOIN qc_items qi ON qi.id = d.qc_item_id
                LEFT JOIN qc_batches qb ON qb.id = d.qc_batch_id
                LEFT JOIN production_programs pp ON pp.id = d.program_id
                LEFT JOIN product_variants pv ON pv.id = d.variant_id
                LEFT JOIN products p ON p.id = COALESCE(pv.product_id, pp.product_id)
                LEFT JOIN sizes sz ON sz.id = pv.size_id
                LEFT JOIN colors col ON col.id = pv.color_id
                LEFT JOIN production_units pu ON pu.id = COALESCE(d.production_unit_id, pp.production_unit_id)
                LEFT JOIN concerns dc ON dc.id = COALESCE(d.concern_id, pu.concern_id)
                LEFT JOIN users reporter ON reporter.id = d.reported_by
                LEFT JOIN users resolver ON resolver.id = d.resolved_by
                WHERE COALESCE(d.concern_id, pu.concern_id) = ?";
        $params = [(int)$concernId];
        if ($scopedUnitId !== null) {
            $sql .= " AND ((d.stock_location_type = 'CONCERN' AND d.stock_location_id = ?) OR COALESCE(d.production_unit_id, pp.production_unit_id) = ?)";
            $params[] = (int)$concernId;
            $params[] = (int)$scopedUnitId;
        }

        foreach (['status', 'source_stage', 'production_unit_id'] as $field) {
            if (!empty($filters[$field])) {
                if ($field === 'production_unit_id') {
                    $sql .= " AND COALESCE(d.production_unit_id, pp.production_unit_id) = ?";
                } else {
                    $sql .= " AND d.{$field} = ?";
                }
                $params[] = $filters[$field];
            }
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (d.damage_number LIKE ? OR d.tag_code LIKE ? OR d.damage_reason LIKE ?
                      OR d.defect_notes LIKE ? OR qb.batch_number LIKE ? OR pp.program_number LIKE ?
                      OR pp.name LIKE ? OR p.name LIKE ? OR p.code LIKE ? OR pv.sku LIKE ?
                      OR qi.size_name LIKE ? OR qi.color_name LIKE ? OR sz.name LIKE ? OR col.name LIKE ?)";
            $term = '%' . trim((string)$filters['search']) . '%';
            $params = array_merge($params, array_fill(0, 14, $term));
        }

        $sql .= ' ORDER BY d.id DESC';
        return Database::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null || $concernId <= 0) {
            return null;
        }
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId === 0) {
            return null;
        }
        $params = [$id, (int)$concernId];
        $unitClause = '';
        if ($scopedUnitId !== null) {
            $unitClause = " AND ((d.stock_location_type = 'CONCERN' AND d.stock_location_id = ?) OR COALESCE(d.production_unit_id, pp.production_unit_id) = ?)";
            $params[] = (int)$concernId;
            $params[] = (int)$scopedUnitId;
        }
        return Database::fetchOne(
            "SELECT d.*,
                    COALESCE(NULLIF(qi.size_name, ''), sz.name, 'Std') AS size_name,
                    COALESCE(NULLIF(qi.color_name, ''), col.name, 'Std') AS color_name,
                    qi.catalog_hierarchy,
                    qb.batch_number AS qc_batch_number,
                    pp.program_number, pp.name AS program_name,
                    pv.sku,
                    COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                    p.code AS product_code,
                    COALESCE(pu.name, CASE WHEN d.stock_location_type = 'CONCERN' THEN CONCAT(COALESCE(dc.name, 'Concern'), ' (Concern Inventory)') ELSE 'Concern Inventory' END) AS production_unit_name,
                    dc.name AS concern_name,
                    reporter.full_name AS reported_by_name,
                    resolver.full_name AS resolved_by_name
             FROM production_inventory_damages d
             LEFT JOIN qc_items qi ON qi.id = d.qc_item_id
             LEFT JOIN qc_batches qb ON qb.id = d.qc_batch_id
             LEFT JOIN production_programs pp ON pp.id = d.program_id
             LEFT JOIN product_variants pv ON pv.id = d.variant_id
             LEFT JOIN products p ON p.id = COALESCE(pv.product_id, pp.product_id)
             LEFT JOIN sizes sz ON sz.id = pv.size_id
             LEFT JOIN colors col ON col.id = pv.color_id
             LEFT JOIN production_units pu ON pu.id = COALESCE(d.production_unit_id, pp.production_unit_id)
             LEFT JOIN concerns dc ON dc.id = COALESCE(d.concern_id, pu.concern_id)
             LEFT JOIN users reporter ON reporter.id = d.reported_by
             LEFT JOIN users resolver ON resolver.id = d.resolved_by
             WHERE d.id = ? AND COALESCE(d.concern_id, pu.concern_id) = ?{$unitClause}",
            $params
        );
    }

    public static function create(array $data): int
    {
        $concernId = !empty($data['concern_id'])
            ? (int)$data['concern_id']
            : (int)(function_exists('currentConcernId') ? currentConcernId() : 0);
        if ($concernId <= 0) {
            throw new InvalidArgumentException('Active concern is required before recording finished goods damage.');
        }

        $tagCode = strtoupper(trim((string)$data['tag_code']));
        $existingRestored = Database::fetchOne(
            "SELECT id, status FROM production_inventory_damages WHERE concern_id = ? AND tag_code = ?",
            [$concernId, $tagCode]
        );
        if ($existingRestored) {
            if (!in_array((string)$existingRestored['status'], ['REPAIRED', 'RESTOCKED'], true)) {
                throw new RuntimeException("QR tag {$tagCode} is already quarantined under an open damage case.");
            }
            Database::execute(
                "UPDATE production_inventory_damages
                 SET damage_number = ?, qc_batch_id = ?, qc_item_id = ?, variant_id = ?, program_id = ?,
                     production_unit_id = ?, stock_location_type = ?, stock_location_id = ?,
                     source_stage = ?, damage_reason = ?, defect_notes = ?, photo_url = ?, unit_value = ?,
                     status = 'LOGGED', reported_by = ?, resolved_by = NULL, resolved_at = NULL, created_at = NOW()
                 WHERE id = ? AND concern_id = ?",
                [
                    $data['damage_number'],
                    !empty($data['qc_batch_id']) ? (int)$data['qc_batch_id'] : null,
                    !empty($data['qc_item_id']) ? (int)$data['qc_item_id'] : null,
                    !empty($data['variant_id']) ? (int)$data['variant_id'] : null,
                    !empty($data['program_id']) ? (int)$data['program_id'] : null,
                    !empty($data['production_unit_id']) ? (int)$data['production_unit_id'] : null,
                    !empty($data['stock_location_type']) ? strtoupper((string)$data['stock_location_type']) : null,
                    !empty($data['stock_location_id']) ? (int)$data['stock_location_id'] : null,
                    $data['source_stage'],
                    $data['damage_reason'],
                    $data['defect_notes'] ?? null,
                    $data['photo_url'] ?? null,
                    max(0, (float)($data['unit_value'] ?? 0)),
                    !empty($data['reported_by']) ? (int)$data['reported_by'] : null,
                    (int)$existingRestored['id'],
                    $concernId,
                ]
            );
            return (int)$existingRestored['id'];
        }

        Database::execute(
            "INSERT INTO production_inventory_damages
                (concern_id, damage_number, qc_batch_id, qc_item_id, variant_id, program_id, production_unit_id,
                 stock_location_type, stock_location_id, tag_code, source_stage, damage_reason, defect_notes,
                 photo_url, unit_value, status, reported_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'LOGGED', ?)",
            [
                $concernId,
                $data['damage_number'],
                !empty($data['qc_batch_id']) ? (int)$data['qc_batch_id'] : null,
                !empty($data['qc_item_id']) ? (int)$data['qc_item_id'] : null,
                !empty($data['variant_id']) ? (int)$data['variant_id'] : null,
                !empty($data['program_id']) ? (int)$data['program_id'] : null,
                !empty($data['production_unit_id']) ? (int)$data['production_unit_id'] : null,
                !empty($data['stock_location_type']) ? strtoupper((string)$data['stock_location_type']) : null,
                !empty($data['stock_location_id']) ? (int)$data['stock_location_id'] : null,
                $tagCode,
                $data['source_stage'],
                $data['damage_reason'],
                $data['defect_notes'] ?? null,
                $data['photo_url'] ?? null,
                max(0, (float)($data['unit_value'] ?? 0)),
                !empty($data['reported_by']) ? (int)$data['reported_by'] : null,
            ]
        );

        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $allowed = ['status', 'defect_notes', 'photo_url', 'resolved_by', 'resolved_at'];
        $fields = [];
        $params = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $params[] = $data[$field];
            }
        }
        if (!$fields) {
            return false;
        }

        $params[] = $id;
        return Database::execute(
            'UPDATE production_inventory_damages SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $params
        ) >= 0;
    }

    public static function getSummaryStats(?int $productionUnitId = null): array
    {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null || $concernId <= 0) {
            return [
                'total_cases' => 0,
                'quarantined_pieces' => 0,
                'rework_pieces' => 0,
                'restored_pieces' => 0,
                'written_off_pieces' => 0,
                'at_risk_value' => 0.0,
            ];
        }
        $effectiveUnitId = $productionUnitId ?? (function_exists('currentProductionUnitId') ? currentProductionUnitId() : null);
        if ($effectiveUnitId === 0) {
            return [
                'total_cases' => 0,
                'quarantined_pieces' => 0,
                'rework_pieces' => 0,
                'restored_pieces' => 0,
                'written_off_pieces' => 0,
                'at_risk_value' => 0.0,
            ];
        }

        $where = ' WHERE COALESCE(d.concern_id, pu.concern_id) = ?';
        $params = [(int)$concernId];
        if ($effectiveUnitId !== null) {
            $where .= " AND ((d.stock_location_type = 'CONCERN' AND d.stock_location_id = ?) OR COALESCE(d.production_unit_id, pp.production_unit_id) = ?)";
            $params[] = (int)$concernId;
            $params[] = (int)$effectiveUnitId;
        }

        $row = Database::fetchOne(
            "SELECT COUNT(*) AS total_cases,
                    COALESCE(SUM(CASE WHEN d.status NOT IN ('REPAIRED','RESTOCKED') THEN 1 ELSE 0 END), 0) AS quarantined_pieces,
                    COALESCE(SUM(CASE WHEN d.status = 'REWORK' THEN 1 ELSE 0 END), 0) AS rework_pieces,
                    COALESCE(SUM(CASE WHEN d.status IN ('REPAIRED','RESTOCKED') THEN 1 ELSE 0 END), 0) AS restored_pieces,
                    COALESCE(SUM(CASE WHEN d.status = 'WRITTEN_OFF' THEN 1 ELSE 0 END), 0) AS written_off_pieces,
                    COALESCE(SUM(CASE WHEN d.status NOT IN ('REPAIRED','RESTOCKED','WRITTEN_OFF') THEN d.unit_value ELSE 0 END), 0) AS at_risk_value
             FROM production_inventory_damages d
             LEFT JOIN production_programs pp ON pp.id = d.program_id
             LEFT JOIN production_units pu ON pu.id = COALESCE(d.production_unit_id, pp.production_unit_id){$where}",
            $params
        ) ?: [];

        return array_map('intval', array_intersect_key($row, array_flip([
            'total_cases', 'quarantined_pieces', 'rework_pieces', 'restored_pieces', 'written_off_pieces',
        ]))) + ['at_risk_value' => (float)($row['at_risk_value'] ?? 0)];
    }

    public static function getUnavailableTags(int $qcBatchId): array
    {
        $rows = Database::fetchAll(
            "SELECT tag_code FROM production_inventory_damages
             WHERE qc_batch_id = ? AND status NOT IN ('REPAIRED','RESTOCKED')",
            [$qcBatchId]
        );
        return array_fill_keys(array_map(
            static fn(array $row): string => strtoupper(trim((string)$row['tag_code'])),
            $rows
        ), true);
    }

    public static function getUnavailableConcernTags(?int $concernId = null): array
    {
        $concernId = $concernId ?? (function_exists('currentConcernId') ? currentConcernId() : null);
        if (!$concernId) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT tag_code FROM production_inventory_damages
             WHERE concern_id = ? AND status NOT IN ('REPAIRED','RESTOCKED')",
            [(int)$concernId]
        );
        return array_fill_keys(array_map(
            static fn(array $row): string => strtoupper(trim((string)$row['tag_code'])),
            $rows
        ), true);
    }
}