<?php
/**
 * Add the permission used to edit warehouse stock prices.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "INSERT INTO permissions (module, action, description, slug, created_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE description = VALUES(description), slug = VALUES(slug)",
        ['stock', 'edit', 'Edit warehouse stock prices (MRP and selling price)', 'stock.edit']
    );

    echo "SUCCESS: Warehouse stock price edit permission is ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Database Error: " . $e->getMessage() . "\n");
    exit(1);
}
