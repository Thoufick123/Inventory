<?php
/**
 * Cutting Batch Model
 * Handles fabric cutting operations, operators, roll specs, wastage, and item yields.
 */

class CuttingBatch {

    /**
     * Find cutting batch by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT cb.*, 
                    pp.name as program_name, pp.program_number, pp.target_date, pp.start_date, pp.end_date,
                    sr.roll_number, sr.color as roll_color, sr.design_name as roll_design,
                    s.name as seller_name, s.company as seller_company, s.material_type,
                    c.name as category_name,
                    sc.name as subcategory_name,
                    ssc.name as sub_subcategory_name,
                    pu.name as unit_name, pu.code as unit_code,
                    p.name as product_name, p.code as product_code,
                    u.full_name as operator_name
             FROM cutting_batches cb
             LEFT JOIN production_programs pp ON cb.program_id = pp.id
             LEFT JOIN seller_rolls sr ON (cb.seller_roll_id = sr.id OR pp.seller_roll_id = sr.id)
             LEFT JOIN sellers s ON sr.seller_id = s.id
             LEFT JOIN categories c ON (cb.category_id = c.id OR pp.category_id = c.id)
             LEFT JOIN subcategories sc ON (cb.subcategory_id = sc.id OR pp.subcategory_id = sc.id)
             LEFT JOIN sub_subcategories ssc ON (cb.sub_subcategory_id = ssc.id OR pp.sub_subcategory_id = ssc.id)
             LEFT JOIN production_units pu ON (cb.production_unit_id = pu.id OR pp.production_unit_id = pu.id)
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN users u ON cb.operator_id = u.id
             WHERE cb.id = ?",
            [$id]
        );
    }

    /**
     * Find cutting batch by batch number.
     */
    public static function findByNumber(string $batchNumber): ?array {
        return Database::fetchOne(
            "SELECT cb.*, pp.name as program_name, pp.program_number 
             FROM cutting_batches cb
             LEFT JOIN production_programs pp ON cb.program_id = pp.id
             WHERE cb.batch_number = ?",
            [$batchNumber]
        );
    }

