<?php
/**
 * Migration: Allow all statuses used by cutting batch workflows.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "ALTER TABLE `cutting_batches`
         MODIFY COLUMN `status` ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'HOLD', 'CANCELLED')
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING'"
    );

    echo "Cutting batch statuses updated successfully.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Cutting batch status migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
