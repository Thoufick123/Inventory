<?php
/**
 * Migration: Ensure Production Outward has full carrier and receipt metadata.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $existing = array_fill_keys(
        array_column(Database::fetchAll('SHOW COLUMNS FROM `production_outwards`'), 'Field'),
        true
    );
    $required = [
        'driver_contact' => "VARCHAR(20) NULL AFTER `driver_name`",
        'dispatched_by' => 'INT UNSIGNED DEFAULT NULL',
        'received_by' => 'INT UNSIGNED DEFAULT NULL',
        'received_date' => 'DATETIME DEFAULT NULL',
    ];

    foreach ($required as $column => $definition) {
        if (!isset($existing[$column])) {
            Database::execute("ALTER TABLE `production_outwards` ADD COLUMN `{$column}` {$definition}");
        }
    }

    echo "Production outward transport and receipt fields are ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Production outward receipt migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}