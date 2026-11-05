<?php
/**
 * Stock Balance Model
 */

class StockBalance {
    public static function findByKey(int $variantId, string $locationType, int $locationId, string $stockState): ?array {
        return Database::fetchOne(
            "SELECT * FROM stock_balances 
             WHERE variant_id = ? AND location_type = ? AND location_id = ? AND stock_state = ?",
            [$variantId, $locationType, $locationId, $stockState]
        );
    }

    public static function getByVariant(int $variantId): array {
        $scopedWhId = function_exists('currentWarehouseId') ? currentWarehouseId() : null;
        $sql = "SELECT sb.*, 
                CASE sb.location_type 
                    WHEN 'WAREHOUSE' THEN (SELECT name FROM warehouses WHERE id = sb.location_id)
                    WHEN 'SHOP' THEN (SELECT name FROM shops WHERE id = sb.location_id)
                    WHEN 'PRODUCTION_UNIT' THEN (SELECT name FROM production_units WHERE id = sb.location_id)
                END as location_name
             FROM stock_balances sb WHERE sb.variant_id = ?";
        $params = [$variantId];
        if ($scopedWhId !== null) {
            $sql .= " AND (sb.location_type != 'WAREHOUSE' OR sb.location_id = ?)";
            $params[] = $scopedWhId;
        }
        $sql .= " ORDER BY sb.location_type, sb.location_id";
        return Database::fetchAll($sql, $params);
    }

    public static function getTotalAvailable(int $variantId): float {
        $result = Database::fetchOne(
            "SELECT SUM(quantity) as total FROM stock_balances 
             WHERE variant_id = ? AND stock_state = 'AVAILABLE'",
            [$variantId]
        );
        return (float) ($result['total'] ?? 0);
    }
}
