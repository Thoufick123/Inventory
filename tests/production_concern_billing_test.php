<?php

define('BASE_PATH', dirname(__DIR__));

final class TestBillingState
{
    public static int $concernId = 2;
    public static ?int $unitId = null;
}

final class Database
{
    public static array $queries = [];

    public static function fetchAll(string $sql, array $params = []): array
    {
        self::$queries[] = [$sql, $params];

        if (str_contains($sql, 'FROM production_bill_item_tags t') && !str_contains($sql, 'qi.qc_batch_id')) {
            return [
                ['tag_code' => 'WMF112699'],
                ['tag_code' => 'RTN-RETIRED'],
            ];
        }

        if (str_contains($sql, 'FROM production_deleted_label_codes')) {
            return [['code' => 'WMF112698']];
        }

        if (str_contains($sql, 'FROM qc_items qi') && str_contains($sql, 'JOIN qc_batches qb')) {
            // Concern 1 owns local QC item #1 (unpacked tag QCLOCAL01); Concern 2 has none.
            if ($params[0] === 1) {
                return [[
                    'qc_item_id' => 1,
                    'qc_batch_id' => 10,
                    'variant_id' => 6,
                    'size_name' => 'S',
                    'color_name' => 'RED',
                    'qc_base_price' => '500.00',
                    'qc_discount_price' => '400.00',
                    'program_number' => 'PP-001',
                    'program_name' => 'Prog 1',
                    'production_unit_id' => 1,
                    'location_label' => 'Main Unit C1',
                    'product_name' => 'Maternity Kurta',
                    'product_code' => 'WMF',
                    'gst_percentage' => '5.00',
                    'sku' => 'WMF-S-RED',
                    'variant_mrp' => '500.00',
                    'selling_price' => '400.00',
                    'color_from_variant' => 'RED',
                    'size_from_variant' => 'S',
                ]];
            }
            return [];
        }

        if (str_contains($sql, 'FROM production_batch_items pbi')) {
            return [];
        }

        if (str_contains($sql, 'FROM qc_items qi') && str_contains($sql, 'JOIN production_programs pp')) {
            return [[
                'qc_item_id' => 1,
                'qc_batch_id' => 10,
                'variant_id' => 6,
                'size_name' => 'S',
                'color_name' => 'RED',
                'qc_base_price' => '500.00',
                'qc_discount_price' => '400.00',
                'program_number' => 'PP-001',
                'program_name' => 'Prog 1',
                'production_unit_id' => 1,
                'location_label' => 'Main Unit C1',
                'product_name' => 'Maternity Kurta',
                'product_code' => 'WMF',
                'gst_percentage' => '5.00',
                'sku' => 'WMF-S-RED',
                'variant_mrp' => '500.00',
                'selling_price' => '400.00',
                'color_from_variant' => 'RED',
                'size_from_variant' => 'S',
            ]];
        }

        if (str_contains($sql, 'FROM production_inventory_damages')) {
            return [];
        }

        if (str_contains($sql, 'FROM stock_balances') && str_contains($sql, 'stock_state = \'AVAILABLE\'')) {
            if ($params[0] === 2) {
                return [
                    ['variant_id' => 6, 'location_type' => 'CONCERN', 'location_id' => 2, 'quantity' => '2.00'],
                    ['variant_id' => 7, 'location_type' => 'PRODUCTION_UNIT', 'location_id' => 3, 'quantity' => '1.00'],
                ];
            }
            return [];
        }

        if (str_contains($sql, 'FROM stock_transactions')) {
            return [];
        }

        if (str_contains($sql, 'FROM production_inward_items pii')) {
            // Two inward calls hit this pattern: getAvailableInventory() and resolveTagStockLocation()
            $concernParam = $params[0];
            if ($concernParam !== 2) {
                return [];
            }

            if (str_contains($sql, 'pii.scanned_codes') && str_contains($sql, 'pii.damaged_codes')) {
                $rows = [[
                    'inward_id' => 1,
                    'grn_number' => 'PGRN-2026-000001',
                    'source_type' => 'PRODUCTION_BATCH',
                    'source_id' => 1,
                    'destination_type' => 'CONCERN',
                    'destination_concern_id' => 2,
                    'destination_unit_id' => null,
                    'source_batch_number' => 'BAT-001',
                    'received_at' => '2026-11-10 10:00:00',
                    'location_label' => 'SULUR SHOP (Concern Inventory)',
                    'variant_id' => 6,
                    'scanned_codes' => json_encode(['WMF112605', 'WMF112606', 'WMF112697', 'WMF112698', 'WMF112699']),
                    'damaged_codes' => 'WMF112697',
                    'missing_codes' => null,
                    'batch_number' => 'BAT-001',
                    'qc_batch_id' => 10,
                    'origin_qc_item_id' => 1,
                    'qc_base_price' => '500.00',
                    'qc_discount_price' => '400.00',
                    'qc_size_name' => 'S',
                    'qc_color_name' => 'RED',
                    'program_number' => 'PP-001',
                    'product_name' => 'Maternity Kurta',
                    'product_code' => 'WMF',
                    'gst_percentage' => '5.00',
                    'sku' => 'WMF-S-RED',
                    'variant_mrp' => '500.00',
                    'selling_price' => '400.00',
                    'color_from_variant' => 'RED',
                    'size_from_variant' => 'S',
                ]];
                if (TestBillingState::$unitId === null || TestBillingState::$unitId === 3) {
                    $rows[] = [
                        'inward_id' => 2,
                        'grn_number' => 'PGRN-2026-000002',
                        'source_type' => 'WAREHOUSE',
                        'source_id' => 1,
                        'destination_type' => 'PRODUCTION_UNIT',
                        'destination_concern_id' => null,
                        'destination_unit_id' => 3,
                        'source_batch_number' => null,
                        'received_at' => '2026-11-10 11:00:00',
                        'location_label' => 'Unit 3',
                        'variant_id' => 7,
                        'scanned_codes' => json_encode(['UNI30001']),
                        'damaged_codes' => null,
                        'missing_codes' => null,
                        'batch_number' => null,
                        'qc_batch_id' => null,
                        'origin_qc_item_id' => null,
                        'qc_base_price' => null,
                        'qc_discount_price' => null,
                        'qc_size_name' => null,
                        'qc_color_name' => null,
                        'program_number' => null,
                        'product_name' => 'Unit 3 Exclusive',
                        'product_code' => 'U3E',
                        'gst_percentage' => '0.00',
                        'sku' => 'U3E-M-BLUE',
                        'variant_mrp' => '750.00',
                        'selling_price' => '700.00',
                        'color_from_variant' => 'BLUE',
                        'size_from_variant' => 'M',
                    ];
                }
                return $rows;
            }

            // resolveTagStockLocation scan
            return [[
                'destination_type' => 'CONCERN',
                'destination_concern_id' => 2,
                'destination_unit_id' => null,
                'variant_id' => 6,
                'scanned_codes' => json_encode(['WMF112605', 'WMF112606']),
            ]];
        }

        if (str_contains($sql, 'FROM production_return_inventory ri')) {
            if ($params[0] === 2) {
                return [[
                    'new_tag_code' => 'RTNC2-001',
                    'original_tag_code' => 'WMF112605',
                    'qc_item_id' => 1,
                    'qc_batch_id' => 10,
                    'variant_id' => 6,
                    'product_name' => 'Maternity Kurta',
                    'sku' => 'WMF-S-RED',
                    'size_name' => 'S',
                    'color_name' => 'RED',
                    'unit_value' => '400.00',
                    'bill_id' => 99,
                    'gst_percentage' => '5.00',
                    'variant_mrp' => '500.00',
                    'selling_price' => '400.00',
                ]];
            }
            return [];
        }

        return [];
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        if (str_contains($sql, 'FROM production_units')) {
            return ['id' => 2];
        }
        if (str_contains($sql, 'FROM qc_batches')) {
            return ['concern_id' => 1];
        }
        if (str_contains($sql, 'FROM production_return_inventory')) {
            if (strtoupper((string)$params[0]) === 'RTNC2-001' && (int)$params[1] === 2) {
                return ['original_tag_code' => 'WMF112605'];
            }
            return null;
        }
        return null;
    }
}

