<?php
/**
 * Support production-unit attribution for damages recorded during inward GRN.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();
    $columns = array_column(Database::fetchAll('SHOW COLUMNS FROM `warehouse_damages`'), 'Field');
    if (!in_array('production_unit_id', $columns, true)) {
        $db->exec('ALTER TABLE `warehouse_damages` ADD COLUMN `production_unit_id` INT UNSIGNED DEFAULT NULL AFTER `product_id`');
        echo "Added `warehouse_damages`.`production_unit_id`.\n";
    } else {
        echo "Verified `warehouse_damages`.`production_unit_id`.\n";
    }

    $indexes = array_column(Database::fetchAll('SHOW INDEX FROM `warehouse_damages`'), 'Key_name');
    if (!in_array('idx_warehouse_damages_production_unit', $indexes, true)) {
        $db->exec('ALTER TABLE `warehouse_damages` ADD INDEX `idx_warehouse_damages_production_unit` (`production_unit_id`)');
        echo "Added production-unit index.\n";
    } else {
        echo "Verified production-unit index.\n";
    }

    $db->exec("
        ALTER TABLE `warehouse_damages`
        MODIFY COLUMN `source_type`
        ENUM('INWARD_TRANSIT','PRODUCTION_INWARD','STORAGE_HANDLING','CUSTOMER_RETURN','EXPIRED_SPOILED','OTHER')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'STORAGE_HANDLING'
    ");
    echo "Updated `warehouse_damages`.`source_type` for production inward records.\n";
    echo "SUCCESS: Warehouse damage inward schema is ready.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Database Error: " . $e->getMessage() . "\n");
    exit(1);
}
