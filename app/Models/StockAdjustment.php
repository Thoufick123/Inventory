<?php
/**
 * Stock Adjustment Model
 * Handles manual stock variance requests, approvals, and discrepancy tracking.
 */

class StockAdjustment {
    private static bool $qrCodesColumnReady = false;

    private static function ensureQrCodesColumn(): void {
        if (self::$qrCodesColumnReady) {
            return;
        }

        $column = Database::fetchOne("SHOW COLUMNS FROM stock_adjustments LIKE 'qr_codes'");
        if (!$column) {
            Database::query("ALTER TABLE stock_adjustments ADD COLUMN qr_codes TEXT NULL AFTER notes");
        }
        self::$qrCodesColumnReady = true;
    }

    public static function findQrCodeUsage(string $tagCode, int $excludeAdjustmentId = 0): ?array {
        self::ensureQrCodesColumn();
        $sql = "SELECT adjustment_number, status
                FROM stock_adjustments
                WHERE qr_codes LIKE ? AND status IN ('PENDING', 'APPROVED')";
        $params = ['%"' . strtoupper(trim($tagCode)) . '"%'];
        if ($excludeAdjustmentId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeAdjustmentId;
        }
        $sql .= " ORDER BY id DESC LIMIT 1";
        return Database::fetchOne($sql, $params);
    }

    public static function getRemovedQrCodes(int $variantId, ?int $warehouseId = null): array {
        self::ensureQrCodesColumn();
        $sql = "SELECT qr_codes FROM stock_adjustments
                WHERE variant_id = ? AND adjustment_type = 'DECREASE' AND status = 'APPROVED'";
        $params = [$variantId];
        if ($warehouseId !== null && $warehouseId > 0) {
            $sql .= " AND warehouse_id = ?";
            $params[] = $warehouseId;
        }

        $removedCodes = [];
        foreach (Database::fetchAll($sql, $params) as $adjustment) {
            $codes = json_decode((string)($adjustment['qr_codes'] ?? ''), true);
            if (!is_array($codes)) {
                continue;
            }
            foreach ($codes as $code) {
                $normalizedCode = strtoupper(trim((string)$code));
                if ($normalizedCode !== '') {
                    $removedCodes[$normalizedCode] = true;
                }
            }
        }

        return array_keys($removedCodes);
    }

    /**
     * Find adjustment by ID.
     */
    public static function find(int $id): ?array {
        self::ensureQrCodesColumn();
        return Database::fetchOne(
            "SELECT sa.*, 
                    w.name as warehouse_name, w.code as warehouse_code,
                    pv.sku, pv.qr_code, pv.selling_price,
                    p.name as product_name, p.code as product_code,
                    s.name as size_name, c.name as color_name,
                    u_req.full_name as requested_by_name,
                    u_app.full_name as approved_by_name
             FROM stock_adjustments sa
             JOIN warehouses w ON sa.warehouse_id = w.id
             JOIN product_variants pv ON sa.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN users u_req ON sa.requested_by = u_req.id
             LEFT JOIN users u_app ON sa.approved_by = u_app.id
             WHERE sa.id = ?",
            [$id]
        );
    }

    /**
     * Get all adjustments with filters.
     */
    public static function getAll(array $filters = []): array {
        self::ensureQrCodesColumn();
        $sql = "SELECT sa.*, 
                       w.name as warehouse_name,
                       pv.sku,
                       p.name as product_name,
                       s.name as size_name, c.name as color_name,
                       u_req.full_name as requested_by_name,
                       u_app.full_name as approved_by_name
                FROM stock_adjustments sa
                JOIN warehouses w ON sa.warehouse_id = w.id
                JOIN product_variants pv ON sa.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN sizes s ON pv.size_id = s.id
                LEFT JOIN colors c ON pv.color_id = c.id
                LEFT JOIN users u_req ON sa.requested_by = u_req.id
                LEFT JOIN users u_app ON sa.approved_by = u_app.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND sa.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND sa.warehouse_id = ?";
            $params[] = (int)$filters['warehouse_id'];
        }

        if (!empty($filters['adjustment_type'])) {
            $sql .= " AND sa.adjustment_type = ?";
            $params[] = $filters['adjustment_type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (sa.adjustment_number LIKE ? OR pv.sku LIKE ? OR p.name LIKE ? OR sa.reason LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        $sql .= " ORDER BY sa.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Create adjustment record.
     */
    public static function create(array $data): int {
        self::ensureQrCodesColumn();
        Database::execute(
            "INSERT INTO stock_adjustments 
             (adjustment_number, warehouse_id, variant_id, adjustment_type, quantity, reason, requested_by, status, notes, qr_codes, adj_number, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['adjustment_number'],
                (int)$data['warehouse_id'],
                (int)$data['variant_id'],
                $data['adjustment_type'],
                (float)$data['quantity'],
                $data['reason'],
                $data['requested_by'] ?? null,
                $data['status'] ?? 'PENDING',
                $data['notes'] ?? null,
                !empty($data['qr_codes']) ? json_encode($data['qr_codes'], JSON_UNESCAPED_UNICODE) : null,
                $data['adjustment_number']
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Approve or reject adjustment.
     */
    public static function updateStatus(int $id, string $status, int $approvedBy): bool {
        return Database::execute(
            "UPDATE stock_adjustments 
             SET status = ?, approved_by = ?, updated_at = NOW() 
             WHERE id = ?",
            [$status, $approvedBy, $id]
        ) > 0;
    }
}
