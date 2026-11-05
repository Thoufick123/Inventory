<?php
/**
 * Persist deleted Production QR label codes so they are not generated again.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "CREATE TABLE IF NOT EXISTS `production_deleted_label_codes` (
            `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            `created_by` int unsigned DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Production deleted-label tracking table is ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Production deleted-label migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
