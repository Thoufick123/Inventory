<?php
/**
 * Stock Transaction Model
 */

class StockTransaction {
    public static function create(array $data): int {
        $concernId = StockService::assertLocationBelongsToActiveConcern(
            (string)$data['location_type'],
            (int)$data['location_id']
        );
        Database::execute(
            "INSERT INTO stock_transactions 
                (variant_id, location_type, location_id, stock_state, transaction_type,
                 quantity, balance_after, reference_type, reference_id, notes, user_id, concern_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
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
                $concernId,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function getByReference(string $referenceType, int $referenceId): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return [];
        }
        $sql = "SELECT st.*, pv.sku, p.name as product_name
             FROM stock_transactions st
             JOIN product_variants pv ON st.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             WHERE st.reference_type = ? AND st.reference_id = ? AND st.concern_id = ?
             ";
        $params = [$referenceType, $referenceId, $concernId];
        self::appendFacilityAssignmentFilters($sql, $params);
        $sql .= " ORDER BY st.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function getRecent(int $limit = 50): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return [];
        }
        $sql = "SELECT st.*, pv.sku, p.name as product_name, u.full_name as user_name
             FROM stock_transactions st
             JOIN product_variants pv ON st.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN users u ON st.user_id = u.id
             WHERE st.concern_id = ?
             ";
        $params = [$concernId];
        self::appendFacilityAssignmentFilters($sql, $params);
        $sql .= " ORDER BY st.created_at DESC LIMIT ?";
        $params[] = $limit;
        return Database::fetchAll($sql, $params);
    }

    private static function appendFacilityAssignmentFilters(string &$sql, array &$params): void {
        foreach ([
            'WAREHOUSE' => 'currentWarehouseId',
            'SHOP' => 'currentShopId',
            'PRODUCTION_UNIT' => 'currentProductionUnitId',
            'CHANNEL' => 'currentChannelId',
        ] as $locationType => $helper) {
            $assignedId = function_exists($helper) ? $helper() : null;
            if ($assignedId === 0) {
                $sql .= " AND st.location_type != ?";
                $params[] = $locationType;
            } elseif ($assignedId !== null) {
                $sql .= " AND (st.location_type != ? OR st.location_id = ?)";
                array_push($params, $locationType, $assignedId);
            }
        }
    }
}
