<?php
/**
 * QC Item Model
 * Manages variant-wise inspection results and defect classifications.
 */

class QCItem {

    /**
     * Get items for a QC batch.
     */
    public static function getByBatch(int $batchId): array {
        return Database::fetchAll(
            "SELECT qi.*, 
                    pv.sku as pv_sku, pv.qr_code as pv_qr,
                    COALESCE(qi.size_name, s.name, s.code, 'Standard') as display_size,
                    COALESCE(qi.color_name, c.name, 'Assorted') as display_color,
                    COALESCE(pv.sku, CONCAT(COALESCE(qi.size_name, s.name, 'Size'), '-', COALESCE(qi.color_name, c.name, 'Color'))) as sku,
                    cat.name as category_name, cat.code as category_code,
                    subcat.name as subcategory_name, subcat.code as subcategory_code,
                    subsubcat.name as sub_subcategory_name, subsubcat.code as sub_subcategory_code
             FROM qc_items qi
             LEFT JOIN product_variants pv ON qi.variant_id = pv.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN categories cat ON qi.category_id = cat.id
             LEFT JOIN subcategories subcat ON qi.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON qi.sub_subcategory_id = subsubcat.id
             WHERE qi.qc_batch_id = ?
             ORDER BY qi.id ASC",
            [$batchId]
        );
    }

    /**
     * Create QC item record.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO qc_items 
             (qc_batch_id, variant_id, category_id, subcategory_id, sub_subcategory_id, catalog_hierarchy, size_name, color_name, tag_code, base_price, discount_price, inspected_qty, passed_qty, rejected_qty, rework_qty, defect_reason, defect_notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['qc_batch_id'],
                !empty($data['variant_id']) ? $data['variant_id'] : null,
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null,
                !empty($data['sub_subcategory_id']) ? (int)$data['sub_subcategory_id'] : null,
                $data['catalog_hierarchy'] ?? null,
                $data['size_name'] ?? null,
                $data['color_name'] ?? null,
                $data['tag_code'] ?? null,
                $data['base_price'] ?? 0.00,
                $data['discount_price'] ?? 0.00,
                $data['inspected_qty'] ?? 0,
                $data['passed_qty'] ?? 0,
                $data['rejected_qty'] ?? 0,
                $data['rework_qty'] ?? 0,
                $data['defect_reason'] ?? null,
                $data['defect_notes'] ?? null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update QC item quantities and defect information.
     */
    public static function update(int $id, array $data): bool {
        return Database::execute(
            "UPDATE qc_items 
             SET inspected_qty = ?, passed_qty = ?, rejected_qty = ?, rework_qty = ?, defect_reason = ?, defect_notes = ?
             WHERE id = ?",
            [
                $data['inspected_qty'] ?? 0,
                $data['passed_qty'] ?? 0,
                $data['rejected_qty'] ?? 0,
                $data['rework_qty'] ?? 0,
                $data['defect_reason'] ?? null,
                $data['defect_notes'] ?? null,
                $id
            ]
        ) >= 0;
    }

    /**
     * Delete all items for a QC batch.
     */
    public static function deleteByBatch(int $batchId): int {
        return Database::execute(
            "DELETE FROM qc_items WHERE qc_batch_id = ?",
            [$batchId]
        );
    }
}
