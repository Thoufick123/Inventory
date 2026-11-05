<?php
/**
 * Purchase Order / Customer Direct Purchase Model
 * Handles direct customer purchases, B2B wholesale orders, headers, financial totals, status transitions, and lookups.
 */

require_once __DIR__ . '/PurchaseItem.php';

class PurchaseOrder {
    private static bool $productEditTrackingReady = false;

    /**
     * Find purchase order by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT po.*, 
                    c.name as customer_name, c.phone as customer_phone, c.email as customer_email, 
                    c.city as customer_city, c.state as customer_state, c.gst_number as customer_gst,
                    c.address as customer_address,
                    COALESCE(c.name, s.name, 'Direct Customer') as display_customer_name,
                    COALESCE(c.phone, s.phone, '') as display_customer_phone,
                    s.name as seller_name, s.company as seller_company, s.phone as seller_phone, s.gst_number as seller_gst,
                    w.name as warehouse_name, w.code as warehouse_code,
                    u.full_name as created_by_name
             FROM purchase_orders po
             LEFT JOIN customers c ON po.customer_id = c.id
             LEFT JOIN sellers s ON po.seller_id = s.id
             JOIN warehouses w ON po.warehouse_id = w.id
             LEFT JOIN users u ON po.created_by = u.id
             WHERE po.id = ?",
            [$id]
        );
    }

    /**
     * Find purchase order by PO / Order number.
     */
    public static function findByNumber(string $poNumber): ?array {
        return Database::fetchOne(
            "SELECT po.*, 
                    COALESCE(c.name, s.name, 'Direct Customer') as display_customer_name,
                    w.name as warehouse_name
             FROM purchase_orders po
             LEFT JOIN customers c ON po.customer_id = c.id
             LEFT JOIN sellers s ON po.seller_id = s.id
             JOIN warehouses w ON po.warehouse_id = w.id
             WHERE po.po_number = ?",
            [$poNumber]
        );
    }

