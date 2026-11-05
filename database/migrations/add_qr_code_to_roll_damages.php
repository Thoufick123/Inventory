<?php
/**
 * Migration: Link production damage reports to the exact scanned fabric label.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $column = Database::fetchOne(
        "SELECT COUNT(*) AS column_count
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
        ['roll_damages', 'qr_code']
    );
    if ((int)($column['column_count'] ?? 0) === 0) {
        Database::execute(
            "ALTER TABLE `roll_damages`
             ADD COLUMN `qr_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
             AFTER `seller_roll_id`"
        );
    }

    $index = Database::fetchOne(
        "SELECT COUNT(*) AS index_count
         FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
        ['roll_damages', 'uniq_roll_damages_qr_code']
    );
    if ((int)($index['index_count'] ?? 0) === 0) {
        Database::execute(
            "ALTER TABLE `roll_damages`
             ADD UNIQUE KEY `uniq_roll_damages_qr_code` (`qr_code`)"
        );
    }

    echo "Production damage QR tracking is ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Production damage QR migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
