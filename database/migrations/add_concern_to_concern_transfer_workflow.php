<?php
/**
 * Add source/destination concern ownership and receiving details to stock transfers.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    foreach (['concerns', 'warehouse_transfers', 'transfer_items'] as $table) {
        $tableExists = Database::fetchOne(
            "SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
        if (!$tableExists) {
            throw new RuntimeException("Required table '{$table}' does not exist.");
        }
    }

    $headerColumns = [
        'concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'source_concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'destination_concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'dispatched_by' => 'INT UNSIGNED NULL DEFAULT NULL',
        'received_by' => 'INT UNSIGNED NULL DEFAULT NULL',
        'dispatched_at' => 'DATETIME NULL DEFAULT NULL',
        'received_at' => 'DATETIME NULL DEFAULT NULL',
    ];
    foreach ($headerColumns as $column => $definition) {
        if (!Database::fetchOne("SHOW COLUMNS FROM warehouse_transfers LIKE '{$column}'")) {
            Database::execute("ALTER TABLE warehouse_transfers ADD COLUMN `{$column}` {$definition}");
        }
    }

    $itemColumns = [
        'concern_id' => 'INT UNSIGNED NULL DEFAULT NULL',
        'discrepancy_qty' => 'INT UNSIGNED NOT NULL DEFAULT 0',
    ];
    foreach ($itemColumns as $column => $definition) {
        if (!Database::fetchOne("SHOW COLUMNS FROM transfer_items LIKE '{$column}'")) {
            Database::execute("ALTER TABLE transfer_items ADD COLUMN `{$column}` {$definition}");
        }
    }

    foreach ([
        'idx_warehouse_transfers_source_concern' => 'source_concern_id',
        'idx_warehouse_transfers_destination_concern' => 'destination_concern_id',
    ] as $indexName => $column) {
        if (!Database::fetchOne("SHOW INDEX FROM warehouse_transfers WHERE Key_name = ?", [$indexName])) {
            Database::execute("ALTER TABLE warehouse_transfers ADD INDEX `{$indexName}` (`{$column}`)");
        }
    }

    foreach ([
        'fk_warehouse_transfers_source_concern' => 'source_concern_id',
        'fk_warehouse_transfers_destination_concern' => 'destination_concern_id',
    ] as $constraintName => $column) {
        $constraint = Database::fetchOne(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'warehouse_transfers'
               AND CONSTRAINT_NAME = ?",
            [$constraintName]
        );
        if (!$constraint) {
            Database::execute(
                "ALTER TABLE warehouse_transfers
                 ADD CONSTRAINT `{$constraintName}`
                 FOREIGN KEY (`{$column}`) REFERENCES concerns (id) ON DELETE RESTRICT"
            );
        }
    }

    echo "Concern-to-concern stock transfer ownership columns are ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Concern-to-concern transfer migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
