<?php
/**
 * Stock Transaction Model
 */

class StockTransaction {
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO stock_transactions 
                (variant_id, location_type, location_id, stock_state, transaction_type, 
                 quantity, balance_after, reference_type, reference_id, notes, user_id, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['variant_id'],
                $data['location_type'],
                $data['location_id'],
                $data['stock_state'],
                $data['transaction_type'],
                $data['quantity'],
                $data['balance_after'],
                $data['reference_type'] ?? null,
                $data['reference_id'] ?? null,
                $data['notes'] ?? null,
                $data['user_id'] ?? null,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function getByReference(string $referenceType, int $referenceId): array {
        return Database::fetchAll(
            "SELECT st.*, pv.sku, p.name as product_name
             FROM stock_transactions st
             JOIN product_variants pv ON st.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             WHERE st.reference_type = ? AND st.reference_id = ?
             ORDER BY st.created_at DESC",
            [$referenceType, $referenceId]
        );
    }

    public static function getRecent(int $limit = 50): array {
        return Database::fetchAll(
            "SELECT st.*, pv.sku, p.name as product_name, u.full_name as user_name
             FROM stock_transactions st
             JOIN product_variants pv ON st.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN users u ON st.user_id = u.id
             ORDER BY st.created_at DESC LIMIT ?",
            [$limit]
        );
    }
}
