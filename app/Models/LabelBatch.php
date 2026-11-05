<?php
/**
 * Label Batch Model
 * Handles barcode & brand label printing, quantities, and usage tracking.
 */

class LabelBatch {

    /**
     * Find label batch by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT lb.*, 
                    sb.batch_number as stitching_batch_number, sb.name as stitching_name, sb.stitched_qty as stitching_total_qty,
                    pp.program_number, pp.target_date, pp.name as program_name,
                    p.name as product_name, p.code as product_code,
                    cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name
             FROM label_batches lb
             LEFT JOIN stitching_batches sb ON lb.stitching_batch_id = sb.id
             LEFT JOIN production_programs pp ON lb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN categories cat ON sb.category_id = cat.id
             LEFT JOIN subcategories subcat ON sb.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON sb.sub_subcategory_id = subsubcat.id
             WHERE lb.id = ?",
            [$id]
        );
    }

    /**
     * Get all label batches with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT lb.*, 
                       pp.program_number, pp.name as program_name,
                       p.name as product_name, p.code as product_code
                FROM label_batches lb
                LEFT JOIN production_programs pp ON lb.program_id = pp.id
                LEFT JOIN products p ON pp.product_id = p.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND lb.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['program_id'])) {
            $sql .= " AND lb.program_id = ?";
            $params[] = $filters['program_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (lb.batch_number LIKE ? OR pp.program_number LIKE ? OR pp.name LIKE ? OR p.name LIKE ? OR p.code LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search, $search]);
        }

        $sql .= " ORDER BY lb.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create label batch.
     */
    public static function create(array $data): int {
        if (empty($data['batch_number'])) {
            if (class_exists('ProductionService')) {
                $data['batch_number'] = ProductionService::generateSequenceNumber('label_batches', 'batch_number', 'LBL');
            } else {
                $data['batch_number'] = 'LBL-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            }
        }
        Database::execute(
            "INSERT INTO label_batches 
             (program_id, stitching_batch_id, batch_number, required_qty, printed_qty, used_qty, damaged_qty, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['program_id'],
                $data['stitching_batch_id'] ?? null,
                $data['batch_number'],
                $data['required_qty'] ?? 0,
                $data['printed_qty'] ?? 0,
                $data['used_qty'] ?? 0,
                $data['damaged_qty'] ?? 0,
                $data['status'] ?? 'PENDING',
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update label batch.
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = [];

        $allowed = ['stitching_batch_id', 'required_qty', 'printed_qty', 'used_qty', 'damaged_qty', 'status'];
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
        $sql = "UPDATE label_batches SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params) >= 0;
    }

    /**
     * Recalculate label batch totals.
     */
    public static function recalculateTotals(int $id): array {
        $totals = Database::fetchOne(
            "SELECT COALESCE(SUM(required_qty), 0) as total_required,
                    COALESCE(SUM(printed_qty), 0) as total_printed,
                    COALESCE(SUM(used_qty), 0) as total_used,
                    COALESCE(SUM(damaged_qty), 0) as total_damaged
             FROM label_items
             WHERE label_batch_id = ?",
            [$id]
        );

        Database::execute(
            "UPDATE label_batches 
             SET required_qty = ?, printed_qty = ?, used_qty = ?, damaged_qty = ?
             WHERE id = ?",
            [
                $totals['total_required'],
                $totals['total_printed'],
                $totals['total_used'],
                $totals['total_damaged'],
                $id
            ]
        );

        return $totals;
    }

    /**
     * Get variant items for this label batch.
     */
    public static function getItems(int $batchId): array {
        return Database::fetchAll(
            "SELECT li.*, 
                    pv.sku, pv.qr_code, 
                    COALESCE(li.base_price, pv.mrp, 0.00) as base_price, 
                    COALESCE(li.discount_price, pv.selling_price, 0.00) as discount_price,
                    COALESCE(li.size_name, s.name, s.code, 'Standard') as display_size,
                    COALESCE(li.color_name, c.name, 'Default') as display_color,
                    COALESCE(li.size_name, s.name, s.code, 'Standard') as size_name,
                    COALESCE(li.color_name, c.name, 'Default') as color_name,
                    s.code as size_code,
                    c.hex_code as color_hex,
                    cat.name as category_name,
                    subcat.name as subcategory_name,
                    subsubcat.name as sub_subcategory_name
             FROM label_items li
             LEFT JOIN product_variants pv ON li.variant_id = pv.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN categories cat ON li.category_id = cat.id
             LEFT JOIN subcategories subcat ON li.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON li.sub_subcategory_id = subsubcat.id
             WHERE li.label_batch_id = ?
             ORDER BY li.id ASC",
            [$batchId]
        );
    }

    /**
     * Delete label batch and its items.
     */
    public static function delete(int $id): bool {
        if (class_exists('LabelItem')) {
            LabelItem::deleteByBatch($id);
        } else {
            Database::execute("DELETE FROM label_items WHERE label_batch_id = ?", [$id]);
        }
        return Database::execute("DELETE FROM label_batches WHERE id = ?", [$id]) > 0;
    }
}

