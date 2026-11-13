-- ============================================================================
-- GRM Inventory - Hostinger Live Database Upgrade Script (Idempotent / Safe)
-- Purpose: Brings an existing live Hostinger MySQL / MariaDB database up to
--          date with Concern Isolation + Phases 1-5 WITHOUT dropping live data.
-- Usage:   Hostinger hPanel -> Databases -> phpMyAdmin -> select DB -> SQL tab
--          -> paste/import this entire file -> Go.
-- ============================================================================

SET @db = DATABASE();
SET @old_fk_checks = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. Core Concern Tables & Missing Production Feature Tables
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `concerns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_concerns_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `concern_modules` (
  `concern_id` int(10) unsigned NOT NULL,
  `module_key` varchar(100) NOT NULL,
  PRIMARY KEY (`concern_id`, `module_key`),
  KEY `idx_concern_modules_key` (`module_key`),
  CONSTRAINT `concern_modules_ibfk_1` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_concerns` (
  `user_id` int(10) unsigned NOT NULL,
  `concern_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`, `concern_id`),
  KEY `idx_user_concerns_concern` (`concern_id`),
  CONSTRAINT `user_concerns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_concerns_ibfk_2` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_outwards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `production_batch_id` int(10) unsigned NOT NULL,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `warehouse_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `total_boxes` int(11) DEFAULT 0,
  `total_quantity` int(11) DEFAULT 0,
  `outward_number` varchar(50) NOT NULL,
  `dispatch_date` datetime DEFAULT NULL,
  `received_date` datetime DEFAULT NULL,
  `status` enum('DISPATCHED','RECEIVED','DISCREPANCY') DEFAULT 'DISPATCHED',
  `vehicle_number` varchar(50) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_contact` varchar(20) DEFAULT NULL,
  `transporter_name` varchar(100) DEFAULT NULL,
  `lr_number` varchar(100) DEFAULT NULL,
  `dispatched_by` int(10) unsigned DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `concern_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `outward_number` (`outward_number`),
  KEY `production_batch_id` (`production_batch_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_production_outwards_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_inventory_damages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `damage_number` varchar(50) NOT NULL,
  `qc_batch_id` int(10) unsigned DEFAULT NULL,
  `qc_item_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `stock_location_type` enum('CONCERN','PRODUCTION_UNIT') DEFAULT NULL,
  `stock_location_id` int(10) unsigned DEFAULT NULL,
  `tag_code` varchar(100) NOT NULL,
  `source_stage` enum('QC','LABELING','PACKING','INWARD','STORAGE_HANDLING','OTHER') NOT NULL DEFAULT 'OTHER',
  `damage_reason` varchar(255) NOT NULL,
  `defect_notes` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `unit_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('LOGGED','INSPECTED','REWORK','REPAIRED','RESTOCKED','SALVAGED','WRITTEN_OFF') NOT NULL DEFAULT 'LOGGED',
  `reported_by` int(10) unsigned DEFAULT NULL,
  `resolved_by` int(10) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `concern_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_prod_inv_dmg_concern_number` (`concern_id`,`damage_number`),
  UNIQUE KEY `uniq_prod_inv_dmg_concern_tag` (`concern_id`,`tag_code`),
  KEY `idx_production_inventory_damage_batch` (`qc_batch_id`),
  KEY `idx_production_inventory_damage_item` (`qc_item_id`),
  KEY `idx_production_inventory_damage_program` (`program_id`),
  KEY `idx_production_inventory_damage_unit_status` (`production_unit_id`,`status`),
  KEY `idx_prod_inv_damages_concern` (`concern_id`),
  KEY `idx_prod_inv_damages_variant` (`variant_id`),
  KEY `idx_prod_inv_damages_location` (`stock_location_type`,`stock_location_id`),
  KEY `idx_prod_inv_damages_number` (`damage_number`),
  KEY `idx_prod_inv_damages_tag` (`tag_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_cash_registers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `register_number` varchar(50) NOT NULL,
  `cashier_id` int(10) unsigned NOT NULL,
  `cashier_name` varchar(150) DEFAULT NULL,
  `closed_by` int(10) unsigned DEFAULT NULL,
  `opening_time` datetime NOT NULL,
  `closing_time` datetime DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `opening_expected_float` decimal(12,2) NOT NULL DEFAULT 0.00,
  `opening_difference` decimal(12,2) NOT NULL DEFAULT 0.00,
  `opening_denominations` longtext DEFAULT NULL,
  `opening_notes` text DEFAULT NULL,
  `opening_discrepancy_reason` text DEFAULT NULL,
  `cash_sales_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `upi_sales_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `card_sales_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_in_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_out_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_refund_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bank_deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expected_cash` decimal(12,2) NOT NULL DEFAULT 0.00,
  `closing_cash_counted` decimal(12,2) DEFAULT NULL,
  `closing_denominations` longtext DEFAULT NULL,
  `cash_difference` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discrepancy_reason` text DEFAULT NULL,
  `status` enum('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `concern_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_cash_register_number` (`register_number`),
  KEY `idx_production_cash_unit_status` (`production_unit_id`,`status`),
  KEY `idx_production_cash_opening_time` (`opening_time`),
  KEY `idx_production_cash_registers_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_cash_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `register_id` int(10) unsigned DEFAULT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `transaction_type` enum('OPENING_FLOAT','SALE_CASH','SALE_UPI','SALE_CARD','SALE_OTHER','CASH_IN','EXPENSE','REFUND_CASH','REFUND_UPI','BANK_DEPOSIT','CLOSING_ADJUSTMENT') NOT NULL,
  `flow_direction` enum('IN','OUT') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_mode` enum('CASH','UPI','BANK_TRANSFER','CARD','OTHER') NOT NULL DEFAULT 'CASH',
  `category` varchar(100) NOT NULL DEFAULT 'GENERAL',
  `reference_id` varchar(100) DEFAULT NULL,
  `source_table` varchar(50) DEFAULT NULL,
  `source_id` int(10) unsigned DEFAULT NULL,
  `handled_by` int(10) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `concern_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_cash_transaction_number` (`transaction_number`),
  KEY `idx_production_cash_tx_unit_flow` (`production_unit_id`,`flow_direction`),
  KEY `idx_production_cash_tx_register` (`register_id`),
  KEY `idx_production_cash_tx_type` (`transaction_type`),
  KEY `idx_production_cash_tx_created` (`created_at`),
  KEY `idx_production_cash_transactions_concern` (`concern_id`),
  KEY `idx_production_cash_tx_source` (`source_table`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_cash_expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `register_id` int(10) unsigned DEFAULT NULL,
  `voucher_number` varchar(50) NOT NULL,
  `expense_category` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_to` varchar(200) NOT NULL,
  `payment_mode` enum('CASH','UPI','BANK_TRANSFER','CARD','OTHER') NOT NULL DEFAULT 'CASH',
  `bill_number` varchar(100) DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `concern_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_cash_voucher` (`voucher_number`),
  KEY `idx_production_cash_exp_unit_category` (`production_unit_id`,`expense_category`),
  KEY `idx_production_cash_exp_created` (`created_at`),
  KEY `idx_production_cash_expenses_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_cash_deposits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `register_id` int(10) unsigned DEFAULT NULL,
  `deposit_number` varchar(50) NOT NULL,
  `bank_name` varchar(150) NOT NULL,
  `account_number` varchar(80) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `slip_number` varchar(100) DEFAULT NULL,
  `deposit_date` date NOT NULL,
  `deposited_by` int(10) unsigned NOT NULL,
  `verified_by` int(10) unsigned DEFAULT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'VERIFIED',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `concern_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_cash_deposit_number` (`deposit_number`),
  KEY `idx_production_cash_dep_unit_status` (`production_unit_id`,`status`),
  KEY `idx_production_cash_dep_date` (`deposit_date`),
  KEY `idx_production_cash_deposits_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_return_inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` int(10) unsigned NOT NULL,
  `return_item_id` int(10) unsigned NOT NULL,
  `production_unit_id` int(10) unsigned DEFAULT NULL,
  `qc_batch_id` int(10) unsigned NOT NULL,
  `qc_item_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `original_tag_code` varchar(100) NOT NULL,
  `new_tag_code` varchar(100) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `size_name` varchar(100) DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `unit_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('AVAILABLE','BILLED','DAMAGED','RETIRED') NOT NULL DEFAULT 'AVAILABLE',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_return_inventory_item` (`return_item_id`),
  UNIQUE KEY `uniq_production_return_inventory_tag` (`new_tag_code`),
  KEY `idx_production_return_inventory_batch_status` (`qc_batch_id`,`status`),
  KEY `idx_production_return_inventory_item` (`qc_item_id`),
  KEY `idx_production_return_inventory_unit` (`production_unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Production Outwards Receipt Columns (if table pre-existed without them)
-- ----------------------------------------------------------------------------
SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'production_outwards')
  AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'production_outwards' AND COLUMN_NAME = 'driver_contact'),
  'ALTER TABLE `production_outwards` ADD COLUMN `driver_contact` VARCHAR(20) NULL DEFAULT NULL AFTER `driver_name`',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'production_outwards')
  AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'production_outwards' AND COLUMN_NAME = 'dispatched_by'),
  'ALTER TABLE `production_outwards` ADD COLUMN `dispatched_by` INT UNSIGNED NULL DEFAULT NULL',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'production_outwards')
  AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'production_outwards' AND COLUMN_NAME = 'received_by'),
  'ALTER TABLE `production_outwards` ADD COLUMN `received_by` INT UNSIGNED NULL DEFAULT NULL',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'production_outwards')
  AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'production_outwards' AND COLUMN_NAME = 'received_date'),
  'ALTER TABLE `production_outwards` ADD COLUMN `received_date` DATETIME NULL DEFAULT NULL',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 3. Concern Ownership on Inventory & Facility Tables + Stock Balances Key
