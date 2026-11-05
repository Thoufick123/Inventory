<?php
/**
 * Migration: Add received_qty, damaged_qty, and missing_qty to production_batches and production_batch_items
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();
    echo "Running migration: Add received_qty, damaged_qty, missing_qty...\n\n";

    // 1. Check & Alter production_batch_items
    $cols = array_column(Database::fetchAll("DESCRIBE `production_batch_items`"), 'Field');
    if (!in_array('received_qty', $cols)) {
        $db->exec("ALTER TABLE `production_batch_items` ADD COLUMN `received_qty` int NOT NULL DEFAULT 0 AFTER `quantity`");
        echo "✓ Added `received_qty` to `production_batch_items`\n";
    }
    if (!in_array('damaged_qty', $cols)) {
        $db->exec("ALTER TABLE `production_batch_items` ADD COLUMN `damaged_qty` int NOT NULL DEFAULT 0 AFTER `received_qty`");
        echo "✓ Added `damaged_qty` to `production_batch_items`\n";
    }
    if (!in_array('missing_qty', $cols)) {
        $db->exec("ALTER TABLE `production_batch_items` ADD COLUMN `missing_qty` int NOT NULL DEFAULT 0 AFTER `damaged_qty`");
        echo "✓ Added `missing_qty` to `production_batch_items`\n";
    }

    // 2. Check & Alter production_batches
    $pbCols = array_column(Database::fetchAll("DESCRIBE `production_batches`"), 'Field');
    if (!in_array('received_qty', $pbCols)) {
        $db->exec("ALTER TABLE `production_batches` ADD COLUMN `received_qty` int NOT NULL DEFAULT 0 AFTER `quantity`");
        echo "✓ Added `received_qty` to `production_batches`\n";
    }
    if (!in_array('damaged_qty', $pbCols)) {
        $db->exec("ALTER TABLE `production_batches` ADD COLUMN `damaged_qty` int NOT NULL DEFAULT 0 AFTER `received_qty`");
        echo "✓ Added `damaged_qty` to `production_batches`\n";
    }
    if (!in_array('missing_qty', $pbCols)) {
        $db->exec("ALTER TABLE `production_batches` ADD COLUMN `missing_qty` int NOT NULL DEFAULT 0 AFTER `damaged_qty`");
        echo "✓ Added `missing_qty` to `production_batches`\n";
    }

    echo "\nMigration completed successfully!\n";
} catch (\Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
