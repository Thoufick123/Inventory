<?php
/**
 * Purchase Item Model
 * Handles itemized variants under a Purchase Order.
 */

class PurchaseItem {

    /**
     * Get all items for a purchase order.
     */
    public static function getByPurchaseOrder(int $purchaseOrderId): array {
        return Database::fetchAll(
            "SELECT pi.*, 
                    COALESCE(pi.batch_lot_no, '') as scanned_qr_codes,
                    pv.sku, pv.qr_code, pv.selling_price, pv.mrp,
                    p.id as product_id, p.name as product_name, p.code as product_code, p.qr_code_prefix,
                    p.category_code, p.subcategory_code, p.sub_subcategory_code,
                    COALESCE(wh_cat.name, cat.name, '') as category_name, 
                    COALESCE(wh_subcat.name, subcat.name, '') as subcategory_name, 
                    COALESCE(wh_subsubcat.name, subsubcat.name, '') as sub_subcategory_name,
                    s.name as size_name, s.code as size_code,
                    c.name as color_name, c.hex_code as color_hex
             FROM purchase_items pi
             JOIN product_variants pv ON pi.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN warehouse_categories wh_cat ON p.warehouse_category_id = wh_cat.id
             LEFT JOIN warehouse_subcategories wh_subcat ON p.warehouse_subcategory_id = wh_subcat.id
             LEFT JOIN warehouse_sub_subcategories wh_subsubcat ON p.warehouse_sub_subcategory_id = wh_subsubcat.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE pi.purchase_order_id = ?
             ORDER BY pi.id ASC",
            [$purchaseOrderId]
        );
    }

    /**
     * Create purchase item.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO purchase_items 
             (purchase_order_id, variant_id, quantity, unit_price, discount_percentage, gst_percentage, expiry_date, mfg_date, batch_lot_no, mrp, cgst_amount, sgst_amount, igst_amount, total_amount, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                (int)$data['purchase_order_id'],
                (int)$data['variant_id'],
                (int)$data['quantity'],
                (float)$data['unit_price'],
                (float)($data['discount_percentage'] ?? 0.00),
                (float)($data['gst_percentage'] ?? 0.00),
                !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                !empty($data['mfg_date']) ? $data['mfg_date'] : null,
                !empty($data['batch_lot_no']) ? $data['batch_lot_no'] : (!empty($data['scanned_qr_codes']) ? $data['scanned_qr_codes'] : null),
                (float)($data['mrp'] ?? 0.00),
                (float)($data['cgst_amount'] ?? 0.00),
                (float)($data['sgst_amount'] ?? 0.00),
                (float)($data['igst_amount'] ?? 0.00),
                (float)$data['total_amount'],
                !empty($data['notes']) ? $data['notes'] : (!empty($data['batch_lot_no']) ? ('QR: ' . $data['batch_lot_no']) : null)
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Delete all items for a purchase order.
     */
    public static function deleteByPurchaseOrder(int $purchaseOrderId): bool {
        return Database::execute(
            "DELETE FROM purchase_items WHERE purchase_order_id = ?",
            [$purchaseOrderId]
        ) >= 0;
    }
}
