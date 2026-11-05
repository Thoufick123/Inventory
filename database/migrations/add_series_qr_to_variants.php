<?php
/**
 * Migration: Add series range QR code columns to product_variants
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();
    $cols = array_column(Database::fetchAll("DESCRIBE product_variants"), 'Field');

    if (!in_array('qr_type', $cols)) {
        $db->exec("ALTER TABLE product_variants ADD COLUMN qr_type ENUM('INDIVIDUAL', 'SERIES_BATCH') DEFAULT 'INDIVIDUAL' AFTER qr_code");
        echo "✓ Added qr_type to product_variants\n";
    }

    if (!in_array('series_start_code', $cols)) {
        $db->exec("ALTER TABLE product_variants ADD COLUMN series_start_code VARCHAR(100) NULL AFTER qr_type");
        echo "✓ Added series_start_code to product_variants\n";
    }

    if (!in_array('series_end_code', $cols)) {
        $db->exec("ALTER TABLE product_variants ADD COLUMN series_end_code VARCHAR(100) NULL AFTER series_start_code");
        echo "✓ Added series_end_code to product_variants\n";
    }

    if (!in_array('pack_quantity', $cols)) {
        $db->exec("ALTER TABLE product_variants ADD COLUMN pack_quantity INT DEFAULT 1 AFTER series_end_code");
        echo "✓ Added pack_quantity to product_variants\n";
    }

    echo "Product variants schema updated successfully!\n";
} catch (\Throwable $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
    exit(1);
}
