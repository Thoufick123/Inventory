<?php
/**
 * Stitching Batch Model
 * Handles stitching workflow, program & catalog linking, worker assignments, received vs stitched, total weight and rework tracking.
 */

class StitchingBatch {

    /**
     * Find stitching batch by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT sb.*, 
                    cb.batch_number as cutting_batch_number, cb.name as cutting_name,
                    pp.program_number, pp.target_date, pp.name as program_name,
                    p.name as product_name, p.code as product_code,
                    cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                    u.full_name as worker_name
             FROM stitching_batches sb
             LEFT JOIN cutting_batches cb ON sb.cutting_batch_id = cb.id
             LEFT JOIN production_programs pp ON sb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN categories cat ON sb.category_id = cat.id
             LEFT JOIN subcategories subcat ON sb.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON sb.sub_subcategory_id = subsubcat.id
             LEFT JOIN users u ON sb.worker_id = u.id
             WHERE sb.id = ?",
            [$id]
        );
    }

    /**
     * Get all stitching batches with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT sb.*, 
                       cb.batch_number as cutting_batch_number, cb.name as cutting_name,
                       pp.program_number, pp.name as program_name,
                       p.name as product_name, p.code as product_code,
                       cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                       u.full_name as worker_name
                FROM stitching_batches sb
                LEFT JOIN cutting_batches cb ON sb.cutting_batch_id = cb.id
                LEFT JOIN production_programs pp ON sb.program_id = pp.id
                LEFT JOIN products p ON pp.product_id = p.id
                LEFT JOIN categories cat ON (sb.category_id = cat.id OR pp.category_id = cat.id)
                LEFT JOIN subcategories subcat ON (sb.subcategory_id = subcat.id OR pp.subcategory_id = subcat.id)
                LEFT JOIN sub_subcategories subsubcat ON (sb.sub_subcategory_id = subsubcat.id OR pp.sub_subcategory_id = subsubcat.id)
                LEFT JOIN users u ON sb.worker_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND sb.status = ?";
            $params[] = $filters['status'];
            if ($filters['status'] !== 'CANCELLED') {
                $sql .= " AND (sb.is_active = 1 OR sb.is_active IS NULL)";
            }
        } else {
            $sql .= " AND (sb.is_active = 1 OR sb.is_active IS NULL)";
        }

        if (!empty($filters['program_id'])) {
            $sql .= " AND sb.program_id = ?";
            $params[] = $filters['program_id'];
        }

        if (!empty($filters['cutting_batch_id'])) {
            $sql .= " AND sb.cutting_batch_id = ?";
            $params[] = $filters['cutting_batch_id'];
        }

        if (!empty($filters['production_unit_id'])) {
            $sql .= " AND pp.production_unit_id = ?";
            $params[] = (int)$filters['production_unit_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                sb.name LIKE ? 
                OR sb.batch_number LIKE ? 
                OR pp.name LIKE ? 
                OR pp.program_number LIKE ? 
                OR cb.name LIKE ? 
                OR cb.batch_number LIKE ? 
                OR p.name LIKE ? 
                OR p.code LIKE ? 
                OR cat.name LIKE ? 
                OR subcat.name LIKE ? 
                OR subsubcat.name LIKE ? 
                OR u.full_name LIKE ? 
                OR EXISTS (
                    SELECT 1 FROM stitching_items si_s 
                    LEFT JOIN product_variants pv_s ON si_s.variant_id = pv_s.id
                    WHERE si_s.stitching_batch_id = sb.id 
                      AND (
                          pv_s.sku LIKE ? 
                          OR si_s.size_name LIKE ? 
                          OR si_s.color_name LIKE ? 
                          OR si_s.catalog_hierarchy LIKE ?
                      )
                )
            )";
            $params = array_merge($params, array_fill(0, 16, $term));
        }

        $sql .= " ORDER BY sb.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create stitching batch.
     */
    public static function create(array $data): int {
        if (empty($data['batch_number'])) {
            if (class_exists('ProductionService')) {
                $data['batch_number'] = ProductionService::generateSequenceNumber('stitching_batches', 'batch_number', 'STCH');
            } else {
                $data['batch_number'] = 'STCH-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            }
        }
        Database::execute(
            "INSERT INTO stitching_batches 
             (name, batch_number, cutting_batch_id, program_id, category_id, subcategory_id, sub_subcategory_id, worker_id, received_qty, stitched_qty, total_weight, rejected_qty, rework_qty, start_date, end_date, status, notes, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [
                $data['name'] ?? null,
                $data['batch_number'],
                !empty($data['cutting_batch_id']) ? $data['cutting_batch_id'] : null,
                $data['program_id'],
                !empty($data['category_id']) ? $data['category_id'] : null,
                !empty($data['subcategory_id']) ? $data['subcategory_id'] : null,
                !empty($data['sub_subcategory_id']) ? $data['sub_subcategory_id'] : null,
                !empty($data['worker_id']) ? $data['worker_id'] : null,
                $data['received_qty'] ?? 0,
                $data['stitched_qty'] ?? 0,
                $data['total_weight'] ?? 0.00,
                $data['rejected_qty'] ?? 0,
                $data['rework_qty'] ?? 0,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['status'] ?? 'IN_PROGRESS',
                $data['notes'] ?? null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update stitching batch.
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = [];

        $allowed = [
            'name', 'batch_number', 'cutting_batch_id', 'program_id', 
            'category_id', 'subcategory_id', 'sub_subcategory_id',
            'worker_id', 'received_qty', 'stitched_qty', 'total_weight', 
            'rejected_qty', 'rework_qty', 'start_date', 'end_date', 'status', 'notes', 'is_active'
        ];

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
        $sql = "UPDATE stitching_batches SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params) >= 0;
    }

    /**
     * Recalculate totals from stitching items.
     */
    public static function recalculateTotals(int $id): array {
        $totals = Database::fetchOne(
            "SELECT COALESCE(SUM(received_qty), 0) as total_received,
                    COALESCE(SUM(stitched_qty), 0) as total_stitched,
                    COALESCE(SUM(weight), 0) as total_weight,
                    COALESCE(SUM(rejected_qty), 0) as total_rejected,
                    COALESCE(SUM(rework_qty), 0) as total_rework
             FROM stitching_items
             WHERE stitching_batch_id = ?",
            [$id]
        );

        Database::execute(
            "UPDATE stitching_batches 
             SET received_qty = ?, stitched_qty = ?, total_weight = ?, rejected_qty = ?, rework_qty = ?
             WHERE id = ?",
            [
                $totals['total_received'],
                $totals['total_stitched'],
                $totals['total_weight'],
                $totals['total_rejected'],
                $totals['total_rework'],
                $id
            ]
        );

        return $totals;
    }

    /**
     * Get variant items for this stitching batch.
     */
    public static function getItems(int $batchId): array {
        return Database::fetchAll(
            "SELECT si.*, 
                    pv.sku, pv.qr_code, pv.mrp as variant_mrp, pv.selling_price as variant_selling_price,
                    COALESCE(si.size_name, s.name, s.code, 'Standard') as size_name,
                    COALESCE(si.color_name, c.name, 'Assorted') as color_name,
                    COALESCE(si.size_name, s.name, s.code) as display_size,
                    COALESCE(si.color_name, c.name) as display_color,
                    s.code as size_code,
                    c.hex_code as color_hex,
                    COALESCE(cat.name, '') as category_name,
                    COALESCE(sc.name, '') as subcategory_name,
                    COALESCE(ssc.name, '') as sub_subcategory_name
             FROM stitching_items si
             LEFT JOIN product_variants pv ON si.variant_id = pv.id
             LEFT JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories cat ON (si.category_id = cat.id OR (si.category_id IS NULL AND p.category_id = cat.id))
             LEFT JOIN subcategories sc ON (si.subcategory_id = sc.id OR (si.subcategory_id IS NULL AND p.subcategory_id = sc.id))
             LEFT JOIN sub_subcategories ssc ON (si.sub_subcategory_id = ssc.id OR (si.sub_subcategory_id IS NULL AND p.sub_subcategory_id = ssc.id))
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE si.stitching_batch_id = ?
             ORDER BY si.id ASC",
            [$batchId]
        );
    }

    /**
     * Get rolls associated with this stitching batch via cutting batch or program.
     */
    public static function getRolls(int $batchId): array {
        $batch = self::find($batchId);
        if (!$batch) return [];
        $rolls = !empty($batch['cutting_batch_id']) ? CuttingBatch::getRolls((int)$batch['cutting_batch_id']) : [];
        if (empty($rolls) && !empty($batch['program_id'])) {
            $rolls = ProductionProgram::getRolls((int)$batch['program_id']);
        }
        return $rolls;
    }
}
