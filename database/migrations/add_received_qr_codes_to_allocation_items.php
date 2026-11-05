<?php
/**
 * Migration: Persist the exact QR tags received into shop inventory.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();
    $columns = array_column(Database::fetchAll("DESCRIBE `allocation_items`"), 'Field');
    if (!in_array('received_qr_codes', $columns, true)) {
        $db->exec("ALTER TABLE `allocation_items` ADD COLUMN `received_qr_codes` LONGTEXT NULL AFTER `selected_qr_codes`");
    }
    echo "Allocation received QR tag migration completed successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Allocation received QR tag migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
