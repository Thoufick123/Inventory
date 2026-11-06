<?php
/**
 * Add per-item SGST and CGST values to Production Purchases.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $columns = [
        'tax_input_type' => "varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PERCENT'",
        'sgst_value' => "decimal(12,3) NOT NULL DEFAULT '0.000'",
        'cgst_value' => "decimal(12,3) NOT NULL DEFAULT '0.000'",
        'sgst_amount' => "decimal(12,2) NOT NULL DEFAULT '0.00'",
        'cgst_amount' => "decimal(12,2) NOT NULL DEFAULT '0.00'",
    ];

    foreach ($columns as $column => $definition) {
        $existing = Database::fetchOne(
            "SELECT COUNT(*) AS column_count
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            ['production_purchase_items', $column]
        );
        if ((int)($existing['column_count'] ?? 0) === 0) {
            Database::execute("ALTER TABLE production_purchase_items ADD COLUMN {$column} {$definition}");
        }
    }

    echo "Production Purchase SGST/CGST fields are ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Production Purchase tax migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
