<?php
/**
 * Allow production batches to be dispatched to a receiving concern and posted through its Production Inward.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    if (!Database::fetchOne("SHOW TABLES LIKE 'concerns'")) {
        throw new RuntimeException('Run create_staff_concerns.php before this migration.');
    }

    foreach (['production_batches', 'production_inwards', 'production_inward_items'] as $table) {
        if (!Database::fetchOne("SHOW TABLES LIKE '{$table}'")) {
            throw new RuntimeException("Run the production batch and inward table migrations before this migration ({$table} is missing).");
        }
        $columns = array_column(Database::fetchAll("SHOW COLUMNS FROM `{$table}`"), 'Field');
        if (!in_array('concern_id', $columns, true)) {
            throw new RuntimeException("Run add_concern_ownership_to_inventory.php before this migration ({$table}.concern_id is missing).");
        }
    }

    $batchColumns = array_column(Database::fetchAll('SHOW COLUMNS FROM production_batches'), 'Field');
    if (!in_array('destination_concern_id', $batchColumns, true)) {
        Database::execute(
            'ALTER TABLE production_batches ADD COLUMN destination_concern_id INT UNSIGNED NULL DEFAULT NULL AFTER destination_warehouse_id'
        );
    }

    $warehouseColumn = Database::fetchOne("SHOW COLUMNS FROM production_batches LIKE 'destination_warehouse_id'");
    if ($warehouseColumn && strtoupper((string)$warehouseColumn['Null']) !== 'YES') {
        Database::execute(
            'ALTER TABLE production_batches MODIFY destination_warehouse_id INT UNSIGNED NULL DEFAULT NULL'
        );
    }

    if (!Database::fetchOne("SHOW INDEX FROM production_batches WHERE Key_name = 'idx_production_batches_destination_concern'")) {
        Database::execute(
            'ALTER TABLE production_batches ADD INDEX idx_production_batches_destination_concern (destination_concern_id)'
        );
    }
    if (!Database::fetchOne(
        "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_batches'
           AND CONSTRAINT_NAME = 'fk_production_batches_destination_concern'"
    )) {
        Database::execute(
            'ALTER TABLE production_batches ADD CONSTRAINT fk_production_batches_destination_concern
             FOREIGN KEY (destination_concern_id) REFERENCES concerns (id) ON DELETE RESTRICT'
        );
    }

    $inwardSourceType = Database::fetchOne("SHOW COLUMNS FROM production_inwards LIKE 'source_type'");
    if (!$inwardSourceType) {
        throw new RuntimeException('Run create_production_inwards.php before this migration.');
    }
    if (strpos((string)$inwardSourceType['Type'], 'PRODUCTION_BATCH') === false) {
        Database::execute(
            "ALTER TABLE production_inwards
             MODIFY source_type ENUM('WAREHOUSE','SHOP','PRODUCTION_UNIT','PRODUCTION_BATCH')
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL"
        );
    }

    $inwardColumns = array_column(Database::fetchAll('SHOW COLUMNS FROM production_inwards'), 'Field');
    if (!in_array('destination_type', $inwardColumns, true)) {
        Database::execute(
            "ALTER TABLE production_inwards
             ADD COLUMN destination_type ENUM('PRODUCTION_UNIT','CONCERN') NOT NULL DEFAULT 'PRODUCTION_UNIT'
             AFTER source_id"
        );
    }
    if (!in_array('destination_concern_id', $inwardColumns, true)) {
        Database::execute(
            'ALTER TABLE production_inwards ADD COLUMN destination_concern_id INT UNSIGNED NULL DEFAULT NULL AFTER destination_unit_id'
        );
    }
    $destinationUnitColumn = Database::fetchOne("SHOW COLUMNS FROM production_inwards LIKE 'destination_unit_id'");
    if ($destinationUnitColumn && strtoupper((string)$destinationUnitColumn['Null']) !== 'YES') {
        Database::execute(
            'ALTER TABLE production_inwards MODIFY destination_unit_id INT UNSIGNED NULL DEFAULT NULL'
        );
    }
    if (!Database::fetchOne("SHOW INDEX FROM production_inwards WHERE Key_name = 'idx_production_inward_destination_concern'")) {
        Database::execute(
            'ALTER TABLE production_inwards ADD INDEX idx_production_inward_destination_concern (destination_concern_id, received_at)'
        );
    }
    if (!Database::fetchOne(
        "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_inwards'
           AND CONSTRAINT_NAME = 'fk_production_inward_destination_concern'"
    )) {
        Database::execute(
            'ALTER TABLE production_inwards ADD CONSTRAINT fk_production_inward_destination_concern
             FOREIGN KEY (destination_concern_id) REFERENCES concerns (id) ON DELETE RESTRICT'
        );
    }

    foreach (['stock_balances', 'stock_transactions'] as $table) {
        $locationColumn = Database::fetchOne("SHOW COLUMNS FROM `{$table}` LIKE 'location_type'");
        if ($locationColumn && strpos((string)$locationColumn['Type'], "'CONCERN'") === false) {
            Database::execute(
                "ALTER TABLE `{$table}`
                 MODIFY location_type ENUM('WAREHOUSE','SHOP','PRODUCTION_UNIT','CHANNEL','OFFICE','CONCERN')
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL"
            );
        }
    }

    $inwardItemColumns = array_column(Database::fetchAll('SHOW COLUMNS FROM production_inward_items'), 'Field');
    foreach ([
        'dispatched_qty' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER quantity',
        'damaged_qty' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER dispatched_qty',
        'missing_qty' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER damaged_qty',
        'excess_qty' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER missing_qty',
        'excess_codes' => 'LONGTEXT NULL AFTER excess_qty',
        'damaged_codes' => 'LONGTEXT NULL AFTER scanned_codes',
        'missing_codes' => 'LONGTEXT NULL AFTER damaged_codes',
    ] as $column => $definition) {
        if (!in_array($column, $inwardItemColumns, true)) {
            Database::execute("ALTER TABLE production_inward_items ADD COLUMN `{$column}` {$definition}");
        }
    }

    $batchItemColumns = array_column(Database::fetchAll('SHOW COLUMNS FROM production_batch_items'), 'Field');
    if (!in_array('missing_qr_codes', $batchItemColumns, true)) {
        Database::execute('ALTER TABLE production_batch_items ADD COLUMN missing_qr_codes LONGTEXT NULL AFTER damaged_qr_codes');
    }

    Database::execute(
        "INSERT INTO permissions (module, action, description, slug, created_at)
         VALUES ('production_inward', 'receive', 'Receive products into concern or production-unit inventory',
                 'production_inward.receive', NOW())
         ON DUPLICATE KEY UPDATE
             description = VALUES(description),
             slug = VALUES(slug)"
    );

    echo "Production concern batch receipt schema is ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Production concern batch receipt migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
