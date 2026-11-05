<?php
/**
 * Migration: Create and Update Expenses & Expense Categories Tables
 * - expense_categories (Category masters with department scope)
 * - expenses (Multi-channel & multi-unit operational expense records)
 */

define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();

    // 1. expense_categories
    $db->exec("
        CREATE TABLE IF NOT EXISTS `expense_categories` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
            `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
            `department_scope` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ALL',
            `description` text COLLATE utf8mb4_unicode_ci,
            `is_active` tinyint(1) NOT NULL DEFAULT '1',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `expense_categories` ready.\n";

    // 2. expenses
    $db->exec("
        CREATE TABLE IF NOT EXISTS `expenses` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `expense_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
            `location_type` enum('PRODUCTION','WAREHOUSE','SHOP','CHANNEL','OFFICE','WHATSAPP','WEBSITE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PRODUCTION',
            `location_id` int unsigned DEFAULT NULL,
            `location_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
            `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `description` text COLLATE utf8mb4_unicode_ci,
            `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
            `unit_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PCS',
            `unit_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
            `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
            `total_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
            `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
            `payment_status` enum('PAID','PENDING','PARTIAL','APPROVED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PAID',
            `payment_method` enum('CASH','UPI','BANK_TRANSFER','CARD','PETTY_CASH','CHEQUE','CREDIT','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
            `payment_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `paid_to` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
            `vendor_phone` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `vendor_company` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `invoice_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `expense_date` date NOT NULL,
            `receipt_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `notes` text COLLATE utf8mb4_unicode_ci,
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
    ");

    // Ensure location_type and payment_method enums are up to date
    $db->exec("ALTER TABLE `expenses` MODIFY COLUMN `location_type` ENUM('PRODUCTION','WAREHOUSE','SHOP','CHANNEL','OFFICE','WHATSAPP','WEBSITE') NOT NULL DEFAULT 'PRODUCTION'");
    $db->exec("ALTER TABLE `expenses` MODIFY COLUMN `payment_method` ENUM('CASH','UPI','BANK_TRANSFER','CARD','PETTY_CASH','CHEQUE','CREDIT','OTHER') NOT NULL DEFAULT 'CASH'");
    echo "✓ Table `expenses` ready and updated.\n";

    // 3. Seed default categories if table is empty
    $count = (int) $db->query("SELECT COUNT(*) FROM `expense_categories`")->fetchColumn();
    if ($count === 0) {
        $db->exec("
            INSERT INTO `expense_categories` (`name`, `code`, `department_scope`, `description`, `is_active`) VALUES
            ('Machinery Spares & Parts', 'PROD_SPARES', 'PRODUCTION', 'Cutting blades, stitching needles, motors, belts, looper parts', 1),
            ('Consumables & Lubricants', 'PROD_CONSUMABLES', 'PRODUCTION', 'Machine oil, thread spools, chalk, fusing tapes, scissors', 1),
            ('Machine Repairs & Maintenance', 'PROD_MAINTENANCE', 'PRODUCTION', 'External technician labor, overlock servicing, generator service', 1),
            ('Factory Utilities & Power', 'PROD_UTILITIES', 'PRODUCTION', 'Electricity bills, diesel for generator, water supply', 1),
            ('Packaging Materials & Cartons', 'WH_PACKAGING', 'WAREHOUSE', 'Corrugated boxes, bubble wrap, polybags, brown tape, barcode ribbons', 1),
            ('Logistics & Freight Shipping', 'WH_FREIGHT', 'WAREHOUSE', 'Inter-unit vehicle transport, tempo rent, courier charges', 1),
            ('Warehouse Handling & Maintenance', 'WH_MAINTENANCE', 'WAREHOUSE', 'Forklift fuel, pallet repairs, rack maintenance, pest control', 1),
            ('Shop Petty Cash & Refreshments', 'SHOP_PETTY', 'SHOP', 'Daily staff tea, coffee, drinking water, customer snacks', 1),
            ('Store Utilities & Cleaning', 'SHOP_UTILITIES', 'SHOP', 'Store electricity, cleaning materials, air freshener, bulb replacement', 1),
            ('Store Rent & Maintenance', 'SHOP_RENT', 'SHOP', 'Retail outlet monthly rent, AC service, glass cleaning', 1),
            ('Digital Ads & Meta Marketing', 'CHAN_ADS', 'CHANNEL', 'Facebook/Instagram ads, Google ads, influencer promotion', 1),
            ('Software & API Charges', 'CHAN_SOFTWARE', 'CHANNEL', 'WhatsApp Cloud API fees, Shopify/WooCommerce plugins, SMS gateway', 1),
            ('Courier & Parcel Dispatch', 'CHAN_COURIER', 'CHANNEL', 'Shiprocket, DTDC, Delhivery customer parcel shipping costs', 1),
            ('Office Supplies & Stationery', 'OFFICE_SUPPLIES', 'OFFICE', 'A4 printing paper, printer ink, pens, registers, staples', 1),
            ('Professional & Legal Fees', 'OFFICE_LEGAL', 'OFFICE', 'Chartered Accountant fees, GST filing consultancy, auditing', 1),
            ('Staff Welfare & General Admin', 'OFFICE_GENERAL', 'OFFICE', 'Team lunches, staff first aid, courier receiving, miscellaneous', 1);
        ");
        echo "✓ Seeded default expense categories.\n";
    }

    echo "\nAll Expense migrations executed successfully!\n";
} catch (\PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
