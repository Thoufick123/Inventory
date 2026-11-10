<?php
/**
 * Add concern ownership to business transaction headers and payment records.
 * Existing records remain unassigned and are not backfilled across concerns.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$columnsByTable = [
    'customers' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'sales' => [
        'concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'sales_channel_id' => 'INT UNSIGNED NULL DEFAULT NULL',
    ],
    'payments' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'purchase_orders' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'purchase_inwards' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'returns' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'exchanges' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'warehouse_returns' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'production_bills' => [
        'concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'production_unit_id' => 'INT UNSIGNED NULL DEFAULT NULL',
    ],
    'production_bill_returns' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'production_purchases' => [
        'concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'production_unit_id' => 'INT UNSIGNED NULL DEFAULT NULL',
    ],
    'production_expenses' => [
        'concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'production_unit_id' => 'INT UNSIGNED NULL DEFAULT NULL',
    ],
    'wastage_sales' => [
        'concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'production_unit_id' => 'INT UNSIGNED NULL DEFAULT NULL',
    ],
    'stitching_wastage_sales' => [
        'concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'production_unit_id' => 'INT UNSIGNED NULL DEFAULT NULL',
    ],
    'expenses' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'cash_registers' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'cash_transactions' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'cash_expenses' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'bank_deposits' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'production_cash_registers' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'production_cash_transactions' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'production_cash_expenses' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
    'production_cash_deposits' => ['concern_id' => 'INT UNSIGNED NULL DEFAULT NULL'],
];

try {
    if (!Database::fetchOne("SHOW TABLES LIKE 'concerns'")) {
        throw new RuntimeException('Run create_staff_concerns.php before this migration.');
    }

    foreach ($columnsByTable as $table => $columns) {
        $tableExists = Database::fetchOne(
            "SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
        if (!$tableExists) {
            continue;
        }

        foreach ($columns as $column => $definition) {
            $columnExists = Database::fetchOne("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            if (!$columnExists) {
                Database::execute("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        }

        $indexName = "idx_{$table}_concern";
        $index = Database::fetchOne("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (!$index) {
            Database::execute("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (concern_id)");
        }
    }

    echo "Concern ownership columns added to business transaction tables. Existing records remain unassigned.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Concern transaction ownership migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