final class Concern
{
    public static function hasFacilityModules(int $concernId, string $facilityType): bool
    {
        return false; // CONCERN billing must work without relying on facility module checks
    }
}

final class ProductionInventoryDamage
{
    public static function getUnavailableTags(int $qcBatchId): array
    {
        return [];
    }
}

function currentConcernId(): ?int
{
    return TestBillingState::$concernId;
}

function currentProductionUnitId(): ?int
{
    return TestBillingState::$unitId;
}

function getScopedProductionUnits(): array
{
    return TestBillingState::$unitId !== null ? [['id' => TestBillingState::$unitId]] : [['id' => 2]];
}

require_once BASE_PATH . '/app/Services/ProductionBillingService.php';

function assertConcernBillingTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// Override getApprovedQCPieceTags behavior? Mocked via QCBatch / production_programs above!
//Wait: ProductionService::getApprovedQCPieceTags queries qc_batches & production_programs + QCBatch::getApprovedQCPieceTags?
// Actually ProductionService has getApprovedQCPieceTags; for concern #2 query on qc_items returns [], so QC loop doesn't even run for Concern #2!

// 1. Sulur Shop (Concern #2, unit unrestricted) sees CONCERN inward tags WMF112605, WMF112606 + RTNC2-001,
//    excludes damaged (WMF112697), deleted (WMF112698), and already billed (WMF112699).
TestBillingState::$concernId = 2;
TestBillingState::$unitId = null;
$inv = ProductionBillingService::getAvailableInventory(0);
assertConcernBillingTest(count($inv) === 2, 'Expected CONCERN variant 6 bucket and UNIT 3 variant 7 bucket in Concern #2.');
$byKey = [];
foreach ($inv as $entry) {
    $byKey[$entry['item_key']] = $entry;
}
assertConcernBillingTest(isset($byKey['inward:CONCERN:2:6']), 'Expected inward CONCERN:2 bucket for variant 6.');
assertConcernBillingTest(
    $byKey['inward:CONCERN:2:6']['available_tags'] === ['WMF112605', 'WMF112606', 'RTNC2-001'],
    'Verified unbilled inward tags and Concern #2 returned inward tag must be available while damaged/deleted/billed tags are excluded. Got: ' . json_encode($byKey['inward:CONCERN:2:6']['available_tags'])
);
assertConcernBillingTest($byKey['inward:CONCERN:2:6']['stock_qty'] === 2, 'Stock cap must reflect stock_balances quantity.');

