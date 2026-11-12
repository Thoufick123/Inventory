<?php

require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';
require_once BASE_PATH . '/app/Services/ProductionInwardService.php';
require_once BASE_PATH . '/app/Services/ProductionService.php';
require_once BASE_PATH . '/app/Services/StockService.php';

class ProductionBillingService
{
    public static function resolveAssignedUnitId(): ?int
    {
        $assignedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($assignedUnitId !== null && $assignedUnitId > 0) {
            ConcernTransactionService::assertFacility('PRODUCTION', $assignedUnitId);
        }
        return $assignedUnitId;
    }

    public static function defaultProductionUnitId(): ?int
    {
        $concernId = ConcernTransactionService::activeConcernId();
        $assignedUnitId = self::resolveAssignedUnitId();
        if ($assignedUnitId === 0) {
            throw new RuntimeException('No production unit is assigned in the active concern.');
        }
        if ($assignedUnitId !== null) {
            return $assignedUnitId;
        }

        $units = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : [];
        if (!empty($units[0]['id'])) {
            return (int)$units[0]['id'];
        }

        $unit = Database::fetchOne(
            'SELECT id FROM production_units WHERE concern_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1',
            [$concernId]
        );
        return $unit ? (int)$unit['id'] : null;
    }

    public static function getSoldTagSet(int $excludeBillId = 0, ?int $concernId = null): array
    {
        $concernId = (int)($concernId ?? ConcernTransactionService::activeConcernId());
        if ($concernId <= 0) {
            return [];
        }
        $rows = $excludeBillId > 0
            ? Database::fetchAll(
                "SELECT t.tag_code
                 FROM production_bill_item_tags t
                 JOIN production_bills pb ON pb.id = t.bill_id
                 WHERE t.status IN ('BILLED', 'DAMAGED', 'RETURNED')
                   AND COALESCE(t.concern_id, pb.concern_id) = ?
                   AND NOT (t.bill_id = ? AND pb.concern_id = ?)",
                [$concernId, $excludeBillId, $concernId]
            )
            : Database::fetchAll(
                "SELECT t.tag_code
                 FROM production_bill_item_tags t
                 JOIN production_bills pb ON pb.id = t.bill_id
                 WHERE t.status IN ('BILLED', 'DAMAGED', 'RETURNED')
                   AND COALESCE(t.concern_id, pb.concern_id) = ?",
                [$concernId]
            );

        $sold = [];
        foreach ($rows as $row) {
            $tag = strtoupper(trim((string)($row['tag_code'] ?? '')));
            if ($tag !== '') {
                $sold[$tag] = true;
            }
        }
        return $sold;
    }

    public static function getDeletedTagSet(): array
    {
        $rows = Database::fetchAll('SELECT code FROM production_deleted_label_codes');
        $deleted = [];
        foreach ($rows as $row) {
            $tag = strtoupper(trim((string)($row['code'] ?? '')));
            if ($tag !== '') {
                $deleted[$tag] = true;
            }
        }
        return $deleted;
    }

