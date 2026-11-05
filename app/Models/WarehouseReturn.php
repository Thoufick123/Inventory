<?php
/**
 * Warehouse Return Model
 * Handles incoming inventory returns from Shops, Website, or WhatsApp, and QC inspection classification.
 */

require_once BASE_PATH . '/app/Models/WarehouseReturnItem.php';

class WarehouseReturn {

    public static function find(int $id): ?array {
        $return = Database::fetchOne(
            "SELECT wr.*, 
                    COALESCE(w_dest.name, w.name, 'Main Central Warehouse') as warehouse_name, 
                    COALESCE(w_dest.code, w.code, 'WH-01') as warehouse_code,
                    u_insp.full_name as inspected_by_name,
                    u_rec.full_name as received_by_name
             FROM warehouse_returns wr
             LEFT JOIN warehouses w ON wr.warehouse_id = w.id
             LEFT JOIN warehouses w_dest ON wr.destination_warehouse_id = w_dest.id
             LEFT JOIN users u_insp ON wr.inspected_by = u_insp.id
             LEFT JOIN users u_rec ON wr.received_by = u_rec.id
             WHERE wr.id = ?",
            [$id]
        );

        if ($return) {
            if ($return['source_type'] === 'SHOP' && !empty($return['source_id'])) {
                $shop = Database::fetchOne("SELECT name, code FROM shops WHERE id = ?", [$return['source_id']]);
                $return['source_name'] = $shop ? $shop['name'] . " (" . $shop['code'] . ")" : "Shop #" . $return['source_id'];
                $return['source_badge_type'] = 'SHOP';
            } elseif (in_array($return['source_type'], ['WHATSAPP', 'WEBSITE']) && !empty($return['source_id'])) {
                $channel = Database::fetchOne("SELECT name, code FROM sales_channels WHERE id = ?", [$return['source_id']]);
                $return['source_name'] = $channel ? $channel['name'] . " (" . $channel['code'] . ")" : ($return['source_type'] . " Channel");
                $return['source_badge_type'] = $return['source_type'];
            } else {
                $return['source_name'] = $return['source_type'] . " Division";
                $return['source_badge_type'] = $return['source_type'];
            }

            $return['items'] = WarehouseReturnItem::getByReturnId($id);
            $notes = json_decode((string)($return['notes'] ?? ''), true);
            $scannedTags = [];
            foreach (($notes['scanned_tags'] ?? []) as $scan) {
                if (!empty($scan['variant_id']) && !empty($scan['tag_code'])) $scannedTags[(int)$scan['variant_id']] = $scan['tag_code'];
            }
            $allocationTags = [];
            if (in_array($return['source_type'], ['WHATSAPP', 'WEBSITE'], true) && !empty($return['source_id'])) {
                $allocationRows = Database::fetchAll(
                    "SELECT ai.variant_id, ai.selected_qr_codes
                     FROM allocation_items ai
                     JOIN warehouse_allocations wa ON wa.id = ai.allocation_id
                     WHERE ai.variant_id IN (SELECT variant_id FROM warehouse_return_items WHERE warehouse_return_id = ?)
                       AND (wa.channel_id = ? OR (wa.destination_type = 'CHANNEL' AND wa.destination_id = ?))
                       AND ai.selected_qr_codes IS NOT NULL AND ai.selected_qr_codes != ''
                     ORDER BY wa.id ASC, ai.id ASC",
                    [$id, (int)$return['source_id'], (int)$return['source_id']]
                );
                foreach ($allocationRows as $allocationRow) {
                    $raw = json_decode((string)$allocationRow['selected_qr_codes'], true);
                    $raw = is_array($raw) ? $raw : explode(',', (string)$allocationRow['selected_qr_codes']);
                    foreach ($raw as $tag) {
                        $tag = is_array($tag) ? ($tag['tag_code'] ?? ($tag['qr_code'] ?? '')) : $tag;
                        $tag = trim((string)$tag);
                        if ($tag !== '') $allocationTags[(int)$allocationRow['variant_id']][] = $tag;
                    }
                }
            }
            $allocationOffsets = [];
            foreach ($return['items'] as &$returnItem) {
                $variantId = (int)$returnItem['variant_id'];
                $tag = $scannedTags[$variantId] ?? null;
                if (!$tag && !empty($allocationTags[$variantId])) {
                    $offset = $allocationOffsets[$variantId] ?? 0;
                    $tag = $allocationTags[$variantId][$offset] ?? $allocationTags[$variantId][0];
                    $allocationOffsets[$variantId] = $offset + 1;
                }
                $returnItem['returned_tag_code'] = $tag ?: ($returnItem['barcode'] ?? $returnItem['sku'] ?? null);
            }
            unset($returnItem);
        }

        return $return;
    }

    public static function findByNumber(string $returnNumber): ?array {
        $return = Database::fetchOne(
            "SELECT wr.* FROM warehouse_returns wr WHERE wr.return_number = ?",
            [$returnNumber]
        );
        if ($return) {
            return self::find((int)$return['id']);
        }
        return null;
    }

