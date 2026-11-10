<?php
/**
 * Add nullable concern ownership to facilities and core inventory records.
 * Existing rows remain unassigned; this migration does not backfill or copy data.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$tables = [
    'production_units',
    'warehouses',
    'shops',
    'sales_channels',
    'stock_balances',
    'stock_transactions',
    'stock_adjustments',
    'production_batches',
    'production_batch_items',
    'production_outwards',
    'production_inwards',
    'production_inward_items',
    'warehouse_transfers',
    'transfer_items',
    'warehouse_allocations',
    'allocation_items',
    'allocation_outwards',
];

try {
    if (!Database::fetchOne("SHOW TABLES LIKE 'concerns'")) {
        throw new RuntimeException('Run create_staff_concerns.php before this migration.');
    }

    foreach ($tables as $table) {
        $tableExists = Database::fetchOne(
            "SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
        if (!$tableExists) {
            throw new RuntimeException("Required table '{$table}' does not exist.");
        }

        $column = Database::fetchOne("SHOW COLUMNS FROM `{$table}` LIKE 'concern_id'");
        if (!$column) {
            Database::execute(
                "ALTER TABLE `{$table}` ADD COLUMN concern_id INT UNSIGNED NULL DEFAULT NULL"
            );
        }

        $indexName = "idx_{$table}_concern";
        $index = Database::fetchOne(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        if (!$index) {
            Database::execute(
                "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (concern_id)"
            );
        }

        $constraintName = "fk_{$table}_concern";
        $constraint = Database::fetchOne(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?",
            [$table, $constraintName]
        );
        if (!$constraint) {
            Database::execute(
                "ALTER TABLE `{$table}`
                 ADD CONSTRAINT `{$constraintName}`
                 FOREIGN KEY (concern_id) REFERENCES concerns (id)
                 ON DELETE RESTRICT"
            );
        }
    }

    echo "Concern ownership columns added to facilities and core inventory tables. Existing records remain unassigned.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Concern inventory ownership migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
