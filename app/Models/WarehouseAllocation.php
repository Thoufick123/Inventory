<?php
/**
 * Warehouse Allocation Model
 * Handles stock allocations/requisitions to Shops, Website, and WhatsApp sales channels.
 */

require_once BASE_PATH . '/app/Models/AllocationItem.php';

class WarehouseAllocation {

    public static function find(int $id): ?array {
        $allocation = Database::fetchOne(
            "SELECT wa.*, 
                    w.name as warehouse_name, w.code as warehouse_code,
                    u_req.full_name as requested_by_name,
                    u_appr.full_name as approved_by_name
             FROM warehouse_allocations wa
             JOIN warehouses w ON wa.warehouse_id = w.id
             LEFT JOIN users u_req ON wa.requested_by = u_req.id
             LEFT JOIN users u_appr ON wa.approved_by = u_appr.id
             WHERE wa.id = ?",
            [$id]
        );

        if ($allocation) {
            // Populate destination name
            $shopName = '';
            $chanName = '';
            $whName = '';

            if (!empty($allocation['shop_id'])) {
                $shop = Database::fetchOne("SELECT name, code FROM shops WHERE id = ?", [$allocation['shop_id']]);
                if ($shop) $shopName = $shop['name'] . " (" . $shop['code'] . ")";
            } elseif ($allocation['destination_type'] === 'SHOP' && !empty($allocation['destination_id'])) {
                $shop = Database::fetchOne("SELECT name, code FROM shops WHERE id = ?", [$allocation['destination_id']]);
                if ($shop) $shopName = $shop['name'] . " (" . $shop['code'] . ")";
            }

            if (!empty($allocation['channel_id'])) {
                $chan = Database::fetchOne("SELECT name, code FROM sales_channels WHERE id = ?", [$allocation['channel_id']]);
                if ($chan) $chanName = $chan['name'];
            } elseif (str_starts_with((string)$allocation['destination_type'], 'CHANNEL_')) {
                $chanId = (int)substr($allocation['destination_type'], 8);
                $chan = Database::fetchOne("SELECT name FROM sales_channels WHERE id = ?", [$chanId]);
                if ($chan) $chanName = $chan['name'];
            }

            if ($allocation['destination_type'] === 'WAREHOUSE' && !empty($allocation['destination_id'])) {
                $wh = Database::fetchOne("SELECT name, code FROM warehouses WHERE id = ?", [$allocation['destination_id']]);
                if ($wh) $whName = $wh['name'] . " (" . $wh['code'] . ")";
            }

            if ($whName) {
                $allocation['destination_name'] = $whName;
            } elseif ($shopName && $chanName) {
                $allocation['destination_name'] = $shopName . " | " . $chanName;
            } elseif ($shopName) {
                $allocation['destination_name'] = $shopName;
            } elseif ($chanName) {
                $allocation['destination_name'] = $chanName;
            } else {
                $allocation['destination_name'] = $allocation['destination_type'];
            }

            $allocation['items'] = AllocationItem::getByAllocationId($id);
        }

        return $allocation;
    }

    public static function findByNumber(string $allocationNumber): ?array {
        $allocation = Database::fetchOne(
            "SELECT wa.* FROM warehouse_allocations wa WHERE wa.allocation_number = ?",
            [$allocationNumber]
        );
        if ($allocation) {
            return self::find((int)$allocation['id']);
        }
        return null;
    }

