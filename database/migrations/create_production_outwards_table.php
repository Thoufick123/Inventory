<?php
/**
 * Create the production outward-dispatch table when it is missing.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::getInstance()->exec("
        CREATE TABLE IF NOT EXISTS `production_outwards` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `production_batch_id` int unsigned NOT NULL,
            `production_unit_id` int unsigned DEFAULT NULL,
            `warehouse_id` int unsigned DEFAULT NULL,
            `quantity` int NOT NULL DEFAULT 0,
            `total_boxes` int DEFAULT 0,
            `total_quantity` int DEFAULT 0,
            `outward_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            `dispatch_date` datetime DEFAULT NULL,
            `received_date` datetime DEFAULT NULL,
            `status` enum('DISPATCHED','RECEIVED','DISCREPANCY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'DISPATCHED',
            `vehicle_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `driver_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `transporter_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `lr_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `dispatched_by` int unsigned DEFAULT NULL,
            `received_by` int unsigned DEFAULT NULL,
            `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            `created_by` int unsigned DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `driver_contact` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `outward_number` (`outward_number`),
            KEY `production_batch_id` (`production_batch_id`),
            KEY `created_by` (`created_by`),
            CONSTRAINT `production_outwards_ibfk_1` FOREIGN KEY (`production_batch_id`) REFERENCES `production_batches` (`id`),
            CONSTRAINT `production_outwards_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "SUCCESS: production_outwards table is ready.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Database Error: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
