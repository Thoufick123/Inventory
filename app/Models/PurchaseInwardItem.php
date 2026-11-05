<?php
/**
 * Purchase Inward Item Model
 * Handles item inspection during PO receiving (ordered, received, damaged, accepted).
 */

class PurchaseInwardItem {

    /**
     * Get items for a purchase inward GRN.
     */
    public static function getByPurchaseInward(int $purchaseInwardId): array {
        return Database::fetchAll(
            "SELECT pii.*, 
                    pv.sku, pv.qr_code, pv.selling_price,
                    p.name as product_name, p.code as product_code,
                    s.name as size_name, s.code as size_code,
                    c.name as color_name,
                    l.rack, l.shelf, l.bin, l.label as location_label
             FROM purchase_inward_items pii
             JOIN product_variants pv ON pii.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN locations l ON pii.location_id = l.id
             WHERE pii.purchase_inward_id = ?
             ORDER BY pii.id ASC",
            [$purchaseInwardId]
        );
    }

    /**
     * Create purchase inward item.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO purchase_inward_items 
             (purchase_inward_id, variant_id, ordered_qty, received_qty, damaged_qty, accepted_qty, location_id, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                (int)$data['purchase_inward_id'],
                (int)$data['variant_id'],
                (int)$data['ordered_qty'],
                (int)($data['received_qty'] ?? 0),
                (int)($data['damaged_qty'] ?? 0),
                (int)($data['accepted_qty'] ?? 0),
                !empty($data['location_id']) ? (int)$data['location_id'] : null,
                $data['notes'] ?? null
            ]
        );
        return (int)Database::lastInsertId();
    }
}
