<?php
/**
 * Migration: Track damage and disposition for QC-passed production inventory.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "CREATE TABLE IF NOT EXISTS `production_inventory_damages` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `damage_number` varchar(50) NOT NULL,
            `qc_batch_id` int unsigned NOT NULL,
            `qc_item_id` int unsigned NOT NULL,
            `program_id` int unsigned NOT NULL,
            `production_unit_id` int unsigned DEFAULT NULL,
            `tag_code` varchar(100) NOT NULL,
            `source_stage` enum('QC','LABELING','PACKING','STORAGE_HANDLING','OTHER') NOT NULL DEFAULT 'OTHER',
            `damage_reason` varchar(255) NOT NULL,
            `defect_notes` text DEFAULT NULL,
            `photo_url` varchar(500) DEFAULT NULL,
            `unit_value` decimal(10,2) NOT NULL DEFAULT 0.00,
            `status` enum('LOGGED','INSPECTED','REWORK','REPAIRED','RESTOCKED','SALVAGED','WRITTEN_OFF') NOT NULL DEFAULT 'LOGGED',
            `reported_by` int unsigned DEFAULT NULL,
            `resolved_by` int unsigned DEFAULT NULL,
            `resolved_at` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_production_inventory_damage_number` (`damage_number`),
            UNIQUE KEY `uniq_production_inventory_damage_tag` (`tag_code`),
            KEY `idx_production_inventory_damage_batch` (`qc_batch_id`),
            KEY `idx_production_inventory_damage_item` (`qc_item_id`),
            KEY `idx_production_inventory_damage_program` (`program_id`),
            KEY `idx_production_inventory_damage_unit_status` (`production_unit_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    echo "Production inventory damage ledger is ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Production inventory damage migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}