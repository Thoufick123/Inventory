<?php
/**
 * Production Program Model
 * Manages production program lifecycle, planning, and item associations.
 */

class ProductionProgram {

    /**
     * Find program by ID with related unit, product, seller roll, category hierarchy, and staff details.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT pp.*, 
                    pu.name as unit_name, pu.code as unit_code,
                    p.name as product_name, p.code as product_code, p.material, p.image_url as product_image,
                    p.qr_code_prefix, p.category_code, p.subcategory_code, p.sub_subcategory_code,
                    sr.roll_number, sr.color as roll_color, sr.design_name as roll_design,
                    s.name as seller_name, s.company as seller_company, s.material_type,
                    c.name as category_name,
                    sc.name as subcategory_name,
                    ssc.name as sub_subcategory_name,
                    u_assign.full_name as assigned_to_name,
                    u_appr.full_name as approved_by_name,
                    u_creat.full_name as created_by_name
             FROM production_programs pp
             LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN seller_rolls sr ON pp.seller_roll_id = sr.id
             LEFT JOIN sellers s ON (pp.seller_id = s.id OR sr.seller_id = s.id)
             LEFT JOIN categories c ON pp.category_id = c.id
             LEFT JOIN subcategories sc ON pp.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON pp.sub_subcategory_id = ssc.id
             LEFT JOIN users u_assign ON pp.assigned_to = u_assign.id
             LEFT JOIN users u_appr ON pp.approved_by = u_appr.id
             LEFT JOIN users u_creat ON pp.created_by = u_creat.id
             WHERE pp.id = ?",
            [$id]
        );
    }

    /**
     * Find program by program number.
     */
    public static function findByNumber(string $programNumber): ?array {
        return Database::fetchOne(
            "SELECT pp.*, pu.name as unit_name, p.name as product_name,
                    sr.roll_number, sr.color as roll_color, sr.design_name as roll_design,
                    s.name as seller_name,
                    c.name as category_name, sc.name as subcategory_name, ssc.name as sub_subcategory_name
             FROM production_programs pp
             LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN seller_rolls sr ON pp.seller_roll_id = sr.id
             LEFT JOIN sellers s ON (pp.seller_id = s.id OR sr.seller_id = s.id)
             LEFT JOIN categories c ON pp.category_id = c.id
             LEFT JOIN subcategories sc ON pp.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON pp.sub_subcategory_id = ssc.id
             WHERE pp.program_number = ?",
            [$programNumber]
        );
    }

