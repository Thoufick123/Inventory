<?php
/**
 * Cutting Item Model
 * Manages variant-wise planned, cut, rejected, and wastage quantities in a cutting batch,
 * including per-item 3-tier catalog hierarchy tracking.
 */

class CuttingItem {

    /**
     * Get items for a cutting batch with catalog and variant details.
     */
    public static function getByBatch(int $batchId): array {
        return Database::fetchAll(
            "SELECT ci.*, 
                    pv.sku, pv.qr_code,
                    COALESCE(s.name, ci.size_name, 'Standard') as size_name, 
                    COALESCE(s.code, ci.size_name, 'STD') as size_code,
                    COALESCE(c.name, ci.color_name, 'Standard') as color_name, 
                    c.hex_code as color_hex,
                    COALESCE(cat.name, '') as category_name,
                    COALESCE(sc.name, '') as subcategory_name,
                    COALESCE(ssc.name, '') as sub_subcategory_name,
                    pv.mrp as variant_mrp, pv.selling_price as variant_selling_price
             FROM cutting_items ci
             LEFT JOIN product_variants pv ON ci.variant_id = pv.id
             LEFT JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories cat ON (ci.category_id = cat.id OR (ci.category_id IS NULL AND p.category_id = cat.id))
             LEFT JOIN subcategories sc ON (ci.subcategory_id = sc.id OR (ci.subcategory_id IS NULL AND p.subcategory_id = sc.id))
             LEFT JOIN sub_subcategories ssc ON (ci.sub_subcategory_id = ssc.id OR (ci.sub_subcategory_id IS NULL AND p.sub_subcategory_id = ssc.id))
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE ci.cutting_batch_id = ?
             ORDER BY ci.id ASC",
            [$batchId]
        );
    }

    /**
     * Create cutting item record.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO cutting_items 
             (cutting_batch_id, variant_id, category_id, subcategory_id, sub_subcategory_id, catalog_hierarchy, size_name, color_name, planned_qty, cut_qty, rejected_qty, wastage_qty)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['cutting_batch_id'],
                !empty($data['variant_id']) ? (int)$data['variant_id'] : null,
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null,
                !empty($data['sub_subcategory_id']) ? (int)$data['sub_subcategory_id'] : null,
                $data['catalog_hierarchy'] ?? null,
                $data['size_name'] ?? null,
                $data['color_name'] ?? null,
                $data['planned_qty'] ?? 0,
                $data['cut_qty'] ?? 0,
                $data['rejected_qty'] ?? 0,
                $data['wastage_qty'] ?? 0,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update cutting item quantities and catalog attributes.
     */
    public static function update(int $id, array $data): bool {
        return Database::execute(
            "UPDATE cutting_items 
             SET variant_id = ?, category_id = ?, subcategory_id = ?, sub_subcategory_id = ?, catalog_hierarchy = ?, planned_qty = ?, cut_qty = ?, rejected_qty = ?, wastage_qty = ?, size_name = ?, color_name = ?
             WHERE id = ?",
            [
                !empty($data['variant_id']) ? (int)$data['variant_id'] : null,
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null,
                !empty($data['sub_subcategory_id']) ? (int)$data['sub_subcategory_id'] : null,
                $data['catalog_hierarchy'] ?? null,
                $data['planned_qty'] ?? 0,
                $data['cut_qty'] ?? 0,
                $data['rejected_qty'] ?? 0,
                $data['wastage_qty'] ?? 0,
                $data['size_name'] ?? null,
                $data['color_name'] ?? null,
                $id
            ]
        ) >= 0;
    }

    /**
     * Delete all items for a cutting batch.
     */
    public static function deleteByBatch(int $batchId): int {
        return Database::execute(
            "DELETE FROM cutting_items WHERE cutting_batch_id = ?",
            [$batchId]
        );
    }
}
