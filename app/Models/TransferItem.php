<?php
/**
 * Transfer Item Model
 * Line items for inter-warehouse and shop inventory transfers.
 */

class TransferItem {

    public static function getByTransferId(int $transferId): array {
        return Database::fetchAll(
            "SELECT ti.*, 
                    pv.sku, pv.qr_code as barcode, pv.mrp, pv.selling_price,
                    p.name as product_name, p.code as product_code,
                    sz.name as size_name, sz.code as size_code,
                    c.name as color_name, c.hex_code as color_hex
             FROM transfer_items ti
             JOIN product_variants pv ON ti.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE ti.transfer_id = ?
             ORDER BY ti.id ASC",
            [$transferId]
        );
    }

    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO transfer_items 
             (transfer_id, concern_id, variant_id, quantity, requested_qty, transferred_qty, received_qty, damaged_qty, discrepancy_qty)
             VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0)",
            [
                $data['transfer_id'],
                $data['concern_id'],
                $data['variant_id'],
                $data['quantity'],
                $data['requested_qty'] ?? $data['quantity'],
            ]
        );
        return (int)Database::lastInsertId();
    }

}
