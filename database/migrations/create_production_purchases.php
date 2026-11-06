<?php
/**
 * Migration: Separate Production supplier profiles from purchase and stock records.
 * Existing fabric rolls are imported as legacy purchases without changing their QR ranges.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $materialTypeColumn = Database::fetchOne(
        "SELECT COUNT(*) AS column_count
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
        ['seller_rolls', 'material_type']
    );
    if ((int)($materialTypeColumn['column_count'] ?? 0) === 0) {
        Database::execute(
            "ALTER TABLE seller_rolls
             ADD COLUMN material_type varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
             AFTER price"
        );
    }

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_purchases (
            id int unsigned NOT NULL AUTO_INCREMENT,
            purchase_number varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            seller_id int unsigned NOT NULL,
            purchase_date date NOT NULL,
            invoice_number varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            document_url varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            notes varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            total_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            created_by int unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_purchase_number (purchase_number),
            KEY idx_production_purchases_seller_date (seller_id, purchase_date),
            CONSTRAINT production_purchases_ibfk_1 FOREIGN KEY (seller_id) REFERENCES sellers (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_purchase_items (
            id int unsigned NOT NULL AUTO_INCREMENT,
            purchase_id int unsigned NOT NULL,
            item_type enum('FABRIC_ROLL','GENERAL_SUPPLY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            item_name varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            quantity decimal(12,3) NOT NULL,
            unit varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            weight_kg decimal(10,3) DEFAULT NULL,
            unit_price decimal(12,2) NOT NULL DEFAULT '0.00',
            total_price decimal(12,2) NOT NULL DEFAULT '0.00',
            gst_type varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EXEMPTED',
            tax_input_type varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PERCENT',
            sgst_value decimal(12,3) NOT NULL DEFAULT '0.000',
            cgst_value decimal(12,3) NOT NULL DEFAULT '0.000',
            igst_value decimal(12,3) NOT NULL DEFAULT '0.000',
            sgst_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            cgst_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            igst_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            roll_number varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            color varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            design_name varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            design_count int DEFAULT NULL,
            seller_roll_id int unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_purchase_seller_roll (seller_roll_id),
            KEY idx_production_purchase_items_purchase (purchase_id),
            KEY idx_production_purchase_items_stock (item_type, item_name, unit),
            CONSTRAINT production_purchase_items_ibfk_1 FOREIGN KEY (purchase_id) REFERENCES production_purchases (id) ON DELETE CASCADE,
            CONSTRAINT production_purchase_items_ibfk_2 FOREIGN KEY (seller_roll_id) REFERENCES seller_rolls (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "UPDATE seller_rolls sr
         JOIN sellers s ON s.id = sr.seller_id
         SET sr.material_type = s.material_type
         WHERE (sr.material_type IS NULL OR sr.material_type = '')
           AND s.material_type IS NOT NULL AND s.material_type <> ''"
    );

    Database::execute(
        "INSERT IGNORE INTO production_purchases
            (purchase_number, seller_id, purchase_date, document_url, notes, total_amount, created_at)
         SELECT CONCAT('LEGACY-ROLL-', s.id), s.id, COALESCE(DATE(MIN(sr.created_at)), CURDATE()),
                MAX(s.document_url), 'Imported existing fabric rolls', SUM(COALESCE(sr.price, 0)),
                COALESCE(MIN(sr.created_at), CURRENT_TIMESTAMP)
         FROM sellers s
         JOIN seller_rolls sr ON sr.seller_id = s.id
         WHERE s.seller_type = 'MATERIAL_SUPPLIER'
         GROUP BY s.id"
    );

    Database::execute(
        "INSERT IGNORE INTO production_purchase_items
            (purchase_id, item_type, item_name, quantity, unit, weight_kg, unit_price, total_price,
             roll_number, color, design_name, design_count, seller_roll_id, created_at)
         SELECT pp.id, 'FABRIC_ROLL',
                COALESCE(NULLIF(sr.material_type, ''), NULLIF(s.material_type, ''), 'FABRIC'),
                GREATEST(COALESCE(sr.quantity, 1), 1), 'PCS', sr.weight, sr.price, sr.price,
                sr.roll_number, sr.color, sr.design_name, sr.design_count, sr.id, sr.created_at
         FROM seller_rolls sr
         JOIN sellers s ON s.id = sr.seller_id
         JOIN production_purchases pp ON pp.purchase_number = CONCAT('LEGACY-ROLL-', s.id)
         WHERE s.seller_type = 'MATERIAL_SUPPLIER'"
    );

    echo "Production Purchases are ready; existing supplier rolls were preserved and imported.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Production Purchases migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
