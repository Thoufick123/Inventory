<?php
/**
 * Add non-destructive concern ownership to Sellers, Seller Rolls, Roll Damages,
 * Catalog Products, and Finished Goods Damages (supporting Concern inward stock).
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    if (!Database::fetchOne("SHOW TABLES LIKE 'concerns'")) {
        throw new RuntimeException('Concerns table is required before running this migration.');
    }

    $tableExists = static function (string $table): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
    };
    $columnExists = static function (string $table, string $column): bool {
        return (bool)Database::fetchOne("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    };
    $indexExists = static function (string $table, string $indexName): bool {
        return (bool)Database::fetchOne("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
    };
    $constraintExists = static function (string $table, string $constraintName): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$table, $constraintName]
        );
    };

    $addConcernColumn = static function (string $table, string $idxName, string $fkName) use ($tableExists, $columnExists, $indexExists, $constraintExists): void {
        if (!$tableExists($table)) {
            return;
        }
        if (!$columnExists($table, 'concern_id')) {
            Database::execute("ALTER TABLE `{$table}` ADD COLUMN concern_id INT UNSIGNED NULL DEFAULT NULL");
        }
        if (!$indexExists($table, $idxName)) {
            Database::execute("ALTER TABLE `{$table}` ADD INDEX `{$idxName}` (concern_id)");
        }
        if (!$constraintExists($table, $fkName)) {
            Database::execute(
                "ALTER TABLE `{$table}`
                 ADD CONSTRAINT `{$fkName}`
                 FOREIGN KEY (concern_id) REFERENCES concerns (id)
                 ON DELETE RESTRICT"
            );
        }
    };

    $addConcernColumn('sellers', 'idx_sellers_concern', 'fk_sellers_concern');
    $addConcernColumn('seller_rolls', 'idx_seller_rolls_concern', 'fk_seller_rolls_concern');
    $addConcernColumn('roll_damages', 'idx_roll_damages_concern', 'fk_roll_damages_concern');
    $addConcernColumn('products', 'idx_products_concern', 'fk_products_concern');
    $addConcernColumn('production_inventory_damages', 'idx_prod_inv_damages_concern', 'fk_prod_inv_damages_concern');

    if ($tableExists('production_inventory_damages')) {
        if (!$columnExists('production_inventory_damages', 'variant_id')) {
            Database::execute("ALTER TABLE production_inventory_damages ADD COLUMN variant_id INT UNSIGNED NULL DEFAULT NULL AFTER qc_item_id");
        }
        if (!$columnExists('production_inventory_damages', 'stock_location_type')) {
            Database::execute("ALTER TABLE production_inventory_damages ADD COLUMN stock_location_type ENUM('CONCERN','PRODUCTION_UNIT') NULL DEFAULT NULL AFTER production_unit_id");
        }
        if (!$columnExists('production_inventory_damages', 'stock_location_id')) {
            Database::execute("ALTER TABLE production_inventory_damages ADD COLUMN stock_location_id INT UNSIGNED NULL DEFAULT NULL AFTER stock_location_type");
        }
        Database::execute("ALTER TABLE production_inventory_damages MODIFY qc_batch_id INT UNSIGNED NULL DEFAULT NULL");
        Database::execute("ALTER TABLE production_inventory_damages MODIFY qc_item_id INT UNSIGNED NULL DEFAULT NULL");
        Database::execute("ALTER TABLE production_inventory_damages MODIFY program_id INT UNSIGNED NULL DEFAULT NULL");
    }

    $primaryConcernRow = Database::fetchOne("SELECT id FROM concerns ORDER BY id ASC LIMIT 1");
    $primaryConcernId = (int)($primaryConcernRow['id'] ?? 1);

    if ($tableExists('sellers')) {
        Database::execute(
            "UPDATE sellers s
             LEFT JOIN (
                 SELECT seller_id, MAX(concern_id) AS concern_id
                 FROM production_purchases
                 WHERE concern_id IS NOT NULL
                 GROUP BY seller_id
             ) pp ON pp.seller_id = s.id
             SET s.concern_id = COALESCE(s.concern_id, pp.concern_id, ?)
             WHERE s.concern_id IS NULL",
            [$primaryConcernId]
        );
    }

    if ($tableExists('seller_rolls')) {
        Database::execute(
            "UPDATE seller_rolls sr
             LEFT JOIN (
                 SELECT ppi.seller_roll_id, MAX(pp.concern_id) AS concern_id
                 FROM production_purchase_items ppi
                 JOIN production_purchases pp ON pp.id = ppi.purchase_id
                 WHERE ppi.seller_roll_id IS NOT NULL AND pp.concern_id IS NOT NULL
                 GROUP BY ppi.seller_roll_id
             ) p ON p.seller_roll_id = sr.id
             LEFT JOIN sellers s ON s.id = sr.seller_id
             SET sr.concern_id = COALESCE(sr.concern_id, p.concern_id, s.concern_id, ?)
             WHERE sr.concern_id IS NULL",
            [$primaryConcernId]
        );
    }

    if ($tableExists('roll_damages')) {
        Database::execute(
            "UPDATE roll_damages rd
             LEFT JOIN seller_rolls sr ON sr.id = rd.seller_roll_id
             LEFT JOIN sellers s ON s.id = rd.seller_id
             SET rd.concern_id = COALESCE(rd.concern_id, sr.concern_id, s.concern_id, ?)
             WHERE rd.concern_id IS NULL",
            [$primaryConcernId]
        );
    }

    if ($tableExists('products')) {
        Database::execute(
            "UPDATE products p
             LEFT JOIN (
                 SELECT pp.product_id, MAX(pu.concern_id) AS concern_id
                 FROM production_programs pp
                 JOIN production_units pu ON pu.id = pp.production_unit_id
                 WHERE pp.product_id IS NOT NULL AND pu.concern_id IS NOT NULL
                 GROUP BY pp.product_id
             ) prog ON prog.product_id = p.id
             SET p.concern_id = COALESCE(p.concern_id, prog.concern_id, ?)
             WHERE p.concern_id IS NULL",
            [$primaryConcernId]
        );
    }

    if ($tableExists('production_inventory_damages')) {
        Database::execute(
            "UPDATE production_inventory_damages d
             LEFT JOIN production_units pu ON pu.id = d.production_unit_id
             LEFT JOIN production_programs pp ON pp.id = d.program_id
             LEFT JOIN production_units pp_pu ON pp_pu.id = pp.production_unit_id
             SET d.concern_id = COALESCE(d.concern_id, pu.concern_id, pp_pu.concern_id, ?)
             WHERE d.concern_id IS NULL",
            [$primaryConcernId]
        );
    }

    echo "Concern ownership migration for Production modules completed successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