    /**
     * Get all programs with optional filtering.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT pp.*, 
                       pu.name as unit_name, pu.code as unit_code,
                       p.name as product_name, p.code as product_code,
                       sr.roll_number, sr.color as roll_color, sr.design_name as roll_design,
                       s.name as seller_name, s.company as seller_company, s.material_type,
                       c.name as category_name,
                       sc.name as subcategory_name,
                       ssc.name as sub_subcategory_name,
                       u.full_name as assigned_to_name
                FROM production_programs pp
                LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
                LEFT JOIN products p ON pp.product_id = p.id
                LEFT JOIN seller_rolls sr ON pp.seller_roll_id = sr.id
                LEFT JOIN sellers s ON (pp.seller_id = s.id OR sr.seller_id = s.id)
                LEFT JOIN categories c ON pp.category_id = c.id
                LEFT JOIN subcategories sc ON pp.subcategory_id = sc.id
                LEFT JOIN sub_subcategories ssc ON pp.sub_subcategory_id = ssc.id
                LEFT JOIN users u ON pp.assigned_to = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND pp.status = ?";
            $params[] = $filters['status'];
            if ($filters['status'] !== 'CANCELLED') {
                $sql .= " AND (pp.is_active IS NULL OR pp.is_active = 1)";
            }
        } else {
            $sql .= " AND (pp.is_active IS NULL OR pp.is_active = 1)";
        }

        if (!empty($filters['production_unit_id'])) {
            $sql .= " AND pp.production_unit_id = ?";
            $params[] = $filters['production_unit_id'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND pp.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                pp.name LIKE ? 
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
                    SELECT 1 FROM production_program_rolls ppr_s 
                    LEFT JOIN seller_rolls sr_s ON ppr_s.seller_roll_id = sr_s.id
                    LEFT JOIN categories c_s ON ppr_s.category_id = c_s.id
                    LEFT JOIN subcategories sc_s ON ppr_s.subcategory_id = sc_s.id
                    LEFT JOIN sub_subcategories ssc_s ON ppr_s.sub_subcategory_id = ssc_s.id
                    WHERE ppr_s.production_program_id = pp.id 
                      AND (
                          sr_s.roll_number LIKE ? 
                          OR sr_s.color LIKE ? 
                          OR sr_s.design_name LIKE ? 
                          OR ppr_s.roll_color LIKE ? 
                          OR ppr_s.roll_design LIKE ? 
                          OR c_s.name LIKE ? 
                          OR sc_s.name LIKE ? 
                          OR ssc_s.name LIKE ?
                      )
                )
            )";
            $params = array_merge($params, array_fill(0, 24, $term));
        }

        $sql .= " ORDER BY pp.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create a new production program.
     */
    public static function create(array $data): int {
        $createdBy = !empty($data['created_by']) ? (int)$data['created_by'] : null;
        if ($createdBy) {
            $u = Database::fetchOne("SELECT id FROM users WHERE id = ?", [$createdBy]);
            if (!$u) {
                $first = Database::fetchOne("SELECT id FROM users WHERE is_active = 1 LIMIT 1");
                $createdBy = $first ? (int)$first['id'] : null;
            }
        } else {
            $first = Database::fetchOne("SELECT id FROM users WHERE is_active = 1 LIMIT 1");
            $createdBy = $first ? (int)$first['id'] : null;
        }

        $assignedTo = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;
        if ($assignedTo) {
            $u = Database::fetchOne("SELECT id FROM users WHERE id = ?", [$assignedTo]);
            if (!$u) $assignedTo = null;
        }

        $unitId = !empty($data['production_unit_id']) ? (int)$data['production_unit_id'] : null;
        if ($unitId) {
            $unit = Database::fetchOne("SELECT id FROM production_units WHERE id = ?", [$unitId]);
            if (!$unit) $unitId = null;
        }

        $productId = !empty($data['product_id']) ? (int)$data['product_id'] : null;
        if ($productId) {
            $prod = Database::fetchOne("SELECT id FROM products WHERE id = ?", [$productId]);
            if (!$prod) $productId = null;
        }

        $sellerRollId = !empty($data['seller_roll_id']) ? (int)$data['seller_roll_id'] : null;
        if ($sellerRollId) {
            $sr = Database::fetchOne("SELECT id FROM seller_rolls WHERE id = ?", [$sellerRollId]);
            if (!$sr) $sellerRollId = null;
        }

        $sellerId = !empty($data['seller_id']) ? (int)$data['seller_id'] : null;
        if ($sellerId) {
            $s = Database::fetchOne("SELECT id FROM sellers WHERE id = ?", [$sellerId]);
            if (!$s) $sellerId = null;
        }

        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        if ($categoryId) {
            $c = Database::fetchOne("SELECT id FROM categories WHERE id = ?", [$categoryId]);
            if (!$c) $categoryId = null;
        }

        $subcategoryId = !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null;
        if ($subcategoryId) {
            $sc = Database::fetchOne("SELECT id FROM subcategories WHERE id = ?", [$subcategoryId]);
            if (!$sc) $subcategoryId = null;
        }

        $subSubcategoryId = !empty($data['sub_subcategory_id']) ? (int)$data['sub_subcategory_id'] : null;
        if ($subSubcategoryId) {
            $ssc = Database::fetchOne("SELECT id FROM sub_subcategories WHERE id = ?", [$subSubcategoryId]);
            if (!$ssc) $subSubcategoryId = null;
        }

        Database::execute(
            "INSERT INTO production_programs 
             (name, program_number, production_unit_id, product_id, seller_roll_id, seller_id, roll_weight, roll_price, category_id, subcategory_id, sub_subcategory_id, planned_quantity, completed_quantity, start_date, end_date, target_date, priority, assigned_to, status, approved_by, approved_at, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'] ?? null,
                $data['program_number'],
                $unitId,
                $productId,
                $sellerRollId,
                $sellerId,
                $data['roll_weight'] ?? 0.00,
                $data['roll_price'] ?? 0.00,
                $categoryId,
                $subcategoryId,
                $subSubcategoryId,
                $data['planned_quantity'] ?? 0,
                $data['completed_quantity'] ?? 0,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['target_date'] ?? ($data['end_date'] ?? null),
                $data['priority'] ?? 'MEDIUM',
                $assignedTo,
                $data['status'] ?? 'APPROVED',
                $data['approved_by'] ?? null,
                $data['approved_at'] ?? null,
                $data['notes'] ?? null,
                $createdBy,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update an existing production program.
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = [];

        $allowed = [
            'name', 'program_number', 'production_unit_id', 'product_id', 
            'seller_roll_id', 'seller_id', 'roll_weight', 'roll_price', 
            'category_id', 'subcategory_id', 'sub_subcategory_id', 
            'planned_quantity', 'completed_quantity', 'start_date', 'end_date', 'target_date', 
            'priority', 'assigned_to', 'status', 'approved_by', 'approved_at', 'notes'
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
        $sql = "UPDATE production_programs SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params) >= 0;
    }

    /**
     * Approve a program.
     */
    public static function approve(int $id, int $userId): bool {
        return Database::execute(
            "UPDATE production_programs 
             SET status = 'APPROVED', approved_by = ?, approved_at = NOW() 
             WHERE id = ? AND status = 'DRAFT'",
            [$userId, $id]
        ) > 0;
    }

    /**
     * Delete / soft-delete a program.
     */
    public static function delete(int $id): bool {
        return Database::execute("UPDATE production_programs SET is_active = 0, status = 'CANCELLED' WHERE id = ?", [$id]) >= 0;
    }

    /**
     * Update status.
     */
    public static function updateStatus(int $id, string $status): bool {
        return Database::execute(
            "UPDATE production_programs SET status = ? WHERE id = ?",
            [$status, $id]
        ) > 0;
    }

    /**
     * Update completed quantity based on finished production batch items.
     */
    public static function recalculateCompletedQty(int $id): int {
        $result = Database::fetchOne(
            "SELECT COALESCE(SUM(pbi.quantity), 0) as completed
             FROM production_batches pb
             JOIN production_batch_items pbi ON pb.id = pbi.production_batch_id
             WHERE pb.program_id = ? AND pb.status IN ('APPROVED', 'DISPATCHED', 'RECEIVED')",
            [$id]
        );
        $completed = (int)($result['completed'] ?? 0);
        Database::execute(
            "UPDATE production_programs SET completed_quantity = ? WHERE id = ?",
            [$completed, $id]
        );
        return $completed;
    }

    /**
     * Get all assigned fabric rolls for this program.
     */
    public static function getRolls(int $programId): array {
        return Database::fetchAll(
            "SELECT ppr.*, 
                    sr.roll_number, sr.color as roll_color, sr.design_name as roll_design,
                    sr.weight as original_weight, sr.price as original_price, sr.quantity as stock_quantity,
                    s.name as seller_name, s.company as seller_company, s.material_type,
                    c.name as category_name,
                    sc.name as subcategory_name,
                    ssc.name as sub_subcategory_name
             FROM production_program_rolls ppr
             JOIN seller_rolls sr ON ppr.seller_roll_id = sr.id
             LEFT JOIN sellers s ON (ppr.seller_id = s.id OR sr.seller_id = s.id)
             LEFT JOIN categories c ON ppr.category_id = c.id
             LEFT JOIN subcategories sc ON ppr.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON ppr.sub_subcategory_id = ssc.id
             WHERE ppr.production_program_id = ?
             ORDER BY ppr.id ASC",
            [$programId]
        );
    }

    /**
     * Sync assigned rolls for a program.
     */
    public static function syncRolls(int $programId, array $rolls): void {
        Database::execute("DELETE FROM production_program_rolls WHERE production_program_id = ?", [$programId]);
        foreach ($rolls as $r) {
            $rollId = !empty($r['seller_roll_id']) ? (int)$r['seller_roll_id'] : 0;
            if ($rollId <= 0) continue;

            $rollQty = max(0, (int)($r['roll_qty'] ?? ($r['quantity'] ?? 0)));
            $rollWeight = max(0.0, (float)($r['roll_weight'] ?? 0));
            $rollPrice = max(0.0, (float)($r['roll_price'] ?? 0));
            $sellerId = !empty($r['seller_id']) ? (int)$r['seller_id'] : null;
            $categoryId = !empty($r['category_id']) ? (int)$r['category_id'] : null;
            $subcategoryId = !empty($r['subcategory_id']) ? (int)$r['subcategory_id'] : null;
            $subSubcategoryId = !empty($r['sub_subcategory_id']) ? (int)$r['sub_subcategory_id'] : null;
            $color = $r['roll_color'] ?? '';
            $design = $r['roll_design'] ?? '';

            if (!$sellerId || empty($color) || $rollQty <= 0) {
                $sr = Database::fetchOne("SELECT seller_id, weight, price, color, design_name, quantity FROM seller_rolls WHERE id = ?", [$rollId]);
                if ($sr) {
                    if (!$sellerId) $sellerId = (int)$sr['seller_id'];
                    if ($rollQty <= 0 && !empty($sr['quantity'])) $rollQty = (int)$sr['quantity'];
                    if ($rollWeight <= 0) $rollWeight = (float)$sr['weight'];
                    if ($rollPrice <= 0) $rollPrice = (float)$sr['price'];
                    if (empty($color)) $color = $sr['color'] ?? '';
                    if (empty($design)) $design = $sr['design_name'] ?? '';
                }
            }

            Database::execute(
                "INSERT INTO production_program_rolls (program_id, production_program_id, seller_roll_id, seller_id, category_id, subcategory_id, sub_subcategory_id, roll_qty, roll_weight, roll_price, roll_color, roll_design)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$programId, $programId, $rollId, $sellerId, $categoryId, $subcategoryId, $subSubcategoryId, $rollQty, $rollWeight, $rollPrice, $color, $design]
            );
        }
    }

    /**
     * Get all program items / planned variants.
     */
    public static function getItems(int $programId): array {
        if (!class_exists('ProductionProgramItem') && defined('BASE_PATH')) {
            $path = BASE_PATH . '/app/Models/ProductionProgramItem.php';
            if (file_exists($path)) {
                require_once $path;
            }
        }

        if (class_exists('ProductionProgramItem')) {
            return ProductionProgramItem::getByProgram($programId);
        }

        return Database::fetchAll(
            "SELECT ppi.*, 
                    pv.sku, pv.qr_code, pv.selling_price, pv.production_cost,
                    s.name as size_name, s.code as size_code,
                    c.name as color_name, c.hex_code as color_hex
             FROM production_program_items ppi
             JOIN product_variants pv ON ppi.variant_id = pv.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE ppi.program_id = ?
             ORDER BY s.sort_order ASC, c.name ASC",
            [$programId]
        );
    }
}