    public static function getAll(array $filters = []): array {
        $sql = "SELECT wa.*, 
                       w.name as warehouse_name, w.code as warehouse_code,
                       s.name as shop_name, s.code as shop_code,
                       sc.name as channel_name, sc.code as channel_code,
                       dw.name as dest_warehouse_name, dw.code as dest_warehouse_code,
                       u_req.full_name as requested_by_name,
                       u_appr.full_name as approved_by_name,
                       (SELECT COUNT(*) FROM allocation_items WHERE allocation_id = wa.id) as item_count,
                       (SELECT COALESCE(SUM(requested_qty), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_requested_qty,
                       (SELECT COALESCE(SUM(dispatched_qty), 0) FROM allocation_items WHERE allocation_id = wa.id) as total_dispatched_qty
                FROM warehouse_allocations wa
                LEFT JOIN warehouses w ON wa.warehouse_id = w.id
                LEFT JOIN shops s ON ((wa.shop_id = s.id) OR (wa.destination_type = 'SHOP' AND wa.destination_id = s.id))
                LEFT JOIN sales_channels sc ON (
                    (wa.channel_id = sc.id) 
                    OR (wa.destination_type = 'CHANNEL' AND wa.destination_id = sc.id)
                    OR (wa.destination_type LIKE 'CHANNEL_%' AND CAST(SUBSTRING(wa.destination_type, 9) AS UNSIGNED) = sc.id)
                )
                LEFT JOIN warehouses dw ON (wa.destination_type = 'WAREHOUSE' AND wa.destination_id = dw.id)
                LEFT JOIN users u_req ON wa.requested_by = u_req.id
                LEFT JOIN users u_appr ON wa.approved_by = u_appr.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND wa.status = ?";
            $params[] = $filters['status'];
        } elseif (!empty($filters['status_in']) && is_array($filters['status_in'])) {
            $in = implode(',', array_fill(0, count($filters['status_in']), '?'));
            $sql .= " AND wa.status IN ({$in})";
            $params = array_merge($params, $filters['status_in']);
        } elseif (!empty($filters['exclude_status'])) {
            $sql .= " AND wa.status != ?";
            $params[] = $filters['exclude_status'];
        }

        if (empty($filters['unscoped']) && function_exists('currentWarehouseId')) {
            $scopedWhId = currentWarehouseId();
            if ($scopedWhId !== null) {
                $sql .= " AND wa.warehouse_id = ?";
                $params[] = $scopedWhId;
            } elseif (!empty($filters['warehouse_id'])) {
                $sql .= " AND wa.warehouse_id = ?";
                $params[] = $filters['warehouse_id'];
            }
        } elseif (!empty($filters['warehouse_id'])) {
            $sql .= " AND wa.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }

        if (!empty($filters['destination_type'])) {
            $sql .= " AND wa.destination_type = ?";
            $params[] = $filters['destination_type'];
        }

        if (!empty($filters['only_allocations'])) {
            $sql .= " AND (wa.allocation_number LIKE 'ALC-%' OR (wa.allocation_number NOT LIKE 'IND-%' AND wa.allocation_number NOT LIKE 'WREQ-%' AND wa.allocation_number NOT LIKE 'REQ-%'))";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (wa.allocation_number LIKE ? OR s.name LIKE ? OR wa.notes LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        $sql .= " ORDER BY wa.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO warehouse_allocations 
             (allocation_number, warehouse_id, destination_type, destination_id, shop_id, channel_id, requested_by, approved_by, approved_at, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['allocation_number'],
                $data['warehouse_id'],
                $data['destination_type'],
                $data['destination_id'] ?? 0,
                $data['shop_id'] ?? null,
                $data['channel_id'] ?? null,
                $data['requested_by'] ?? null,
                $data['approved_by'] ?? null,
                $data['approved_at'] ?? null,
                $data['status'] ?? 'REQUESTED',
                $data['notes'] ?? null
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function updateStatus(int $id, string $status, ?int $approvedBy = null): bool {
        if ($approvedBy) {
            return Database::execute(
                "UPDATE warehouse_allocations SET status = ?, approved_by = ?, approved_at = NOW(), updated_at = NOW() WHERE id = ?",
                [$status, $approvedBy, $id]
            );
        }
        return Database::execute(
            "UPDATE warehouse_allocations SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $id]
        );
    }

    public static function generateNumber(): string {
        $prefix = 'ALC-' . date('Ymd') . '-';
        $last = Database::fetchOne(
            "SELECT allocation_number FROM warehouse_allocations WHERE allocation_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($last) {
            $num = (int)substr($last['allocation_number'], -4) + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public static function generateRequestNumber(string $reqPrefix = 'REQ-'): string {
        $prefix = $reqPrefix . date('Ymd') . '-';
        $last = Database::fetchOne(
            "SELECT allocation_number FROM warehouse_allocations WHERE allocation_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($last) {
            $num = (int)substr($last['allocation_number'], -4) + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