-- ----------------------------------------------------------------------------
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_units') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_units' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_units` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_units') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_units' AND INDEX_NAME='idx_production_units_concern'), 'ALTER TABLE `production_units` ADD INDEX `idx_production_units_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouses') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouses' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `warehouses` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouses') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouses' AND INDEX_NAME='idx_warehouses_concern'), 'ALTER TABLE `warehouses` ADD INDEX `idx_warehouses_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='shops') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='shops' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `shops` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='shops') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='shops' AND INDEX_NAME='idx_shops_concern'), 'ALTER TABLE `shops` ADD INDEX `idx_shops_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales_channels') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales_channels' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `sales_channels` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales_channels') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales_channels' AND INDEX_NAME='idx_sales_channels_concern'), 'ALTER TABLE `sales_channels` ADD INDEX `idx_sales_channels_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_balances') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_balances' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `stock_balances` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_balances') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_balances' AND INDEX_NAME='idx_stock_balances_concern'), 'ALTER TABLE `stock_balances` ADD INDEX `idx_stock_balances_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_transactions') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_transactions' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `stock_transactions` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_transactions') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_transactions' AND INDEX_NAME='idx_stock_transactions_concern'), 'ALTER TABLE `stock_transactions` ADD INDEX `idx_stock_transactions_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_adjustments') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_adjustments' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `stock_adjustments` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_adjustments') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_adjustments' AND INDEX_NAME='idx_stock_adjustments_concern'), 'ALTER TABLE `stock_adjustments` ADD INDEX `idx_stock_adjustments_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_batches` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND INDEX_NAME='idx_production_batches_concern'), 'ALTER TABLE `production_batches` ADD INDEX `idx_production_batches_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_batch_items` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items' AND INDEX_NAME='idx_production_batch_items_concern'), 'ALTER TABLE `production_batch_items` ADD INDEX `idx_production_batch_items_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_outwards') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_outwards' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_outwards` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_outwards') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_outwards' AND INDEX_NAME='idx_production_outwards_concern'), 'ALTER TABLE `production_outwards` ADD INDEX `idx_production_outwards_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_inwards` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards' AND INDEX_NAME='idx_production_inwards_concern'), 'ALTER TABLE `production_inwards` ADD INDEX `idx_production_inwards_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_inward_items` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items' AND INDEX_NAME='idx_production_inward_items_concern'), 'ALTER TABLE `production_inward_items` ADD INDEX `idx_production_inward_items_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `warehouse_transfers` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND INDEX_NAME='idx_warehouse_transfers_concern'), 'ALTER TABLE `warehouse_transfers` ADD INDEX `idx_warehouse_transfers_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='transfer_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='transfer_items' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `transfer_items` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='transfer_items') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='transfer_items' AND INDEX_NAME='idx_transfer_items_concern'), 'ALTER TABLE `transfer_items` ADD INDEX `idx_transfer_items_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_allocations') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_allocations' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `warehouse_allocations` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_allocations') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_allocations' AND INDEX_NAME='idx_warehouse_allocations_concern'), 'ALTER TABLE `warehouse_allocations` ADD INDEX `idx_warehouse_allocations_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='allocation_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='allocation_items' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `allocation_items` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='allocation_items') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='allocation_items' AND INDEX_NAME='idx_allocation_items_concern'), 'ALTER TABLE `allocation_items` ADD INDEX `idx_allocation_items_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='allocation_outwards') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='allocation_outwards' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `allocation_outwards` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='allocation_outwards') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='allocation_outwards' AND INDEX_NAME='idx_allocation_outwards_concern'), 'ALTER TABLE `allocation_outwards` ADD INDEX `idx_allocation_outwards_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Rebuild stock_balances unique key uk_stock_balance if missing concern_id
SET @uk_sb_cols = (
  SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_balances' AND INDEX_NAME = 'uk_stock_balance'
);
SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_balances')
  AND @uk_sb_cols IS NOT NULL
  AND @uk_sb_cols <> 'concern_id,variant_id,location_type,location_id,stock_state',
  'ALTER TABLE `stock_balances` DROP INDEX `uk_stock_balance`',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_balances')
  AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_balances' AND INDEX_NAME='uk_stock_balance'),
  'ALTER TABLE `stock_balances` ADD UNIQUE INDEX `uk_stock_balance` (`concern_id`,`variant_id`,`location_type`,`location_id`,`stock_state`)',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 4. Concern Ownership on Business Transaction & Cash Tables
