<?php
/**
 * Warehouse Return Item Model
 * Line items for return receipts and QC inspection classification.
 */

require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';

class WarehouseReturnItem {

    public static function getByReturnId(int $returnId): array {
        if (!ConcernTransactionService::findOwned('warehouse_returns', $returnId)) {
            return [];
        }
        return Database::fetchAll(
            "SELECT wri.*, 
                    pv.sku, pv.qr_code as barcode, pv.mrp, pv.selling_price,
                    p.name as product_name, p.code as product_code, p.qr_code_prefix,
                    p.category_code, p.subcategory_code, p.sub_subcategory_code,
                    COALESCE(wcat.name, cat.name) as category_name,
                    COALESCE(wsub.name, subcat.name) as subcategory_name,
                    COALESCE(wsubsub.name, subsubcat.name) as sub_subcategory_name,
                    sz.name as size_name, sz.code as size_code,
                    c.name as color_name, c.hex_code as color_hex
             FROM warehouse_return_items wri
             JOIN product_variants pv ON wri.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
             LEFT JOIN warehouse_categories wcat ON p.warehouse_category_id = wcat.id
             LEFT JOIN warehouse_subcategories wsub ON p.warehouse_subcategory_id = wsub.id
             LEFT JOIN warehouse_sub_subcategories wsubsub ON p.warehouse_sub_subcategory_id = wsubsub.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE wri.warehouse_return_id = ?
             ORDER BY wri.id ASC",
            [$returnId]
        );
    }

    public static function create(array $data): int {
        ConcernTransactionService::assertOwned('warehouse_returns', (int)$data['warehouse_return_id']);
        Database::execute(
            "INSERT INTO warehouse_return_items 
             (warehouse_return_id, variant_id, returned_qty, good_qty, damaged_qty, defective_qty, classification)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['warehouse_return_id'],
                $data['variant_id'],
                $data['returned_qty'],
                $data['good_qty'] ?? 0,
                $data['damaged_qty'] ?? 0,
                $data['defective_qty'] ?? 0,
                $data['classification'] ?? 'GOOD'
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function updateClassification(int $itemId, array $classificationData): bool {
        return Database::execute(
            "UPDATE warehouse_return_items wri
             JOIN warehouse_returns wr ON wr.id = wri.warehouse_return_id
             SET good_qty = ?, damaged_qty = ?, defective_qty = ?, classification = ? 
             WHERE wri.id = ? AND wr.concern_id = ?",
            [
                $classificationData['good_qty'] ?? 0,
                $classificationData['damaged_qty'] ?? 0,
                $classificationData['defective_qty'] ?? 0,
                $classificationData['classification'] ?? 'GOOD',
                $itemId,
                ConcernTransactionService::activeConcernId()
            ]
        );
    }
}
