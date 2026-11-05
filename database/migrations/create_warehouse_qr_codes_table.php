<?php
/**
 * Persist warehouse QR labels and enforce uniqueness across inward operations.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::getInstance()->exec("
        CREATE TABLE IF NOT EXISTS warehouse_qr_codes (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            qr_code VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
            variant_id INT UNSIGNED DEFAULT NULL,
            warehouse_id INT UNSIGNED DEFAULT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            inwarded_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_warehouse_qr_code (qr_code),
            KEY idx_warehouse_qr_variant (variant_id),
            KEY idx_warehouse_qr_warehouse (warehouse_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $existingColumns = array_column(Database::fetchAll("SHOW COLUMNS FROM `warehouse_qr_codes`"), 'Field');
    if (!in_array('inwarded_at', $existingColumns, true)) {
        Database::getInstance()->exec("ALTER TABLE `warehouse_qr_codes` ADD COLUMN `inwarded_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`");
    }

    echo "SUCCESS: Warehouse QR code registry is ready.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Database Error: " . $e->getMessage() . "\n");
    exit(1);
}
