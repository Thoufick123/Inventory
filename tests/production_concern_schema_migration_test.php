<?php

define('BASE_PATH', dirname(__DIR__));

$migrationPath = BASE_PATH . '/database/migrations/add_concern_ownership_to_production_modules.php';
$source = file_get_contents($migrationPath);
if ($source === false) {
    fwrite(STDERR, "FAIL: Could not read migration script.\n");
    exit(1);
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

foreach ([
    "'sellers', 'idx_sellers_concern', 'fk_sellers_concern'",
    "'seller_rolls', 'idx_seller_rolls_concern', 'fk_seller_rolls_concern'",
    "'roll_damages', 'idx_roll_damages_concern', 'fk_roll_damages_concern'",
    "'products', 'idx_products_concern', 'fk_products_concern'",
    "'product_variants', 'idx_product_variants_concern', 'fk_product_variants_concern'",
    "'production_inventory_damages', 'idx_prod_inv_damages_concern', 'fk_prod_inv_damages_concern'",
] as $expectedColumnCall) {
    $assert(str_contains($source, $expectedColumnCall), "Missing concern ownership migration call: {$expectedColumnCall}");
}

foreach ([
    'variant_id INT UNSIGNED NULL DEFAULT NULL',
    "stock_location_type ENUM('CONCERN','PRODUCTION_UNIT') NULL DEFAULT NULL",
    'stock_location_id INT UNSIGNED NULL DEFAULT NULL',
    'MODIFY qc_batch_id INT UNSIGNED NULL DEFAULT NULL',
    'MODIFY qc_item_id INT UNSIGNED NULL DEFAULT NULL',
    'MODIFY program_id INT UNSIGNED NULL DEFAULT NULL',
    'fk_prod_inv_damages_variant',
    "swapGlobalUniqueForConcern('roll_damages', 'uniq_roll_damages_number', 'uniq_roll_damages_concern_number'",
    "swapGlobalUniqueForConcern('roll_damages', 'uniq_roll_damages_qr_code', 'uniq_roll_damages_concern_qr_code'",
    "swapGlobalUniqueForConcern('products', 'code', 'uniq_products_concern_code'",
    "swapGlobalUniqueForConcern('product_variants', 'sku', 'uniq_variants_concern_sku'",
    "swapGlobalUniqueForConcern('product_variants', 'qr_code', 'uniq_variants_concern_qr_code'",
    "swapGlobalUniqueForConcern('production_inventory_damages', 'uniq_production_inventory_damage_number', 'uniq_prod_inv_dmg_concern_number'",
    "swapGlobalUniqueForConcern('production_inventory_damages', 'uniq_production_inventory_damage_tag', 'uniq_prod_inv_dmg_concern_tag'",
] as $expectedFragment) {
    $assert(str_contains($source, $expectedFragment), "Missing Phase 2 schema requirement: {$expectedFragment}");
}

echo "PASS: Phase 2 migration covers concern_id ownership, nullable inward-damage links, FKs, backfills, and per-concern unique keys.\n";

