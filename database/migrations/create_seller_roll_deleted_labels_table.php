<?php
/**
 * Migration: Persist individual deleted fabric roll label sequences.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "CREATE TABLE IF NOT EXISTS `seller_roll_deleted_labels` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `seller_roll_id` int unsigned NOT NULL,
            `sequence` int unsigned NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_seller_roll_deleted_sequence` (`seller_roll_id`, `sequence`),
            CONSTRAINT `seller_roll_deleted_labels_ibfk_1`
                FOREIGN KEY (`seller_roll_id`) REFERENCES `seller_rolls` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Seller roll deleted-label tracking table is ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Seller roll deleted-label migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
