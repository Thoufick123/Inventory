<?php
/**
 * Migration: Add Warehouse Direct Purchases Columns to `returns`, `return_items`, and `exchanges`
 */

define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();

    echo "Starting schema update for Warehouse Direct Purchases Returns & Exchanges...\n";

    // 1. Update `returns` table
    $returnsCols = array_column(Database::fetchAll("DESCRIBE returns"), 'Field');

    // Make sale_id nullable if it's currently NOT NULL
    $db->exec("ALTER TABLE `returns` MODIFY `sale_id` INT UNSIGNED NULL DEFAULT NULL");
    echo "✓ Modified `returns.sale_id` to be NULLABLE\n";

    if (!in_array('source_type', $returnsCols)) {
        $db->exec("ALTER TABLE `returns` ADD COLUMN `source_type` VARCHAR(50) NOT NULL DEFAULT 'SHOP' AFTER `return_number`");
        echo "✓ Added `source_type` to `returns`\n";
    }

    if (!in_array('purchase_order_id', $returnsCols)) {
        $db->exec("ALTER TABLE `returns` ADD COLUMN `purchase_order_id` INT UNSIGNED DEFAULT NULL AFTER `sale_id`");
        echo "✓ Added `purchase_order_id` to `returns`\n";
    }

    if (!in_array('warehouse_id', $returnsCols)) {
        $db->exec("ALTER TABLE `returns` ADD COLUMN `warehouse_id` INT UNSIGNED DEFAULT NULL AFTER `customer_id`");
        echo "✓ Added `warehouse_id` to `returns`\n";
    }

    if (!in_array('total_amount', $returnsCols)) {
        $db->exec("ALTER TABLE `returns` ADD COLUMN `total_amount` DECIMAL(10,2) DEFAULT '0.00' AFTER `updated_at`");
        echo "✓ Added `total_amount` to `returns`\n";
    }

    if (!in_array('status', $returnsCols)) {
        $db->exec("ALTER TABLE `returns` ADD COLUMN `status` ENUM('PENDING','APPROVED','REJECTED','REFUNDED') DEFAULT 'PENDING' AFTER `total_amount`");
        echo "✓ Added `status` to `returns`\n";
    }

    if (!in_array('processed_by', $returnsCols)) {
        $db->exec("ALTER TABLE `returns` ADD COLUMN `processed_by` INT UNSIGNED DEFAULT NULL AFTER `status`");
        echo "✓ Added `processed_by` to `returns`\n";
    }

    // Add indexes for returns if not exist
    try {
        $db->exec("ALTER TABLE `returns` ADD INDEX `idx_returns_source` (`source_type`)");
    } catch (\Throwable $e) {}
    try {
        $db->exec("ALTER TABLE `returns` ADD INDEX `idx_returns_po` (`purchase_order_id`)");
    } catch (\Throwable $e) {}
    try {
        $db->exec("ALTER TABLE `returns` ADD INDEX `idx_returns_warehouse` (`warehouse_id`)");
    } catch (\Throwable $e) {}

    // 2. Update `return_items` table
    $returnItemsCols = array_column(Database::fetchAll("DESCRIBE return_items"), 'Field');

    if (!in_array('refund_amount', $returnItemsCols)) {
        $db->exec("ALTER TABLE `return_items` ADD COLUMN `refund_amount` DECIMAL(10,2) DEFAULT '0.00'");
        echo "✓ Added `refund_amount` to `return_items`\n";
    }

    if (!in_array('condition_type', $returnItemsCols)) {
        $db->exec("ALTER TABLE `return_items` ADD COLUMN `condition_type` ENUM('GOOD','DAMAGED','DEFECTIVE') DEFAULT 'GOOD'");
        echo "✓ Added `condition_type` to `return_items`\n";
    }

    if (!in_array('reason', $returnItemsCols)) {
        $db->exec("ALTER TABLE `return_items` ADD COLUMN `reason` VARCHAR(255) DEFAULT NULL");
        echo "✓ Added `reason` to `return_items`\n";
    }

    // 3. Update `exchanges` table
    $exchangesCols = array_column(Database::fetchAll("DESCRIBE exchanges"), 'Field');

    if (!in_array('exchange_number', $exchangesCols)) {
        $db->exec("ALTER TABLE `exchanges` ADD COLUMN `exchange_number` VARCHAR(50) NULL AFTER `id`");
        echo "✓ Added `exchange_number` to `exchanges`\n";
    }

    if (!in_array('source_type', $exchangesCols)) {
        $db->exec("ALTER TABLE `exchanges` ADD COLUMN `source_type` VARCHAR(50) NOT NULL DEFAULT 'SHOP' AFTER `return_id`");
        echo "✓ Added `source_type` to `exchanges`\n";
    }

    if (!in_array('purchase_order_id', $exchangesCols)) {
        $db->exec("ALTER TABLE `exchanges` ADD COLUMN `purchase_order_id` INT UNSIGNED DEFAULT NULL AFTER `sale_id`");
        echo "✓ Added `purchase_order_id` to `exchanges`\n";
    }

    if (!in_array('settlement_mode', $exchangesCols)) {
        $db->exec("ALTER TABLE `exchanges` ADD COLUMN `settlement_mode` VARCHAR(50) NOT NULL DEFAULT 'CASH' AFTER `difference_amount`");
        echo "✓ Added `settlement_mode` to `exchanges`\n";
    }

    if (!in_array('payment_status', $exchangesCols)) {
        $db->exec("ALTER TABLE `exchanges` ADD COLUMN `payment_status` ENUM('PAID','PENDING','REFUNDED') DEFAULT 'PAID'");
        echo "✓ Added `payment_status` to `exchanges`\n";
    }

    if (!in_array('notes', $exchangesCols)) {
        $db->exec("ALTER TABLE `exchanges` ADD COLUMN `notes` TEXT NULL");
        echo "✓ Added `notes` to `exchanges`\n";
    }

    try {
        $db->exec("ALTER TABLE `exchanges` ADD INDEX `idx_exchanges_source` (`source_type`)");
    } catch (\Throwable $e) {}
    try {
        $db->exec("ALTER TABLE `exchanges` ADD INDEX `idx_exchanges_po` (`purchase_order_id`)");
    } catch (\Throwable $e) {}

    echo "\nAll schema updates completed successfully!\n";

} catch (\Throwable $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
    exit(1);
}
