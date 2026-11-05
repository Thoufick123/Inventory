-- =================================================
-- GRM Inventory Management System Canonical Database Schema
-- Generated: 2026-09-28 13:55:55
-- Total Tables: 87
-- =================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for table `allocation_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `allocation_items`;
CREATE TABLE `allocation_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `allocation_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `requested_qty` int NOT NULL,
  `approved_qty` int DEFAULT '0',
  `picked_qty` int DEFAULT '0',
  `packed_qty` int DEFAULT '0',
  `dispatched_qty` int DEFAULT '0',
  `selected_qr_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `received_qr_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `received_qty` int NOT NULL DEFAULT '0',
  `damaged_qty` int NOT NULL DEFAULT '0',
  `missing_qty` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `allocation_id` (`allocation_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `allocation_items_ibfk_1` FOREIGN KEY (`allocation_id`) REFERENCES `warehouse_allocations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `allocation_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `allocation_outwards`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `allocation_outwards`;
CREATE TABLE `allocation_outwards` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `outward_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `allocation_id` int unsigned NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `destination_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_id` int unsigned DEFAULT '0',
  `shop_id` int unsigned DEFAULT NULL,
  `channel_id` int unsigned DEFAULT NULL,
  `total_boxes` int DEFAULT '1',
  `total_quantity` int DEFAULT '0',
  `vehicle_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_contact` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transporter_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lr_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispatched_by` int unsigned DEFAULT NULL,
  `dispatch_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('DISPATCHED','DELIVERED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'DISPATCHED',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total_pieces` int NOT NULL DEFAULT '0',
  `transport_mode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispatched_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `outward_number` (`outward_number`),
  KEY `idx_outward_alloc` (`allocation_id`),
  KEY `idx_outward_wh` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `asset_audit_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `asset_audit_items`;
CREATE TABLE `asset_audit_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `audit_id` int unsigned NOT NULL,
  `asset_id` int unsigned NOT NULL,
  `status` enum('VERIFIED_OK','DAMAGED','MISSING','WRONG_LOCATION') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VERIFIED_OK',
  `scanned_at` datetime DEFAULT NULL,
  `observed_condition` enum('EXCELLENT','GOOD','FAIR','POOR','CRITICAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_audit` (`audit_id`),
  KEY `idx_asset` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `asset_audits`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `asset_audits`;
