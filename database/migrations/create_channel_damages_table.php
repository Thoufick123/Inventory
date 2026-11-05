<?php
// database/migrations/create_channel_damages_table.php
define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `channel_damages` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `damage_number` varchar(50) NOT NULL,
      `channel_id` int unsigned NOT NULL,
      `variant_id` int unsigned NOT NULL,
      `product_id` int unsigned DEFAULT NULL,
      `tag_code` text DEFAULT NULL,
      `source_type` enum('INWARD_TRANSIT','STORAGE_HANDLING','CUSTOMER_RETURN','EXPIRED_SPOILED','OTHER') DEFAULT 'INWARD_TRANSIT',
      `reference_id` int unsigned DEFAULT NULL,
      `allocation_id` int unsigned DEFAULT NULL,
      `defect_type` enum('FABRIC_DEFECT','COLOR_FADE','STITCHING_ERROR','PACKAGING_DAMAGE','WATER_STAIN','TRANSIT_DAMAGE','EXPIRED','OTHER') DEFAULT 'TRANSIT_DAMAGE',
      `quantity` int NOT NULL DEFAULT '1',
      `cost_per_unit` decimal(10,2) DEFAULT '0.00',
      `estimated_cost` decimal(10,2) DEFAULT '0.00',
      `total_loss` decimal(10,2) DEFAULT '0.00',
      `damage_reason` varchar(255) NOT NULL,
      `defect_notes` text,
      `photo_url` varchar(500) DEFAULT NULL,
      `status` enum('LOGGED','INSPECTED','OUTWARD_BATCHED','DISPATCHED','RECEIVED_AT_WH','SALVAGED','WRITTEN_OFF','REWORK') DEFAULT 'LOGGED',
      `outward_batch_id` int unsigned DEFAULT NULL,
      `disposition_notes` text,
      `reported_by` int unsigned DEFAULT NULL,
      `inspected_by` int unsigned DEFAULT NULL,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_channel_damage_number` (`damage_number`),
      KEY `idx_channel_damages_channel` (`channel_id`),
      KEY `idx_channel_damages_variant` (`variant_id`),
      KEY `idx_channel_damages_status` (`status`),
      KEY `idx_channel_damages_alloc` (`allocation_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    Database::execute($sql);
    echo "SUCCESS: channel_damages table created or verified.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
