<?php
/**
 * Create Production Billing tables and permissions.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_bills (
            id int unsigned NOT NULL AUTO_INCREMENT,
            bill_number varchar(50) NOT NULL,
            company_name varchar(200) NOT NULL,
            company_address text,
            company_phone varchar(30) DEFAULT NULL,
            company_email varchar(150) DEFAULT NULL,
            company_gstin varchar(30) DEFAULT NULL,
            customer_name varchar(150) NOT NULL,
            customer_phone varchar(20) NOT NULL,
            customer_address text,
            customer_city varchar(100) DEFAULT NULL,
            customer_state varchar(100) DEFAULT NULL,
            customer_gstin varchar(30) DEFAULT NULL,
            bill_date date NOT NULL,
            payment_method varchar(30) NOT NULL DEFAULT 'CASH',
            payment_status enum('PENDING','PAID') NOT NULL DEFAULT 'PAID',
            tax_type enum('CGST_SGST','IGST') NOT NULL DEFAULT 'CGST_SGST',
            cash_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            upi_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            upi_reference varchar(150) DEFAULT NULL,
            gross_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            discount_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            subtotal decimal(12,2) NOT NULL DEFAULT '0.00',
            cgst_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            sgst_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            igst_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            tax_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            total_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            notes text,
            created_by int unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_bills_number (bill_number),
            KEY idx_production_bills_date (bill_date),
            KEY idx_production_bills_company (company_name),
            KEY idx_production_bills_customer_phone (customer_phone),
            CONSTRAINT production_bills_ibfk_1 FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_bill_items (
            id int unsigned NOT NULL AUTO_INCREMENT,
            bill_id int unsigned NOT NULL,
            qc_item_id int unsigned NOT NULL,
            variant_id int unsigned DEFAULT NULL,
            product_name varchar(255) NOT NULL,
            sku varchar(100) DEFAULT NULL,
            size_name varchar(100) DEFAULT NULL,
            color_name varchar(100) DEFAULT NULL,
            quantity int unsigned NOT NULL,
            mrp decimal(12,2) NOT NULL DEFAULT '0.00',
            unit_price decimal(12,2) NOT NULL DEFAULT '0.00',
            discount_percentage decimal(7,3) NOT NULL DEFAULT '0.000',
            discount_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            gst_percentage decimal(7,3) NOT NULL DEFAULT '0.000',
            taxable_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            cgst_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            sgst_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            igst_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            tax_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            total_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_production_bill_items_bill (bill_id),
            KEY idx_production_bill_items_qc (qc_item_id),
            CONSTRAINT production_bill_items_ibfk_1 FOREIGN KEY (bill_id) REFERENCES production_bills (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_bill_item_tags (
            id int unsigned NOT NULL AUTO_INCREMENT,
            bill_id int unsigned NOT NULL,
            bill_item_id int unsigned NOT NULL,
            qc_item_id int unsigned NOT NULL,
            tag_code varchar(100) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_bill_tag (tag_code),
            KEY idx_production_bill_tags_bill (bill_id),
            KEY idx_production_bill_tags_item (bill_item_id),
            KEY idx_production_bill_tags_qc (qc_item_id),
            CONSTRAINT production_bill_tags_ibfk_1 FOREIGN KEY (bill_id) REFERENCES production_bills (id) ON DELETE CASCADE,
            CONSTRAINT production_bill_tags_ibfk_2 FOREIGN KEY (bill_item_id) REFERENCES production_bill_items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    foreach (['view', 'create', 'edit', 'delete', 'print'] as $action) {
        Database::execute(
            "INSERT INTO permissions (module, action, description, slug, created_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE description = VALUES(description), slug = VALUES(slug)",
            ['production_billing', $action, ucfirst($action) . ' Production Bills', 'production_billing.' . $action]
        );
    }

    echo "Production Billing tables and permissions are ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Production Billing migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
