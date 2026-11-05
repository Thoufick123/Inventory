<?php
/**
 * Production Batch Item Model
 * Manages variant-wise packed quantities in a production batch.
 */

class ProductionBatchItem {

    /**
     * Get items for a production batch.
     */
    public static function getByBatch(int $batchId): array {
        return Database::fetchAll(
            "SELECT pbi.*, 
                    pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost,
                    s.name as size_name, s.code as size_code,
                    c.name as color_name, c.hex_code as color_hex
             FROM production_batch_items pbi
             JOIN product_variants pv ON pbi.variant_id = pv.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE pbi.production_batch_id = ?
             ORDER BY s.sort_order ASC, c.name ASC",
            [$batchId]
        );
    }

    /**
     * Create batch item.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO production_batch_items 
             (production_batch_id, variant_id, quantity, piece_tags)
             VALUES (?, ?, ?, ?)",
            [
                $data['production_batch_id'],
                $data['variant_id'],
                $data['quantity'] ?? 0,
                !empty($data['piece_tags']) ? json_encode(array_values($data['piece_tags']), JSON_UNESCAPED_UNICODE) : null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Delete all items for a production batch.
     */
    public static function deleteByBatch(int $batchId): int {
        return Database::execute(
            "DELETE FROM production_batch_items WHERE production_batch_id = ?",
            [$batchId]
        );
    }
}