-- ----------------------------------------------------------------------------
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='customers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='customers' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `customers` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='customers') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='customers' AND INDEX_NAME='idx_customers_concern'), 'ALTER TABLE `customers` ADD INDEX `idx_customers_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `sales` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales' AND COLUMN_NAME='sales_channel_id'), 'ALTER TABLE `sales` ADD COLUMN `sales_channel_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sales' AND INDEX_NAME='idx_sales_concern'), 'ALTER TABLE `sales` ADD INDEX `idx_sales_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `payments` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_concern'), 'ALTER TABLE `payments` ADD INDEX `idx_payments_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_orders') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_orders' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `purchase_orders` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_orders') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_orders' AND INDEX_NAME='idx_purchase_orders_concern'), 'ALTER TABLE `purchase_orders` ADD INDEX `idx_purchase_orders_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_inwards') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_inwards' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `purchase_inwards` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_inwards') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='purchase_inwards' AND INDEX_NAME='idx_purchase_inwards_concern'), 'ALTER TABLE `purchase_inwards` ADD INDEX `idx_purchase_inwards_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='returns') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='returns' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `returns` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='returns') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='returns' AND INDEX_NAME='idx_returns_concern'), 'ALTER TABLE `returns` ADD INDEX `idx_returns_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='exchanges') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='exchanges' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `exchanges` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='exchanges') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='exchanges' AND INDEX_NAME='idx_exchanges_concern'), 'ALTER TABLE `exchanges` ADD INDEX `idx_exchanges_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_returns') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_returns' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `warehouse_returns` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_returns') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_returns' AND INDEX_NAME='idx_warehouse_returns_concern'), 'ALTER TABLE `warehouse_returns` ADD INDEX `idx_warehouse_returns_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_bills` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills' AND COLUMN_NAME='production_unit_id'), 'ALTER TABLE `production_bills` ADD COLUMN `production_unit_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills' AND INDEX_NAME='idx_production_bills_concern'), 'ALTER TABLE `production_bills` ADD INDEX `idx_production_bills_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_returns') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_returns' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_bill_returns` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_returns') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_returns' AND INDEX_NAME='idx_production_bill_returns_concern'), 'ALTER TABLE `production_bill_returns` ADD INDEX `idx_production_bill_returns_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_purchases` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases' AND COLUMN_NAME='production_unit_id'), 'ALTER TABLE `production_purchases` ADD COLUMN `production_unit_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases' AND INDEX_NAME='idx_production_purchases_concern'), 'ALTER TABLE `production_purchases` ADD INDEX `idx_production_purchases_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_expenses') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_expenses' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_expenses` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_expenses') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_expenses' AND COLUMN_NAME='production_unit_id'), 'ALTER TABLE `production_expenses` ADD COLUMN `production_unit_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_expenses') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_expenses' AND INDEX_NAME='idx_production_expenses_concern'), 'ALTER TABLE `production_expenses` ADD INDEX `idx_production_expenses_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='wastage_sales') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='wastage_sales' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `wastage_sales` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='wastage_sales') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='wastage_sales' AND COLUMN_NAME='production_unit_id'), 'ALTER TABLE `wastage_sales` ADD COLUMN `production_unit_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='wastage_sales') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='wastage_sales' AND INDEX_NAME='idx_wastage_sales_concern'), 'ALTER TABLE `wastage_sales` ADD INDEX `idx_wastage_sales_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stitching_wastage_sales') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stitching_wastage_sales' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `stitching_wastage_sales` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stitching_wastage_sales') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stitching_wastage_sales' AND COLUMN_NAME='production_unit_id'), 'ALTER TABLE `stitching_wastage_sales` ADD COLUMN `production_unit_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stitching_wastage_sales') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stitching_wastage_sales' AND INDEX_NAME='idx_stitching_wastage_sales_concern'), 'ALTER TABLE `stitching_wastage_sales` ADD INDEX `idx_stitching_wastage_sales_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='expenses') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='expenses' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `expenses` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='expenses') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='expenses' AND INDEX_NAME='idx_expenses_concern'), 'ALTER TABLE `expenses` ADD INDEX `idx_expenses_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_registers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_registers' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `cash_registers` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_registers') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_registers' AND INDEX_NAME='idx_cash_registers_concern'), 'ALTER TABLE `cash_registers` ADD INDEX `idx_cash_registers_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_transactions') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_transactions' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `cash_transactions` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_transactions') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_transactions' AND INDEX_NAME='idx_cash_transactions_concern'), 'ALTER TABLE `cash_transactions` ADD INDEX `idx_cash_transactions_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_expenses') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_expenses' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `cash_expenses` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_expenses') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cash_expenses' AND INDEX_NAME='idx_cash_expenses_concern'), 'ALTER TABLE `cash_expenses` ADD INDEX `idx_cash_expenses_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='bank_deposits') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='bank_deposits' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `bank_deposits` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='bank_deposits') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='bank_deposits' AND INDEX_NAME='idx_bank_deposits_concern'), 'ALTER TABLE `bank_deposits` ADD INDEX `idx_bank_deposits_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_cash_registers` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers' AND INDEX_NAME='idx_production_cash_registers_concern'), 'ALTER TABLE `production_cash_registers` ADD INDEX `idx_production_cash_registers_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_transactions') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_transactions' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_cash_transactions` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_transactions') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_transactions' AND INDEX_NAME='idx_production_cash_transactions_concern'), 'ALTER TABLE `production_cash_transactions` ADD INDEX `idx_production_cash_transactions_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_expenses') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_expenses' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_cash_expenses` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_expenses') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_expenses' AND INDEX_NAME='idx_production_cash_expenses_concern'), 'ALTER TABLE `production_cash_expenses` ADD INDEX `idx_production_cash_expenses_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_deposits') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_deposits' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_cash_deposits` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_deposits') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_deposits' AND INDEX_NAME='idx_production_cash_deposits_concern'), 'ALTER TABLE `production_cash_deposits` ADD INDEX `idx_production_cash_deposits_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 5. Concern-to-Concern Transfers
-- ----------------------------------------------------------------------------
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND COLUMN_NAME='source_concern_id'), 'ALTER TABLE `warehouse_transfers` ADD COLUMN `source_concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND COLUMN_NAME='destination_concern_id'), 'ALTER TABLE `warehouse_transfers` ADD COLUMN `destination_concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND COLUMN_NAME='dispatched_by'), 'ALTER TABLE `warehouse_transfers` ADD COLUMN `dispatched_by` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND COLUMN_NAME='received_by'), 'ALTER TABLE `warehouse_transfers` ADD COLUMN `received_by` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND COLUMN_NAME='dispatched_at'), 'ALTER TABLE `warehouse_transfers` ADD COLUMN `dispatched_at` DATETIME NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND COLUMN_NAME='received_at'), 'ALTER TABLE `warehouse_transfers` ADD COLUMN `received_at` DATETIME NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='transfer_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='transfer_items' AND COLUMN_NAME='discrepancy_qty'), 'ALTER TABLE `transfer_items` ADD COLUMN `discrepancy_qty` INT UNSIGNED NOT NULL DEFAULT 0', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND INDEX_NAME='idx_warehouse_transfers_source_concern'), 'ALTER TABLE `warehouse_transfers` ADD INDEX `idx_warehouse_transfers_source_concern` (`source_concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='warehouse_transfers' AND INDEX_NAME='idx_warehouse_transfers_destination_concern'), 'ALTER TABLE `warehouse_transfers` ADD INDEX `idx_warehouse_transfers_destination_concern` (`destination_concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 6. Concern Production Batch Dispatch & Inward Receipts (Dashboard line 135 fix!)
-- ----------------------------------------------------------------------------
SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches')
  AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND COLUMN_NAME='destination_concern_id'),
  'ALTER TABLE `production_batches` ADD COLUMN `destination_concern_id` INT UNSIGNED NULL DEFAULT NULL AFTER `destination_warehouse_id`',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND COLUMN_NAME='destination_warehouse_id' AND IS_NULLABLE='NO'),
  'ALTER TABLE `production_batches` MODIFY `destination_warehouse_id` INT UNSIGNED NULL DEFAULT NULL',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches')
  AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND INDEX_NAME='idx_production_batches_destination_concern'),
  'ALTER TABLE `production_batches` ADD INDEX `idx_production_batches_destination_concern` (`destination_concern_id`)',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches')
  AND EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='concerns')
  AND NOT EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db AND TABLE_NAME='production_batches' AND CONSTRAINT_NAME='fk_production_batches_destination_concern'),
  'ALTER TABLE `production_batches` ADD CONSTRAINT `fk_production_batches_destination_concern` FOREIGN KEY (`destination_concern_id`) REFERENCES `concerns` (`id`) ON DELETE RESTRICT',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards' AND COLUMN_NAME='source_type' AND COLUMN_TYPE NOT LIKE '%PRODUCTION_BATCH%'),
  'ALTER TABLE `production_inwards` MODIFY `source_type` ENUM(''WAREHOUSE'',''SHOP'',''PRODUCTION_UNIT'',''PRODUCTION_BATCH'') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards')
  AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards' AND COLUMN_NAME='destination_type'),
  'ALTER TABLE `production_inwards` ADD COLUMN `destination_type` ENUM(''PRODUCTION_UNIT'',''CONCERN'') NOT NULL DEFAULT ''PRODUCTION_UNIT'' AFTER `source_id`',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards')
  AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards' AND COLUMN_NAME='destination_concern_id'),
  'ALTER TABLE `production_inwards` ADD COLUMN `destination_concern_id` INT UNSIGNED NULL DEFAULT NULL AFTER `destination_unit_id`',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards' AND COLUMN_NAME='destination_unit_id' AND IS_NULLABLE='NO'),
  'ALTER TABLE `production_inwards` MODIFY `destination_unit_id` INT UNSIGNED NULL DEFAULT NULL',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards')
  AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards' AND INDEX_NAME='idx_production_inward_destination_concern'),
  'ALTER TABLE `production_inwards` ADD INDEX `idx_production_inward_destination_concern` (`destination_concern_id`, `received_at`)',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inwards')
  AND EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='concerns')
  AND NOT EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db AND TABLE_NAME='production_inwards' AND CONSTRAINT_NAME='fk_production_inward_destination_concern'),
  'ALTER TABLE `production_inwards` ADD CONSTRAINT `fk_production_inward_destination_concern` FOREIGN KEY (`destination_concern_id`) REFERENCES `concerns` (`id`) ON DELETE RESTRICT',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_balances' AND COLUMN_NAME='location_type' AND COLUMN_TYPE NOT LIKE '%''CONCERN''%'),
  'ALTER TABLE `stock_balances` MODIFY `location_type` ENUM(''WAREHOUSE'',''SHOP'',''PRODUCTION_UNIT'',''CHANNEL'',''OFFICE'',''CONCERN'') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='stock_transactions' AND COLUMN_NAME='location_type' AND COLUMN_TYPE NOT LIKE '%''CONCERN''%'),
  'ALTER TABLE `stock_transactions` MODIFY `location_type` ENUM(''WAREHOUSE'',''SHOP'',''PRODUCTION_UNIT'',''CHANNEL'',''OFFICE'',''CONCERN'') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL',
  'DO 0'
) INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items' AND COLUMN_NAME='dispatched_qty'), 'ALTER TABLE `production_inward_items` ADD COLUMN `dispatched_qty` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `quantity`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items' AND COLUMN_NAME='damaged_qty'), 'ALTER TABLE `production_inward_items` ADD COLUMN `damaged_qty` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `dispatched_qty`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items' AND COLUMN_NAME='missing_qty'), 'ALTER TABLE `production_inward_items` ADD COLUMN `missing_qty` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `damaged_qty`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items' AND COLUMN_NAME='excess_qty'), 'ALTER TABLE `production_inward_items` ADD COLUMN `excess_qty` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `missing_qty`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items' AND COLUMN_NAME='excess_codes'), 'ALTER TABLE `production_inward_items` ADD COLUMN `excess_codes` LONGTEXT NULL AFTER `excess_qty`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items' AND COLUMN_NAME='damaged_codes'), 'ALTER TABLE `production_inward_items` ADD COLUMN `damaged_codes` LONGTEXT NULL AFTER `scanned_codes`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inward_items' AND COLUMN_NAME='missing_codes'), 'ALTER TABLE `production_inward_items` ADD COLUMN `missing_codes` LONGTEXT NULL AFTER `damaged_codes`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items' AND COLUMN_NAME='missing_qr_codes'), 'ALTER TABLE `production_batch_items` ADD COLUMN `missing_qr_codes` LONGTEXT NULL AFTER `damaged_qr_codes`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 7. Phase 2 / 3 / 4: Production Master Data & Damages Concern Columns
-- ----------------------------------------------------------------------------
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sellers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sellers' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `sellers` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sellers') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sellers' AND INDEX_NAME='idx_sellers_concern'), 'ALTER TABLE `sellers` ADD INDEX `idx_sellers_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='seller_rolls') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='seller_rolls' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `seller_rolls` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='seller_rolls') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='seller_rolls' AND INDEX_NAME='idx_seller_rolls_concern'), 'ALTER TABLE `seller_rolls` ADD INDEX `idx_seller_rolls_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `roll_damages` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages' AND INDEX_NAME='idx_roll_damages_concern'), 'ALTER TABLE `roll_damages` ADD INDEX `idx_roll_damages_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `products` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND INDEX_NAME='idx_products_concern'), 'ALTER TABLE `products` ADD INDEX `idx_products_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `product_variants` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants' AND INDEX_NAME='idx_product_variants_concern'), 'ALTER TABLE `product_variants` ADD INDEX `idx_product_variants_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_inventory_damages` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND COLUMN_NAME='variant_id'), 'ALTER TABLE `production_inventory_damages` ADD COLUMN `variant_id` INT UNSIGNED NULL DEFAULT NULL AFTER `qc_item_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND COLUMN_NAME='stock_location_type'), 'ALTER TABLE `production_inventory_damages` ADD COLUMN `stock_location_type` ENUM(''CONCERN'',''PRODUCTION_UNIT'') NULL DEFAULT NULL AFTER `production_unit_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND COLUMN_NAME='stock_location_id'), 'ALTER TABLE `production_inventory_damages` ADD COLUMN `stock_location_id` INT UNSIGNED NULL DEFAULT NULL AFTER `stock_location_type`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND COLUMN_NAME='qc_batch_id' AND IS_NULLABLE='NO'), 'ALTER TABLE `production_inventory_damages` MODIFY `qc_batch_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND COLUMN_NAME='qc_item_id' AND IS_NULLABLE='NO'), 'ALTER TABLE `production_inventory_damages` MODIFY `qc_item_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND COLUMN_NAME='program_id' AND IS_NULLABLE='NO'), 'ALTER TABLE `production_inventory_damages` MODIFY `program_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND INDEX_NAME='idx_prod_inv_damages_concern'), 'ALTER TABLE `production_inventory_damages` ADD INDEX `idx_prod_inv_damages_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND INDEX_NAME='idx_prod_inv_damages_variant'), 'ALTER TABLE `production_inventory_damages` ADD INDEX `idx_prod_inv_damages_variant` (`variant_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND INDEX_NAME='idx_prod_inv_damages_location'), 'ALTER TABLE `production_inventory_damages` ADD INDEX `idx_prod_inv_damages_location` (`stock_location_type`, `stock_location_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Safe non-destructive concern_id backfill only where rows have concern_id IS NULL and a concern row exists
SET @primary_concern_id = (SELECT id FROM `concerns` ORDER BY id ASC LIMIT 1);

UPDATE `sellers` s
LEFT JOIN (
  SELECT seller_id, MAX(concern_id) AS concern_id
  FROM `production_purchases`
  WHERE concern_id IS NOT NULL
  GROUP BY seller_id
) pp ON pp.seller_id = s.id
SET s.concern_id = COALESCE(s.concern_id, pp.concern_id, @primary_concern_id)
WHERE s.concern_id IS NULL AND @primary_concern_id IS NOT NULL;

UPDATE `seller_rolls` sr
LEFT JOIN (
  SELECT ppi.seller_roll_id, MAX(pp.concern_id) AS concern_id
  FROM `production_purchase_items` ppi
  JOIN `production_purchases` pp ON pp.id = ppi.purchase_id
  WHERE ppi.seller_roll_id IS NOT NULL AND pp.concern_id IS NOT NULL
  GROUP BY ppi.seller_roll_id
) p ON p.seller_roll_id = sr.id
LEFT JOIN `sellers` s ON s.id = sr.seller_id
SET sr.concern_id = COALESCE(sr.concern_id, p.concern_id, s.concern_id, @primary_concern_id)
WHERE sr.concern_id IS NULL AND @primary_concern_id IS NOT NULL;

UPDATE `roll_damages` rd
LEFT JOIN `seller_rolls` sr ON sr.id = rd.seller_roll_id
LEFT JOIN `sellers` s ON s.id = rd.seller_id
SET rd.concern_id = COALESCE(rd.concern_id, sr.concern_id, s.concern_id, @primary_concern_id)
WHERE rd.concern_id IS NULL AND @primary_concern_id IS NOT NULL;

UPDATE `products` p
LEFT JOIN (
  SELECT pp.product_id, MAX(pu.concern_id) AS concern_id
  FROM `production_programs` pp
  JOIN `production_units` pu ON pu.id = pp.production_unit_id
  WHERE pp.product_id IS NOT NULL AND pu.concern_id IS NOT NULL
  GROUP BY pp.product_id
) prog ON prog.product_id = p.id
SET p.concern_id = COALESCE(p.concern_id, prog.concern_id, @primary_concern_id)
WHERE p.concern_id IS NULL AND @primary_concern_id IS NOT NULL;

UPDATE `product_variants` pv
LEFT JOIN `products` p ON p.id = pv.product_id
SET pv.concern_id = COALESCE(pv.concern_id, p.concern_id, @primary_concern_id)
WHERE pv.concern_id IS NULL AND @primary_concern_id IS NOT NULL;

UPDATE `production_inventory_damages` d
LEFT JOIN `production_units` pu ON pu.id = d.production_unit_id
LEFT JOIN `production_programs` pp ON pp.id = d.program_id
LEFT JOIN `production_units` pp_pu ON pp_pu.id = pp.production_unit_id
SET d.concern_id = COALESCE(d.concern_id, pu.concern_id, pp_pu.concern_id, @primary_concern_id)
WHERE d.concern_id IS NULL AND @primary_concern_id IS NOT NULL;

-- Swap global unique indexes to per-concern unique indexes
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages' AND INDEX_NAME='idx_roll_damages_number'), 'ALTER TABLE `roll_damages` ADD INDEX `idx_roll_damages_number` (`damage_number`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages' AND INDEX_NAME='uniq_roll_damages_number' AND NON_UNIQUE=0), 'ALTER TABLE `roll_damages` DROP INDEX `uniq_roll_damages_number`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages' AND INDEX_NAME='uniq_roll_damages_concern_number'), 'ALTER TABLE `roll_damages` ADD UNIQUE KEY `uniq_roll_damages_concern_number` (`concern_id`,`damage_number`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages' AND INDEX_NAME='idx_roll_damages_qr_code'), 'ALTER TABLE `roll_damages` ADD INDEX `idx_roll_damages_qr_code` (`qr_code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages' AND INDEX_NAME='uniq_roll_damages_qr_code' AND NON_UNIQUE=0), 'ALTER TABLE `roll_damages` DROP INDEX `uniq_roll_damages_qr_code`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='roll_damages' AND INDEX_NAME='uniq_roll_damages_concern_qr_code'), 'ALTER TABLE `roll_damages` ADD UNIQUE KEY `uniq_roll_damages_concern_qr_code` (`concern_id`,`qr_code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND INDEX_NAME='idx_products_code'), 'ALTER TABLE `products` ADD INDEX `idx_products_code` (`code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND INDEX_NAME='code' AND NON_UNIQUE=0), 'ALTER TABLE `products` DROP INDEX `code`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND INDEX_NAME='uniq_products_concern_code'), 'ALTER TABLE `products` ADD UNIQUE KEY `uniq_products_concern_code` (`concern_id`,`code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND COLUMN_NAME='qr_code_prefix') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='products' AND INDEX_NAME='idx_products_concern_qr_prefix'), 'ALTER TABLE `products` ADD INDEX `idx_products_concern_qr_prefix` (`concern_id`,`qr_code_prefix`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants' AND INDEX_NAME='idx_variant_sku'), 'ALTER TABLE `product_variants` ADD INDEX `idx_variant_sku` (`sku`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants' AND INDEX_NAME='sku' AND NON_UNIQUE=0), 'ALTER TABLE `product_variants` DROP INDEX `sku`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants' AND INDEX_NAME='uniq_variants_concern_sku'), 'ALTER TABLE `product_variants` ADD UNIQUE KEY `uniq_variants_concern_sku` (`concern_id`,`sku`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants' AND INDEX_NAME='idx_variant_qr'), 'ALTER TABLE `product_variants` ADD INDEX `idx_variant_qr` (`qr_code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants' AND INDEX_NAME='qr_code' AND NON_UNIQUE=0), 'ALTER TABLE `product_variants` DROP INDEX `qr_code`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='product_variants' AND INDEX_NAME='uniq_variants_concern_qr_code'), 'ALTER TABLE `product_variants` ADD UNIQUE KEY `uniq_variants_concern_qr_code` (`concern_id`,`qr_code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND INDEX_NAME='idx_prod_inv_damages_number'), 'ALTER TABLE `production_inventory_damages` ADD INDEX `idx_prod_inv_damages_number` (`damage_number`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND INDEX_NAME='uniq_production_inventory_damage_number' AND NON_UNIQUE=0), 'ALTER TABLE `production_inventory_damages` DROP INDEX `uniq_production_inventory_damage_number`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND INDEX_NAME='uniq_prod_inv_dmg_concern_number'), 'ALTER TABLE `production_inventory_damages` ADD UNIQUE KEY `uniq_prod_inv_dmg_concern_number` (`concern_id`,`damage_number`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND INDEX_NAME='idx_prod_inv_damages_tag'), 'ALTER TABLE `production_inventory_damages` ADD INDEX `idx_prod_inv_damages_tag` (`tag_code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND INDEX_NAME='uniq_production_inventory_damage_tag' AND NON_UNIQUE=0), 'ALTER TABLE `production_inventory_damages` DROP INDEX `uniq_production_inventory_damage_tag`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND INDEX_NAME='uniq_prod_inv_dmg_concern_tag'), 'ALTER TABLE `production_inventory_damages` ADD UNIQUE KEY `uniq_prod_inv_dmg_concern_tag` (`concern_id`,`tag_code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 8. Phase 5 Workflows: Inward-Packed Batches, Unit-less Cash & Damage Stage
-- ----------------------------------------------------------------------------
SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND COLUMN_NAME='program_id' AND IS_NULLABLE='NO'), 'ALTER TABLE `production_batches` MODIFY `program_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND COLUMN_NAME='source_type'), 'ALTER TABLE `production_batches` ADD COLUMN `source_type` ENUM(''QC'',''INWARD'') NOT NULL DEFAULT ''QC'' AFTER `qc_batch_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND COLUMN_NAME='source_location_type'), 'ALTER TABLE `production_batches` ADD COLUMN `source_location_type` ENUM(''CONCERN'',''PRODUCTION_UNIT'') NULL DEFAULT NULL AFTER `source_type`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND COLUMN_NAME='source_location_id'), 'ALTER TABLE `production_batches` ADD COLUMN `source_location_id` INT UNSIGNED NULL DEFAULT NULL AFTER `source_location_type`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND INDEX_NAME='idx_production_batches_source'), 'ALTER TABLE `production_batches` ADD INDEX `idx_production_batches_source` (`source_type`,`source_location_type`,`source_location_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers' AND COLUMN_NAME='production_unit_id' AND IS_NULLABLE='NO'), 'ALTER TABLE `production_cash_registers` MODIFY `production_unit_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers' AND COLUMN_NAME='cash_sales_amount'), 'ALTER TABLE `production_cash_registers` ADD COLUMN `cash_sales_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `opening_discrepancy_reason`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers' AND COLUMN_NAME='upi_sales_amount'), 'ALTER TABLE `production_cash_registers` ADD COLUMN `upi_sales_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `cash_sales_amount`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers' AND COLUMN_NAME='card_sales_amount'), 'ALTER TABLE `production_cash_registers` ADD COLUMN `card_sales_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `upi_sales_amount`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_registers' AND COLUMN_NAME='cash_refund_amount'), 'ALTER TABLE `production_cash_registers` ADD COLUMN `cash_refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `cash_out_amount`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_transactions' AND COLUMN_NAME='production_unit_id' AND IS_NULLABLE='NO'), 'ALTER TABLE `production_cash_transactions` MODIFY `production_unit_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_transactions' AND COLUMN_NAME='transaction_type' AND COLUMN_TYPE NOT LIKE '%SALE_CASH%'), 'ALTER TABLE `production_cash_transactions` MODIFY `transaction_type` ENUM(''OPENING_FLOAT'',''SALE_CASH'',''SALE_UPI'',''SALE_CARD'',''SALE_OTHER'',''CASH_IN'',''EXPENSE'',''REFUND_CASH'',''REFUND_UPI'',''BANK_DEPOSIT'',''CLOSING_ADJUSTMENT'') NOT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_transactions') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_transactions' AND INDEX_NAME='idx_production_cash_tx_source'), 'ALTER TABLE `production_cash_transactions` ADD INDEX `idx_production_cash_tx_source` (`source_table`,`source_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_expenses' AND COLUMN_NAME='production_unit_id' AND IS_NULLABLE='NO'), 'ALTER TABLE `production_cash_expenses` MODIFY `production_unit_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_cash_deposits' AND COLUMN_NAME='production_unit_id' AND IS_NULLABLE='NO'), 'ALTER TABLE `production_cash_deposits` MODIFY `production_unit_id` INT UNSIGNED NULL DEFAULT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_inventory_damages' AND COLUMN_NAME='source_stage' AND COLUMN_TYPE NOT LIKE '%''INWARD''%'), 'ALTER TABLE `production_inventory_damages` MODIFY `source_stage` ENUM(''QC'',''LABELING'',''PACKING'',''INWARD'',''STORAGE_HANDLING'',''OTHER'') NOT NULL DEFAULT ''OTHER''', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ----------------------------------------------------------------------------
-- 9. Permissions Upsert (Safe to Re-run)
-- ----------------------------------------------------------------------------
INSERT INTO `permissions` (`module`, `action`, `description`, `slug`, `created_at`)
VALUES
  ('production_inward', 'receive', 'Receive products into concern or production-unit inventory', 'production_inward.receive', NOW()),
  ('production_cash', 'view', 'View production-unit cash flow and shift records', 'production_cash.view', NOW()),
  ('production_cash', 'create', 'Open shifts and record production cash movements', 'production_cash.create', NOW()),
  ('production_cash', 'close_shift', 'Close and reconcile a production cash shift', 'production_cash.close_shift', NOW()),
  ('production_cash', 'voucher', 'View and print production cash vouchers', 'production_cash.voucher', NOW()),
  ('production_billing', 'print', 'Print production tax invoices and POS receipts', 'production_billing.print', NOW())
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `slug` = VALUES(`slug`);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT rp.role_id, p_print.id
FROM `role_permissions` rp
JOIN `permissions` p_view ON p_view.id = rp.permission_id AND p_view.module = 'production_billing' AND p_view.action = 'view'
JOIN `permissions` p_print ON p_print.module = 'production_billing' AND p_print.action = 'print';

INSERT IGNORE INTO `user_permissions` (`user_id`, `permission_id`)
SELECT up.user_id, p_print.id
FROM `user_permissions` up
JOIN `permissions` p_view ON p_view.id = up.permission_id AND p_view.module = 'production_billing' AND p_view.action = 'view'
JOIN `permissions` p_print ON p_print.module = 'production_billing' AND p_print.action = 'print';

-- ----------------------------------------------------------------------------
-- 10. Phase 6 Workflows: Batch Sale Billing, Scan Mode & Concern Purchase Bills
-- ----------------------------------------------------------------------------
SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND COLUMN_NAME='source_type' AND COLUMN_TYPE NOT LIKE '%''SCAN''%'), 'ALTER TABLE `production_batches` MODIFY `source_type` ENUM(''QC'',''INWARD'',''SCAN'') NOT NULL DEFAULT ''QC''', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND COLUMN_NAME='sale_bill_id'), 'ALTER TABLE `production_batches` ADD COLUMN `sale_bill_id` INT UNSIGNED NULL DEFAULT NULL AFTER `destination_concern_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches' AND INDEX_NAME='idx_production_batches_sale_bill'), 'ALTER TABLE `production_batches` ADD INDEX `idx_production_batches_sale_bill` (`sale_bill_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batches') AND EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills') AND NOT EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@db AND TABLE_NAME='production_batches' AND CONSTRAINT_NAME='fk_production_batches_sale_bill'), 'ALTER TABLE `production_batches` ADD CONSTRAINT `fk_production_batches_sale_bill` FOREIGN KEY (`sale_bill_id`) REFERENCES `production_bills` (`id`) ON DELETE SET NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items' AND COLUMN_NAME='qc_item_id'), 'ALTER TABLE `production_batch_items` ADD COLUMN `qc_item_id` INT UNSIGNED NULL DEFAULT NULL AFTER `variant_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items' AND COLUMN_NAME='source_kind'), 'ALTER TABLE `production_batch_items` ADD COLUMN `source_kind` ENUM(''QC'',''INWARD'') NOT NULL DEFAULT ''QC'' AFTER `qc_item_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items' AND COLUMN_NAME='source_location_type'), 'ALTER TABLE `production_batch_items` ADD COLUMN `source_location_type` ENUM(''CONCERN'',''PRODUCTION_UNIT'') NULL DEFAULT NULL AFTER `source_kind`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items' AND COLUMN_NAME='source_location_id'), 'ALTER TABLE `production_batch_items` ADD COLUMN `source_location_id` INT UNSIGNED NULL DEFAULT NULL AFTER `source_location_type`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_batch_items' AND INDEX_NAME='idx_production_batch_items_qc_item'), 'ALTER TABLE `production_batch_items` ADD INDEX `idx_production_batch_items_qc_item` (`qc_item_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills' AND COLUMN_NAME='bill_source'), 'ALTER TABLE `production_bills` ADD COLUMN `bill_source` ENUM(''DIRECT'',''BATCH_OUTWARD'') NOT NULL DEFAULT ''DIRECT'' AFTER `concern_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills' AND COLUMN_NAME='source_batch_id'), 'ALTER TABLE `production_bills` ADD COLUMN `source_batch_id` INT UNSIGNED NULL DEFAULT NULL AFTER `bill_source`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills' AND COLUMN_NAME='destination_concern_id'), 'ALTER TABLE `production_bills` ADD COLUMN `destination_concern_id` INT UNSIGNED NULL DEFAULT NULL AFTER `source_batch_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills' AND INDEX_NAME='idx_production_bills_source_batch'), 'ALTER TABLE `production_bills` ADD INDEX `idx_production_bills_source_batch` (`source_batch_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bills' AND INDEX_NAME='idx_production_bills_dest_concern'), 'ALTER TABLE `production_bills` ADD INDEX `idx_production_bills_dest_concern` (`destination_concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_item_tags') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_item_tags' AND COLUMN_NAME='concern_id'), 'ALTER TABLE `production_bill_item_tags` ADD COLUMN `concern_id` INT UNSIGNED NULL DEFAULT NULL AFTER `bill_item_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
UPDATE `production_bill_item_tags` t JOIN `production_bills` pb ON pb.id = t.bill_id SET t.concern_id = pb.concern_id WHERE t.concern_id IS NULL AND pb.concern_id IS NOT NULL;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_item_tags') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_item_tags' AND INDEX_NAME='idx_production_bill_tag_code'), 'ALTER TABLE `production_bill_item_tags` ADD INDEX `idx_production_bill_tag_code` (`tag_code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_item_tags') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_item_tags' AND INDEX_NAME='idx_production_bill_tags_concern'), 'ALTER TABLE `production_bill_item_tags` ADD INDEX `idx_production_bill_tags_concern` (`concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_item_tags' AND INDEX_NAME='uniq_production_bill_tag' AND NON_UNIQUE=0), 'ALTER TABLE `production_bill_item_tags` DROP INDEX `uniq_production_bill_tag`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_item_tags') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_bill_item_tags' AND INDEX_NAME='uniq_production_bill_concern_tag'), 'ALTER TABLE `production_bill_item_tags` ADD UNIQUE KEY `uniq_production_bill_concern_tag` (`concern_id`,`tag_code`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sellers') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sellers' AND COLUMN_NAME='linked_concern_id'), 'ALTER TABLE `sellers` ADD COLUMN `linked_concern_id` INT UNSIGNED NULL DEFAULT NULL AFTER `concern_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sellers') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sellers' AND INDEX_NAME='idx_sellers_linked_concern'), 'ALTER TABLE `sellers` ADD INDEX `idx_sellers_linked_concern` (`concern_id`,`linked_concern_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases' AND COLUMN_NAME='source_batch_id'), 'ALTER TABLE `production_purchases` ADD COLUMN `source_batch_id` INT UNSIGNED NULL DEFAULT NULL AFTER `production_unit_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases' AND COLUMN_NAME='source_sale_bill_id'), 'ALTER TABLE `production_purchases` ADD COLUMN `source_sale_bill_id` INT UNSIGNED NULL DEFAULT NULL AFTER `source_batch_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases' AND COLUMN_NAME='source_inward_id'), 'ALTER TABLE `production_purchases` ADD COLUMN `source_inward_id` INT UNSIGNED NULL DEFAULT NULL AFTER `source_sale_bill_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases' AND INDEX_NAME='idx_production_purchases_source_batch'), 'ALTER TABLE `production_purchases` ADD INDEX `idx_production_purchases_source_batch` (`source_batch_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchases' AND INDEX_NAME='idx_production_purchases_source_bill'), 'ALTER TABLE `production_purchases` ADD INDEX `idx_production_purchases_source_bill` (`source_sale_bill_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items' AND COLUMN_NAME='item_type' AND COLUMN_TYPE NOT LIKE '%''FINISHED_GOODS''%'), 'ALTER TABLE `production_purchase_items` MODIFY `item_type` ENUM(''FABRIC_ROLL'',''GENERAL_SUPPLY'',''FINISHED_GOODS'') NOT NULL', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items' AND COLUMN_NAME='variant_id'), 'ALTER TABLE `production_purchase_items` ADD COLUMN `variant_id` INT UNSIGNED NULL DEFAULT NULL AFTER `purchase_id`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items' AND COLUMN_NAME='sku'), 'ALTER TABLE `production_purchase_items` ADD COLUMN `sku` VARCHAR(100) NULL DEFAULT NULL AFTER `item_name`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items' AND COLUMN_NAME='size_name'), 'ALTER TABLE `production_purchase_items` ADD COLUMN `size_name` VARCHAR(100) NULL DEFAULT NULL AFTER `sku`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items' AND COLUMN_NAME='color_name'), 'ALTER TABLE `production_purchase_items` ADD COLUMN `color_name` VARCHAR(100) NULL DEFAULT NULL AFTER `size_name`', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SELECT IF(EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items') AND NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='production_purchase_items' AND INDEX_NAME='idx_production_purchase_items_variant'), 'ALTER TABLE `production_purchase_items` ADD INDEX `idx_production_purchase_items_variant` (`variant_id`)', 'DO 0') INTO @sql; PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET FOREIGN_KEY_CHECKS = @old_fk_checks;
-- Hostinger live database upgrade completed cleanly.
