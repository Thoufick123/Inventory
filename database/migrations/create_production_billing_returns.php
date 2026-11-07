<?php
/**
 * Create QR-tracked returns and exchanges for Production Billing.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $columnExists = static function (string $table, string $column): bool {
        return (bool)Database::fetchOne(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
    };

    if (!$columnExists('production_bill_item_tags', 'status')) {
        Database::execute(
            "ALTER TABLE production_bill_item_tags
             ADD COLUMN status enum('BILLED','RETURNED','DAMAGED') NOT NULL DEFAULT 'BILLED' AFTER tag_code"
        );
    }

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_bill_returns (
            id int unsigned NOT NULL AUTO_INCREMENT,
            return_number varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            bill_id int unsigned NOT NULL,
            action_type enum('RETURN','EXCHANGE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            reason varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            refund_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            new_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            difference_amount decimal(12,2) NOT NULL DEFAULT '0.00',
            payment_action enum('COLLECT','REFUND','NONE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NONE',
            settlement_mode enum('CASH','UPI','CREDIT_NOTE','NONE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
            notes text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            processed_by int unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_bill_return_number (return_number),
            KEY idx_production_bill_returns_bill (bill_id, created_at),
            CONSTRAINT production_bill_returns_ibfk_1 FOREIGN KEY (bill_id) REFERENCES production_bills (id),
            CONSTRAINT production_bill_returns_ibfk_2 FOREIGN KEY (processed_by) REFERENCES users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_bill_return_items (
            id int unsigned NOT NULL AUTO_INCREMENT,
            return_id int unsigned NOT NULL,
            original_bill_item_id int unsigned NOT NULL,
            original_qc_item_id int unsigned NOT NULL,
            original_variant_id int unsigned DEFAULT NULL,
            original_tag_code varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            item_condition enum('GOOD','DAMAGED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GOOD',
            original_product_name varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            original_sku varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            original_size_name varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            original_color_name varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            original_unit_price decimal(12,2) NOT NULL DEFAULT '0.00',
            replacement_qc_item_id int unsigned DEFAULT NULL,
            replacement_variant_id int unsigned DEFAULT NULL,
            replacement_tag_code varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            replacement_product_name varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            replacement_sku varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            replacement_size_name varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            replacement_color_name varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            replacement_unit_price decimal(12,2) NOT NULL DEFAULT '0.00',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_return_original_tag (return_id, original_tag_code),
            KEY idx_production_return_item_tag (original_tag_code),
            KEY idx_production_return_replacement_tag (replacement_tag_code),
            CONSTRAINT production_bill_return_items_ibfk_1 FOREIGN KEY (return_id) REFERENCES production_bill_returns (id) ON DELETE CASCADE,
            CONSTRAINT production_bill_return_items_ibfk_2 FOREIGN KEY (original_bill_item_id) REFERENCES production_bill_items (id),
            CONSTRAINT production_bill_return_items_ibfk_3 FOREIGN KEY (original_variant_id) REFERENCES product_variants (id) ON DELETE SET NULL,
            CONSTRAINT production_bill_return_items_ibfk_4 FOREIGN KEY (replacement_variant_id) REFERENCES product_variants (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    echo "Production Billing Returns & Exchanges tables are ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'Production Billing Returns migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}