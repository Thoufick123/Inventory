<?php
/**
 * Central Stock Service — THE SINGLE SOURCE OF TRUTH
 * 
 * ALL stock-changing operations across ALL modules MUST go through this service.
 * Production, Warehouse, Shop, Sales — everyone calls this.
 * 
 * Uses MySQL transactions and row locking (SELECT ... FOR UPDATE)
 * for data consistency.
 */

require_once BASE_PATH . '/app/Models/StockBalance.php';
require_once BASE_PATH . '/app/Models/StockTransaction.php';

class StockService {

    /**
     * Increase stock at a location.
     * Used for: Production GRN, Purchase GRN, Transfer In, Return (good stock)
     */
    public static function increase(
        int $variantId,
        string $locationType,
        int $locationId,
        string $stockState,
        float $quantity,
        string $transactionType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Increase quantity must be positive. Got: $quantity");
        }

        Database::beginTransaction();
        try {
            // Get or create balance record with row lock
            $balance = self::getBalanceLocked($variantId, $locationType, $locationId, $stockState);
            $newQty = $balance + $quantity;

            // Update balance
            self::upsertBalance($variantId, $locationType, $locationId, $stockState, $newQty);

            // Record transaction
            StockTransaction::create([
                'variant_id'       => $variantId,
                'location_type'    => $locationType,
                'location_id'      => $locationId,
                'stock_state'      => $stockState,
                'transaction_type' => $transactionType,
                'quantity'         => $quantity,
                'balance_after'    => $newQty,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'notes'            => $notes,
            ]);

            Database::commit();

            // Check low stock alerts
            self::checkLowStockAlert($variantId, $locationType, $locationId);

            return true;
        } catch (Exception $e) {
            Database::rollback();
            error_log("StockService::increase failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Decrease stock at a location.
     * Used for: Sale, Transfer Out, Damage, Disposal
     */
    public static function decrease(
        int $variantId,
        string $locationType,
        int $locationId,
        string $stockState,
        float $quantity,
        string $transactionType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Decrease quantity must be positive. Got: $quantity");
        }

        Database::beginTransaction();
        try {
            // Get balance with row lock
            $balance = self::getBalanceLocked($variantId, $locationType, $locationId, $stockState);

            if ($balance < $quantity) {
                throw new RuntimeException(
                    "Insufficient stock. Available: $balance, Requested: $quantity " .
                    "(Variant: $variantId, Location: $locationType/$locationId, State: $stockState)"
                );
            }

            $newQty = $balance - $quantity;

            // Update balance
            self::upsertBalance($variantId, $locationType, $locationId, $stockState, $newQty);

            // Record transaction
            StockTransaction::create([
                'variant_id'       => $variantId,
                'location_type'    => $locationType,
                'location_id'      => $locationId,
                'stock_state'      => $stockState,
                'transaction_type' => $transactionType,
                'quantity'         => -$quantity,
                'balance_after'    => $newQty,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'notes'            => $notes,
            ]);

            Database::commit();

            // Check low stock alerts
            self::checkLowStockAlert($variantId, $locationType, $locationId);

            return true;
        } catch (Exception $e) {
            Database::rollback();
            error_log("StockService::decrease failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reserve stock (move from Available → Reserved).
     * Used for: Allocation, Order confirmation
     */
    public static function reserve(
        int $variantId,
        string $locationType,
        int $locationId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Reserve quantity must be positive.");
        }

        Database::beginTransaction();
        try {
            // Lock available balance
            $availableBalance = self::getBalanceLocked($variantId, $locationType, $locationId, 'AVAILABLE');

            if ($availableBalance < $quantity) {
                throw new RuntimeException(
                    "Insufficient available stock for reservation. Available: $availableBalance, Requested: $quantity"
                );
            }

            $newAvailable = $availableBalance - $quantity;

            // Lock reserved balance
            $reservedBalance = self::getBalanceLocked($variantId, $locationType, $locationId, 'RESERVED');
            $newReserved = $reservedBalance + $quantity;

            // Update both balances
            self::upsertBalance($variantId, $locationType, $locationId, 'AVAILABLE', $newAvailable);
            self::upsertBalance($variantId, $locationType, $locationId, 'RESERVED', $newReserved);

            // Record decrease from available
            StockTransaction::create([
                'variant_id'       => $variantId,
                'location_type'    => $locationType,
                'location_id'      => $locationId,
                'stock_state'      => 'AVAILABLE',
                'transaction_type' => 'ALLOCATION_RESERVE',
                'quantity'         => -$quantity,
                'balance_after'    => $newAvailable,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'notes'            => $notes,
            ]);

            // Record increase in reserved
            StockTransaction::create([
                'variant_id'       => $variantId,
                'location_type'    => $locationType,
                'location_id'      => $locationId,
                'stock_state'      => 'RESERVED',
                'transaction_type' => 'ALLOCATION_RESERVE',
                'quantity'         => $quantity,
                'balance_after'    => $newReserved,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'notes'            => $notes,
            ]);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            error_log("StockService::reserve failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Release reserved stock (move from Reserved → Available).
     * Used for: Cancelled allocation, Order cancellation
     */
    public static function release(
        int $variantId,
        string $locationType,
        int $locationId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Release quantity must be positive.");
        }

        Database::beginTransaction();
        try {
            $reservedBalance = self::getBalanceLocked($variantId, $locationType, $locationId, 'RESERVED');

            if ($reservedBalance < $quantity) {
                throw new RuntimeException(
                    "Insufficient reserved stock for release. Reserved: $reservedBalance, Requested: $quantity"
                );
            }

            $newReserved = $reservedBalance - $quantity;
            $availableBalance = self::getBalanceLocked($variantId, $locationType, $locationId, 'AVAILABLE');
            $newAvailable = $availableBalance + $quantity;

            self::upsertBalance($variantId, $locationType, $locationId, 'RESERVED', $newReserved);
            self::upsertBalance($variantId, $locationType, $locationId, 'AVAILABLE', $newAvailable);

            StockTransaction::create([
                'variant_id'       => $variantId,
                'location_type'    => $locationType,
                'location_id'      => $locationId,
                'stock_state'      => 'RESERVED',
                'transaction_type' => 'ALLOCATION_RELEASE',
                'quantity'         => -$quantity,
                'balance_after'    => $newReserved,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'notes'            => $notes,
            ]);

            StockTransaction::create([
                'variant_id'       => $variantId,
                'location_type'    => $locationType,
                'location_id'      => $locationId,
                'stock_state'      => 'AVAILABLE',
                'transaction_type' => 'ALLOCATION_RELEASE',
                'quantity'         => $quantity,
                'balance_after'    => $newAvailable,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'notes'            => $notes,
            ]);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            error_log("StockService::release failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Transfer stock between locations.
     * Used for: Warehouse-to-Warehouse, Warehouse-to-Shop transfers
     */
    public static function transfer(
        int $variantId,
        string $srcLocationType,
        int $srcLocationId,
        string $destLocationType,
        int $destLocationId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Transfer quantity must be positive.");
        }

        Database::beginTransaction();
        try {
            // Decrease source available
            $srcBalance = self::getBalanceLocked($variantId, $srcLocationType, $srcLocationId, 'AVAILABLE');

            if ($srcBalance < $quantity) {
                throw new RuntimeException(
                    "Insufficient stock at source for transfer. Available: $srcBalance, Requested: $quantity"
                );
            }

            $newSrcBalance = $srcBalance - $quantity;
            self::upsertBalance($variantId, $srcLocationType, $srcLocationId, 'AVAILABLE', $newSrcBalance);

            // Increase destination available
            $destBalance = self::getBalanceLocked($variantId, $destLocationType, $destLocationId, 'AVAILABLE');
            $newDestBalance = $destBalance + $quantity;
            self::upsertBalance($variantId, $destLocationType, $destLocationId, 'AVAILABLE', $newDestBalance);

            // Record source transaction
            StockTransaction::create([
                'variant_id'       => $variantId,
                'location_type'    => $srcLocationType,
                'location_id'      => $srcLocationId,
                'stock_state'      => 'AVAILABLE',
                'transaction_type' => 'TRANSFER_OUT',
                'quantity'         => -$quantity,
                'balance_after'    => $newSrcBalance,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'notes'            => $notes . " (Transfer to $destLocationType #$destLocationId)",
            ]);

            // Record destination transaction
            StockTransaction::create([
                'variant_id'       => $variantId,
                'location_type'    => $destLocationType,
                'location_id'      => $destLocationId,
                'stock_state'      => 'AVAILABLE',
                'transaction_type' => 'TRANSFER_IN',
                'quantity'         => $quantity,
                'balance_after'    => $newDestBalance,
                'reference_type'   => $referenceType,
                'reference_id'     => $referenceId,
                'user_id'          => $userId,
                'notes'            => $notes . " (Transfer from $srcLocationType #$srcLocationId)",
            ]);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            error_log("StockService::transfer failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Adjust stock (increase or decrease with reason).
     * Used for: Approved stock adjustments
     */
    public static function adjust(
        int $variantId,
        string $locationType,
        int $locationId,
        string $adjustmentType,
        float $quantity,
        string $reason,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Adjustment quantity must be positive.");
        }

        $transactionType = $adjustmentType === 'INCREASE' ? 'ADJUSTMENT_INCREASE' : 'ADJUSTMENT_DECREASE';

        if ($adjustmentType === 'INCREASE') {
            return self::increase(
                $variantId, $locationType, $locationId, 'AVAILABLE',
                $quantity, $transactionType, 'stock_adjustments', null,
                $userId, "ADJUSTMENT: $reason. $notes"
            );
        } else {
            return self::decrease(
                $variantId, $locationType, $locationId, 'AVAILABLE',
                $quantity, $transactionType, 'stock_adjustments', null,
                $userId, "ADJUSTMENT: $reason. $notes"
            );
        }
    }

    // ====================================================
    // QUERY METHODS
    // ====================================================

    /**
     * Get current balance for a variant at a specific location.
     */
    public static function getBalance(int $variantId, string $locationType, int $locationId, ?string $stockState = null): float {
        if ($stockState) {
            $result = Database::fetchOne(
                "SELECT quantity FROM stock_balances 
                 WHERE variant_id = ? AND location_type = ? AND location_id = ? AND stock_state = ?",
                [$variantId, $locationType, $locationId, $stockState]
            );
            return (float) ($result['quantity'] ?? 0);
        }

        // Sum all states
        $result = Database::fetchOne(
            "SELECT SUM(quantity) as total FROM stock_balances 
             WHERE variant_id = ? AND location_type = ? AND location_id = ?",
            [$variantId, $locationType, $locationId]
        );
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Get all stock balances for a location.
     */
    public static function getStockByLocation(string $locationType, int $locationId, array $filters = []): array {
        $sql = "SELECT sb.*, pv.sku, pv.qr_code, pv.selling_price, pv.mrp,
                       p.name as product_name, p.code as product_code,
                       s.name as size_name, c.name as color_name
                FROM stock_balances sb
                JOIN product_variants pv ON sb.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN sizes s ON pv.size_id = s.id
                LEFT JOIN colors c ON pv.color_id = c.id
                WHERE sb.location_type = ? AND sb.location_id = ?";
        $params = [$locationType, $locationId];

        if (!empty($filters['stock_state'])) {
            $sql .= " AND sb.stock_state = ?";
            $params[] = $filters['stock_state'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR pv.sku LIKE ? OR p.code LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }
        if (isset($filters['min_qty'])) {
            $sql .= " AND sb.quantity >= ?";
            $params[] = $filters['min_qty'];
        }

        $sql .= " ORDER BY p.name ASC, pv.sku ASC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get transaction history for a variant.
     */
    public static function getTransactionHistory(int $variantId, array $filters = []): array {
        $sql = "SELECT st.*, u.full_name as user_name
                FROM stock_transactions st
                LEFT JOIN users u ON st.user_id = u.id
                WHERE st.variant_id = ?";
        $params = [$variantId];

        if (!empty($filters['location_type'])) {
            $sql .= " AND st.location_type = ?";
            $params[] = $filters['location_type'];
        }
        if (!empty($filters['location_id'])) {
            $sql .= " AND st.location_id = ?";
            $params[] = $filters['location_id'];
        }
        if (!empty($filters['transaction_type'])) {
            $sql .= " AND st.transaction_type = ?";
            $params[] = $filters['transaction_type'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND st.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND st.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $sql .= " ORDER BY st.created_at DESC, st.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get low stock alerts for a location.
     */
    public static function getLowStockAlerts(string $locationType, int $locationId): array {
        return Database::fetchAll(
            "SELECT sb.*, pv.sku, p.name as product_name, p.min_stock,
                    s.name as size_name, c.name as color_name
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE sb.location_type = ? AND sb.location_id = ?
               AND sb.stock_state = 'AVAILABLE'
               AND sb.quantity <= p.min_stock
               AND p.min_stock > 0
             ORDER BY sb.quantity ASC",
            [$locationType, $locationId]
        );
    }

    /**
     * Get total stock value for a location.
     */
    public static function getStockValue(string $locationType, int $locationId): float {
        $result = Database::fetchOne(
            "SELECT SUM(sb.quantity * pv.selling_price) as total_value
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             WHERE sb.location_type = ? AND sb.location_id = ?
               AND sb.stock_state = 'AVAILABLE'",
            [$locationType, $locationId]
        );
        return (float) ($result['total_value'] ?? 0);
    }

    // ====================================================
    // PRIVATE HELPERS
    // ====================================================

    /**
     * Get balance with row lock for transactional safety.
     */
    private static function getBalanceLocked(int $variantId, string $locationType, int $locationId, string $stockState): float {
        $result = Database::fetchOne(
            "SELECT quantity FROM stock_balances 
             WHERE variant_id = ? AND location_type = ? AND location_id = ? AND stock_state = ?
             FOR UPDATE",
            [$variantId, $locationType, $locationId, $stockState]
        );
        return (float) ($result['quantity'] ?? 0);
    }

    /**
     * Insert or update a stock balance record.
     */
    private static function upsertBalance(int $variantId, string $locationType, int $locationId, string $stockState, float $quantity): void {
        Database::execute(
            "INSERT INTO stock_balances (variant_id, location_type, location_id, stock_state, quantity, last_updated, created_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), last_updated = NOW()",
            [$variantId, $locationType, $locationId, $stockState, $quantity]
        );

        // Keep master products.quantity cached total synchronized
        try {
            Database::execute(
                "UPDATE products p
                 JOIN product_variants pv ON p.id = pv.product_id
                 SET p.quantity = (
                     SELECT COALESCE(SUM(sb.quantity), 0)
                     FROM stock_balances sb
                     JOIN product_variants pv2 ON sb.variant_id = pv2.id
                     WHERE pv2.product_id = p.id AND sb.location_type = 'WAREHOUSE' AND sb.stock_state = 'AVAILABLE'
                 )
                 WHERE pv.id = ?",
                [$variantId]
            );
        } catch (Exception $e) {
            // Ignore during nested triggers or migrations
        }
    }

    /**
     * Check and trigger low stock notification.
     */
    private static function checkLowStockAlert(int $variantId, string $locationType, int $locationId): void {
        $product = Database::fetchOne(
            "SELECT p.name, p.min_stock, pv.sku
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             WHERE pv.id = ?",
            [$variantId]
        );

        if (!$product || $product['min_stock'] <= 0) return;

        $balance = self::getBalance($variantId, $locationType, $locationId, 'AVAILABLE');

        if ($balance <= $product['min_stock']) {
            // Create notification (if NotificationService is available)
            $notifFile = BASE_PATH . '/app/Services/NotificationService.php';
            if (file_exists($notifFile)) {
                require_once $notifFile;
                if (class_exists('NotificationService')) {
                    NotificationService::createLowStockAlert(
                        $variantId, $locationType, $locationId,
                        $balance, $product['min_stock'],
                        $product['name'] . ' (' . $product['sku'] . ')'
                    );
                }
            }
        }
    }
}
