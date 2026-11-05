<?php
/**
 * Warehouse Transfer Model
 * Handles inter-warehouse and warehouse-to-shop inventory transfers.
 */

require_once BASE_PATH . '/app/Models/TransferItem.php';

class WarehouseTransfer {

    public static function find(int $id): ?array {
        $transfer = Database::fetchOne(
            "SELECT wt.*, 
                    u_req.full_name as requested_by_name,
                    u_appr.full_name as approved_by_name
             FROM warehouse_transfers wt
             LEFT JOIN users u_req ON wt.requested_by = u_req.id
             LEFT JOIN users u_appr ON wt.approved_by = u_appr.id
             WHERE wt.id = ?",
            [$id]
        );

        if ($transfer) {
            $transfer['source_name'] = self::getLocationName($transfer['source_type'], (int)$transfer['source_id']);
            $transfer['destination_name'] = self::getLocationName($transfer['destination_type'], (int)$transfer['destination_id']);
            $transfer['items'] = TransferItem::getByTransferId($id);
            self::parseLogistics($transfer);
        }

        return $transfer;
    }

    private static function parseLogistics(array &$transfer): void {
        $notes = $transfer['notes'] ?? '';
        $transfer['carrier_name'] = '';
        $transfer['vehicle_no'] = '';
        $transfer['driver_phone'] = '';
        $transfer['dispatch_at'] = '';
        $transfer['expected_arrival'] = '';

        if (preg_match('/\[LOGISTICS\](.*?)\[\/LOGISTICS\]/s', $notes, $matches)) {
            $logistics = json_decode($matches[1], true);
            if (is_array($logistics)) {
                $transfer['carrier_name']     = $logistics['carrier_name'] ?? '';
                $transfer['vehicle_no']       = $logistics['vehicle_no'] ?? '';
                $transfer['driver_phone']     = $logistics['driver_phone'] ?? '';
                $transfer['dispatch_at']      = $logistics['dispatch_at'] ?? '';
                $transfer['expected_arrival'] = $logistics['expected_arrival'] ?? '';
            }
        }
    }

    public static function findByNumber(string $transferNumber): ?array {
        $transfer = Database::fetchOne(
            "SELECT wt.* FROM warehouse_transfers wt WHERE wt.transfer_number = ?",
            [$transferNumber]
        );
        if ($transfer) {
            return self::find((int)$transfer['id']);
        }
        return null;
    }

    public static function getAll(array $filters = []): array {
        $sql = "SELECT wt.*, 
                       u_req.full_name as requested_by_name,
                       u_appr.full_name as approved_by_name,
                       (SELECT COUNT(*) FROM transfer_items WHERE transfer_id = wt.id) as item_count,
                       (SELECT COALESCE(SUM(quantity), 0) FROM transfer_items WHERE transfer_id = wt.id) as total_qty
                FROM warehouse_transfers wt
                LEFT JOIN users u_req ON wt.requested_by = u_req.id
                LEFT JOIN users u_appr ON wt.approved_by = u_appr.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND wt.status = ?";
            $params[] = $filters['status'];
        }

        if (empty($filters['unscoped']) && function_exists('currentWarehouseId')) {
            $scopedWhId = currentWarehouseId();
            if ($scopedWhId !== null) {
                $sql .= " AND ((wt.source_type = 'WAREHOUSE' AND wt.source_id = ?) OR (wt.destination_type = 'WAREHOUSE' AND wt.destination_id = ?))";
                $params[] = $scopedWhId;
                $params[] = $scopedWhId;
            } elseif (!empty($filters['source_id'])) {
                $sql .= " AND wt.source_id = ?";
                $params[] = $filters['source_id'];
            }
        } elseif (!empty($filters['source_id'])) {
            $sql .= " AND wt.source_id = ?";
            $params[] = $filters['source_id'];
        }

        if (!empty($filters['destination_id'])) {
            $sql .= " AND wt.destination_id = ?";
            $params[] = $filters['destination_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (wt.transfer_number LIKE ? OR wt.notes LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search]);
        }

        $sql .= " ORDER BY wt.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $rows = Database::fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $row['source_name'] = self::getLocationName($row['source_type'], (int)$row['source_id']);
            $row['destination_name'] = self::getLocationName($row['destination_type'], (int)$row['destination_id']);
            self::parseLogistics($row);
        }

        return $rows;
    }

    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO warehouse_transfers 
             (transfer_number, source_type, source_id, destination_type, destination_id, requested_by, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['transfer_number'],
                $data['source_type'],
                $data['source_id'],
                $data['destination_type'],
                $data['destination_id'],
                $data['requested_by'] ?? null,
                $data['status'] ?? 'REQUESTED',
                $data['notes'] ?? null
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function updateStatus(int $id, string $status, ?int $approvedBy = null): bool {
        if ($approvedBy) {
            return Database::execute(
                "UPDATE warehouse_transfers SET status = ?, approved_by = ?, updated_at = NOW() WHERE id = ?",
                [$status, $approvedBy, $id]
            );
        }
        return Database::execute(
            "UPDATE warehouse_transfers SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $id]
        );
    }

    public static function generateNumber(): string {
        $prefix = 'TRF-' . date('Ymd') . '-';
        $last = Database::fetchOne(
            "SELECT transfer_number FROM warehouse_transfers WHERE transfer_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($last) {
            $num = (int)substr($last['transfer_number'], -4) + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    private static function getLocationName(string $type, int $id): string {
        if ($type === 'WAREHOUSE') {
            $wh = Database::fetchOne("SELECT name, code FROM warehouses WHERE id = ?", [$id]);
            return $wh ? $wh['name'] . " (" . $wh['code'] . ")" : "Warehouse #" . $id;
        } else if ($type === 'SHOP') {
            $sh = Database::fetchOne("SELECT name, code FROM shops WHERE id = ?", [$id]);
            return $sh ? $sh['name'] . " (" . $sh['code'] . ")" : "Shop #" . $id;
        }
        return $type . " #" . $id;
    }
}
