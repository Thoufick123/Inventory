<?php
/**
 * Migration: Store reusable Production roll damage reasons.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "CREATE TABLE IF NOT EXISTS `roll_damage_reasons` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_roll_damage_reasons_reason` (`reason`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "INSERT IGNORE INTO roll_damage_reasons (reason)
         SELECT DISTINCT TRIM(damage_reason)
         FROM roll_damages
         WHERE damage_reason IS NOT NULL AND TRIM(damage_reason) <> ''"
    );

    echo "Production roll damage reasons are ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Production roll damage reason migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
