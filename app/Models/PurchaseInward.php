<?php
/**
 * Purchase Inward (GRN) Model
 * Handles supplier goods receipt notes, verification, and warehouse intake.
 */

class PurchaseInward {

    /**
     * Find purchase inward by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT pi.*, 
                    po.po_number, po.order_date,
                    s.name as seller_name, s.company as seller_company,
                    w.name as warehouse_name, w.code as warehouse_code,
                    u_rec.full_name as received_by_name,
                    u_ver.full_name as verified_by_name
             FROM purchase_inwards pi
             JOIN purchase_orders po ON pi.purchase_order_id = po.id
             JOIN sellers s ON po.seller_id = s.id
             JOIN warehouses w ON pi.warehouse_id = w.id
             LEFT JOIN users u_rec ON pi.received_by = u_rec.id
             LEFT JOIN users u_ver ON pi.verified_by = u_ver.id
             WHERE pi.id = ?",
            [$id]
        );
    }

    /**
     * Get all purchase inwards with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT pi.*, 
                       po.po_number,
                       s.name as seller_name,
                       w.name as warehouse_name,
                       u_rec.full_name as received_by_name
                FROM purchase_inwards pi
                JOIN purchase_orders po ON pi.purchase_order_id = po.id
                JOIN sellers s ON po.seller_id = s.id
                JOIN warehouses w ON pi.warehouse_id = w.id
                LEFT JOIN users u_rec ON pi.received_by = u_rec.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND pi.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['purchase_order_id'])) {
            $sql .= " AND pi.purchase_order_id = ?";
            $params[] = (int)$filters['purchase_order_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (pi.grn_number LIKE ? OR po.po_number LIKE ? OR s.name LIKE ? OR w.name LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        $sql .= " ORDER BY pi.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create purchase inward record.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO purchase_inwards 
             (purchase_order_id, grn_number, warehouse_id, received_date, received_by, verified_by, notes, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                (int)$data['purchase_order_id'],
                $data['grn_number'],
                (int)$data['warehouse_id'],
                $data['received_date'] ?? date('Y-m-d'),
                $data['received_by'] ?? null,
                $data['verified_by'] ?? null,
                $data['notes'] ?? null
            ]
        );
        return (int)Database::lastInsertId();
    }
}
