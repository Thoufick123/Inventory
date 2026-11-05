<?php
/**
 * Production Outward Model
 * Handles dispatch of finished goods from production to warehouses.
 */

class ProductionOutward {

    /**
     * Find outward dispatch by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT po.*, 
                    pb.batch_number, pb.total_qty, pb.box_count, pb.status as batch_status,
                    pp.program_number,
                    COALESCE(p.name, pp.name) as product_name, p.code as product_code,
                    w.id as warehouse_id, w.name as warehouse_name, w.code as warehouse_code,
                    u.full_name as created_by_name
             FROM production_outwards po
             JOIN production_batches pb ON po.production_batch_id = pb.id
             JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN users u ON po.created_by = u.id
             WHERE po.id = ?",
            [$id]
        );
    }

    /**
     * Find outward dispatch by production batch ID.
     */
    public static function findByBatchId(int $batchId): ?array {
        return Database::fetchOne(
            "SELECT po.*, 
                    pb.batch_number, pb.total_qty, pb.box_count, pb.status as batch_status,
                    pp.program_number,
                    COALESCE(p.name, pp.name) as product_name, p.code as product_code,
                    w.id as warehouse_id, w.name as warehouse_name, w.code as warehouse_code,
                    u.full_name as created_by_name
             FROM production_outwards po
             JOIN production_batches pb ON po.production_batch_id = pb.id
             JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN users u ON po.created_by = u.id
             WHERE po.production_batch_id = ?
             ORDER BY po.id DESC LIMIT 1",
            [$batchId]
        );
    }

    /**
     * Find outward dispatch by outward number.
     */
    public static function findByOutwardNumber(string $outwardNumber): ?array {
        return Database::fetchOne(
            "SELECT po.*, 
                    pb.batch_number, pb.total_qty, pb.box_count, pb.status as batch_status,
                    pp.program_number,
                    COALESCE(p.name, pp.name) as product_name, p.code as product_code,
                    w.id as warehouse_id, w.name as warehouse_name, w.code as warehouse_code,
                    u.full_name as created_by_name
             FROM production_outwards po
             JOIN production_batches pb ON po.production_batch_id = pb.id
             JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN users u ON po.created_by = u.id
             WHERE po.outward_number = ?",
            [$outwardNumber]
        );
    }

    /**
     * Get all outward records with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT po.*, 
                       pb.batch_number, pb.total_qty, pb.box_count, pb.status as batch_status,
                       pp.program_number,
                       COALESCE(p.name, pp.name) as product_name,
                       w.name as warehouse_name,
                       u.full_name as created_by_name
                FROM production_outwards po
                JOIN production_batches pb ON po.production_batch_id = pb.id
                JOIN production_programs pp ON pb.program_id = pp.id
                LEFT JOIN products p ON pp.product_id = p.id
                JOIN warehouses w ON pb.destination_warehouse_id = w.id
                LEFT JOIN users u ON po.created_by = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['production_unit_id'])) {
            $sql .= " AND pp.production_unit_id = ?";
            $params[] = (int)$filters['production_unit_id'];
        }

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND pb.destination_warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (po.outward_number LIKE ? OR pb.batch_number LIKE ? OR po.vehicle_number LIKE ? OR w.name LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        $sql .= " ORDER BY po.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create outward record.
     */
    public static function create(array $data): int {
        if (empty($data['outward_number'])) {
            if (class_exists('ProductionService')) {
                $data['outward_number'] = ProductionService::generateSequenceNumber('production_outwards', 'outward_number', 'OUT');
            } else {
                $data['outward_number'] = 'OUT-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            }
        }

        // Auto-resolve unit, warehouse, quantity from production_batches if missing
        if (!empty($data['production_batch_id']) && (empty($data['production_unit_id']) || empty($data['warehouse_id']) || empty($data['quantity']))) {
            $pb = Database::fetchOne(
                "SELECT pb.*, pp.production_unit_id 
                 FROM production_batches pb 
                 LEFT JOIN production_programs pp ON pb.program_id = pp.id 
                 WHERE pb.id = ?",
                [$data['production_batch_id']]
            );
            if ($pb) {
                if (empty($data['production_unit_id'])) $data['production_unit_id'] = $pb['production_unit_id'] ?? null;
                if (empty($data['warehouse_id'])) $data['warehouse_id'] = $pb['destination_warehouse_id'] ?? null;
                if (empty($data['quantity'])) $data['quantity'] = $pb['total_qty'] ?? ($pb['quantity'] ?? 0);
                if (empty($data['total_boxes'])) $data['total_boxes'] = $pb['box_count'] ?? 0;
            }
        }

        Database::execute(
            "INSERT INTO production_outwards 
             (production_batch_id, production_unit_id, warehouse_id, outward_number, quantity, total_boxes, total_quantity, dispatch_date, vehicle_number, driver_name, transporter_name, lr_number, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['production_batch_id'],
                !empty($data['production_unit_id']) ? (int)$data['production_unit_id'] : null,
                !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null,
                $data['outward_number'],
                $data['quantity'] ?? 0,
                $data['total_boxes'] ?? 0,
                $data['total_quantity'] ?? ($data['quantity'] ?? 0),
                $data['dispatch_date'] ?? date('Y-m-d H:i:s'),
                $data['vehicle_number'] ?? null,
                $data['driver_name'] ?? null,
                $data['transporter_name'] ?? null,
                $data['lr_number'] ?? null,
                $data['notes'] ?? null,
                $data['created_by'] ?? null,
            ]
        );
        return (int)Database::lastInsertId();
    }
}