    public static function getAll(array $filters = []): array {
        $sql = "SELECT wr.*, 
                       COALESCE(w_dest.name, w.name, 'Main Central Warehouse') as warehouse_name, 
                       COALESCE(w_dest.code, w.code, 'WH-01') as warehouse_code,
                       s.name as shop_name, s.code as shop_code,
                       sc.name as channel_name, sc.code as channel_code,
                       CASE
                           WHEN wr.source_type = 'SHOP' AND s.name IS NOT NULL
                                THEN CONCAT('Shop: ', s.name, ' (', s.code, ')')
                           WHEN wr.source_type = 'SHOP'
                                THEN CONCAT('Shop #', COALESCE(wr.source_id, '?'))
                           WHEN wr.source_type = 'WHATSAPP' AND sc.name IS NOT NULL
                                THEN CONCAT('WhatsApp: ', sc.name, ' (', sc.code, ')')
                           WHEN wr.source_type = 'WHATSAPP'
                                THEN 'WhatsApp Channel (WA-01)'
                           WHEN wr.source_type = 'WEBSITE' AND sc.name IS NOT NULL
                                THEN CONCAT('Website: ', sc.name, ' (', sc.code, ')')
                           WHEN wr.source_type = 'WEBSITE'
                                THEN 'Website Channel (WEB-01)'
                           ELSE wr.source_type
                       END as source_name,
                       u_insp.full_name as inspected_by_name,
                       (SELECT COUNT(*) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as item_count,
                       (SELECT COALESCE(SUM(returned_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as total_returned_qty,
                       (SELECT COALESCE(SUM(good_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as total_good_qty,
                       (SELECT COALESCE(SUM(damaged_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id) as total_damaged_qty,
                       (SELECT COALESCE(SUM(defective_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id AND classification = 'DEFECTIVE') as total_defective_qty,
                       (SELECT COALESCE(SUM(defective_qty), 0) FROM warehouse_return_items WHERE warehouse_return_id = wr.id AND classification = 'WRONG_SIZE') as total_rework_qty
                FROM warehouse_returns wr
                LEFT JOIN warehouses w ON wr.warehouse_id = w.id
                LEFT JOIN warehouses w_dest ON wr.destination_warehouse_id = w_dest.id
                LEFT JOIN shops s ON (wr.source_type = 'SHOP' AND wr.source_id = s.id)
                LEFT JOIN sales_channels sc ON ((wr.source_type = 'WHATSAPP' OR wr.source_type = 'WEBSITE') AND wr.source_id = sc.id)
                LEFT JOIN users u_insp ON wr.inspected_by = u_insp.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            $sql .= " AND wr.status = ?";
            $params[] = $filters['status'];
        }

        if (empty($filters['unscoped']) && function_exists('currentWarehouseId')) {
            $scopedWhId = currentWarehouseId();
            if ($scopedWhId !== null) {
                $sql .= " AND (wr.warehouse_id = ? OR wr.destination_warehouse_id = ?)";
                $params[] = $scopedWhId;
                $params[] = $scopedWhId;
            } elseif (!empty($filters['warehouse_id']) && $filters['warehouse_id'] !== 'ALL') {
                $sql .= " AND (wr.warehouse_id = ? OR wr.destination_warehouse_id = ?)";
                $params[] = (int)$filters['warehouse_id'];
                $params[] = (int)$filters['warehouse_id'];
            }
        } elseif (!empty($filters['warehouse_id']) && $filters['warehouse_id'] !== 'ALL') {
            $sql .= " AND (wr.warehouse_id = ? OR wr.destination_warehouse_id = ?)";
            $params[] = (int)$filters['warehouse_id'];
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['source_type']) && $filters['source_type'] !== 'ALL') {
            $sql .= " AND wr.source_type = ?";
            $params[] = $filters['source_type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (wr.return_number LIKE ? OR wr.reason LIKE ? OR s.name LIKE ? OR sc.name LIKE ? OR w.name LIKE ? OR w_dest.name LIKE ?)";
            $search = '%' . trim($filters['search']) . '%';
            $params = array_merge($params, [$search, $search, $search, $search, $search, $search]);
        }

        $sql .= " ORDER BY wr.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO warehouse_returns 
             (return_number, source_type, source_id, warehouse_id, destination_warehouse_id, reason, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['return_number'],
                $data['source_type'],
                $data['source_id'] ?? null,
                $data['warehouse_id'],
                $data['destination_warehouse_id'] ?? $data['warehouse_id'],
                $data['reason'] ?? null,
                $data['status'] ?? 'RECEIVED',
                $data['notes'] ?? null
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function updateStatus(int $id, string $status, ?int $inspectedBy = null): bool {
        if ($inspectedBy) {
            return Database::execute(
                "UPDATE warehouse_returns SET status = ?, inspected_by = ?, updated_at = NOW() WHERE id = ?",
                [$status, $inspectedBy, $id]
            );
        }
        return Database::execute(
            "UPDATE warehouse_returns SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $id]
        );
    }

    public static function generateNumber(): string {
        $prefix = 'RET-' . date('Ymd') . '-';
        $last = Database::fetchOne(
            "SELECT return_number FROM warehouse_returns WHERE return_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($last) {
            $num = (int)substr($last['return_number'], -4) + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