CREATE TABLE `asset_audits` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `audit_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_type` enum('PRODUCTION','WAREHOUSE','SHOP','OFFICE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` int unsigned NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `audit_date` date NOT NULL,
  `status` enum('PLANNED','IN_PROGRESS','COMPLETED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPLETED',
  `total_assets_expected` int NOT NULL DEFAULT '0',
  `total_assets_verified` int NOT NULL DEFAULT '0',
  `total_assets_missing` int NOT NULL DEFAULT '0',
  `total_assets_damaged` int NOT NULL DEFAULT '0',
  `auditor_id` int unsigned DEFAULT NULL,
  `auditor_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_number` (`audit_number`),
  KEY `idx_loc` (`location_type`,`location_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`audit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `asset_categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `asset_categories`;
CREATE TABLE `asset_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bi-box-seam',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `depreciation_rate_annual` decimal(5,2) NOT NULL DEFAULT '15.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `asset_maintenance_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `asset_maintenance_logs`;
CREATE TABLE `asset_maintenance_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `log_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_id` int unsigned NOT NULL,
  `service_type` enum('ROUTINE_PREVENTIVE','BREAKDOWN_REPAIR','CALIBRATION','PART_REPLACEMENT','ANNUAL_AMC') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ROUTINE_PREVENTIVE',
  `issue_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `action_taken` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `serviced_by_vendor` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `technician_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `technician_contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `parts_replaced` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `service_date` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `downtime_hours` decimal(5,1) NOT NULL DEFAULT '0.0',
  `status` enum('PENDING','IN_PROGRESS','COMPLETED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPLETED',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `log_number` (`log_number`),
  KEY `idx_asset` (`asset_id`),
  KEY `idx_service_type` (`service_type`),
  KEY `idx_service_date` (`service_date`),
  KEY `idx_next_due` (`next_due_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `asset_transfers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `asset_transfers`;
CREATE TABLE `asset_transfers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_id` int unsigned NOT NULL,
  `from_location_type` enum('PRODUCTION','WAREHOUSE','SHOP','OFFICE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_location_id` int unsigned NOT NULL,
  `from_floor_area` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_location_type` enum('PRODUCTION','WAREHOUSE','SHOP','OFFICE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_location_id` int unsigned NOT NULL,
  `to_floor_area` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_date` datetime NOT NULL,
  `status` enum('INITIATED','IN_TRANSIT','COMPLETED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPLETED',
  `transferred_by` int unsigned DEFAULT NULL,
  `received_by` int unsigned DEFAULT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `challan_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_number` (`transfer_number`),
  KEY `idx_asset` (`asset_id`),
  KEY `idx_from` (`from_location_type`,`from_location_id`),
  KEY `idx_to` (`to_location_type`,`to_location_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`transfer_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `assets`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int unsigned NOT NULL,
  `location_type` enum('PRODUCTION','WAREHOUSE','SHOP','OFFICE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PRODUCTION',
  `location_id` int unsigned NOT NULL DEFAULT '1',
  `floor_area` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode_qr` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `specifications` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vendor_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_contact` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `useful_life_years` int NOT NULL DEFAULT '5',
  `salvage_value` decimal(12,2) NOT NULL DEFAULT '0.00',
  `current_book_value` decimal(12,2) NOT NULL DEFAULT '0.00',
  `last_depreciation_date` date DEFAULT NULL,
  `status` enum('IN_USE','IDLE','IN_MAINTENANCE','DAMAGED','DISPOSED','WRITTEN_OFF') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IN_USE',
  `condition_state` enum('EXCELLENT','GOOD','FAIR','POOR','CRITICAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GOOD',
  `custodian_id` int unsigned DEFAULT NULL,
  `custodian_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  UNIQUE KEY `barcode_qr` (`barcode_qr`),
  KEY `idx_category` (`category_id`),
  KEY `idx_location` (`location_type`,`location_id`),
  KEY `idx_status` (`status`),
  KEY `idx_condition` (`condition_state`),
  KEY `idx_qr` (`barcode_qr`),
  KEY `idx_code` (`asset_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `audit_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int unsigned DEFAULT NULL,
  `previous_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `record_id` int unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_reference` (`reference_type`,`reference_id`),
  KEY `idx_audit_date` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_chk_1` CHECK (json_valid(`previous_value`)),
  CONSTRAINT `audit_logs_chk_2` CHECK (json_valid(`new_value`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `backups`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `backups`;
CREATE TABLE `backups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint unsigned DEFAULT '0',
  `backup_type` enum('FULL','SCHEMA','DATA') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'FULL',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `filepath` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `filesize` bigint DEFAULT '0',
  `status` enum('COMPLETED','FAILED','IN_PROGRESS') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'IN_PROGRESS',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `bank_deposits`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `bank_deposits`;
CREATE TABLE `bank_deposits` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned NOT NULL,
  `register_id` int unsigned DEFAULT NULL,
  `deposit_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `slip_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_date` date NOT NULL,
  `deposited_by` int unsigned NOT NULL,
  `verified_by` int unsigned DEFAULT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VERIFIED',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `slip_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deposit_number` (`deposit_number`),
  KEY `idx_shop_status` (`shop_id`,`status`),
  KEY `idx_deposit_date` (`deposit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `brands`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `cash_expenses`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cash_expenses`;
CREATE TABLE `cash_expenses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned NOT NULL,
  `register_id` int unsigned DEFAULT NULL,
  `voucher_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expense_category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General Expense',
  `amount` decimal(10,2) NOT NULL,
  `paid_to` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_mode` enum('CASH','UPI','CARD','ONLINE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `bill_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `receipt_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_number` (`voucher_number`),
  KEY `idx_shop_cat` (`shop_id`,`expense_category`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `cash_registers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cash_registers`;
CREATE TABLE `cash_registers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned NOT NULL,
  `register_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cashier_id` int unsigned NOT NULL,
  `cashier_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `closed_by` int unsigned DEFAULT NULL,
  `opening_time` datetime NOT NULL,
  `closing_time` datetime DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `opening_expected_float` decimal(12,2) NOT NULL DEFAULT '0.00',
  `opening_difference` decimal(12,2) NOT NULL DEFAULT '0.00',
  `opening_denominations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `opening_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `opening_discrepancy_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cash_sales_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `upi_sales_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `card_sales_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_in_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_out_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_refund_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bank_deposit_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `expected_cash` decimal(12,2) NOT NULL DEFAULT '0.00',
  `closing_cash_counted` decimal(12,2) DEFAULT NULL,
  `closing_denominations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cash_difference` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discrepancy_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('OPEN','CLOSED','AUDITED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OPEN',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `register_number` (`register_number`),
  KEY `idx_shop_status` (`shop_id`,`status`),
  KEY `idx_cashier` (`cashier_id`),
  KEY `idx_opening_time` (`opening_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `cash_transactions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cash_transactions`;
CREATE TABLE `cash_transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int unsigned NOT NULL,
  `register_id` int unsigned DEFAULT NULL,
  `transaction_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_type` enum('OPENING_FLOAT','SALE_CASH','SALE_UPI','SALE_CARD','CASH_IN','CASH_OUT','EXPENSE','REFUND_CASH','BANK_DEPOSIT','SAFE_DROP','CLOSING_ADJUSTMENT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flow_direction` enum('IN','OUT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_mode` enum('CASH','UPI','CARD','BANK_TRANSFER','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GENERAL',
  `reference_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_table` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` int unsigned DEFAULT NULL,
  `handled_by` int unsigned NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_number` (`transaction_number`),
  KEY `idx_shop_flow` (`shop_id`,`flow_direction`),
  KEY `idx_register` (`register_id`),
  KEY `idx_type` (`transaction_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `channel_damages`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `channel_damages`;
CREATE TABLE `channel_damages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `damage_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `channel_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `product_id` int unsigned DEFAULT NULL,
  `tag_code` text COLLATE utf8mb4_general_ci,
  `source_type` enum('INWARD_TRANSIT','STORAGE_HANDLING','CUSTOMER_RETURN','EXPIRED_SPOILED','OTHER') COLLATE utf8mb4_general_ci DEFAULT 'INWARD_TRANSIT',
  `reference_id` int unsigned DEFAULT NULL,
  `allocation_id` int unsigned DEFAULT NULL,
  `defect_type` enum('FABRIC_DEFECT','COLOR_FADE','STITCHING_ERROR','PACKAGING_DAMAGE','WATER_STAIN','TRANSIT_DAMAGE','EXPIRED','OTHER') COLLATE utf8mb4_general_ci DEFAULT 'TRANSIT_DAMAGE',
  `quantity` int NOT NULL DEFAULT '1',
  `cost_per_unit` decimal(10,2) DEFAULT '0.00',
  `estimated_cost` decimal(10,2) DEFAULT '0.00',
  `total_loss` decimal(10,2) DEFAULT '0.00',
  `damage_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `defect_notes` text COLLATE utf8mb4_general_ci,
  `photo_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('LOGGED','INSPECTED','OUTWARD_BATCHED','DISPATCHED','RECEIVED_AT_WH','SALVAGED','WRITTEN_OFF','REWORK') COLLATE utf8mb4_general_ci DEFAULT 'LOGGED',
  `outward_batch_id` int unsigned DEFAULT NULL,
  `disposition_notes` text COLLATE utf8mb4_general_ci,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `colors`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `colors`;
CREATE TABLE `colors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hex_code` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `customers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gst_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pincode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_purchases` decimal(12,2) DEFAULT '0.00',
  `source_channel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'SHOP',
  PRIMARY KEY (`id`),
  KEY `idx_customer_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `cutting_batch_rolls`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cutting_batch_rolls`;
CREATE TABLE `cutting_batch_rolls` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cutting_batch_id` int unsigned NOT NULL,
  `seller_roll_id` int unsigned NOT NULL,
  `seller_id` int unsigned DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `roll_weight` decimal(10,2) NOT NULL DEFAULT '0.00',
  `roll_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `wastage_weight` decimal(10,2) NOT NULL DEFAULT '0.00',
  `wastage_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `roll_color` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roll_design` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `used_weight` decimal(10,2) DEFAULT '0.00',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cbr_batch` (`cutting_batch_id`),
  KEY `idx_cbr_roll` (`seller_roll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `cutting_batches`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cutting_batches`;
CREATE TABLE `cutting_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int unsigned NOT NULL,
  `seller_roll_id` int unsigned DEFAULT NULL,
  `roll_weight` decimal(10,2) DEFAULT '0.00',
  `roll_price` decimal(12,2) DEFAULT '0.00',
  `wastage_weight` decimal(10,2) DEFAULT '0.00',
  `wastage_cost` decimal(12,2) DEFAULT '0.00',
  `used_weight` decimal(10,2) DEFAULT '0.00',
  `cost_per_piece` decimal(12,2) DEFAULT '0.00',
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `batch_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operator_id` int unsigned DEFAULT NULL,
  `production_unit_id` int unsigned DEFAULT NULL,
  `material_issued` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `planned_qty` int NOT NULL DEFAULT '0',
  `cut_qty` int DEFAULT '0',
  `rejected_qty` int DEFAULT '0',
  `wastage_qty` int DEFAULT '0',
  `status` enum('PENDING','IN_PROGRESS','COMPLETED','HOLD','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cutting_master` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operator_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`),
  KEY `program_id` (`program_id`),
  KEY `operator_id` (`operator_id`),
  KEY `production_unit_id` (`production_unit_id`),
  CONSTRAINT `cutting_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `cutting_batches_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cutting_batches_ibfk_3` FOREIGN KEY (`production_unit_id`) REFERENCES `production_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `cutting_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cutting_items`;
CREATE TABLE `cutting_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cutting_batch_id` int unsigned NOT NULL,
  `variant_id` int unsigned DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `catalog_hierarchy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `planned_qty` int NOT NULL DEFAULT '0',
  `cut_qty` int DEFAULT '0',
  `rejected_qty` int DEFAULT '0',
  `wastage_qty` int DEFAULT '0',
  `weight_per_piece` decimal(10,3) DEFAULT '0.000',
  `weight` decimal(10,2) DEFAULT '0.00',
  `base_price` decimal(10,2) DEFAULT '0.00',
  `discount_price` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `cutting_batch_id` (`cutting_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `cutting_items_ibfk_1` FOREIGN KEY (`cutting_batch_id`) REFERENCES `cutting_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cutting_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `deliveries`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `deliveries`;
CREATE TABLE `deliveries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` int unsigned NOT NULL,
  `delivery_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('REQUESTED','APPROVED','RESERVED','PICKING','PACKING','INVOICED','DISPATCHED','IN_TRANSIT','DELIVERED','RETURNED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'REQUESTED',
  `dispatch_date` datetime DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tracking_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_staff_id` int unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `shipping_cost` decimal(10,2) DEFAULT '0.00',
  `dispatched_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `courier_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_number` (`delivery_number`),
  KEY `sale_id` (`sale_id`),
  KEY `delivery_staff_id` (`delivery_staff_id`),
  CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`delivery_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `exchange_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `exchange_items`;
CREATE TABLE `exchange_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `exchange_id` int unsigned NOT NULL,
  `item_type` enum('OLD','NEW') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `piece_tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exchange_id` (`exchange_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `exchange_items_ibfk_1` FOREIGN KEY (`exchange_id`) REFERENCES `exchanges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exchange_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `exchanges`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `exchanges`;
CREATE TABLE `exchanges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `exchange_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `return_id` int unsigned NOT NULL,
  `source_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SHOP',
  `sale_id` int unsigned DEFAULT NULL,
  `purchase_order_id` int unsigned DEFAULT NULL,
  `original_amount` decimal(10,2) DEFAULT '0.00',
  `new_amount` decimal(10,2) DEFAULT '0.00',
  `difference_amount` decimal(10,2) DEFAULT '0.00',
  `settlement_mode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `payment_action` enum('COLLECT','REFUND','NONE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'NONE',
  `status` enum('PENDING','PROCESSING','COMPLETED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `payment_status` enum('PAID','PENDING','REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PAID',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`),
  KEY `sale_id` (`sale_id`),
  KEY `idx_exchanges_source` (`source_type`),
  KEY `idx_exchanges_po` (`purchase_order_id`),
  CONSTRAINT `exchanges_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`),
  CONSTRAINT `exchanges_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `expense_categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `expense_categories`;
CREATE TABLE `expense_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_scope` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ALL',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `expenses`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_type` enum('PRODUCTION','WAREHOUSE','SHOP','CHANNEL','OFFICE','WHATSAPP','WEBSITE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PRODUCTION',
  `location_id` int unsigned DEFAULT NULL,
  `location_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PCS',
  `unit_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('PAID','PENDING','PARTIAL','APPROVED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PAID',
  `payment_method` enum('CASH','UPI','BANK_TRANSFER','CARD','PETTY_CASH','CHEQUE','CREDIT','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `payment_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_to` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor_phone` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_company` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_date` date NOT NULL,
  `receipt_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_number` (`expense_number`),
  KEY `idx_location` (`location_type`,`location_id`),
  KEY `idx_date` (`expense_date`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `label_batches`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `label_batches`;
CREATE TABLE `label_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int unsigned NOT NULL,
  `stitching_batch_id` int unsigned DEFAULT NULL,
  `batch_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `required_qty` int NOT NULL DEFAULT '0',
  `printed_qty` int DEFAULT '0',
  `used_qty` int DEFAULT '0',
  `damaged_qty` int DEFAULT '0',
  `status` enum('PENDING','IN_PROGRESS','COMPLETED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total_qty` int NOT NULL DEFAULT '0',
  `applied_qty` int DEFAULT '0',
  `session_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`),
  KEY `program_id` (`program_id`),
  KEY `fk_label_batches_stitching` (`stitching_batch_id`),
  CONSTRAINT `fk_label_batches_stitching` FOREIGN KEY (`stitching_batch_id`) REFERENCES `stitching_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `label_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `label_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `label_items`;
CREATE TABLE `label_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `label_batch_id` int unsigned NOT NULL,
  `variant_id` int unsigned DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `catalog_hierarchy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT '0.00',
  `discount_price` decimal(10,2) DEFAULT '0.00',
  `required_qty` int NOT NULL DEFAULT '0',
  `printed_qty` int DEFAULT '0',
  `used_qty` int DEFAULT '0',
  `damaged_qty` int DEFAULT '0',
  `start_seq` int DEFAULT '1',
  `end_seq` int DEFAULT '1',
  `start_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `label_batch_id` (`label_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `label_items_ibfk_1` FOREIGN KEY (`label_batch_id`) REFERENCES `label_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `label_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `locations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` int unsigned NOT NULL,
  `rack` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shelf` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `zone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_loc_warehouse` (`warehouse_id`),
  CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int unsigned DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` int unsigned NOT NULL,
  `payment_mode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('PENDING','COMPLETED','FAILED','REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'COMPLETED',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `collected_by` int unsigned DEFAULT NULL,
  `payment_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'CASH',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `purchase_order_id` int unsigned DEFAULT NULL,
  `payment_type` enum('INCOMING','OUTGOING') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INCOMING',
  `method` enum('CASH','UPI','CARD','NET_BANKING','CHEQUE','CREDIT_NOTE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `permissions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_module_action` (`module`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `product_variants`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `size_id` int unsigned DEFAULT NULL,
  `color_id` int unsigned DEFAULT NULL,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_type` enum('INDIVIDUAL','SERIES_BATCH') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'INDIVIDUAL',
  `series_start_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `series_end_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pack_quantity` int DEFAULT '1',
  `is_complementary` tinyint(1) NOT NULL DEFAULT '0',
  `mrp` decimal(10,2) DEFAULT '0.00',
  `purchase_price` decimal(10,2) DEFAULT '0.00',
  `production_cost` decimal(10,2) DEFAULT '0.00',
  `selling_price` decimal(10,2) DEFAULT '0.00',
  `weight` decimal(10,3) DEFAULT NULL,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `qr_code` (`qr_code`),
  KEY `idx_variant_product` (`product_id`),
  KEY `idx_variant_sku` (`sku`),
  KEY `idx_variant_qr` (`qr_code`),
  KEY `size_id` (`size_id`),
  KEY `color_id` (`color_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variants_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_variants_ibfk_3` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `production_batch_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `production_batch_items`;
CREATE TABLE `production_batch_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `production_batch_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `piece_tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `received_qty` int NOT NULL DEFAULT '0',
  `damaged_qty` int NOT NULL DEFAULT '0',
  `missing_qty` int NOT NULL DEFAULT '0',
  `received_qr_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `damaged_qr_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `production_batch_id` (`production_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `production_batch_items_ibfk_1` FOREIGN KEY (`production_batch_id`) REFERENCES `production_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_batch_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `production_batches`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `production_batches`;
CREATE TABLE `production_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `batch_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_id` int unsigned NOT NULL,
  `qc_batch_id` int unsigned DEFAULT NULL,
  `total_qty` int NOT NULL DEFAULT '0',
  `box_count` int DEFAULT '0',
  `destination_warehouse_id` int unsigned NOT NULL,
  `status` enum('PREPARING','APPROVED','DISPATCHED','RECEIVED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PREPARING',
  `prepared_by` int unsigned DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `quantity` int NOT NULL DEFAULT '0',
  `received_qty` int NOT NULL DEFAULT '0',
  `damaged_qty` int NOT NULL DEFAULT '0',
  `missing_qty` int NOT NULL DEFAULT '0',
  `cost_per_piece` decimal(10,2) DEFAULT '0.00',
  `total_cost` decimal(12,2) DEFAULT '0.00',
  `mfg_date` date DEFAULT NULL,
  `packed_date` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`),
  KEY `program_id` (`program_id`),
  KEY `qc_batch_id` (`qc_batch_id`),
  KEY `destination_warehouse_id` (`destination_warehouse_id`),
  KEY `prepared_by` (`prepared_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `production_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `production_batches_ibfk_2` FOREIGN KEY (`qc_batch_id`) REFERENCES `qc_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_batches_ibfk_3` FOREIGN KEY (`destination_warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `production_batches_ibfk_4` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_batches_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `production_expenses`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `production_expenses`;
CREATE TABLE `production_expenses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `production_unit` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asset_tag_or_serial` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PCS',
  `unit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('PENDING','PARTIAL','PAID','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PAID',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `payment_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_company` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_date` date NOT NULL,
  `program_id` int unsigned DEFAULT NULL,
  `photo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_expenses_number` (`expense_number`),
  KEY `idx_production_expenses_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `production_expense_items`
-- --------------------------------------------------------
CREATE TABLE `production_expense_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GENERAL',
  `unit_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PCS',
  `default_rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_expense_items_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `production_outwards`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `production_outwards`;
CREATE TABLE `production_outwards` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `production_batch_id` int unsigned NOT NULL,
  `production_unit_id` int unsigned DEFAULT NULL,
  `warehouse_id` int unsigned DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `total_boxes` int DEFAULT '0',
  `total_quantity` int DEFAULT '0',
  `outward_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dispatch_date` datetime DEFAULT NULL,
  `received_date` datetime DEFAULT NULL,
  `status` enum('DISPATCHED','RECEIVED','DISCREPANCY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'DISPATCHED',
  `vehicle_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transporter_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lr_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispatched_by` int unsigned DEFAULT NULL,
  `received_by` int unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `driver_contact` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `outward_number` (`outward_number`),
  KEY `production_batch_id` (`production_batch_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `production_outwards_ibfk_1` FOREIGN KEY (`production_batch_id`) REFERENCES `production_batches` (`id`),
  CONSTRAINT `production_outwards_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `production_program_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `production_program_items`;
CREATE TABLE `production_program_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `planned_quantity` int NOT NULL,
  `completed_quantity` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `produced_quantity` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `production_program_items_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_program_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `production_program_rolls`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `production_program_rolls`;
CREATE TABLE `production_program_rolls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `production_program_id` int NOT NULL,
  `program_id` int unsigned DEFAULT NULL,
  `seller_roll_id` int NOT NULL,
  `seller_id` int DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `roll_qty` int DEFAULT '0',
  `roll_weight` decimal(10,2) DEFAULT '0.00',
  `roll_price` decimal(12,2) DEFAULT '0.00',
  `roll_color` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `roll_design` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `color` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `design_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppr_program` (`production_program_id`),
  KEY `idx_ppr_roll` (`seller_roll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `production_programs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `production_programs`;
CREATE TABLE `production_programs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `program_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `production_unit_id` int unsigned DEFAULT NULL,
  `product_id` int unsigned DEFAULT NULL,
  `seller_roll_id` int unsigned DEFAULT NULL,
  `seller_id` int unsigned DEFAULT NULL,
  `roll_weight` decimal(10,2) DEFAULT '0.00',
  `roll_price` decimal(12,2) DEFAULT '0.00',
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `planned_quantity` int DEFAULT '0',
  `completed_quantity` int DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `priority` enum('LOW','MEDIUM','HIGH','URGENT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'MEDIUM',
  `assigned_to` int unsigned DEFAULT NULL,
  `status` enum('DRAFT','APPROVED','SCHEDULED','IN_PROGRESS','COMPLETED','HOLD','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'DRAFT',
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `produced_quantity` int DEFAULT '0',
  `target_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_number` (`program_number`),
  KEY `idx_program_unit` (`production_unit_id`),
  KEY `idx_program_status` (`status`),
  KEY `idx_program_date` (`target_date`),
  KEY `product_id` (`product_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `approved_by` (`approved_by`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `production_programs_ibfk_1` FOREIGN KEY (`production_unit_id`) REFERENCES `production_units` (`id`),
  CONSTRAINT `production_programs_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `production_programs_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_programs_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_programs_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `production_units`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `production_units`;
CREATE TABLE `production_units` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_id` int unsigned DEFAULT NULL,
  `manager_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `location` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contact_person` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity_per_day` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  CONSTRAINT `production_units_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `catalog_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `category_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subcategory_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_subcategory_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code_prefix` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `seller_id` int unsigned DEFAULT NULL,
  `source_type` enum('OWN_PRODUCTION','EXTERNAL_PURCHASE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OWN_PRODUCTION',
  `is_complementary` tinyint(1) NOT NULL DEFAULT '0',
  `material` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `hsn_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gst_percentage` decimal(5,2) DEFAULT '0.00',
  `quantity` int DEFAULT '0',
  `weight` decimal(10,3) DEFAULT '0.000',
  `min_stock` int DEFAULT '0',
  `max_stock` int DEFAULT '0',
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cloudinary_public_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `product_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warehouse_supplier_id` int unsigned DEFAULT NULL,
  `unit_id` int unsigned DEFAULT NULL,
  `warehouse_category_id` int unsigned DEFAULT NULL,
  `warehouse_subcategory_id` int unsigned DEFAULT NULL,
  `warehouse_sub_subcategory_id` int unsigned DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_product_category` (`category_id`),
  KEY `idx_product_brand` (`brand_id`),
  KEY `idx_product_source` (`source_type`),
  KEY `subcategory_id` (`subcategory_id`),
  KEY `sub_subcategory_id` (`sub_subcategory_id`),
  KEY `fk_products_seller` (`seller_id`),
  CONSTRAINT `fk_products_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_3` FOREIGN KEY (`sub_subcategory_id`) REFERENCES `sub_subcategories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_4` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `purchase_inward_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `purchase_inward_items`;
CREATE TABLE `purchase_inward_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_inward_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `ordered_qty` int NOT NULL,
  `received_qty` int NOT NULL DEFAULT '0',
  `damaged_qty` int DEFAULT '0',
  `accepted_qty` int NOT NULL DEFAULT '0',
  `location_id` int unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `batch_lot_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT '0.00',
  `received_quantity` int NOT NULL,
  `accepted_quantity` int NOT NULL,
  `rejected_quantity` int DEFAULT '0',
  `rejection_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_inward_id` (`purchase_inward_id`),
  KEY `variant_id` (`variant_id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `purchase_inward_items_ibfk_1` FOREIGN KEY (`purchase_inward_id`) REFERENCES `purchase_inwards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_inward_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `purchase_inward_items_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `purchase_inwards`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `purchase_inwards`;
CREATE TABLE `purchase_inwards` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int unsigned NOT NULL,
  `grn_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `received_date` date NOT NULL,
  `received_by` int unsigned DEFAULT NULL,
  `verified_by` int unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `invoice_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `status` enum('PENDING','INSPECTED','STOCKED','DISCREPANCY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total_received_qty` int DEFAULT '0',
  `total_accepted_qty` int DEFAULT '0',
  `total_rejected_qty` int DEFAULT '0',
  `vehicle_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_contact` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gate_pass_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grn_number` (`grn_number`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `received_by` (`received_by`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `purchase_inwards_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `purchase_inwards_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `purchase_inwards_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_inwards_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `purchase_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `purchase_items`;
CREATE TABLE `purchase_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `gst_percentage` decimal(5,2) DEFAULT '0.00',
  `cgst_amount` decimal(10,2) DEFAULT '0.00',
  `sgst_amount` decimal(10,2) DEFAULT '0.00',
  `igst_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(12,2) DEFAULT '0.00',
  `batch_lot_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT '0.00',
  `received_quantity` int DEFAULT '0',
  `tax_percentage` decimal(5,2) DEFAULT '0.00',
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `purchase_orders`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `seller_id` int unsigned DEFAULT NULL,
  `customer_id` int unsigned DEFAULT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `cgst_amount` decimal(10,2) DEFAULT '0.00',
  `sgst_amount` decimal(10,2) DEFAULT '0.00',
  `igst_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(12,2) DEFAULT '0.00',
  `payment_status` enum('PENDING','PARTIAL','PAID') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'CASH',
  `status` enum('DRAFT','CONFIRMED','RECEIVED','PARTIAL_RECEIVED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'DRAFT',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `product_edit_count` tinyint unsigned NOT NULL DEFAULT '0',
  `payment_screenshot_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_screenshot_public_id` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `paid_amount` decimal(12,2) DEFAULT '0.00',
  `warehouse_supplier_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `seller_id` (`seller_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`),
  CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `qc_batches`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `qc_batches`;
CREATE TABLE `qc_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `batch_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qc_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_id` int unsigned NOT NULL,
  `stitching_batch_id` int unsigned DEFAULT NULL,
  `label_batch_id` int unsigned DEFAULT NULL,
  `inspector_id` int unsigned DEFAULT NULL,
  `total_qty` int NOT NULL DEFAULT '0',
  `passed_qty` int DEFAULT '0',
  `rejected_qty` int DEFAULT '0',
  `rework_qty` int DEFAULT '0',
  `status` enum('PENDING','PASSED','PARTIAL','REWORK','REJECTED','COMPLETED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pieces_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `inspected_qty` int NOT NULL DEFAULT '0',
  `minor_defect_qty` int DEFAULT '0',
  `major_defect_qty` int DEFAULT '0',
  `critical_defect_qty` int DEFAULT '0',
  `qc_status` enum('PENDING','PASSED','CONDITIONALLY_PASSED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `stitching_batch_id` (`stitching_batch_id`),
  KEY `inspector_id` (`inspector_id`),
  KEY `fk_qc_label_batch` (`label_batch_id`),
  CONSTRAINT `fk_qc_label_batch` FOREIGN KEY (`label_batch_id`) REFERENCES `label_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qc_batches_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `qc_batches_ibfk_2` FOREIGN KEY (`stitching_batch_id`) REFERENCES `stitching_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qc_batches_ibfk_3` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `qc_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `qc_items`;
CREATE TABLE `qc_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `qc_batch_id` int unsigned NOT NULL,
  `variant_id` int unsigned DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `catalog_hierarchy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tag_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT '0.00',
  `discount_price` decimal(10,2) DEFAULT '0.00',
  `inspected_qty` int NOT NULL DEFAULT '0',
  `passed_qty` int DEFAULT '0',
  `rejected_qty` int DEFAULT '0',
  `rework_qty` int DEFAULT '0',
  `defect_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defect_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `defect_type` enum('FABRIC_FAULT','STITCHING_FAULT','SIZE_DEVIATION','STAIN_DIRT','LABEL_FAULT','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'OTHER',
  `defect_severity` enum('MINOR','MAJOR','CRITICAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'MINOR',
  `quantity` int NOT NULL DEFAULT '1',
  `action_taken` enum('PASSED','REWORK','REJECTED','SCRAP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PASSED',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `qc_batch_id` (`qc_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `qc_items_ibfk_1` FOREIGN KEY (`qc_batch_id`) REFERENCES `qc_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qc_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `return_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `return_items`;
CREATE TABLE `return_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `return_id` int unsigned NOT NULL,
  `sale_item_id` int unsigned DEFAULT NULL,
  `variant_id` int unsigned NOT NULL,
  `quantity` int NOT NULL,
  `item_condition` enum('GOOD','DAMAGED','DEFECTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'GOOD',
  `destination` enum('AVAILABLE','DAMAGE','REWORK','DISPOSAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'AVAILABLE',
  `refund_amount` decimal(10,2) DEFAULT '0.00',
  `condition_type` enum('GOOD','DAMAGED','DEFECTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'GOOD',
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`),
  KEY `sale_item_id` (`sale_item_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `return_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `returns`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `returns`;
CREATE TABLE `returns` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SHOP',
  `sale_id` int unsigned DEFAULT NULL,
  `purchase_order_id` int unsigned DEFAULT NULL,
  `customer_id` int unsigned DEFAULT NULL,
  `warehouse_id` int unsigned DEFAULT NULL,
  `return_type` enum('RETURN','EXCHANGE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'RETURN',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `inspection_status` enum('PENDING','INSPECTING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `refund_amount` decimal(10,2) DEFAULT '0.00',
  `refund_mode` enum('CASH','UPI','CARD','ONLINE','CREDIT_NOTE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refund_status` enum('PENDING','PROCESSED','COMPLETED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `inspected_by` int unsigned DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `status` enum('PENDING','APPROVED','REJECTED','REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `processed_by` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_number` (`return_number`),
  KEY `sale_id` (`sale_id`),
  KEY `customer_id` (`customer_id`),
  KEY `inspected_by` (`inspected_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_returns_source` (`source_type`),
  KEY `idx_returns_po` (`purchase_order_id`),
  KEY `idx_returns_warehouse` (`warehouse_id`),
  CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `returns_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `role_permissions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL,
  `permission_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `roles`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_system` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `roll_damages`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `roll_damages`;
CREATE TABLE `roll_damages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `damage_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `seller_id` int unsigned DEFAULT NULL,
  `seller_roll_id` int unsigned DEFAULT NULL,
  `damaged_quantity` int DEFAULT '0',
  `damaged_weight` decimal(10,2) DEFAULT '0.00',
  `loss_amount` decimal(12,2) DEFAULT '0.00',
  `damage_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `defect_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `photo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('LOGGED','INVESTIGATING','CLAIMED','SETTLED','REJECTED','CLOSED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOGGED',
  `resolution_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'NONE',
  `refund_amount` decimal(12,2) DEFAULT '0.00',
  `debit_note_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replacement_roll_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replacement_roll_id` int unsigned DEFAULT NULL,
  `resolution_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `resolved_by` int unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `reported_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_roll_damages_number` (`damage_number`),
  KEY `idx_roll_damages_seller` (`seller_id`),
  KEY `idx_roll_damages_roll` (`seller_roll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sale_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE `sale_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `cgst_amount` decimal(10,2) DEFAULT '0.00',
  `sgst_amount` decimal(10,2) DEFAULT '0.00',
  `igst_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL,
  `scanned_qr_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `mrp` decimal(10,2) NOT NULL,
  `gst_percentage` decimal(5,2) DEFAULT '0.00',
  `qr_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sales`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sale_type` enum('SHOP','WEBSITE','WHATSAPP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_id` int unsigned DEFAULT NULL,
  `customer_id` int unsigned DEFAULT NULL,
  `customer_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(12,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `gst_percentage` decimal(5,2) NOT NULL DEFAULT '5.00',
  `gst_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cgst_amount` decimal(10,2) DEFAULT '0.00',
  `sgst_amount` decimal(10,2) DEFAULT '0.00',
  `igst_amount` decimal(10,2) DEFAULT '0.00',
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_mode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'CASH',
  `payment_status` enum('PENDING','PAID','PARTIAL','REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `status` enum('DRAFT','CONFIRMED','PAID','PACKED','SHIPPED','DELIVERED','CANCELLED','RETURNED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'DRAFT',
  `sales_channel` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_staff_id` int unsigned DEFAULT NULL,
  `biller_id` int unsigned DEFAULT NULL,
  `biller_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `taxable_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_type` enum('PERCENTAGE','FIXED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'FIXED',
  `discount_value` decimal(10,2) DEFAULT '0.00',
  `round_off` decimal(5,2) DEFAULT '0.00',
  `change_amount` decimal(10,2) DEFAULT '0.00',
  `payment_method` enum('CASH','UPI','CARD','SPLIT','CREDIT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'CASH',
  `customer_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `created_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_sale_type` (`sale_type`),
  KEY `idx_sale_shop` (`shop_id`),
  KEY `idx_sale_date` (`created_at`),
  KEY `idx_sale_status` (`status`),
  KEY `customer_id` (`customer_id`),
  KEY `billing_staff_id` (`billing_staff_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`billing_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sales_channels`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sales_channels`;
CREATE TABLE `sales_channels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `manager_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `channel_type` enum('WHATSAPP','WEBSITE','OTHER') COLLATE utf8mb4_unicode_ci DEFAULT 'OTHER',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `seller_rolls`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `seller_rolls`;
CREATE TABLE `seller_rolls` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int unsigned NOT NULL,
  `roll_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `design_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `design_count` int DEFAULT '1',
  `quantity` int DEFAULT '0',
  `weight` decimal(10,2) DEFAULT '0.00',
  `price` decimal(12,2) DEFAULT '0.00',
  `photo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_seq` int unsigned DEFAULT NULL,
  `end_seq` int unsigned DEFAULT NULL,
  `start_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_seller_rolls_seller` (`seller_id`),
  CONSTRAINT `seller_rolls_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sellers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sellers`;
CREATE TABLE `sellers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gst_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_terms` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_type` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colors` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roll_photo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roll_count` int DEFAULT '0',
  `color_count` int DEFAULT '0',
  `design_count` int DEFAULT '0',
  `color_design_count` int DEFAULT '0',
  `roll_weight` decimal(10,2) DEFAULT '0.00',
  `roll_price` decimal(12,2) DEFAULT '0.00',
  `document_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seller_type` enum('MATERIAL_SUPPLIER','PRODUCT_SELLER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PRODUCT_SELLER',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `gstin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `setting_group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_public` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `shop_damages`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `shop_damages`;
CREATE TABLE `shop_damages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `damage_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `shop_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `tag_code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_type` enum('INWARD_TRANSIT','SHOP_BIN_HANDLING','STORAGE_HANDLING','CUSTOMER_RETURN','EXPIRED_SPOILED','OTHER') COLLATE utf8mb4_general_ci DEFAULT 'SHOP_BIN_HANDLING',
  `reference_id` int unsigned DEFAULT NULL,
  `allocation_id` int unsigned DEFAULT NULL,
  `defect_type` enum('FABRIC_DEFECT','COLOR_FADE','STITCHING_ERROR','PACKAGING_DAMAGE','WATER_STAIN','TRANSIT_DAMAGE','EXPIRED','SHOP_SOIL','OTHER') COLLATE utf8mb4_general_ci DEFAULT 'OTHER',
  `quantity` int NOT NULL DEFAULT '1',
  `cost_per_unit` decimal(10,2) DEFAULT '0.00',
  `estimated_cost` decimal(10,2) DEFAULT '0.00',
  `total_loss` decimal(10,2) DEFAULT '0.00',
  `damage_reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `defect_notes` text COLLATE utf8mb4_general_ci,
  `photo_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('LOGGED','INSPECTED','SALVAGED','WRITTEN_OFF','REPAIRED','RETURN_TO_WH','REWORK') COLLATE utf8mb4_general_ci DEFAULT 'LOGGED',
  `disposition_notes` text COLLATE utf8mb4_general_ci,
  `reported_by` int unsigned DEFAULT NULL,
  `inspected_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `product_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `damage_number` (`damage_number`),
  KEY `shop_id` (`shop_id`),
  KEY `variant_id` (`variant_id`),
  KEY `reported_by` (`reported_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `shops`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `shops`;
CREATE TABLE `shops` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_id` int unsigned DEFAULT NULL,
  `manager_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gst_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sizes`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sizes`;
CREATE TABLE `sizes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `stitching_batches`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stitching_batches`;
CREATE TABLE `stitching_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cutting_batch_id` int unsigned DEFAULT NULL,
  `program_id` int unsigned NOT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `worker_id` int unsigned DEFAULT NULL,
  `received_qty` int NOT NULL DEFAULT '0',
  `stitched_qty` int DEFAULT '0',
  `total_weight` decimal(10,2) DEFAULT '0.00',
  `rejected_qty` int DEFAULT '0',
  `rework_qty` int DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('PENDING','IN_PROGRESS','COMPLETED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `production_unit_id` int unsigned DEFAULT NULL,
  `tailor_master` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operator_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_cut_qty` int NOT NULL DEFAULT '0',
  `total_stitched_qty` int DEFAULT '0',
  `total_rejected_qty` int DEFAULT '0',
  `total_wastage_qty` int DEFAULT '0',
  `completion_date` date DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cutting_batch_id` (`cutting_batch_id`),
  KEY `program_id` (`program_id`),
  KEY `worker_id` (`worker_id`),
  CONSTRAINT `stitching_batches_ibfk_1` FOREIGN KEY (`cutting_batch_id`) REFERENCES `cutting_batches` (`id`),
  CONSTRAINT `stitching_batches_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `production_programs` (`id`),
  CONSTRAINT `stitching_batches_ibfk_3` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `stitching_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stitching_items`;
CREATE TABLE `stitching_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `stitching_batch_id` int unsigned NOT NULL,
  `variant_id` int unsigned DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `catalog_hierarchy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_qty` int NOT NULL DEFAULT '0',
  `stitched_qty` int DEFAULT '0',
  `weight_per_piece` decimal(10,3) DEFAULT '0.000',
  `weight` decimal(10,2) DEFAULT '0.00',
  `base_price` decimal(10,2) DEFAULT '0.00',
  `discount_price` decimal(10,2) DEFAULT '0.00',
  `rejected_qty` int DEFAULT '0',
  `rework_qty` int DEFAULT '0',
  `cut_quantity` int NOT NULL DEFAULT '0',
  `stitched_quantity` int NOT NULL DEFAULT '0',
  `rejected_quantity` int DEFAULT '0',
  `wastage_quantity` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `stitching_batch_id` (`stitching_batch_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `stitching_items_ibfk_1` FOREIGN KEY (`stitching_batch_id`) REFERENCES `stitching_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stitching_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `stitching_wastage_sales`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stitching_wastage_sales`;
CREATE TABLE `stitching_wastage_sales` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sale_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyer_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyer_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `buyer_company` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stitching_batch_id` int unsigned DEFAULT NULL,
  `program_id` int unsigned DEFAULT NULL,
  `waste_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THREAD_SCRAP',
  `unit_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KG',
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `rate_per_unit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('PENDING','PARTIAL','PAID','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `payment_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sale_date` date NOT NULL,
  `photo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_stitching_wastage_sales_number` (`sale_number`),
  KEY `idx_stitching_wastage_sales_batch` (`stitching_batch_id`),
  KEY `idx_stitching_wastage_sales_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `stock_adjustments`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stock_adjustments`;
CREATE TABLE `stock_adjustments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `adjustment_type` enum('INCREASE','DECREASE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by` int unsigned DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `qr_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `adj_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_id` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjustment_number` (`adjustment_number`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `variant_id` (`variant_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `stock_adjustments_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `stock_adjustments_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `stock_adjustments_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_adjustments_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `stock_balances`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stock_balances`;
CREATE TABLE `stock_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int unsigned NOT NULL,
  `location_type` enum('WAREHOUSE','SHOP','PRODUCTION_UNIT','CHANNEL','OFFICE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` int unsigned NOT NULL,
  `stock_state` enum('AVAILABLE','RESERVED','DAMAGED','RETURNED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AVAILABLE',
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stock_balance` (`variant_id`,`location_type`,`location_id`,`stock_state`),
  KEY `idx_stock_location` (`location_type`,`location_id`),
  KEY `idx_stock_variant` (`variant_id`),
  CONSTRAINT `stock_balances_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `stock_transactions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE `stock_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int unsigned NOT NULL,
  `location_type` enum('WAREHOUSE','SHOP','PRODUCTION_UNIT','CHANNEL','OFFICE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` int unsigned NOT NULL,
  `stock_state` enum('AVAILABLE','RESERVED','DAMAGED','RETURNED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reference_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `source_type` enum('PRODUCTION','PURCHASE','WAREHOUSE','SHOP','CUSTOMER','SUPPLIER','ADJUSTMENT','CHANNEL','OFFICE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` int unsigned DEFAULT NULL,
  `dest_type` enum('PRODUCTION','PURCHASE','WAREHOUSE','SHOP','CUSTOMER','SUPPLIER','ADJUSTMENT','CHANNEL','OFFICE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dest_id` int unsigned DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_txn_variant` (`variant_id`),
  KEY `idx_txn_location` (`location_type`,`location_id`),
  KEY `idx_txn_reference` (`reference_type`,`reference_id`),
  KEY `idx_txn_type` (`transaction_type`),
  KEY `idx_txn_date` (`created_at`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sub_subcategories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sub_subcategories`;
CREATE TABLE `sub_subcategories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `subcategory_id` int unsigned NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sub_subcat_slug` (`subcategory_id`,`slug`),
  CONSTRAINT `sub_subcategories_ibfk_1` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `subcategories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `subcategories`;
CREATE TABLE `subcategories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subcat_slug` (`category_id`,`slug`),
  CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `transfer_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `transfer_items`;
CREATE TABLE `transfer_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `quantity` int NOT NULL,
  `received_qty` int DEFAULT '0',
  `damaged_qty` int DEFAULT '0',
  `requested_qty` int NOT NULL,
  `transferred_qty` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `transfer_id` (`transfer_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `transfer_items_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `warehouse_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transfer_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `units`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `units`;
CREATE TABLE `units` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abbreviation` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_permissions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE `user_permissions` (
  `user_id` int unsigned NOT NULL,
  `permission_id` int unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`permission_id`),
  KEY `idx_user_perms` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `emp_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` int unsigned NOT NULL,
  `department` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'GENERAL',
  `production_unit_id` int unsigned DEFAULT NULL,
  `warehouse_id` int unsigned DEFAULT NULL,
  `shop_id` int unsigned DEFAULT NULL,
  `reports_access` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'ALL',
  `is_active` tinyint(1) DEFAULT '1',
  `must_change_password` tinyint(1) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_allocations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_allocations`;
CREATE TABLE `warehouse_allocations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `allocation_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `destination_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SHOP',
  `destination_id` int unsigned DEFAULT NULL,
  `shop_id` int unsigned DEFAULT NULL,
  `channel_id` int unsigned DEFAULT NULL,
  `requested_by` int unsigned DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `status` enum('REQUESTED','APPROVED','PICKED','PACKED','DISPATCHED','DELIVERED','PARTIAL_RECEIVED','RECEIVED','COMPLETED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REQUESTED',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `allocation_number` (`allocation_number`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `warehouse_allocations_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `warehouse_allocations_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouse_allocations_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_categories`;
CREATE TABLE `warehouse_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NORMAL',
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `wh_category_attributes`
-- --------------------------------------------------------
CREATE TABLE `wh_category_attributes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `attr_name` varchar(100) NOT NULL,
  `attr_type` enum('TEXT','NUMBER','SELECT','MULTI_SELECT') NOT NULL DEFAULT 'TEXT',
  `attr_options` text,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cat_attrs` (`category_id`,`subcategory_id`,`sub_subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `wh_category_default_products`
-- --------------------------------------------------------
CREATE TABLE `wh_category_default_products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `subcategory_id` int unsigned DEFAULT NULL,
  `sub_subcategory_id` int unsigned DEFAULT NULL,
  `product_template_name` varchar(255) NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `default_gst_pct` decimal(5,2) NOT NULL DEFAULT '5.00',
  `default_weight_kg` decimal(8,3) NOT NULL DEFAULT '0.000',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cat_defaults` (`category_id`,`subcategory_id`,`sub_subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_damages`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_damages`;
CREATE TABLE `warehouse_damages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `damage_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `tag_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_type` enum('INWARD_TRANSIT','PRODUCTION_INWARD','STORAGE_HANDLING','CUSTOMER_RETURN','EXPIRED_SPOILED','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'STORAGE_HANDLING',
  `reference_id` int unsigned DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `cost_per_unit` decimal(10,2) DEFAULT '0.00',
  `total_loss` decimal(10,2) DEFAULT '0.00',
  `damage_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `defect_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `photo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('LOGGED','INSPECTED','SALVAGED','WRITTEN_OFF','REPAIRED','REWORK') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'LOGGED',
  `reported_by` int unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `production_batch_id` int unsigned DEFAULT NULL,
  `purchase_inward_id` int unsigned DEFAULT NULL,
  `defect_type` enum('FABRIC_DEFECT','COLOR_FADE','STITCHING_ERROR','PACKAGING_DAMAGE','WATER_STAIN','TRANSIT_DAMAGE','EXPIRED','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'OTHER',
  `estimated_cost` decimal(10,2) DEFAULT '0.00',
  `disposition_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `inspected_by` int unsigned DEFAULT NULL,
  `damaged_qty` int NOT NULL DEFAULT '1',
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `product_id` int unsigned DEFAULT NULL,
  `production_unit_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `damage_number` (`damage_number`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `variant_id` (`variant_id`),
  KEY `idx_warehouse_damages_production_unit` (`production_unit_id`),
  KEY `reported_by` (`reported_by`),
  CONSTRAINT `warehouse_damages_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `warehouse_damages_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`),
  CONSTRAINT `warehouse_damages_ibfk_3` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_return_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_return_items`;
CREATE TABLE `warehouse_return_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_return_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `returned_qty` int NOT NULL,
  `good_qty` int DEFAULT '0',
  `damaged_qty` int DEFAULT '0',
  `defective_qty` int DEFAULT '0',
  `classification` enum('GOOD','DAMAGED','DEFECTIVE','WRONG_PRODUCT','WRONG_SIZE','MISSING') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'GOOD',
  `scanned_qr_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `warehouse_return_id` (`warehouse_return_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `warehouse_return_items_ibfk_1` FOREIGN KEY (`warehouse_return_id`) REFERENCES `warehouse_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warehouse_return_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_returns`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_returns`;
CREATE TABLE `warehouse_returns` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_type` enum('SHOP','WEBSITE','WHATSAPP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` int unsigned DEFAULT NULL,
  `warehouse_id` int unsigned NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `inspected_by` int unsigned DEFAULT NULL,
  `status` enum('RECEIVED','INSPECTING','CLASSIFIED','COMPLETED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'RECEIVED',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `destination_warehouse_id` int unsigned NOT NULL,
  `received_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_number` (`return_number`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `inspected_by` (`inspected_by`),
  CONSTRAINT `warehouse_returns_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `warehouse_returns_ibfk_2` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_sub_subcategories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_sub_subcategories`;
CREATE TABLE `warehouse_sub_subcategories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `subcategory_id` int unsigned NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `unique_subcat_slug` (`subcategory_id`, `slug`),
  KEY `subcategory_id` (`subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_subcategories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_subcategories`;
CREATE TABLE `warehouse_subcategories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `unique_category_slug` (`category_id`, `slug`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_suppliers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_suppliers`;
CREATE TABLE `warehouse_suppliers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `company` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gst_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seller_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'PRODUCT_SELLER',
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_terms` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `material_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `document_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_warehouse_suppliers_brand` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_transfers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_transfers`;
CREATE TABLE `warehouse_transfers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_type` enum('WAREHOUSE','SHOP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` int unsigned NOT NULL,
  `destination_type` enum('WAREHOUSE','SHOP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_id` int unsigned NOT NULL,
  `requested_by` int unsigned DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `status` enum('REQUESTED','APPROVED','IN_TRANSIT','RECEIVED','COMPLETED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'REQUESTED',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_at` datetime DEFAULT NULL,
  `source_warehouse_id` int unsigned DEFAULT NULL,
  `dest_warehouse_id` int unsigned DEFAULT NULL,
  `vehicle_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_number` (`transfer_number`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `warehouse_transfers_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warehouse_transfers_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouses`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_id` int unsigned DEFAULT NULL,
  `manager_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  CONSTRAINT `warehouses_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `wastage_sales`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `wastage_sales`;
CREATE TABLE `wastage_sales` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `sale_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyer_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyer_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `buyer_company` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cutting_batch_id` int unsigned DEFAULT NULL,
  `program_id` int unsigned DEFAULT NULL,
  `wastage_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FABRIC_SCRAP',
  `weight` decimal(10,2) NOT NULL DEFAULT '0.00',
  `rate_per_kg` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('PENDING','PARTIAL','PAID','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `payment_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sale_date` date NOT NULL,
  `photo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_wastage_sales_number` (`sale_number`),
  KEY `idx_wastage_sales_batch` (`cutting_batch_id`),
  KEY `idx_wastage_sales_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `production_warehouse_reworks`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `production_warehouse_reworks`;
CREATE TABLE `production_warehouse_reworks` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `warehouse_qr_codes`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `warehouse_qr_codes`;
CREATE TABLE `warehouse_qr_codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `qr_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `variant_id` int unsigned DEFAULT NULL,
  `warehouse_id` int unsigned DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `inwarded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_warehouse_qr_code` (`qr_code`),
  KEY `idx_warehouse_qr_variant` (`variant_id`),
  KEY `idx_warehouse_qr_warehouse` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