    /**
     * Get all purchase orders with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT po.*, 
                       c.name as customer_name, c.phone as customer_phone, c.city as customer_city, c.gst_number as customer_gst,
                       COALESCE(c.name, s.name, 'Direct Customer') as display_customer_name,
                       COALESCE(c.phone, s.phone, '') as display_customer_phone,
                       s.name as seller_name, s.company as seller_company,
                       w.name as warehouse_name,
                       u.full_name as created_by_name,
                       COALESCE(pi_count.total_items, 0) as total_items,
                       COALESCE(pi_count.total_ordered_qty, 0) as total_ordered_qty,
                       COALESCE(cust_orders.cust_order_count, 1) as customer_total_orders
                FROM purchase_orders po
                LEFT JOIN customers c ON po.customer_id = c.id
                LEFT JOIN sellers s ON po.seller_id = s.id
                JOIN warehouses w ON po.warehouse_id = w.id
                LEFT JOIN users u ON po.created_by = u.id
                LEFT JOIN (
                    SELECT purchase_order_id, COUNT(*) as total_items, SUM(quantity) as total_ordered_qty
                    FROM purchase_items
                    GROUP BY purchase_order_id
                ) pi_count ON po.id = pi_count.purchase_order_id
                LEFT JOIN (
                    SELECT customer_id, COUNT(*) as cust_order_count
                    FROM purchase_orders
                    WHERE customer_id IS NOT NULL AND warehouse_supplier_id IS NULL AND seller_id IS NULL
                    GROUP BY customer_id
                ) cust_orders ON po.customer_id = cust_orders.customer_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['only_direct'])) {
            $sql .= " AND (po.warehouse_supplier_id IS NULL AND po.seller_id IS NULL)";
        }

        if (!empty($filters['status'])) {
            $sql .= " AND po.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND po.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND po.customer_id = ?";
            $params[] = (int)$filters['customer_id'];
        }

        if (!empty($filters['seller_id'])) {
            $sql .= " AND po.seller_id = ?";
            $params[] = (int)$filters['seller_id'];
        }

        if (!empty($filters['payment_status'])) {
            $sql .= " AND po.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (po.po_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR s.name LIKE ? OR w.name LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY po.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create purchase order.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO purchase_orders 
             (po_number, customer_id, seller_id, warehouse_supplier_id, warehouse_id, order_date, expected_date, subtotal, discount_amount, cgst_amount, sgst_amount, igst_amount, tax_amount, total_amount, payment_status, payment_method, payment_screenshot_url, payment_screenshot_public_id, status, notes, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['po_number'],
                !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                !empty($data['seller_id']) ? (int)$data['seller_id'] : null,
                !empty($data['warehouse_supplier_id']) ? (int)$data['warehouse_supplier_id'] : (!empty($data['seller_id']) ? (int)$data['seller_id'] : null),
                (int)$data['warehouse_id'],
                $data['order_date'] ?? date('Y-m-d'),
                $data['expected_date'] ?? null,
                $data['subtotal'] ?? 0.00,
                $data['discount_amount'] ?? 0.00,
                $data['cgst_amount'] ?? 0.00,
                $data['sgst_amount'] ?? 0.00,
                $data['igst_amount'] ?? 0.00,
                $data['tax_amount'] ?? 0.00,
                $data['total_amount'] ?? 0.00,
                $data['payment_status'] ?? 'PENDING',
                $data['payment_method'] ?? 'CASH',
                $data['payment_screenshot_url'] ?? null,
                $data['payment_screenshot_public_id'] ?? null,
                $data['status'] ?? 'CONFIRMED',
                $data['notes'] ?? null,
                $data['created_by'] ?? null
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update purchase order.
     */
    public static function update(int $id, array $data): bool {
        return Database::execute(
            "UPDATE purchase_orders 
             SET customer_id = ?, seller_id = ?, warehouse_id = ?, order_date = ?, expected_date = ?,
                 subtotal = ?, discount_amount = ?, cgst_amount = ?, sgst_amount = ?, igst_amount = ?, tax_amount = ?, total_amount = ?,
                 payment_status = ?, payment_method = ?, status = ?, notes = ?, updated_at = NOW()
             WHERE id = ?",
            [
                !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                !empty($data['seller_id']) ? (int)$data['seller_id'] : null,
                (int)$data['warehouse_id'],
                $data['order_date'],
                $data['expected_date'] ?? null,
                $data['subtotal'] ?? 0.00,
                $data['discount_amount'] ?? 0.00,
                $data['cgst_amount'] ?? 0.00,
                $data['sgst_amount'] ?? 0.00,
                $data['igst_amount'] ?? 0.00,
                $data['tax_amount'] ?? 0.00,
                $data['total_amount'] ?? 0.00,
                $data['payment_status'] ?? 'PENDING',
                $data['payment_method'] ?? 'CASH',
                $data['status'] ?? 'CONFIRMED',
                $data['notes'] ?? null,
                $id
            ]
        ) >= 0;
    }

    /**
     * Update PO status.
     */
    public static function updateStatus(int $id, string $status): bool {
        return Database::execute(
            "UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $id]
        ) > 0;
    }

    /**
     * Get all itemized products for a purchase order.
     */
    public static function getItems(int $id): array {
        return PurchaseItem::getByPurchaseOrder($id);
    }

    /**
     * Check whether this purchase order has already been edited.
     */
    public static function hasPriorEdit(int $id): bool {
        if (!self::$productEditTrackingReady) {
            $column = Database::fetchOne("SHOW COLUMNS FROM purchase_orders LIKE 'product_edit_count'");
            if (!$column) {
                Database::execute(
                    "ALTER TABLE purchase_orders
                     ADD COLUMN product_edit_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER updated_at"
                );
                Database::execute(
                    "UPDATE purchase_orders po
                     SET product_edit_count = 1
                     WHERE EXISTS (
                         SELECT 1
                         FROM audit_logs al
                         WHERE al.reference_type = 'purchase_orders'
                           AND al.reference_id = po.id
                           AND al.action = 'EDIT'
                     )"
                );
            }
            self::$productEditTrackingReady = true;
        }

        $purchase = Database::fetchOne(
            "SELECT product_edit_count
             FROM purchase_orders
             WHERE id = ?",
            [$id]
        );

        return (int)($purchase['product_edit_count'] ?? 0) > 0;
    }
}
