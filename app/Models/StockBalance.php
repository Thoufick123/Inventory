<?php
/**
 * Stock Balance Model
 */

class StockBalance {
    public static function findByKey(int $variantId, string $locationType, int $locationId, string $stockState): ?array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return null;
        }
        StockService::assertLocationBelongsToActiveConcern($locationType, $locationId);
        return Database::fetchOne(
            "SELECT * FROM stock_balances 
             WHERE variant_id = ? AND location_type = ? AND location_id = ? AND stock_state = ? AND concern_id = ?",
            [$variantId, $locationType, $locationId, $stockState, $concernId]
        );
    }

    public static function getByVariant(int $variantId): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return [];
        }
        $scopedWhId = function_exists('currentWarehouseId') ? currentWarehouseId() : null;
        $sql = "SELECT sb.*, 
                CASE sb.location_type 
                    WHEN 'WAREHOUSE' THEN (SELECT name FROM warehouses WHERE id = sb.location_id AND concern_id = sb.concern_id)
                    WHEN 'SHOP' THEN (SELECT name FROM shops WHERE id = sb.location_id AND concern_id = sb.concern_id)
                    WHEN 'PRODUCTION_UNIT' THEN (SELECT name FROM production_units WHERE id = sb.location_id AND concern_id = sb.concern_id)
                    WHEN 'CHANNEL' THEN (SELECT name FROM sales_channels WHERE id = sb.location_id AND concern_id = sb.concern_id)
                    WHEN 'CONCERN' THEN (SELECT name FROM concerns WHERE id = sb.location_id AND id = sb.concern_id)
                END as location_name
             FROM stock_balances sb WHERE sb.variant_id = ? AND sb.concern_id = ?";
        $params = [$variantId, $concernId];
        $sql .= " AND (
            (sb.location_type = 'WAREHOUSE' AND EXISTS (
                SELECT 1 FROM warehouses w_scope WHERE w_scope.id = sb.location_id AND w_scope.concern_id = ?
            ))
            OR (sb.location_type = 'SHOP' AND EXISTS (
                SELECT 1 FROM shops s_scope WHERE s_scope.id = sb.location_id AND s_scope.concern_id = ?
            ))
            OR (sb.location_type = 'PRODUCTION_UNIT' AND EXISTS (
                SELECT 1 FROM production_units pu_scope WHERE pu_scope.id = sb.location_id AND pu_scope.concern_id = ?
            ))
            OR (sb.location_type = 'CHANNEL' AND EXISTS (
                SELECT 1 FROM sales_channels sc_scope WHERE sc_scope.id = sb.location_id AND sc_scope.concern_id = ?
            ))
            OR (sb.location_type = 'CONCERN' AND sb.location_id = ?)
            OR (sb.location_type = 'OFFICE' AND ? = 1)
        )";
        array_push($params, $concernId, $concernId, $concernId, $concernId, $concernId, isSuperAdmin() ? 1 : 0);
        if ($scopedWhId === 0) {
            return [];
        }
        if ($scopedWhId !== null) {
            $sql .= " AND (sb.location_type != 'WAREHOUSE' OR sb.location_id = ?)";
            $params[] = $scopedWhId;
        }
        foreach ([
            'SHOP' => 'currentShopId',
            'PRODUCTION_UNIT' => 'currentProductionUnitId',
            'CHANNEL' => 'currentChannelId',
        ] as $locationType => $helper) {
            $assignedId = function_exists($helper) ? $helper() : null;
            if ($assignedId === 0) {
                $sql .= " AND sb.location_type != ?";
                $params[] = $locationType;
            } elseif ($assignedId !== null) {
                $sql .= " AND (sb.location_type != ? OR sb.location_id = ?)";
                array_push($params, $locationType, $assignedId);
            }
        }
        $sql .= " ORDER BY sb.location_type, sb.location_id";
        return Database::fetchAll($sql, $params);
    }

    public static function getTotalAvailable(int $variantId): float {
        $balances = self::getByVariant($variantId);
        $total = 0.0;
        foreach ($balances as $balance) {
            if ($balance['stock_state'] === 'AVAILABLE') {
                $total += (float)$balance['quantity'];
            }
        }
        return $total;
    }
}