    public static function getPackedInwardTagSet(int $concernId): array
    {
        if ($concernId <= 0) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT pbi.piece_tags
             FROM production_batch_items pbi
             JOIN production_batches pb ON pb.id = pbi.production_batch_id
             WHERE pb.concern_id = ?
               AND (pb.source_type = 'INWARD' OR (pb.source_type = 'SCAN' AND pbi.source_kind = 'INWARD'))
               AND pb.status != 'CANCELLED'
               AND pbi.piece_tags IS NOT NULL
               AND pbi.piece_tags != ''",
            [$concernId]
        );
        $packed = [];
        foreach ($rows as $row) {
            $decoded = json_decode((string)($row['piece_tags'] ?? ''), true);
            if (!is_array($decoded)) {
                continue;
            }
            foreach ($decoded as $tag) {
                $clean = strtoupper(trim((string)$tag));
                if ($clean !== '') {
                    $packed[$clean] = true;
                }
            }
        }
        return $packed;
    }

    public static function getQuarantinedConcernTagSet(int $concernId): array
    {
        if ($concernId <= 0) {
            return [];
        }
        if (class_exists('ProductionInventoryDamage') && method_exists('ProductionInventoryDamage', 'getUnavailableConcernTags')) {
            return ProductionInventoryDamage::getUnavailableConcernTags($concernId);
        }
        return [];
    }

    public static function getBillHeldStockMap(int $billId, int $concernId): array
    {
        if ($billId <= 0 || $concernId <= 0) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT variant_id, location_type, location_id, SUM(quantity) AS net_qty
             FROM stock_transactions
             WHERE reference_type = 'production_bills'
               AND reference_id = ?
               AND concern_id = ?
               AND stock_state = 'AVAILABLE'
             GROUP BY variant_id, location_type, location_id
             HAVING SUM(quantity) < 0",
            [$billId, $concernId]
        );
        $held = [];
        foreach ($rows as $row) {
            $key = (int)$row['variant_id'] . '|' . strtoupper((string)$row['location_type']) . '|' . (int)$row['location_id'];
            $held[$key] = abs((float)$row['net_qty']);
        }
        return $held;
    }

    public static function resolveTagStockLocation(int $concernId, int $billId, int $variantId, string $tagCode): ?array
    {
        if ($concernId <= 0) {
            return null;
        }
        $rootTag = strtoupper(trim($tagCode));
        if ($rootTag === '') {
            return null;
        }

        $visited = [];
        for ($hop = 0; $hop < 10; $hop++) {
            if (isset($visited[$rootTag])) {
                break;
            }
            $visited[$rootTag] = true;
            $returned = Database::fetchOne(
                "SELECT ri.original_tag_code
                 FROM production_return_inventory ri
                 JOIN production_bill_returns pbr ON pbr.id = ri.return_id
                 WHERE UPPER(ri.new_tag_code) = ? AND pbr.concern_id = ?
                 ORDER BY ri.id DESC
                 LIMIT 1",
                [$rootTag, $concernId]
            );
            if (!$returned) {
                break;
            }
            $nextTag = strtoupper(trim((string)($returned['original_tag_code'] ?? '')));
            if ($nextTag === '') {
                break;
            }
            $rootTag = $nextTag;
        }

        $sql = "SELECT pi.destination_type, pi.destination_concern_id, pi.destination_unit_id,
                       pii.variant_id, pii.scanned_codes
                FROM production_inward_items pii
                JOIN production_inwards pi ON pi.id = pii.inward_id
                WHERE pi.concern_id = ? AND pi.status = 'RECEIVED'";
        $params = [$concernId];
        if ($variantId > 0) {
            $sql .= ' AND pii.variant_id = ?';
            $params[] = $variantId;
        }
        $sql .= ' ORDER BY pi.id DESC, pii.id DESC';

        foreach (Database::fetchAll($sql, $params) as $row) {
            $scanned = ProductionInwardService::decodePieceCodeList($row['scanned_codes'] ?? null);
            if (in_array($rootTag, $scanned, true)) {
                $locType = strtoupper((string)$row['destination_type']);
                $locId = $locType === 'CONCERN'
                    ? (int)($row['destination_concern_id'] ?? 0)
                    : (int)($row['destination_unit_id'] ?? 0);
                if ($locId > 0) {
                    return [
                        'location_type' => $locType,
                        'location_id' => $locId,
                        'variant_id' => (int)$row['variant_id'],
                    ];
                }
            }
        }

        if ($billId > 0 && $variantId > 0) {
            $txn = Database::fetchOne(
                "SELECT location_type, location_id
                 FROM stock_transactions
                 WHERE reference_type = 'production_bills'
                   AND reference_id = ?
                   AND concern_id = ?
                   AND variant_id = ?
                   AND stock_state = 'AVAILABLE'
                 GROUP BY location_type, location_id
                 HAVING SUM(quantity) < 0
                 ORDER BY location_type DESC, location_id ASC
                 LIMIT 1",
                [$billId, $concernId, $variantId]
            );
            if ($txn) {
                return [
                    'location_type' => strtoupper((string)$txn['location_type']),
                    'location_id' => (int)$txn['location_id'],
                    'variant_id' => $variantId,
                ];
            }
        }

        return null;
    }

    public static function inferBillItemKey(int $concernId, int $billId, array $billItem, array $tags): string
    {
        $variantId = (int)($billItem['variant_id'] ?? 0);
        if ($variantId > 0) {
            foreach ($tags as $tag) {
                $loc = self::resolveTagStockLocation($concernId, $billId, $variantId, (string)$tag);
                if ($loc !== null) {
                    return 'inward:' . $loc['location_type'] . ':' . $loc['location_id'] . ':' . $variantId;
                }
            }
        }
        return 'qc:' . (int)($billItem['qc_item_id'] ?? 0);
    }

    public static function getAvailableInventory(int $excludeBillId = 0): array
    {
        $concernId = ConcernTransactionService::activeConcernId();
        $assignedUnitId = self::resolveAssignedUnitId();
        if ($assignedUnitId === 0) {
            return [];
        }

        $soldTags = self::getSoldTagSet($excludeBillId, $concernId);
        $deletedTags = self::getDeletedTagSet();
        $quarantinedConcernTags = self::getQuarantinedConcernTagSet($concernId);
        $packedInwardTags = self::getPackedInwardTagSet($concernId);
        $seenTags = [];
        $available = [];

        // 1. Local QC-passed production inventory within the active concern (and unit, if restricted)
        $qcSql = "SELECT qi.id AS qc_item_id, qi.qc_batch_id, qi.variant_id, qi.size_name, qi.color_name,
                         qi.base_price AS qc_base_price, qi.discount_price AS qc_discount_price,
                         pp.program_number, pp.name AS program_name, pp.production_unit_id,
                         COALESCE(pu.name, 'QC Batch Stock') AS location_label,
                         COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                         p.code AS product_code, p.gst_percentage,
                         pv.sku, pv.mrp AS variant_mrp, pv.selling_price,
                         c.name AS color_from_variant, sz.name AS size_from_variant
                  FROM qc_items qi
                  JOIN qc_batches qb ON qb.id = qi.qc_batch_id
                  JOIN production_programs pp ON pp.id = qb.program_id
                  JOIN production_units pu ON pu.id = pp.production_unit_id
                  LEFT JOIN products p ON p.id = pp.product_id
                  LEFT JOIN product_variants pv ON pv.id = qi.variant_id
                  LEFT JOIN colors c ON c.id = pv.color_id
                  LEFT JOIN sizes sz ON sz.id = pv.size_id
                  WHERE qi.passed_qty > 0
                    AND pu.concern_id = ?";
        $qcParams = [$concernId];
        if ($assignedUnitId !== null) {
            $qcSql .= ' AND pp.production_unit_id = ?';
            $qcParams[] = $assignedUnitId;
        }
        $qcSql .= ' ORDER BY qb.created_at DESC, qi.id DESC';
        $qcRows = Database::fetchAll($qcSql, $qcParams);

        $batchIds = array_values(array_unique(array_map(
            static fn(array $row): int => (int)$row['qc_batch_id'],
            $qcRows
        )));
        $tagsByItem = [];
        foreach ($batchIds as $batchId) {
            $tagsByItem += ProductionService::getAvailableQCPieceTags($batchId);
        }

        foreach ($qcRows as $row) {
            $qcItemId = (int)$row['qc_item_id'];
            $tags = [];
            foreach ($tagsByItem[$qcItemId] ?? [] as $tag) {
                $clean = trim((string)$tag);
                $upper = strtoupper($clean);
                if ($clean === '' || isset($soldTags[$upper]) || isset($deletedTags[$upper]) || isset($seenTags[$upper])) {
                    continue;
                }
                $seenTags[$upper] = true;
                $tags[] = $clean;
            }
            if (!$tags) {
                continue;
            }

            [$mrp, $defaultPrice] = self::resolvePrices($row);
            $available[] = [
                'item_key' => 'qc:' . $qcItemId,
                'source_kind' => 'QC',
                'qc_item_id' => $qcItemId,
                'qc_batch_id' => (int)$row['qc_batch_id'],
                'variant_id' => (int)($row['variant_id'] ?? 0),
                'production_unit_id' => (int)($row['production_unit_id'] ?? 0),
                'stock_location_type' => null,
                'stock_location_id' => null,
                'location_label' => (string)$row['location_label'],
                'product_name' => (string)$row['product_name'],
                'program_number' => (string)($row['program_number'] ?? ''),
                'sku' => (string)(!empty($row['sku']) ? $row['sku'] : ($row['product_code'] ?? '')),
                'size_name' => (string)(!empty($row['size_name']) ? $row['size_name'] : ($row['size_from_variant'] ?? '')),
                'color_name' => (string)(!empty($row['color_name']) ? $row['color_name'] : ($row['color_from_variant'] ?? '')),
                'mrp' => $mrp,
                'unit_price' => $defaultPrice,
                'gst_percentage' => max(0, (float)($row['gst_percentage'] ?? 0)),
                'stock_qty' => count($tags),
                'available_tags' => $tags,
            ];
        }

        // 2. Concern / Unit Inwarded Stock (e.g., Sulur Shop CONCERN stock or unit inward stock)
        $stockMap = [];
        $balanceRows = Database::fetchAll(
            "SELECT variant_id, location_type, location_id, quantity
             FROM stock_balances
             WHERE concern_id = ? AND stock_state = 'AVAILABLE' AND location_type IN ('CONCERN', 'PRODUCTION_UNIT')",
            [$concernId]
        );
        foreach ($balanceRows as $bal) {
            $bKey = (int)$bal['variant_id'] . '|' . strtoupper((string)$bal['location_type']) . '|' . (int)$bal['location_id'];
            $stockMap[$bKey] = (float)$bal['quantity'];
        }
        if ($excludeBillId > 0) {
            foreach (self::getBillHeldStockMap($excludeBillId, $concernId) as $bKey => $heldQty) {
                $stockMap[$bKey] = ($stockMap[$bKey] ?? 0.0) + $heldQty;
            }
        }

        $inwardSql = "SELECT pi.id AS inward_id, pi.grn_number, pi.source_type, pi.source_id,
                             pi.destination_type, pi.destination_concern_id, pi.destination_unit_id,
                             pi.source_batch_number, pi.received_at,
                             COALESCE(pu.name, CONCAT(dest_c.name, ' (Concern Inventory)')) AS location_label,
                             pii.variant_id, pii.scanned_codes, pii.damaged_codes, pii.missing_codes,
                             pb.batch_number, pb.qc_batch_id,
                             qi.id AS origin_qc_item_id, qi.base_price AS qc_base_price, qi.discount_price AS qc_discount_price,
                             qi.size_name AS qc_size_name, qi.color_name AS qc_color_name,
                             pp.program_number,
                             COALESCE(p.name, 'Finished Product') AS product_name,
                             p.code AS product_code, p.gst_percentage,
                             pv.sku, pv.mrp AS variant_mrp, pv.selling_price,
                             c.name AS color_from_variant, sz.name AS size_from_variant
                      FROM production_inward_items pii
                      JOIN production_inwards pi ON pi.id = pii.inward_id
                      LEFT JOIN concerns dest_c ON pi.destination_type = 'CONCERN' AND dest_c.id = pi.destination_concern_id
                      LEFT JOIN production_units pu ON pi.destination_type = 'PRODUCTION_UNIT'
                          AND pu.id = pi.destination_unit_id AND pu.concern_id = ? AND pu.is_active = 1
                      LEFT JOIN production_batches pb ON pi.source_type = 'PRODUCTION_BATCH' AND pb.id = pi.source_id
                      LEFT JOIN qc_items qi ON qi.qc_batch_id = pb.qc_batch_id AND qi.variant_id = pii.variant_id
                      LEFT JOIN qc_batches qb ON qb.id = qi.qc_batch_id
                      LEFT JOIN production_programs pp ON pp.id = qb.program_id
                      JOIN product_variants pv ON pv.id = pii.variant_id
                      JOIN products p ON p.id = pv.product_id
                      LEFT JOIN colors c ON c.id = pv.color_id
                      LEFT JOIN sizes sz ON sz.id = pv.size_id
                      WHERE pi.concern_id = ? AND pi.status = 'RECEIVED'";
        $inwardParams = [$concernId, $concernId];
        if ($assignedUnitId !== null) {
            $inwardSql .= " AND ((pi.destination_type = 'CONCERN' AND pi.destination_concern_id = ?)
                              OR (pi.destination_type = 'PRODUCTION_UNIT' AND pi.destination_unit_id = ? AND pu.id IS NOT NULL))";
            array_push($inwardParams, $concernId, $assignedUnitId);
        } else {
            $inwardSql .= " AND ((pi.destination_type = 'CONCERN' AND pi.destination_concern_id = ?)
                              OR (pi.destination_type = 'PRODUCTION_UNIT' AND pu.id IS NOT NULL))";
            $inwardParams[] = $concernId;
        }
        $inwardSql .= ' ORDER BY pi.received_at DESC, pi.id DESC, pii.id DESC';
        $inwardRows = Database::fetchAll($inwardSql, $inwardParams);

        $inwardBuckets = [];
        foreach ($inwardRows as $row) {
            $locType = strtoupper((string)$row['destination_type']);
            $locId = $locType === 'CONCERN'
                ? (int)($row['destination_concern_id'] ?? 0)
                : (int)($row['destination_unit_id'] ?? 0);
            $variantId = (int)$row['variant_id'];
            if ($locId <= 0 || $variantId <= 0) {
                continue;
            }
            $bucketKey = $variantId . '|' . $locType . '|' . $locId;
            if (!isset($inwardBuckets[$bucketKey])) {
                [$mrp, $defaultPrice] = self::resolvePrices($row);
                $inwardBuckets[$bucketKey] = [
                    'item_key' => 'inward:' . $locType . ':' . $locId . ':' . $variantId,
                    'source_kind' => 'INWARD',
                    'qc_item_id' => (int)($row['origin_qc_item_id'] ?? 0),
                    'qc_batch_id' => (int)($row['qc_batch_id'] ?? 0),
                    'variant_id' => $variantId,
                    'production_unit_id' => $locType === 'PRODUCTION_UNIT' ? $locId : 0,
                    'stock_location_type' => $locType,
                    'stock_location_id' => $locId,
                    'location_label' => (string)$row['location_label'],
                    'product_name' => (string)$row['product_name'],
                    'program_number' => (string)($row['batch_number'] ?: ($row['grn_number'] ?? '')),
                    'sku' => (string)(!empty($row['sku']) ? $row['sku'] : ($row['product_code'] ?? '')),
                    'size_name' => (string)(!empty($row['qc_size_name']) ? $row['qc_size_name'] : ($row['size_from_variant'] ?? '')),
                    'color_name' => (string)(!empty($row['qc_color_name']) ? $row['qc_color_name'] : ($row['color_from_variant'] ?? '')),
                    'mrp' => $mrp,
                    'unit_price' => $defaultPrice,
                    'gst_percentage' => max(0, (float)($row['gst_percentage'] ?? 0)),
                    'available_tags' => [],
                ];
            }

            $damagedSet = array_fill_keys(ProductionInwardService::decodePieceCodeList($row['damaged_codes'] ?? null), true);
            $missingSet = array_fill_keys(ProductionInwardService::decodePieceCodeList($row['missing_codes'] ?? null), true);
            foreach (ProductionInwardService::decodePieceCodeList($row['scanned_codes'] ?? null) as $tag) {
                $upper = strtoupper(trim($tag));
                if ($upper === ''
                    || isset($damagedSet[$upper])
                    || isset($missingSet[$upper])
                    || isset($soldTags[$upper])
                    || isset($deletedTags[$upper])
                    || isset($quarantinedConcernTags[$upper])
                    || isset($packedInwardTags[$upper])
                    || isset($seenTags[$upper])) {
                    continue;
                }
                $seenTags[$upper] = true;
                $inwardBuckets[$bucketKey]['available_tags'][] = $upper;
            }
        }

        // 3. Returned inward items in this concern whose original item came from inward stock
        $returnedInwardRows = Database::fetchAll(
            "SELECT ri.new_tag_code, ri.original_tag_code, ri.qc_item_id, ri.qc_batch_id, ri.variant_id,
                    ri.product_name, ri.sku, ri.size_name, ri.color_name, ri.unit_value,
                    pb.id AS bill_id, p.gst_percentage, pv.mrp AS variant_mrp, pv.selling_price
             FROM production_return_inventory ri
             JOIN production_bill_returns pbr ON pbr.id = ri.return_id
             JOIN production_bills pb ON pb.id = pbr.bill_id
             LEFT JOIN product_variants pv ON pv.id = ri.variant_id
             LEFT JOIN products p ON p.id = pv.product_id
             WHERE pbr.concern_id = ? AND ri.status = 'AVAILABLE'
             ORDER BY ri.id ASC",
            [$concernId]
        );
        foreach ($returnedInwardRows as $retRow) {
            $newTag = strtoupper(trim((string)($retRow['new_tag_code'] ?? '')));
            if ($newTag === ''
                || isset($soldTags[$newTag])
                || isset($deletedTags[$newTag])
                || isset($quarantinedConcernTags[$newTag])
                || isset($packedInwardTags[$newTag])
                || isset($seenTags[$newTag])) {
                continue;
            }
            $variantId = (int)($retRow['variant_id'] ?? 0);
            $loc = self::resolveTagStockLocation($concernId, (int)$retRow['bill_id'], $variantId, (string)$retRow['original_tag_code']);
            if ($loc === null) {
                continue;
            }
            $locType = $loc['location_type'];
            $locId = (int)$loc['location_id'];
            if ($locType === 'PRODUCTION_UNIT' && $assignedUnitId !== null && $locId !== $assignedUnitId) {
                continue;
            }
            if ($variantId <= 0) {
                $variantId = (int)($loc['variant_id'] ?? 0);
            }
            $bucketKey = $variantId . '|' . $locType . '|' . $locId;
            if (!isset($inwardBuckets[$bucketKey])) {
                $mrp = (float)($retRow['variant_mrp'] ?? $retRow['unit_value'] ?? 0);
                $price = (float)($retRow['selling_price'] ?? 0);
                if ($price <= 0) {
                    $price = (float)($retRow['unit_value'] ?? $mrp);
                }
                $inwardBuckets[$bucketKey] = [
                    'item_key' => 'inward:' . $locType . ':' . $locId . ':' . $variantId,
                    'source_kind' => 'INWARD',
                    'qc_item_id' => (int)($retRow['qc_item_id'] ?? 0),
                    'qc_batch_id' => (int)($retRow['qc_batch_id'] ?? 0),
                    'variant_id' => $variantId,
                    'production_unit_id' => $locType === 'PRODUCTION_UNIT' ? $locId : 0,
                    'stock_location_type' => $locType,
                    'stock_location_id' => $locId,
                    'location_label' => $locType === 'CONCERN' ? 'Concern Inventory' : 'Production Unit',
                    'product_name' => (string)$retRow['product_name'],
                    'program_number' => 'Returned Inward',
                    'sku' => (string)($retRow['sku'] ?? ''),
                    'size_name' => (string)($retRow['size_name'] ?? ''),
                    'color_name' => (string)($retRow['color_name'] ?? ''),
                    'mrp' => $mrp,
                    'unit_price' => $price,
                    'gst_percentage' => max(0, (float)($retRow['gst_percentage'] ?? 0)),
                    'available_tags' => [],
                ];
            }
            $seenTags[$newTag] = true;
            $inwardBuckets[$bucketKey]['available_tags'][] = $newTag;
        }

        foreach ($inwardBuckets as $bucketKey => $bucket) {
            $stockQty = (int)floor((float)($stockMap[$bucketKey] ?? 0.0));
            if ($stockQty <= 0 || empty($bucket['available_tags'])) {
                continue;
            }
            $bucket['stock_qty'] = $stockQty;
            $available[] = $bucket;
        }

        return $available;
    }

    public static function resolveBillItemEntry(array $submitted, array $inventoryByKey, array $inventory): ?array
    {
        $itemKey = trim((string)($submitted['item_key'] ?? ''));
        if ($itemKey !== '' && isset($inventoryByKey[$itemKey])) {
            return $inventoryByKey[$itemKey];
        }

        $submittedTags = array_values(array_unique(array_filter(array_map(
            static fn($tag): string => strtoupper(trim((string)$tag)),
            (array)($submitted['tags'] ?? [])
        ))));
        $qcItemId = (int)($submitted['qc_item_id'] ?? 0);

        if ($submittedTags) {
            foreach ($inventory as $entry) {
                $entryTags = array_fill_keys(array_map('strtoupper', $entry['available_tags']), true);
                if (isset($entryTags[$submittedTags[0]])) {
                    return $entry;
                }
            }
        }

        if ($qcItemId > 0 && isset($inventoryByKey['qc:' . $qcItemId])) {
            return $inventoryByKey['qc:' . $qcItemId];
        }

        foreach ($inventory as $entry) {
            if ($qcItemId > 0 && (int)$entry['qc_item_id'] === $qcItemId) {
                return $entry;
            }
        }

        return null;
    }

    public static function restoreBillStock(int $billId, int $concernId, int $userId, string $transactionType = 'PRODUCTION_BILL_REVERSAL'): void
    {
        if ($billId <= 0 || $concernId <= 0) {
            return;
        }
        $rows = Database::fetchAll(
            "SELECT variant_id, location_type, location_id, stock_state, SUM(quantity) AS net_qty
             FROM stock_transactions
             WHERE reference_type = 'production_bills' AND reference_id = ? AND concern_id = ?
             GROUP BY variant_id, location_type, location_id, stock_state
             HAVING SUM(quantity) < 0",
            [$billId, $concernId]
        );
        foreach ($rows as $row) {
            $restoreQty = abs((float)$row['net_qty']);
            if ($restoreQty <= 0) {
                continue;
            }
            StockService::increase(
                (int)$row['variant_id'],
                strtoupper((string)$row['location_type']),
                (int)$row['location_id'],
                strtoupper((string)$row['stock_state']),
                $restoreQty,
                $transactionType,
                'production_bills',
                $billId,
                $userId ?: null,
                'Restore stock for Production Bill #' . $billId
            );
        }
    }

    public static function decreaseBillStockBuckets(array $buckets, int $billId, string $billNumber, int $userId): void
    {
        foreach ($buckets as $bucket) {
            $qty = (int)($bucket['quantity'] ?? 0);
            $variantId = (int)($bucket['variant_id'] ?? 0);
            $locType = strtoupper(trim((string)($bucket['location_type'] ?? '')));
            $locId = (int)($bucket['location_id'] ?? 0);
            if ($qty <= 0 || $variantId <= 0 || $locType === '' || $locId <= 0) {
                continue;
            }
            StockService::decrease(
                $variantId,
                $locType,
                $locId,
                'AVAILABLE',
                $qty,
                'PRODUCTION_BILL',
                'production_bills',
                $billId,
                $userId ?: null,
                'Production bill ' . $billNumber
            );
        }
    }

    public static function findAvailablePieceByTag(string $tagCode): ?array
    {
        $needle = strtoupper(trim($tagCode));
        if ($needle === '') {
            return null;
        }
        foreach (self::getAvailableInventory(0) as $entry) {
            foreach ($entry['available_tags'] as $candidate) {
                if (strtoupper(trim($candidate)) === $needle) {
                    return [
                        'item_key' => $entry['item_key'],
                        'source_kind' => $entry['source_kind'],
                        'qc_item_id' => (int)$entry['qc_item_id'],
                        'qc_batch_id' => (int)$entry['qc_batch_id'],
                        'variant_id' => (int)$entry['variant_id'],
                        'production_unit_id' => (int)($entry['production_unit_id'] ?? 0),
                        'stock_location_type' => $entry['stock_location_type'],
                        'stock_location_id' => $entry['stock_location_id'],
                        'location_label' => (string)($entry['location_label'] ?? ''),
                        'program_number' => (string)($entry['program_number'] ?? ''),
                        'tag_code' => $tagCode,
                        'product_name' => $entry['product_name'],
                        'sku' => $entry['sku'],
                        'size_name' => $entry['size_name'],
                        'color_name' => $entry['color_name'],
                        'unit_price' => (float)$entry['unit_price'],
                        'mrp' => (float)$entry['mrp'],
                        'gst_percentage' => max(0, (float)$entry['gst_percentage']),
                    ];
                }
            }
        }
        return null;
    }

    private static function resolvePrices(array $row): array
    {
        $mrp = (float)($row['variant_mrp'] ?? 0);
        if ($mrp <= 0) {
            $mrp = (float)($row['qc_base_price'] ?? 0);
        }
        $defaultPrice = (float)($row['selling_price'] ?? 0);
        if ($defaultPrice <= 0) {
            $defaultPrice = (float)($row['qc_discount_price'] ?? 0);
        }
        if ($defaultPrice <= 0) {
            $defaultPrice = $mrp;
        }
        return [$mrp, $defaultPrice];
    }
}
