<?php
/**
 * Stitching Item Model
 * Manages variant-wise received, stitched, weight, prices, rejected, and rework quantities,
 * including per-item 3-tier catalog hierarchy tracking.
 */

class StitchingItem {

    /**
     * Get items for a stitching batch with catalog and variant details.
     */
    public static function getByBatch(int $batchId): array {
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
     * Create stitching item record.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO stitching_items 
             (stitching_batch_id, variant_id, category_id, subcategory_id, sub_subcategory_id, catalog_hierarchy, size_name, color_name, received_qty, stitched_qty, weight_per_piece, weight, base_price, discount_price, rejected_qty, rework_qty)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['stitching_batch_id'],
                !empty($data['variant_id']) ? (int)$data['variant_id'] : null,
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null,
                !empty($data['sub_subcategory_id']) ? (int)$data['sub_subcategory_id'] : null,
                $data['catalog_hierarchy'] ?? null,
                $data['size_name'] ?? null,
                $data['color_name'] ?? null,
                $data['received_qty'] ?? 0,
                $data['stitched_qty'] ?? 0,
                $data['weight_per_piece'] ?? 0.000,
                $data['weight'] ?? 0.00,
                $data['base_price'] ?? 0.00,
                $data['discount_price'] ?? 0.00,
                $data['rejected_qty'] ?? 0,
                $data['rework_qty'] ?? 0,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update stitching item.
     */
    public static function update(int $id, array $data): bool {
        return Database::execute(
            "UPDATE stitching_items 
             SET variant_id = ?, category_id = ?, subcategory_id = ?, sub_subcategory_id = ?, catalog_hierarchy = ?, size_name = ?, color_name = ?, received_qty = ?, stitched_qty = ?, weight_per_piece = ?, weight = ?, base_price = ?, discount_price = ?, rejected_qty = ?, rework_qty = ?
             WHERE id = ?",
            [
                !empty($data['variant_id']) ? (int)$data['variant_id'] : null,
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null,
                !empty($data['sub_subcategory_id']) ? (int)$data['sub_subcategory_id'] : null,
                $data['catalog_hierarchy'] ?? null,
                $data['size_name'] ?? null,
                $data['color_name'] ?? null,
                $data['received_qty'] ?? 0,
                $data['stitched_qty'] ?? 0,
                $data['weight_per_piece'] ?? 0.000,
                $data['weight'] ?? 0.00,
                $data['base_price'] ?? 0.00,
                $data['discount_price'] ?? 0.00,
                $data['rejected_qty'] ?? 0,
                $data['rework_qty'] ?? 0,
                $id
            ]
        ) >= 0;
    }

    /**
     * Delete all items for a stitching batch.
     */
    public static function deleteByBatch(int $batchId): int {
        return Database::execute(
            "DELETE FROM stitching_items WHERE stitching_batch_id = ?",
            [$batchId]
        );
    }
}