// 2. Unit-assigned staff in Unit #2 still sees shared CONCERN:2 inventory, but NOT Unit #3 inward stock.
TestBillingState::$unitId = 2;
$unit2Inv = ProductionBillingService::getAvailableInventory(0);
$unit2Keys = array_column($unit2Inv, 'item_key');
assertConcernBillingTest($unit2Keys === ['inward:CONCERN:2:6'], 'Unit #2 staff should see CONCERN:2 stock and must not see Unit #3 stock.');

// 3. Unit scope 0 fails closed with empty inventory.
TestBillingState::$unitId = 0;
assertConcernBillingTest(ProductionBillingService::getAvailableInventory(0) === [], 'Out-of-concern unit assignment must fail closed.');

// 4. Exchange piece lookup finds WMF112605 inside Concern #2 and returns CONCERN:2 stock location.
TestBillingState::$unitId = null;
$found = ProductionBillingService::findAvailablePieceByTag('wmf112605');
assertConcernBillingTest($found !== null && $found['stock_location_type'] === 'CONCERN' && $found['stock_location_id'] === 2, 'Exchange lookup must resolve inward concern tag location.');

// 5. Return tag RTNC2-001 traces back to CONCERN:2 stock location for future return stock restoration.
$rtLoc = ProductionBillingService::resolveTagStockLocation(2, 99, 6, 'RTNC2-001');
assertConcernBillingTest($rtLoc !== null && $rtLoc['location_type'] === 'CONCERN' && $rtLoc['location_id'] === 2, 'Returned inward tag must resolve back to CONCERN:2 location.');

// 6. Zero qc_item_id multi-variant items resolve without colliding by item_key.
$resolved = ProductionBillingService::resolveBillItemEntry(
    ['item_key' => 'inward:PRODUCTION_UNIT:3:7', 'qc_item_id' => 0, 'tags' => ['UNI30001']],
    $byKey,
    $inv
);
assertConcernBillingTest($resolved !== null && $resolved['variant_id'] === 7, 'resolveBillItemEntry must resolve zero-qc_item_id inward rows cleanly by item_key.');

// 7. Concern #1 does NOT see Concern #2 inward tag WMF112605.
TestBillingState::$concernId = 1;
assertConcernBillingTest(ProductionBillingService::findAvailablePieceByTag('WMF112605') === null, 'Concern #1 must never see Concern #2 inward stock in billing.');

echo "PASS: concern billing inventory, unit boundaries, exchange lookup, and return location resolution all work cleanly.\n";
