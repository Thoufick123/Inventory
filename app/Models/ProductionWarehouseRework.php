<?php
/**
 * Production Warehouse Rework Model
 * Handles damaged/defective items returned from warehouses to production for rework/repair.
 */

class ProductionWarehouseRework {

    /**
     * Find rework record by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT pwr.*, 
                    w.name as warehouse_name, w.code as warehouse_code,
                    pv.sku, pv.qr_code, pv.selling_price, pv.mrp, pv.purchase_price, pv.production_cost,
                    p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                    s.name as size_name, c.name as color_name,
                    cat.name as category_name,
                    pb.batch_number, pb.total_qty as batch_total_qty, pb.status as batch_status,
                    po.outward_number, po.dispatch_date as outward_dispatch_date, po.vehicle_number as outward_vehicle,
                    pu.name as production_unit_name, pu.code as production_unit_code,
                    u_rec.full_name as received_by_name,
                    u_cre.full_name as created_by_name
             FROM production_warehouse_reworks pwr
             JOIN warehouses w ON pwr.warehouse_id = w.id
             JOIN product_variants pv ON pwr.variant_id = pv.id
             JOIN products p ON COALESCE(pwr.product_id, pv.product_id) = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN production_batches pb ON pwr.production_batch_id = pb.id
             LEFT JOIN production_outwards po ON pwr.production_outward_id = po.id
             LEFT JOIN production_units pu ON pwr.production_unit_id = pu.id
             LEFT JOIN users u_rec ON pwr.received_by = u_rec.id
             LEFT JOIN users u_cre ON pwr.created_by = u_cre.id
             WHERE pwr.id = ?",
            [$id]
        );
    }

    /**
     * Get all rework records with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT pwr.*, 
                       w.name as warehouse_name, w.code as warehouse_code,
                       pv.sku, pv.qr_code, pv.selling_price, pv.mrp, pv.production_cost,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       s.name as size_name, c.name as color_name,
                       cat.name as category_name,
                       pb.batch_number, pb.status as batch_status,
                       po.outward_number,
                       pu.name as production_unit_name,
                       u_rec.full_name as received_by_name,
                       u_cre.full_name as created_by_name
                FROM production_warehouse_reworks pwr
                JOIN warehouses w ON pwr.warehouse_id = w.id
                JOIN product_variants pv ON pwr.variant_id = pv.id
                JOIN products p ON COALESCE(pwr.product_id, pv.product_id) = p.id
                LEFT JOIN sizes s ON pv.size_id = s.id
                LEFT JOIN colors c ON pv.color_id = c.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN production_batches pb ON pwr.production_batch_id = pb.id
                LEFT JOIN production_outwards po ON pwr.production_outward_id = po.id
                LEFT JOIN production_units pu ON pwr.production_unit_id = pu.id
                LEFT JOIN users u_rec ON pwr.received_by = u_rec.id
                LEFT JOIN users u_cre ON pwr.created_by = u_cre.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND pwr.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND pwr.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['defect_type'])) {
            $sql .= " AND pwr.defect_type = ?";
            $params[] = $filters['defect_type'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND pwr.received_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND pwr.received_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['production_unit_id'])) {
            $sql .= " AND (pwr.production_unit_id = ? OR pwr.production_unit_id IS NULL)";
            $params[] = (int)$filters['production_unit_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                pwr.rework_number LIKE ? 
                OR pwr.challan_number LIKE ? 
                OR pwr.damage_reason LIKE ? 
                OR p.name LIKE ? 
                OR p.code LIKE ? 
                OR pv.sku LIKE ? 
                OR pb.batch_number LIKE ? 
                OR po.outward_number LIKE ?
                OR w.name LIKE ?
            )";
            $params = array_merge($params, array_fill(0, 9, $term));
        }

        $sql .= " ORDER BY pwr.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create a new rework entry.
     */
    public static function create(array $data): int {
        if (empty($data['rework_number'])) {
            $baseCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM production_warehouse_reworks")['c'] ?? 0);
            $data['rework_number'] = 'RWK-WH-' . date('Ymd') . '-' . str_pad((string)($baseCount + 1), 4, '0', STR_PAD_LEFT);
        }

        // Auto determine product_id from variant if missing
        if (empty($data['product_id']) && !empty($data['variant_id'])) {
            $v = Database::fetchOne("SELECT product_id FROM product_variants WHERE id = ?", [$data['variant_id']]);
            $data['product_id'] = $v['product_id'] ?? null;
        }

        Database::execute(
            "INSERT INTO production_warehouse_reworks (
                rework_number, warehouse_id, production_outward_id, production_batch_id,
                warehouse_damage_id, variant_id, product_id, production_unit_id,
                quantity, repaired_qty, scrapped_qty, defect_type, damage_reason,
                defect_notes, photo_url, challan_number, received_date, received_by,
                assigned_to, status, repair_notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['rework_number'],
                $data['warehouse_id'],
                $data['production_outward_id'] ?? null,
                $data['production_batch_id'] ?? null,
                $data['warehouse_damage_id'] ?? null,
                $data['variant_id'],
                $data['product_id'] ?? null,
                $data['production_unit_id'] ?? null,
                $data['quantity'] ?? 1,
                $data['repaired_qty'] ?? 0,
                $data['scrapped_qty'] ?? 0,
                $data['defect_type'] ?? 'STITCHING_ERROR',
                $data['damage_reason'] ?? 'Defective in Warehouse',
                $data['defect_notes'] ?? null,
                $data['photo_url'] ?? null,
                $data['challan_number'] ?? null,
                $data['received_date'] ?? date('Y-m-d'),
                $data['received_by'] ?? null,
                $data['assigned_to'] ?? null,
                $data['status'] ?? 'RECEIVED',
                $data['repair_notes'] ?? null,
                $data['created_by'] ?? null,
            ]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Update rework record.
     */
    public static function update(int $id, array $data): bool {
        $allowed = [
            'rework_number', 'warehouse_id', 'production_outward_id', 'production_batch_id',
            'warehouse_damage_id', 'variant_id', 'product_id', 'production_unit_id',
            'quantity', 'repaired_qty', 'scrapped_qty', 'defect_type', 'damage_reason',
            'defect_notes', 'photo_url', 'challan_number', 'received_date', 'received_by',
            'assigned_to', 'status', 'repair_notes', 'repaired_at', 'redispatched_date',
            'redispatched_vehicle', 'redispatched_notes'
        ];

        $fields = [];
        $params = [];

        foreach ($data as $k => $v) {
            if (in_array($k, $allowed, true)) {
                $fields[] = "`$k` = ?";
                $params[] = $v;
            }
        }

        if (empty($fields)) return false;

        $params[] = $id;
        $sql = "UPDATE production_warehouse_reworks SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params);
    }

    /**
     * Delete rework record.
     */
    public static function delete(int $id): bool {
        return Database::execute("DELETE FROM production_warehouse_reworks WHERE id = ?", [$id]);
    }

    /**
     * Compute statistics for KPIs.
     */
    public static function getStats(array $filters = []): array {
        $scopedUnitId = $filters['production_unit_id'] ?? null;
        $unitClause = $scopedUnitId ? " AND (pwr.production_unit_id = $scopedUnitId OR pwr.production_unit_id IS NULL)" : "";

        $row = Database::fetchOne(
            "SELECT 
                COUNT(*) as total_records,
                COALESCE(SUM(quantity), 0) as total_pieces,
                COALESCE(SUM(CASE WHEN status = 'RECEIVED' THEN 1 ELSE 0 END), 0) as count_received,
                COALESCE(SUM(CASE WHEN status = 'RECEIVED' THEN quantity ELSE 0 END), 0) as qty_received,
                COALESCE(SUM(CASE WHEN status = 'IN_REWORK' THEN 1 ELSE 0 END), 0) as count_in_rework,
                COALESCE(SUM(CASE WHEN status = 'IN_REWORK' THEN quantity ELSE 0 END), 0) as qty_in_rework,
                COALESCE(SUM(CASE WHEN status = 'REPAIRED' THEN 1 ELSE 0 END), 0) as count_repaired,
                COALESCE(SUM(repaired_qty), 0) as qty_repaired,
                COALESCE(SUM(CASE WHEN status = 'SCRAPPED' THEN 1 ELSE 0 END), 0) as count_scrapped,
                COALESCE(SUM(scrapped_qty), 0) as qty_scrapped,
                COALESCE(SUM(CASE WHEN status = 'RE_DISPATCHED' THEN 1 ELSE 0 END), 0) as count_redispatched,
                COALESCE(SUM(CASE WHEN status = 'RE_DISPATCHED' THEN quantity ELSE 0 END), 0) as qty_redispatched
             FROM production_warehouse_reworks pwr
             WHERE 1=1 $unitClause"
        );

        return [
            'total_records'      => (int)($row['total_records'] ?? 0),
            'total_pieces'       => (int)($row['total_pieces'] ?? 0),
            'count_received'     => (int)($row['count_received'] ?? 0),
            'qty_received'       => (int)($row['qty_received'] ?? 0),
            'count_in_rework'    => (int)($row['count_in_rework'] ?? 0),
            'qty_in_rework'      => (int)($row['qty_in_rework'] ?? 0),
            'count_repaired'     => (int)($row['count_repaired'] ?? 0),
            'qty_repaired'       => (int)($row['qty_repaired'] ?? 0),
            'count_scrapped'     => (int)($row['count_scrapped'] ?? 0),
            'qty_scrapped'       => (int)($row['qty_scrapped'] ?? 0),
            'count_redispatched' => (int)($row['count_redispatched'] ?? 0),
            'qty_redispatched'   => (int)($row['qty_redispatched'] ?? 0),
        ];
    }
}
