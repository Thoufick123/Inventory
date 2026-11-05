<?php
/**
 * Migration: Add 'CHANNEL' and 'OFFICE' to location_type and source/dest enums in stock tables
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();
    echo "Starting migration: update_stock_location_enums...\n";

    // 1. Update stock_balances.location_type
    echo "Updating stock_balances.location_type...\n";
    $db->exec("ALTER TABLE `stock_balances` 
               MODIFY COLUMN `location_type` ENUM('WAREHOUSE','SHOP','PRODUCTION_UNIT','CHANNEL','OFFICE') 
               CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    echo "✓ stock_balances.location_type updated successfully.\n";

    // 2. Update stock_transactions.location_type
    echo "Updating stock_transactions.location_type...\n";
    $db->exec("ALTER TABLE `stock_transactions` 
               MODIFY COLUMN `location_type` ENUM('WAREHOUSE','SHOP','PRODUCTION_UNIT','CHANNEL','OFFICE') 
               CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    echo "✓ stock_transactions.location_type updated successfully.\n";

    // 3. Update stock_transactions.source_type
    echo "Updating stock_transactions.source_type...\n";
    $db->exec("ALTER TABLE `stock_transactions` 
               MODIFY COLUMN `source_type` ENUM('PRODUCTION','PURCHASE','WAREHOUSE','SHOP','CUSTOMER','SUPPLIER','ADJUSTMENT','CHANNEL','OFFICE') 
               COLLATE utf8mb4_unicode_ci DEFAULT NULL");
    echo "✓ stock_transactions.source_type updated successfully.\n";

    // 4. Update stock_transactions.dest_type
    echo "Updating stock_transactions.dest_type...\n";
    $db->exec("ALTER TABLE `stock_transactions` 
               MODIFY COLUMN `dest_type` ENUM('PRODUCTION','PURCHASE','WAREHOUSE','SHOP','CUSTOMER','SUPPLIER','ADJUSTMENT','CHANNEL','OFFICE') 
               COLLATE utf8mb4_unicode_ci DEFAULT NULL");
    echo "✓ stock_transactions.dest_type updated successfully.\n";

    echo "\n--------------------------------------------\n";
    echo "Migration completed successfully!\n";
} catch (\Throwable $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
