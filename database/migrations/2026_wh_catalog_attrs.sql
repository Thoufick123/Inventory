-- ============================================================
-- Migration: Warehouse Catalog Attribute System
-- Created: 2026-10-22
-- ============================================================

CREATE TABLE IF NOT EXISTS `wh_category_attributes` (
    `id`                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `category_id`        INT UNSIGNED     NOT NULL,
    `subcategory_id`     INT UNSIGNED     NULL DEFAULT NULL,
    `sub_subcategory_id` INT UNSIGNED     NULL DEFAULT NULL,
    `attr_name`          VARCHAR(100)     NOT NULL,
    `attr_type`          ENUM('TEXT','NUMBER','SELECT','MULTI_SELECT') NOT NULL DEFAULT 'TEXT',
    `attr_options`       TEXT             NULL,
    `is_required`        TINYINT(1)       NOT NULL DEFAULT 0,
    `sort_order`         INT              NOT NULL DEFAULT 0,
    `is_active`          TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cat_attrs` (`category_id`, `subcategory_id`, `sub_subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `wh_category_default_products` (
    `id`                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `category_id`           INT UNSIGNED  NOT NULL,
    `subcategory_id`        INT UNSIGNED  NULL DEFAULT NULL,
    `sub_subcategory_id`    INT UNSIGNED  NULL DEFAULT NULL,
    `product_template_name` VARCHAR(255)  NOT NULL,
    `hsn_code`              VARCHAR(20)   NULL DEFAULT NULL,
    `default_gst_pct`       DECIMAL(5,2)  NOT NULL DEFAULT 5.00,
    `default_weight_kg`     DECIMAL(8,3)  NOT NULL DEFAULT 0.000,
    `sort_order`            INT           NOT NULL DEFAULT 0,
    `is_active`             TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cat_defaults` (`category_id`, `subcategory_id`, `sub_subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add category_type to warehouse_categories if not exists
ALTER TABLE `warehouse_categories` ADD COLUMN IF NOT EXISTS `category_type` VARCHAR(50) NOT NULL DEFAULT 'NORMAL' AFTER `name`;
