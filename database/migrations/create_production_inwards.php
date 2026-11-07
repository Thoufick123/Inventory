<?php
/**
 * Create production-unit inward receipts for inter-concern product transfers.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_inwards (
            id int unsigned NOT NULL AUTO_INCREMENT,
            grn_number varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            source_type enum('WAREHOUSE','SHOP','PRODUCTION_UNIT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            source_id int unsigned NOT NULL,
            destination_unit_id int unsigned NOT NULL,
            source_batch_number varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            status enum('RECEIVED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RECEIVED',
            notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            received_by int unsigned DEFAULT NULL,
            received_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_inward_grn (grn_number),
            UNIQUE KEY uniq_production_inward_source_batch (source_type, source_id, source_batch_number),
            KEY idx_production_inward_destination (destination_unit_id, received_at),
            KEY idx_production_inward_source (source_type, source_id, received_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_inward_items (
            id int unsigned NOT NULL AUTO_INCREMENT,
            inward_id int unsigned NOT NULL,
            variant_id int unsigned NOT NULL,
            quantity int unsigned NOT NULL,
            scanned_codes longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_inward_variant (inward_id, variant_id),
            KEY idx_production_inward_item_variant (variant_id),
            CONSTRAINT production_inward_items_ibfk_1 FOREIGN KEY (inward_id) REFERENCES production_inwards (id) ON DELETE CASCADE,
            CONSTRAINT production_inward_items_ibfk_2 FOREIGN KEY (variant_id) REFERENCES product_variants (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    echo "Production Inward tables are ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'Production Inward migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}