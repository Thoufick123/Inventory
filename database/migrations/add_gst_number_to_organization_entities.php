<?php
/**
 * Migration: Add GSTIN fields to production units, warehouses, and sales channels.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    foreach (['production_units', 'warehouses', 'sales_channels'] as $table) {
        $column = Database::fetchOne("SHOW COLUMNS FROM `{$table}` LIKE 'gst_number'");
        if (!$column) {
            Database::execute("ALTER TABLE `{$table}` ADD COLUMN `gst_number` VARCHAR(20) NULL AFTER `phone`");
            echo "Added gst_number to {$table}.\n";
        } else {
            echo "gst_number already exists on {$table}.\n";
        }
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "GSTIN migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
