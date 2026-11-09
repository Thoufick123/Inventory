<?php
/**
 * Migration: Track good Production Billing returns as newly tagged inventory.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_return_inventory (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            return_id INT UNSIGNED NOT NULL,
            return_item_id INT UNSIGNED NOT NULL,
            production_unit_id INT UNSIGNED DEFAULT NULL,
            qc_batch_id INT UNSIGNED NOT NULL,
            qc_item_id INT UNSIGNED NOT NULL,
            variant_id INT UNSIGNED DEFAULT NULL,
            original_tag_code VARCHAR(100) NOT NULL,
            new_tag_code VARCHAR(100) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            sku VARCHAR(100) DEFAULT NULL,
            size_name VARCHAR(100) DEFAULT NULL,
            color_name VARCHAR(100) DEFAULT NULL,
            unit_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('AVAILABLE','BILLED','DAMAGED','RETIRED') NOT NULL DEFAULT 'AVAILABLE',
            created_by INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_return_inventory_item (return_item_id),
            UNIQUE KEY uniq_production_return_inventory_tag (new_tag_code),
            KEY idx_production_return_inventory_batch_status (qc_batch_id, status),
            KEY idx_production_return_inventory_item (qc_item_id),
            KEY idx_production_return_inventory_unit (production_unit_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $legacyGoodReturns = Database::fetchAll(
        "SELECT pri.id AS return_item_id, pri.return_id, pri.original_qc_item_id,
                pri.original_variant_id, pri.original_tag_code, pri.original_product_name,
                pri.original_sku, pri.original_size_name, pri.original_color_name,
                pri.original_unit_price, pr.processed_by, qi.qc_batch_id,
                pp.production_unit_id
         FROM production_bill_return_items pri
         JOIN production_bill_returns pr ON pr.id = pri.return_id
         JOIN qc_items qi ON qi.id = pri.original_qc_item_id
         JOIN qc_batches qb ON qb.id = qi.qc_batch_id
         JOIN production_programs pp ON pp.id = qb.program_id
         LEFT JOIN production_return_inventory ri ON ri.return_item_id = pri.id
         WHERE pri.item_condition = 'GOOD' AND ri.id IS NULL
         ORDER BY pri.id"
    );

    Database::beginTransaction();
    try {
        foreach ($legacyGoodReturns as $item) {
            do {
                $newTag = 'RTN' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
                $exists = Database::fetchOne(
                    "SELECT new_tag_code FROM production_return_inventory WHERE new_tag_code = ?
                     UNION ALL
                     SELECT tag_code AS new_tag_code FROM production_bill_item_tags WHERE tag_code = ?
                     UNION ALL
                     SELECT code AS new_tag_code FROM production_deleted_label_codes WHERE code = ?
                     LIMIT 1",
                    [$newTag, $newTag, $newTag]
                );
            } while ($exists);

            Database::execute(
                "INSERT INTO production_return_inventory
                    (return_id, return_item_id, production_unit_id, qc_batch_id, qc_item_id, variant_id,
                     original_tag_code, new_tag_code, product_name, sku, size_name, color_name, unit_value,
                     status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'AVAILABLE', ?)",
                [
                    (int)$item['return_id'],
                    (int)$item['return_item_id'],
                    !empty($item['production_unit_id']) ? (int)$item['production_unit_id'] : null,
                    (int)$item['qc_batch_id'],
                    (int)$item['original_qc_item_id'],
                    !empty($item['original_variant_id']) ? (int)$item['original_variant_id'] : null,
                    $item['original_tag_code'],
                    $newTag,
                    $item['original_product_name'],
                    $item['original_sku'],
                    $item['original_size_name'],
                    $item['original_color_name'],
                    (float)$item['original_unit_price'],
                    !empty($item['processed_by']) ? (int)$item['processed_by'] : null,
                ]
            );
        }

        Database::commit();
    } catch (\Throwable $e) {
        if (Database::inTransaction()) {
            Database::rollback();
        }
        throw $e;
    }

    echo 'Production returned-item inventory is ready; backfilled ' . count($legacyGoodReturns) . " good return item(s).\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Production returned-item inventory migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}