<?php
/**
 * Create warehouse catalog attribute and default-product tables.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();
    $db->exec("
        CREATE TABLE IF NOT EXISTS `wh_category_attributes` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `category_id` INT UNSIGNED NOT NULL,
            `subcategory_id` INT UNSIGNED DEFAULT NULL,
            `sub_subcategory_id` INT UNSIGNED DEFAULT NULL,
            `attr_name` VARCHAR(100) NOT NULL,
            `attr_type` ENUM('TEXT','NUMBER','SELECT','MULTI_SELECT') NOT NULL DEFAULT 'TEXT',
            `attr_options` TEXT DEFAULT NULL,
            `is_required` TINYINT(1) NOT NULL DEFAULT 0,
            `sort_order` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_cat_attrs` (`category_id`, `subcategory_id`, `sub_subcategory_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "Verified `wh_category_attributes`.\n";

    $db->exec("
        CREATE TABLE IF NOT EXISTS `wh_category_default_products` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `category_id` INT UNSIGNED NOT NULL,
            `subcategory_id` INT UNSIGNED DEFAULT NULL,
            `sub_subcategory_id` INT UNSIGNED DEFAULT NULL,
            `product_template_name` VARCHAR(255) NOT NULL,
            `hsn_code` VARCHAR(20) DEFAULT NULL,
            `default_gst_pct` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
            `default_weight_kg` DECIMAL(8,3) NOT NULL DEFAULT 0.000,
            `sort_order` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_cat_defaults` (`category_id`, `subcategory_id`, `sub_subcategory_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "Verified `wh_category_default_products`.\n";
    echo "SUCCESS: Warehouse catalog attribute tables are ready.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Database Error: " . $e->getMessage() . "\n");
    exit(1);
}
