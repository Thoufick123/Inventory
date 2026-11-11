<?php

class ProductionInventoryDamage
{
    public static function getAll(array $filters = []): array
    {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return [];
        }
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId === 0) {
            return [];
        }
        $sql = "SELECT d.*, qi.size_name, qi.color_name, qi.catalog_hierarchy,
                       qb.batch_number AS qc_batch_number,
                       pp.program_number, pp.name AS program_name,
                       p.name AS product_name, p.code AS product_code,
                       pu.name AS production_unit_name,
                       reporter.full_name AS reported_by_name,
                       resolver.full_name AS resolved_by_name
                FROM production_inventory_damages d
                JOIN qc_items qi ON qi.id = d.qc_item_id
                JOIN qc_batches qb ON qb.id = d.qc_batch_id
                JOIN production_programs pp ON pp.id = d.program_id
                LEFT JOIN products p ON p.id = pp.product_id
                LEFT JOIN production_units pu ON pu.id = COALESCE(d.production_unit_id, pp.production_unit_id)
                LEFT JOIN users reporter ON reporter.id = d.reported_by
                LEFT JOIN users resolver ON resolver.id = d.resolved_by
                WHERE pu.concern_id = ?";
        $params = [$concernId];
        if ($scopedUnitId !== null) {
            $sql .= " AND COALESCE(d.production_unit_id, pp.production_unit_id) = ?";
            $params[] = $scopedUnitId;
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
                      OR pp.name LIKE ? OR p.name LIKE ? OR p.code LIKE ? OR qi.size_name LIKE ? OR qi.color_name LIKE ?)";
            $term = '%' . trim((string)$filters['search']) . '%';
            $params = array_merge($params, array_fill(0, 11, $term));
        }

        $sql .= ' ORDER BY d.id DESC';
        return Database::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return null;
        }
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId === 0) {
            return null;
        }
        $params = [$id, $concernId];
        $unitClause = '';
        if ($scopedUnitId !== null) {
            $unitClause = ' AND COALESCE(d.production_unit_id, pp.production_unit_id) = ?';
            $params[] = $scopedUnitId;
        }
        return Database::fetchOne(
            "SELECT d.*, qi.size_name, qi.color_name, qi.catalog_hierarchy,
                    qb.batch_number AS qc_batch_number,
                    pp.program_number, pp.name AS program_name,
                    p.name AS product_name, p.code AS product_code,
                    pu.name AS production_unit_name,
                    reporter.full_name AS reported_by_name,
                    resolver.full_name AS resolved_by_name
             FROM production_inventory_damages d
             JOIN qc_items qi ON qi.id = d.qc_item_id
             JOIN qc_batches qb ON qb.id = d.qc_batch_id
             JOIN production_programs pp ON pp.id = d.program_id
             LEFT JOIN products p ON p.id = pp.product_id
             LEFT JOIN production_units pu ON pu.id = COALESCE(d.production_unit_id, pp.production_unit_id)
             LEFT JOIN users reporter ON reporter.id = d.reported_by
             LEFT JOIN users resolver ON resolver.id = d.resolved_by
             WHERE d.id = ? AND pu.concern_id = ?{$unitClause}",
            $params
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            "INSERT INTO production_inventory_damages
                (damage_number, qc_batch_id, qc_item_id, program_id, production_unit_id,
                 tag_code, source_stage, damage_reason, defect_notes, photo_url, unit_value,
                 status, reported_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'LOGGED', ?)",
            [
                $data['damage_number'],
                (int)$data['qc_batch_id'],
                (int)$data['qc_item_id'],
                (int)$data['program_id'],
                !empty($data['production_unit_id']) ? (int)$data['production_unit_id'] : null,
                strtoupper(trim((string)$data['tag_code'])),
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
        if ($concernId === null) {
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

        $where = ' WHERE pu.concern_id = ?';
        $params = [$concernId];
        if ($effectiveUnitId !== null) {
            $where .= ' AND COALESCE(d.production_unit_id, pp.production_unit_id) = ?';
            $params[] = $effectiveUnitId;
        }

        $row = Database::fetchOne(
            "SELECT COUNT(*) AS total_cases,
                    COALESCE(SUM(CASE WHEN d.status NOT IN ('REPAIRED','RESTOCKED') THEN 1 ELSE 0 END), 0) AS quarantined_pieces,
                    COALESCE(SUM(CASE WHEN d.status = 'REWORK' THEN 1 ELSE 0 END), 0) AS rework_pieces,
                    COALESCE(SUM(CASE WHEN d.status IN ('REPAIRED','RESTOCKED') THEN 1 ELSE 0 END), 0) AS restored_pieces,
                    COALESCE(SUM(CASE WHEN d.status = 'WRITTEN_OFF' THEN 1 ELSE 0 END), 0) AS written_off_pieces,
                    COALESCE(SUM(CASE WHEN d.status NOT IN ('REPAIRED','RESTOCKED','WRITTEN_OFF') THEN d.unit_value ELSE 0 END), 0) AS at_risk_value
             FROM production_inventory_damages d
             JOIN production_programs pp ON pp.id = d.program_id
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
}