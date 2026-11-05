<?php
/**
 * Add permission for removing individual available Production inventory QR pieces.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "INSERT INTO permissions (module, action, description, slug, created_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE description = VALUES(description), slug = VALUES(slug)",
        ['inventory', 'delete', 'Delete an available Production inventory QR piece', 'inventory.delete']
    );

    echo "SUCCESS: Production inventory QR delete permission is ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Database Error: " . $e->getMessage() . "\n");
    exit(1);
}
