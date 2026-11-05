<?php
/**
 * Warehouse Damage Model
 * Handles damaged goods logging, defect categorization, photo attachments,
 * salvage/write-off lifecycle, and financial loss calculations.
 */

class WarehouseDamage {

    /**
     * Find damage record by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT wd.*, 
                    w.name as warehouse_name, w.code as warehouse_code,
                    pv.sku, pv.qr_code, pv.selling_price, pv.mrp, pv.purchase_price, pv.production_cost,
                    p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                    s.name as size_name, c.name as color_name,
                    cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                    u.full_name as reported_by_name,
                    pb.batch_number, pb.id as batch_id, pb.status as batch_status,
                    pp.program_number, pp.name as program_name,
                    COALESCE(pu_target.id, pu_out.id, pu.id) as production_unit_id,
                    COALESCE(pu_target.name, pu_out.name, pu.name, 'Main Production Facility') as production_unit_name,
                    COALESCE(pu_target.code, pu_out.code, pu.code, 'PU1') as production_unit_code,
                    COALESCE(pu_target.manager_name, pu_out.manager_name, pu.manager_name, '') as production_unit_manager,
                    COALESCE(pu_target.phone, pu_out.phone, pu.phone, '') as production_unit_phone,
                    po.outward_number, po.dispatch_date as outward_dispatch_date, po.vehicle_number, po.driver_name
             FROM warehouse_damages wd
             JOIN warehouses w ON wd.warehouse_id = w.id
             JOIN product_variants pv ON wd.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
             LEFT JOIN users u ON wd.reported_by = u.id
             LEFT JOIN production_batches pb ON (wd.source_type IN ('PRODUCTION_INWARD', 'INWARD_GRN', 'PRODUCTION_BATCH', 'INWARD_TRANSIT') AND wd.reference_id = pb.id)
             LEFT JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
             LEFT JOIN production_outwards po ON po.production_batch_id = pb.id
             LEFT JOIN production_units pu_out ON po.production_unit_id = pu_out.id
             LEFT JOIN production_units pu_target ON wd.production_unit_id = pu_target.id
             WHERE wd.id = ?",
            [$id]
        );
    }

    /**
     * Get all damage records with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT wd.*, 
                       w.name as warehouse_name, w.code as warehouse_code,
                       pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       s.name as size_name, c.name as color_name,
                       cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                       u.full_name as reported_by_name,
                       pb.batch_number, pb.id as batch_id, pb.status as batch_status,
                       pp.program_number,
                       COALESCE(pu_out.name, pu.name, 'Main Production Facility') as production_unit_name,
                       COALESCE(pu_out.code, pu.code, 'PU1') as production_unit_code,
                       po.outward_number, po.dispatch_date as outward_dispatch_date, po.vehicle_number
                FROM warehouse_damages wd
                JOIN warehouses w ON wd.warehouse_id = w.id
                JOIN product_variants pv ON wd.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN sizes s ON pv.size_id = s.id
                LEFT JOIN colors c ON pv.color_id = c.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
                LEFT JOIN users u ON wd.reported_by = u.id
                LEFT JOIN production_batches pb ON (wd.source_type IN ('PRODUCTION_INWARD', 'INWARD_GRN', 'PRODUCTION_BATCH', 'INWARD_TRANSIT') AND wd.reference_id = pb.id)
                LEFT JOIN production_programs pp ON pb.program_id = pp.id
                LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
                LEFT JOIN production_outwards po ON po.production_batch_id = pb.id
                LEFT JOIN production_units pu_out ON po.production_unit_id = pu_out.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND wd.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['source_type'])) {
            $sql .= " AND wd.source_type = ?";
            $params[] = $filters['source_type'];
        }

        if (empty($filters['unscoped']) && function_exists('currentWarehouseId')) {
            $scopedWhId = currentWarehouseId();
            if ($scopedWhId !== null) {
                $sql .= " AND wd.warehouse_id = ?";
                $params[] = $scopedWhId;
            } elseif (!empty($filters['warehouse_id'])) {
                $sql .= " AND wd.warehouse_id = ?";
                $params[] = (int)$filters['warehouse_id'];
            }
        } elseif (!empty($filters['warehouse_id'])) {
            $sql .= " AND wd.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['reference_id'])) {
            $sql .= " AND wd.reference_id = ?";
            $params[] = (int)$filters['reference_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (wd.damage_number LIKE ? OR pv.sku LIKE ? OR p.name LIKE ? OR wd.damage_reason LIKE ? OR wd.tag_code LIKE ? OR pb.batch_number LIKE ? OR pp.program_number LIKE ? OR po.outward_number LIKE ? OR pu.name LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY wd.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get all damage cases for a specific production batch.
     */
    public static function getByBatchId(int $batchId): array {
        return self::getAll(['reference_id' => $batchId]);
    }

