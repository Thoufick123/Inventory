<?php
/**
 * Allocation Item Model
 * Line items for shop stock allocations/requisitions.
 */

class AllocationItem {

    public static function getByAllocationId(int $allocationId): array {
        return Database::fetchAll(
            "SELECT ai.*, 
                    pv.sku, pv.qr_code as barcode, pv.mrp, pv.selling_price,
                    p.name as product_name, p.code as product_code,
                    sz.name as size_name, sz.code as size_code,
                    c.name as color_name, c.hex_code as color_hex
             FROM allocation_items ai
             JOIN product_variants pv ON ai.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE ai.allocation_id = ?
             ORDER BY ai.id ASC",
            [$allocationId]
        );
    }

    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO allocation_items 
             (allocation_id, variant_id, requested_qty, approved_qty, picked_qty, packed_qty, dispatched_qty, selected_qr_codes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['allocation_id'],
                $data['variant_id'],
                $data['requested_qty'],
                $data['approved_qty'] ?? $data['requested_qty'],
                $data['picked_qty'] ?? 0,
                $data['packed_qty'] ?? 0,
                $data['dispatched_qty'] ?? 0,
                $data['selected_qr_codes'] ?? null
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function updateQuantities(int $itemId, array $quantities): bool {
        $fields = [];
        $params = [];

        if (isset($quantities['approved_qty'])) {
            $fields[] = "approved_qty = ?";
            $params[] = $quantities['approved_qty'];
        }
        if (isset($quantities['picked_qty'])) {
            $fields[] = "picked_qty = ?";
            $params[] = $quantities['picked_qty'];
        }
        if (isset($quantities['packed_qty'])) {
            $fields[] = "packed_qty = ?";
            $params[] = $quantities['packed_qty'];
        }
        if (isset($quantities['dispatched_qty'])) {
            $fields[] = "dispatched_qty = ?";
            $params[] = $quantities['dispatched_qty'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $itemId;
        return Database::execute(
            "UPDATE allocation_items SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
    }
}
