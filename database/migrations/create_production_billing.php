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
            buyer_type enum('COMPANY','CUSTOMER') NOT NULL DEFAULT 'COMPANY',
            company_name varchar(200) DEFAULT NULL,
            company_address text,
            company_phone varchar(30) DEFAULT NULL,
            company_email varchar(150) DEFAULT NULL,
            company_gstin varchar(30) DEFAULT NULL,
            customer_name varchar(150) DEFAULT NULL,
            customer_phone varchar(20) DEFAULT NULL,
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

    $columnExists = static function (string $table, string $column): bool {
        return (bool)Database::fetchOne(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
    };

    if (!$columnExists('production_bills', 'buyer_type')) {
        Database::execute(
            "ALTER TABLE production_bills
             ADD COLUMN buyer_type enum('COMPANY','CUSTOMER') NOT NULL DEFAULT 'COMPANY' AFTER bill_number"
        );
    }
    Database::execute("ALTER TABLE production_bills MODIFY company_name varchar(200) DEFAULT NULL");
    Database::execute("ALTER TABLE production_bills MODIFY customer_name varchar(150) DEFAULT NULL");
    Database::execute("ALTER TABLE production_bills MODIFY customer_phone varchar(20) DEFAULT NULL");

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
            gst_type enum('SGST','IGST','CGST','BOTH','EXEMPTED') NOT NULL DEFAULT 'BOTH',
            tax_input_type enum('PERCENT','FIXED') NOT NULL DEFAULT 'PERCENT',
            sgst_rate decimal(7,3) NOT NULL DEFAULT '0.000',
            cgst_rate decimal(7,3) NOT NULL DEFAULT '0.000',
            igst_rate decimal(7,3) NOT NULL DEFAULT '0.000',
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

    $itemColumns = [
        'gst_type' => "ALTER TABLE production_bill_items ADD COLUMN gst_type enum('SGST','IGST','CGST','BOTH','EXEMPTED') NOT NULL DEFAULT 'BOTH' AFTER gst_percentage",
        'tax_input_type' => "ALTER TABLE production_bill_items ADD COLUMN tax_input_type enum('PERCENT','FIXED') NOT NULL DEFAULT 'PERCENT' AFTER gst_type",
        'sgst_rate' => "ALTER TABLE production_bill_items ADD COLUMN sgst_rate decimal(7,3) NOT NULL DEFAULT '0.000' AFTER tax_input_type",
        'cgst_rate' => "ALTER TABLE production_bill_items ADD COLUMN cgst_rate decimal(7,3) NOT NULL DEFAULT '0.000' AFTER sgst_rate",
        'igst_rate' => "ALTER TABLE production_bill_items ADD COLUMN igst_rate decimal(7,3) NOT NULL DEFAULT '0.000' AFTER cgst_rate",
    ];
    foreach ($itemColumns as $column => $alterSql) {
        if (!$columnExists('production_bill_items', $column)) {
            Database::execute($alterSql);
        }
    }

    Database::execute(
        "UPDATE production_bill_items
         SET sgst_rate = gst_percentage / 2, cgst_rate = gst_percentage / 2,
             gst_type = CASE WHEN gst_percentage > 0 THEN 'BOTH' ELSE 'EXEMPTED' END
         WHERE sgst_rate = 0 AND cgst_rate = 0 AND igst_rate = 0"
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