    /**
     * Get all cutting batches with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT cb.*, 
                       pp.name as program_name, pp.program_number, 
                       sr.roll_number, sr.color as roll_color, sr.design_name as roll_design,
                       s.name as seller_name, s.material_type,
                       c.name as category_name,
                       sc.name as subcategory_name,
                       ssc.name as sub_subcategory_name,
                       pu.name as unit_name, pu.code as unit_code,
                       p.name as product_name, p.code as product_code,
                       u.full_name as operator_name
                FROM cutting_batches cb
                LEFT JOIN production_programs pp ON cb.program_id = pp.id
                LEFT JOIN seller_rolls sr ON (cb.seller_roll_id = sr.id OR pp.seller_roll_id = sr.id)
                LEFT JOIN sellers s ON sr.seller_id = s.id
                LEFT JOIN categories c ON (cb.category_id = c.id OR pp.category_id = c.id)
                LEFT JOIN subcategories sc ON (cb.subcategory_id = sc.id OR pp.subcategory_id = sc.id)
                LEFT JOIN sub_subcategories ssc ON (cb.sub_subcategory_id = ssc.id OR pp.sub_subcategory_id = ssc.id)
                LEFT JOIN production_units pu ON (cb.production_unit_id = pu.id OR pp.production_unit_id = pu.id)
                LEFT JOIN products p ON pp.product_id = p.id
                LEFT JOIN users u ON cb.operator_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND cb.status = ?";
            $params[] = $filters['status'];
            if ($filters['status'] !== 'CANCELLED') {
                $sql .= " AND (cb.is_active IS NULL OR cb.is_active = 1)";
            }
        } else {
            $sql .= " AND (cb.is_active IS NULL OR cb.is_active = 1)";
        }

        if (!empty($filters['program_id'])) {
            $sql .= " AND cb.program_id = ?";
            $params[] = $filters['program_id'];
        }

        if (!empty($filters['production_unit_id'])) {
            $sql .= " AND (cb.production_unit_id = ? OR pp.production_unit_id = ?)";
            $params[] = $filters['production_unit_id'];
            $params[] = $filters['production_unit_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                cb.name LIKE ? 
                OR cb.batch_number LIKE ? 
                OR pp.name LIKE ? 
                OR pp.program_number LIKE ? 
                OR p.name LIKE ? 
                OR p.code LIKE ? 
                OR p.material LIKE ? 
                OR c.name LIKE ? 
                OR sc.name LIKE ? 
                OR ssc.name LIKE ? 
                OR pu.name LIKE ? 
                OR pu.code LIKE ? 
                OR sr.roll_number LIKE ? 
                OR sr.color LIKE ? 
                OR sr.design_name LIKE ? 
                OR s.name LIKE ? 
                OR s.company LIKE ? 
                OR u.full_name LIKE ? 
                OR EXISTS (
                    SELECT 1 FROM cutting_batch_rolls cbr_s 
                    LEFT JOIN seller_rolls sr_s ON cbr_s.seller_roll_id = sr_s.id
                    LEFT JOIN categories c_s ON cbr_s.category_id = c_s.id
                    LEFT JOIN subcategories sc_s ON cbr_s.subcategory_id = sc_s.id
                    LEFT JOIN sub_subcategories ssc_s ON cbr_s.sub_subcategory_id = ssc_s.id
                    WHERE cbr_s.cutting_batch_id = cb.id 
                      AND (
                          sr_s.roll_number LIKE ? 
                          OR sr_s.color LIKE ? 
                          OR sr_s.design_name LIKE ? 
                          OR cbr_s.roll_color LIKE ? 
                          OR cbr_s.roll_design LIKE ? 
                          OR c_s.name LIKE ? 
                          OR sc_s.name LIKE ? 
                          OR ssc_s.name LIKE ?
                      )
                )
                OR EXISTS (
                    SELECT 1 FROM cutting_items ci_s 
                    LEFT JOIN product_variants pv_s ON ci_s.variant_id = pv_s.id
                    WHERE ci_s.cutting_batch_id = cb.id 
                      AND (
                          pv_s.sku LIKE ? 
                          OR ci_s.size_name LIKE ? 
                          OR ci_s.color_name LIKE ? 
                          OR ci_s.catalog_hierarchy LIKE ?
                      )
                )
            )";
            // 18 main fields + 8 cbr_s fields + 4 ci_s fields = 30 params
            $params = array_merge($params, array_fill(0, 30, $term));
        }

        $sql .= " ORDER BY cb.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create cutting batch.
     */
    public static function create(array $data): int {
        if (empty($data['batch_number'])) {
            if (class_exists('ProductionService')) {
                $data['batch_number'] = ProductionService::generateSequenceNumber('cutting_batches', 'batch_number', 'CUT');
            } else {
                $data['batch_number'] = 'CUT-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            }
        }
        Database::execute(
            "INSERT INTO cutting_batches 
             (name, program_id, batch_number, seller_roll_id, roll_weight, roll_price, wastage_weight, wastage_cost, used_weight, cost_per_piece, category_id, subcategory_id, sub_subcategory_id, operator_id, production_unit_id, material_issued, planned_qty, cut_qty, rejected_qty, wastage_qty, status, notes, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [
                $data['name'] ?? null,
                $data['program_id'],
                $data['batch_number'],
                $data['seller_roll_id'] ?? null,
                $data['roll_weight'] ?? 0.00,
                $data['roll_price'] ?? 0.00,
                $data['wastage_weight'] ?? 0.00,
                $data['wastage_cost'] ?? 0.00,
                $data['used_weight'] ?? 0.00,
                $data['cost_per_piece'] ?? 0.00,
                $data['category_id'] ?? null,
                $data['subcategory_id'] ?? null,
                $data['sub_subcategory_id'] ?? null,
                $data['operator_id'] ?? null,
                $data['production_unit_id'] ?? null,
                $data['material_issued'] ?? null,
                $data['planned_qty'] ?? 0,
                $data['cut_qty'] ?? 0,
                $data['rejected_qty'] ?? 0,
                $data['wastage_qty'] ?? 0,
                $data['status'] ?? 'IN_PROGRESS',
                $data['notes'] ?? null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update cutting batch.
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = [];

        $allowed = [
            'name', 'batch_number', 'program_id', 'seller_roll_id', 'roll_weight', 'roll_price',
            'wastage_weight', 'wastage_cost', 'used_weight', 'cost_per_piece', 'category_id', 'subcategory_id', 'sub_subcategory_id',
            'operator_id', 'production_unit_id', 'material_issued', 'planned_qty', 'cut_qty', 'rejected_qty', 'wastage_qty',
            'status', 'notes'
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
        $sql = "UPDATE cutting_batches SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params) >= 0;
    }

    /**
     * Delete / Soft-delete cutting batch.
     */
    public static function delete(int $id): bool {
        return Database::execute("UPDATE cutting_batches SET is_active = 0, status = 'CANCELLED' WHERE id = ?", [$id]) >= 0;
    }

    /**
     * Recalculate cutting batch totals from cutting_items.
     */
    public static function recalculateTotals(int $id): array {
        $totals = Database::fetchOne(
            "SELECT COALESCE(SUM(planned_qty), 0) as total_planned,
                    COALESCE(SUM(cut_qty), 0) as total_cut,
                    COALESCE(SUM(rejected_qty), 0) as total_rejected,
                    COALESCE(SUM(wastage_qty), 0) as total_wastage
             FROM cutting_items
             WHERE cutting_batch_id = ?",
            [$id]
        );

        $totalCut = (int)($totals['total_cut'] ?? 0);
        $totalRejected = (int)($totals['total_rejected'] ?? 0);
        $totalWastage = (int)($totals['total_wastage'] ?? 0);
        $totalPlanned = (int)($totals['total_planned'] ?? 0);

        // Fetch roll price to recalculate cost per piece
        $batch = Database::fetchOne("SELECT roll_price, roll_weight, wastage_weight FROM cutting_batches WHERE id = ?", [$id]);
        $costPerPiece = 0.00;
        if ($batch && (float)$batch['roll_price'] > 0 && $totalCut > 0) {
            $costPerPiece = round((float)$batch['roll_price'] / $totalCut, 2);
        }

        Database::execute(
            "UPDATE cutting_batches 
             SET planned_qty = ?, cut_qty = ?, rejected_qty = ?, wastage_qty = ?, cost_per_piece = ?
             WHERE id = ?",
            [$totalPlanned, $totalCut, $totalRejected, $totalWastage, $costPerPiece, $id]
        );

        return $totals ?? [];
    }

    /**
     * Get cutting batch items.
     */
    public static function getItems(int $batchId): array {
        return CuttingItem::getByBatch($batchId);
    }

    /**
     * Get all assigned fabric rolls for this cutting batch.
     */
    public static function getRolls(int $batchId): array {
        return Database::fetchAll(
            "SELECT cbr.*, 
                    sr.roll_number, sr.color as roll_color, sr.design_name as roll_design,
                    sr.weight as original_weight, sr.price as original_price,
                    s.name as seller_name, s.company as seller_company, s.material_type,
                    c.name as category_name,
                    sc.name as subcategory_name,
                    ssc.name as sub_subcategory_name
             FROM cutting_batch_rolls cbr
             JOIN seller_rolls sr ON cbr.seller_roll_id = sr.id
             LEFT JOIN sellers s ON (cbr.seller_id = s.id OR sr.seller_id = s.id)
             LEFT JOIN categories c ON cbr.category_id = c.id
             LEFT JOIN subcategories sc ON cbr.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON cbr.sub_subcategory_id = ssc.id
             WHERE cbr.cutting_batch_id = ?
             ORDER BY cbr.id ASC",
            [$batchId]
        );
    }

    /**
     * Sync assigned rolls for a cutting batch.
     */
    public static function syncRolls(int $batchId, array $rolls): void {
        Database::execute("DELETE FROM cutting_batch_rolls WHERE cutting_batch_id = ?", [$batchId]);
        foreach ($rolls as $r) {
            $rollId = !empty($r['seller_roll_id']) ? (int)$r['seller_roll_id'] : 0;
            if ($rollId <= 0) continue;

            $rollWeight = max(0.0, (float)($r['roll_weight'] ?? 0));
            $rollPrice = max(0.0, (float)($r['roll_price'] ?? 0));
            $sellerId = !empty($r['seller_id']) ? (int)$r['seller_id'] : null;
            $categoryId = !empty($r['category_id']) ? (int)$r['category_id'] : null;
            $subcategoryId = !empty($r['subcategory_id']) ? (int)$r['subcategory_id'] : null;
            $subSubcategoryId = !empty($r['sub_subcategory_id']) ? (int)$r['sub_subcategory_id'] : null;
            $color = $r['roll_color'] ?? '';
            $design = $r['roll_design'] ?? '';

            if (!$sellerId || empty($color)) {
                $sr = Database::fetchOne("SELECT seller_id, weight, price, color, design_name FROM seller_rolls WHERE id = ?", [$rollId]);
                if ($sr) {
                    if (!$sellerId) $sellerId = (int)$sr['seller_id'];
                    if ($rollWeight <= 0) $rollWeight = (float)$sr['weight'];
                    if ($rollPrice <= 0) $rollPrice = (float)$sr['price'];
                    if (empty($color)) $color = $sr['color'] ?? '';
                    if (empty($design)) $design = $sr['design_name'] ?? '';
                }
            }

            $wastageWeight = max(0.0, (float)($r['wastage_weight'] ?? 0));
            $ratePerKg = $rollWeight > 0 ? ($rollPrice / $rollWeight) : 0;
            $wastageCost = max(0.0, (float)($r['wastage_cost'] ?? round($wastageWeight * $ratePerKg, 2)));

            Database::execute(
                "INSERT INTO cutting_batch_rolls (cutting_batch_id, seller_roll_id, seller_id, category_id, subcategory_id, sub_subcategory_id, roll_weight, roll_price, wastage_weight, wastage_cost, roll_color, roll_design)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$batchId, $rollId, $sellerId, $categoryId, $subcategoryId, $subSubcategoryId, $rollWeight, $rollPrice, $wastageWeight, $wastageCost, $color, $design]
            );
        }
    }
}