    /**
     * Generate unique damage case number (e.g., DMG-20261026-0001).
     */
    public static function generateDamageNumber(string $prefix = 'DMG'): string {
        $today = date('Ymd');
        $fullPrefix = $prefix . '-' . $today . '-';
        $last = Database::fetchOne(
            "SELECT damage_number FROM warehouse_damages WHERE damage_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$fullPrefix . '%']
        );
        $seq = 1;
        if ($last && !empty($last['damage_number'])) {
            $parts = explode('-', $last['damage_number']);
            $seq = (int)end($parts) + 1;
        }

        do {
            $num = sprintf('%s%04d', $fullPrefix, $seq);
            $exists = Database::fetchOne("SELECT id FROM warehouse_damages WHERE damage_number = ? LIMIT 1", [$num]);
            if (!$exists) {
                return $num;
            }
            $seq++;
        } while ($seq < 9999);

        return sprintf('%s%04d', $fullPrefix, $seq);
    }

    /**
     * Create damage entry record.
     */
    public static function create(array $data): int {
        if (empty($data['damage_number'])) {
            $data['damage_number'] = self::generateDamageNumber();
        }

        Database::execute(
            "INSERT INTO warehouse_damages 
             (damage_number, warehouse_id, variant_id, tag_code, source_type, reference_id, quantity, cost_per_unit, total_loss, damage_reason, defect_notes, photo_url, status, reported_by, production_unit_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['damage_number'],
                (int)$data['warehouse_id'],
                (int)$data['variant_id'],
                !empty($data['tag_code']) ? trim($data['tag_code']) : null,
                $data['source_type'] ?? 'STORAGE_HANDLING',
                !empty($data['reference_id']) ? (int)$data['reference_id'] : null,
                (int)$data['quantity'],
                (float)($data['cost_per_unit'] ?? 0.00),
                (float)($data['total_loss'] ?? 0.00),
                $data['damage_reason'],
                $data['defect_notes'] ?? null,
                $data['photo_url'] ?? null,
                $data['status'] ?? 'LOGGED',
                (int)$data['reported_by'],
                !empty($data['production_unit_id']) ? (int)$data['production_unit_id'] : null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update damage record.
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = [];

        $allowed = ['status', 'tag_code', 'defect_notes', 'damage_reason', 'quantity', 'cost_per_unit', 'total_loss', 'photo_url', 'production_unit_id'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`$field` = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE warehouse_damages SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params) >= 0;
    }

    /**
     * Get KPI Summary Statistics for Damage Module.
     */
    public static function getSummaryStats(?int $warehouseId = null): array {
        if ($warehouseId === null && function_exists('currentWarehouseId')) {
            $warehouseId = currentWarehouseId();
        }
        $where = $warehouseId ? " WHERE wd.warehouse_id = " . (int)$warehouseId : "";
        $stats = Database::fetchOne(
            "SELECT 
                COUNT(*) as total_records,
                COALESCE(SUM(wd.quantity), 0) as total_units,
                COALESCE(SUM(
                    CASE 
                        WHEN wd.total_loss > 0 THEN wd.total_loss 
                        ELSE wd.quantity * COALESCE(NULLIF(pv.selling_price, 0), NULLIF(pv.mrp, 0), NULLIF(pv.production_cost, 0), NULLIF(pv.purchase_price, 0), 0) 
                    END
                ), 0.00) as total_valuation_loss,
                SUM(CASE WHEN wd.status = 'LOGGED' THEN wd.quantity ELSE 0 END) as pending_inspection_units,
                SUM(CASE WHEN wd.status = 'INSPECTED' THEN wd.quantity ELSE 0 END) as inspected_units,
                SUM(CASE WHEN wd.status = 'SALVAGED' THEN wd.quantity ELSE 0 END) as salvaged_units,
                SUM(CASE WHEN wd.status = 'WRITTEN_OFF' THEN wd.quantity ELSE 0 END) as written_off_units,
                SUM(CASE WHEN wd.status IN ('REPAIRED', 'RESTOCKED') THEN wd.quantity ELSE 0 END) as repaired_units,
                SUM(CASE WHEN wd.status = 'REWORK' THEN wd.quantity ELSE 0 END) as rework_units
             FROM warehouse_damages wd
             LEFT JOIN product_variants pv ON wd.variant_id = pv.id$where"
        );

        return [
            'total_records'          => (int)($stats['total_records'] ?? 0),
            'total_units'            => (int)($stats['total_units'] ?? 0),
            'total_valuation_loss'   => (float)($stats['total_valuation_loss'] ?? 0.00),
            'pending_inspection'     => (int)($stats['pending_inspection_units'] ?? 0),
            'inspected'              => (int)($stats['inspected_units'] ?? 0),
            'salvaged'               => (int)($stats['salvaged_units'] ?? 0),
            'written_off'            => (int)($stats['written_off_units'] ?? 0),
            'repaired'               => (int)($stats['repaired_units'] ?? 0),
            'rework_units'           => (int)($stats['rework_units'] ?? 0),
        ];
    }
}
