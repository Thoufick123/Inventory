<?php
/**
 * Phase 6 migration (additive & idempotent):
 * 1. Allow batch-linked Sale Bills and billing-style QR scan batches (`source_type = 'SCAN'`).
 * 2. Track per-item origin metadata (`qc_item_id`, `source_kind`, `source_location_type/id`) in `production_batch_items`.
 * 3. Scope `production_bill_item_tags` uniqueness per `concern_id` so receiving concerns can retail-sell inwarded QR tags.
 * 4. Link receiving-concern `production_purchases` (`item_type = 'FINISHED_GOODS'`) and sender-concern `sellers.linked_concern_id`.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $tableExists = static function (string $table): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
    };
    $columnExists = static function (string $table, string $column): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
    };
    $indexExists = static function (string $table, string $indexName): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $indexName]
        );
    };
    $uniqueIndexExists = static function (string $table, string $indexName): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? AND Non_unique = 0",
            [$table, $indexName]
        );
    };
    $constraintExists = static function (string $table, string $constraintName): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$table, $constraintName]
        );
    };

    if ($tableExists('production_batches')) {
        Database::execute(
            "ALTER TABLE production_batches
             MODIFY source_type ENUM('QC','INWARD','SCAN') NOT NULL DEFAULT 'QC'"
        );
        if (!$columnExists('production_batches', 'sale_bill_id')) {
            Database::execute(
                "ALTER TABLE production_batches ADD COLUMN sale_bill_id INT UNSIGNED NULL DEFAULT NULL AFTER destination_concern_id"
            );
        }
        if (!$indexExists('production_batches', 'idx_production_batches_sale_bill')) {
            Database::execute(
                "ALTER TABLE production_batches ADD INDEX idx_production_batches_sale_bill (sale_bill_id)"
            );
        }
        if ($tableExists('production_bills') && !$constraintExists('production_batches', 'fk_production_batches_sale_bill')) {
            Database::execute(
                "ALTER TABLE production_batches
                 ADD CONSTRAINT fk_production_batches_sale_bill
                 FOREIGN KEY (sale_bill_id) REFERENCES production_bills (id) ON DELETE SET NULL"
            );
        }
    }

    if ($tableExists('production_batch_items')) {
        if (!$columnExists('production_batch_items', 'qc_item_id')) {
            Database::execute(
                "ALTER TABLE production_batch_items ADD COLUMN qc_item_id INT UNSIGNED NULL DEFAULT NULL AFTER variant_id"
            );
        }
        if (!$columnExists('production_batch_items', 'source_kind')) {
            Database::execute(
                "ALTER TABLE production_batch_items ADD COLUMN source_kind ENUM('QC','INWARD') NOT NULL DEFAULT 'QC' AFTER qc_item_id"
            );
        }
        if (!$columnExists('production_batch_items', 'source_location_type')) {
            Database::execute(
                "ALTER TABLE production_batch_items ADD COLUMN source_location_type ENUM('CONCERN','PRODUCTION_UNIT') NULL DEFAULT NULL AFTER source_kind"
            );
        }
        if (!$columnExists('production_batch_items', 'source_location_id')) {
            Database::execute(
                "ALTER TABLE production_batch_items ADD COLUMN source_location_id INT UNSIGNED NULL DEFAULT NULL AFTER source_location_type"
            );
        }
        if (!$indexExists('production_batch_items', 'idx_production_batch_items_qc_item')) {
            Database::execute(
                "ALTER TABLE production_batch_items ADD INDEX idx_production_batch_items_qc_item (qc_item_id)"
            );
        }
    }

    if ($tableExists('production_bills')) {
        if (!$columnExists('production_bills', 'bill_source')) {
            Database::execute(
                "ALTER TABLE production_bills ADD COLUMN bill_source ENUM('DIRECT','BATCH_OUTWARD') NOT NULL DEFAULT 'DIRECT' AFTER concern_id"
            );
        }
        if (!$columnExists('production_bills', 'source_batch_id')) {
            Database::execute(
                "ALTER TABLE production_bills ADD COLUMN source_batch_id INT UNSIGNED NULL DEFAULT NULL AFTER bill_source"
            );
        }
        if (!$columnExists('production_bills', 'destination_concern_id')) {
            Database::execute(
                "ALTER TABLE production_bills ADD COLUMN destination_concern_id INT UNSIGNED NULL DEFAULT NULL AFTER source_batch_id"
            );
        }
        if (!$indexExists('production_bills', 'idx_production_bills_source_batch')) {
            Database::execute(
                "ALTER TABLE production_bills ADD INDEX idx_production_bills_source_batch (source_batch_id)"
            );
        }
        if (!$indexExists('production_bills', 'idx_production_bills_dest_concern')) {
            Database::execute(
                "ALTER TABLE production_bills ADD INDEX idx_production_bills_dest_concern (destination_concern_id)"
            );
        }
    }

    if ($tableExists('production_bill_item_tags')) {
        if (!$columnExists('production_bill_item_tags', 'concern_id')) {
            Database::execute(
                "ALTER TABLE production_bill_item_tags ADD COLUMN concern_id INT UNSIGNED NULL DEFAULT NULL AFTER bill_item_id"
            );
        }
        Database::execute(
            "UPDATE production_bill_item_tags t
             JOIN production_bills pb ON pb.id = t.bill_id
             SET t.concern_id = pb.concern_id
             WHERE t.concern_id IS NULL AND pb.concern_id IS NOT NULL"
        );
        if (!$indexExists('production_bill_item_tags', 'idx_production_bill_tag_code')) {
            Database::execute(
                "ALTER TABLE production_bill_item_tags ADD INDEX idx_production_bill_tag_code (tag_code)"
            );
        }
        if (!$indexExists('production_bill_item_tags', 'idx_production_bill_tags_concern')) {
            Database::execute(
                "ALTER TABLE production_bill_item_tags ADD INDEX idx_production_bill_tags_concern (concern_id)"
            );
        }
        if ($uniqueIndexExists('production_bill_item_tags', 'uniq_production_bill_tag')) {
            Database::execute(
                "ALTER TABLE production_bill_item_tags DROP INDEX uniq_production_bill_tag"
            );
        }
        if (!$indexExists('production_bill_item_tags', 'uniq_production_bill_concern_tag')) {
            Database::execute(
                "ALTER TABLE production_bill_item_tags ADD UNIQUE KEY uniq_production_bill_concern_tag (concern_id, tag_code)"
            );
        }
    }

    if ($tableExists('sellers')) {
        if (!$columnExists('sellers', 'linked_concern_id')) {
            Database::execute(
                "ALTER TABLE sellers ADD COLUMN linked_concern_id INT UNSIGNED NULL DEFAULT NULL AFTER concern_id"
            );
        }
        if (!$indexExists('sellers', 'idx_sellers_linked_concern')) {
            Database::execute(
                "ALTER TABLE sellers ADD INDEX idx_sellers_linked_concern (concern_id, linked_concern_id)"
            );
        }
    }

    if ($tableExists('production_purchases')) {
        if (!$columnExists('production_purchases', 'source_batch_id')) {
            Database::execute(
                "ALTER TABLE production_purchases ADD COLUMN source_batch_id INT UNSIGNED NULL DEFAULT NULL AFTER production_unit_id"
            );
        }
        if (!$columnExists('production_purchases', 'source_sale_bill_id')) {
            Database::execute(
                "ALTER TABLE production_purchases ADD COLUMN source_sale_bill_id INT UNSIGNED NULL DEFAULT NULL AFTER source_batch_id"
            );
        }
        if (!$columnExists('production_purchases', 'source_inward_id')) {
            Database::execute(
                "ALTER TABLE production_purchases ADD COLUMN source_inward_id INT UNSIGNED NULL DEFAULT NULL AFTER source_sale_bill_id"
            );
        }
        if (!$indexExists('production_purchases', 'idx_production_purchases_source_batch')) {
            Database::execute(
                "ALTER TABLE production_purchases ADD INDEX idx_production_purchases_source_batch (source_batch_id)"
            );
        }
        if (!$indexExists('production_purchases', 'idx_production_purchases_source_bill')) {
            Database::execute(
                "ALTER TABLE production_purchases ADD INDEX idx_production_purchases_source_bill (source_sale_bill_id)"
            );
        }
    }

    if ($tableExists('production_purchase_items')) {
        Database::execute(
            "ALTER TABLE production_purchase_items
             MODIFY item_type ENUM('FABRIC_ROLL','GENERAL_SUPPLY','FINISHED_GOODS') NOT NULL"
        );
        if (!$columnExists('production_purchase_items', 'variant_id')) {
            Database::execute(
                "ALTER TABLE production_purchase_items ADD COLUMN variant_id INT UNSIGNED NULL DEFAULT NULL AFTER purchase_id"
            );
        }
        if (!$columnExists('production_purchase_items', 'sku')) {
            Database::execute(
                "ALTER TABLE production_purchase_items ADD COLUMN sku VARCHAR(100) NULL DEFAULT NULL AFTER item_name"
            );
        }
        if (!$columnExists('production_purchase_items', 'size_name')) {
            Database::execute(
                "ALTER TABLE production_purchase_items ADD COLUMN size_name VARCHAR(100) NULL DEFAULT NULL AFTER sku"
            );
        }
        if (!$columnExists('production_purchase_items', 'color_name')) {
            Database::execute(
                "ALTER TABLE production_purchase_items ADD COLUMN color_name VARCHAR(100) NULL DEFAULT NULL AFTER size_name"
            );
        }
        if (!$indexExists('production_purchase_items', 'idx_production_purchase_items_variant')) {
            Database::execute(
                "ALTER TABLE production_purchase_items ADD INDEX idx_production_purchase_items_variant (variant_id)"
            );
        }
    }

    if ($tableExists('permissions')) {
        Database::execute(
            "INSERT INTO permissions (module, action, description, slug, created_at)
             VALUES ('production_billing', 'print', 'Print production tax invoices and POS receipts', 'production_billing.print', NOW())
             ON DUPLICATE KEY UPDATE description = VALUES(description), slug = VALUES(slug)"
        );
        if ($tableExists('role_permissions')) {
            Database::execute(
                "INSERT IGNORE INTO role_permissions (role_id, permission_id)
                 SELECT rp.role_id, p_print.id
                 FROM role_permissions rp
                 JOIN permissions p_view ON p_view.id = rp.permission_id AND p_view.module = 'production_billing' AND p_view.action = 'view'
                 JOIN permissions p_print ON p_print.module = 'production_billing' AND p_print.action = 'print'"
            );
        }
        if ($tableExists('user_permissions')) {
            Database::execute(
                "INSERT IGNORE INTO user_permissions (user_id, permission_id)
                 SELECT up.user_id, p_print.id
                 FROM user_permissions up
                 JOIN permissions p_view ON p_view.id = up.permission_id AND p_view.module = 'production_billing' AND p_view.action = 'view'
                 JOIN permissions p_print ON p_print.module = 'production_billing' AND p_print.action = 'print'"
            );
        }
    }

    echo "Phase 6 batch billing, per-concern tag uniqueness, and inter-concern purchase schema updates applied.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Phase 6 migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

