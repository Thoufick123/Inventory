<?php
// database/migrations/create_production_warehouse_reworks_table.php
define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `production_warehouse_reworks` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `rework_number` varchar(50) NOT NULL,
      `warehouse_id` int unsigned NOT NULL,
      `production_outward_id` int unsigned DEFAULT NULL,
      `production_batch_id` int unsigned DEFAULT NULL,
      `warehouse_damage_id` int unsigned DEFAULT NULL,
      `variant_id` int unsigned NOT NULL,
      `product_id` int unsigned DEFAULT NULL,
      `production_unit_id` int unsigned DEFAULT NULL,
      `quantity` int NOT NULL DEFAULT 1,
      `repaired_qty` int NOT NULL DEFAULT 0,
      `scrapped_qty` int NOT NULL DEFAULT 0,
      `defect_type` varchar(100) NOT NULL DEFAULT 'STITCHING_ERROR',
      `damage_reason` varchar(255) NOT NULL,
      `defect_notes` text DEFAULT NULL,
      `photo_url` varchar(500) DEFAULT NULL,
      `challan_number` varchar(100) DEFAULT NULL,
      `received_date` date NOT NULL,
      `received_by` int unsigned DEFAULT NULL,
      `assigned_to` varchar(150) DEFAULT NULL,
      `status` enum('RECEIVED','IN_REWORK','REPAIRED','SCRAPPED','RE_DISPATCHED') NOT NULL DEFAULT 'RECEIVED',
      `repair_notes` text DEFAULT NULL,
      `repaired_at` datetime DEFAULT NULL,
      `redispatched_date` datetime DEFAULT NULL,
      `redispatched_vehicle` varchar(100) DEFAULT NULL,
      `redispatched_notes` text DEFAULT NULL,
      `created_by` int unsigned DEFAULT NULL,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_pwr_rework_number` (`rework_number`),
      KEY `idx_pwr_warehouse` (`warehouse_id`),
      KEY `idx_pwr_outward` (`production_outward_id`),
      KEY `idx_pwr_batch` (`production_batch_id`),
      KEY `idx_pwr_variant` (`variant_id`),
      KEY `idx_pwr_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    Database::execute($sql);
    echo "SUCCESS: production_warehouse_reworks table created or verified.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
