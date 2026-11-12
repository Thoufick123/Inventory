<?php

class ProductionInwardService {
    private const SOURCE_TYPES = ['WAREHOUSE', 'SHOP', 'PRODUCTION_UNIT'];

    public static function getLocations(): array {
        return [
            'WAREHOUSE' => function_exists('getScopedWarehouses') ? getScopedWarehouses() : [],
            'SHOP' => function_exists('getScopedShops') ? getScopedShops() : [],
            'PRODUCTION_UNIT' => function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : [],
        ];
    }

    public static function getReceipts(?int $unitId = null, string $search = '', string $destinationType = 'PRODUCTION_UNIT'): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return [];
        }
        $destinationType = strtoupper($destinationType);
        if (!in_array($destinationType, ['PRODUCTION_UNIT', 'CONCERN'], true)) {
            throw new InvalidArgumentException('Unsupported inward destination type.');
        }
        $assignedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($assignedUnitId === 0) {
            return [];
        }
        if ($destinationType === 'PRODUCTION_UNIT' && $assignedUnitId !== null) {
            if ($unitId !== null && $unitId !== $assignedUnitId) {
                return [];
            }
            $unitId = $assignedUnitId;
        } elseif ($destinationType === 'CONCERN') {
            $unitId = null;
        }
        $sql = "SELECT pi.*, COALESCE(dest.name, dest_concern.name, 'Concern inventory') AS destination_name,
                       CASE pi.source_type
                           WHEN 'WAREHOUSE' THEN wh.name
                           WHEN 'SHOP' THEN sh.name
                           WHEN 'PRODUCTION_UNIT' THEN src_unit.name
                           WHEN 'PRODUCTION_BATCH' THEN src_concern.name
                       END AS source_name,
                       u.full_name AS received_by_name,
                       COUNT(pii.id) AS line_count,
                       COALESCE(SUM(pii.quantity + pii.damaged_qty), 0) AS total_quantity,
                       COALESCE(SUM(pii.damaged_qty), 0) AS total_damaged,
                       COALESCE(SUM(pii.missing_qty), 0) AS total_missing,
                       COALESCE(SUM(pii.excess_qty), 0) AS total_excess
                FROM production_inwards pi
                LEFT JOIN production_units dest ON pi.destination_type = 'PRODUCTION_UNIT'
                    AND dest.id = pi.destination_unit_id AND dest.concern_id = ?
                LEFT JOIN concerns dest_concern ON pi.destination_type = 'CONCERN'
                    AND dest_concern.id = pi.destination_concern_id AND dest_concern.id = pi.concern_id
                LEFT JOIN warehouses wh ON pi.source_type = 'WAREHOUSE' AND wh.id = pi.source_id AND wh.concern_id = ?
                LEFT JOIN shops sh ON pi.source_type = 'SHOP' AND sh.id = pi.source_id AND sh.concern_id = ?
                LEFT JOIN production_units src_unit ON pi.source_type = 'PRODUCTION_UNIT' AND src_unit.id = pi.source_id AND src_unit.concern_id = ?
                LEFT JOIN production_batches src_batch ON pi.source_type = 'PRODUCTION_BATCH' AND src_batch.id = pi.source_id
                LEFT JOIN concerns src_concern ON src_batch.concern_id = src_concern.id
                LEFT JOIN users u ON u.id = pi.received_by
                LEFT JOIN production_inward_items pii ON pii.inward_id = pi.id
                WHERE pi.status = 'RECEIVED' AND pi.concern_id = ?";
        $params = [$concernId, $concernId, $concernId, $concernId, $concernId];
        $sql .= ' AND pi.destination_type = ?';
        $params[] = $destinationType;
        if ($unitId !== null) {
            $sql .= ' AND pi.destination_unit_id = ?';
            $params[] = $unitId;
        }
        if ($search !== '') {
            $sql .= ' AND (pi.grn_number LIKE ? OR pi.source_batch_number LIKE ? OR dest.name LIKE ? OR dest_concern.name LIKE ? OR wh.name LIKE ? OR sh.name LIKE ? OR src_unit.name LIKE ? OR src_concern.name LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
        }
        $sql .= ' GROUP BY pi.id ORDER BY pi.received_at DESC, pi.id DESC LIMIT 200';
        $receipts = Database::fetchAll($sql, $params);

        foreach ($receipts as &$receipt) {
            $receipt['items'] = Database::fetchAll(
                "SELECT pii.*, pv.sku, p.name AS product_name, s.name AS size_name, c.name AS color_name
                 FROM production_inward_items pii
                 JOIN product_variants pv ON pv.id = pii.variant_id
                 JOIN products p ON p.id = pv.product_id
                 LEFT JOIN sizes s ON s.id = pv.size_id
                 LEFT JOIN colors c ON c.id = pv.color_id
                 WHERE pii.inward_id = ? ORDER BY p.name, pv.sku",
                [(int)$receipt['id']]
            );
            if (($receipt['source_type'] ?? '') === 'PRODUCTION_BATCH') {
                $itemsByVariant = [];
                foreach ($receipt['items'] as &$receiptItem) {
                    $variantId = (int)$receiptItem['variant_id'];
                    $itemsByVariant[$variantId] = &$receiptItem;
                    $receiptItem['scanned_piece_codes'] = self::decodePieceCodeList($receiptItem['scanned_codes'] ?? null);
                    $receiptItem['damaged_piece_codes'] = self::decodePieceCodeList($receiptItem['damaged_codes'] ?? null);
                    $receiptItem['missing_piece_codes'] = self::decodePieceCodeList($receiptItem['missing_codes'] ?? null);
                    $receiptItem['excess_piece_codes'] = self::decodePieceCodeList($receiptItem['excess_codes'] ?? null);
                }
                unset($receiptItem);
                foreach (ProductionBatch::getItems((int)$receipt['source_id']) as $batchItem) {
                    $variantId = (int)$batchItem['variant_id'];
                    if (!isset($itemsByVariant[$variantId])) continue;
                    $batchDamaged = self::decodePieceCodeList($batchItem['damaged_qr_codes'] ?? null);
                    $batchMissing = self::decodePieceCodeList($batchItem['missing_qr_codes'] ?? null);
                    $itemsByVariant[$variantId]['damaged_piece_codes'] = array_values(array_unique(array_merge(
                        $itemsByVariant[$variantId]['damaged_piece_codes'],
                        $batchDamaged
                    )));
                    $itemsByVariant[$variantId]['missing_piece_codes'] = array_values(array_unique(array_merge(
                        $itemsByVariant[$variantId]['missing_piece_codes'],
                        $batchMissing
                    )));
                    $known = array_fill_keys(array_merge(
                        $itemsByVariant[$variantId]['scanned_piece_codes'],
                        $itemsByVariant[$variantId]['missing_piece_codes']
                    ), true);
                    foreach (($batchItem['outward_piece_tags'] ?? []) as $tag) {
                        $tag = self::normalizeScannedPieceTag((string)$tag);
                        if ($tag !== '' && !isset($known[$tag])) {
                            $itemsByVariant[$variantId]['missing_piece_codes'][] = $tag;
                            $known[$tag] = true;
                        }
                    }
                }
            }
        }
        unset($receipt);
        return $receipts;
    }

    public static function getPendingConcernBatches(): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return [];
        }
        $assignedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($assignedUnitId === 0) {
            return [];
        }
        $batches = Database::fetchAll(
            "SELECT pb.id, pb.batch_number, pb.total_qty, pb.box_count, pb.created_at, pb.source_type,
                    COALESCE(pp.program_number, 'Inward Stock') AS program_number,
                    COALESCE(p.name, pp.name, (SELECT GROUP_CONCAT(DISTINCT bp.name SEPARATOR ', ') FROM production_batch_items bpbi JOIN product_variants bpv ON bpv.id = bpbi.variant_id JOIN products bp ON bp.id = bpv.product_id WHERE bpbi.production_batch_id = pb.id), 'Finished Product') AS product_name,
                    src_concern.name AS source_concern_name, po.outward_number, po.dispatch_date,
                    po.vehicle_number, po.transporter_name, po.lr_number
             FROM production_batches pb
             LEFT JOIN production_programs pp ON pp.id = pb.program_id
             LEFT JOIN products p ON p.id = pp.product_id
             JOIN concerns src_concern ON src_concern.id = pb.concern_id
             JOIN production_outwards po ON po.production_batch_id = pb.id AND po.status = 'DISPATCHED'
             WHERE pb.destination_concern_id = ? AND pb.status = 'DISPATCHED'
               AND NOT EXISTS (
                   SELECT 1 FROM production_inwards pi
                   WHERE pi.source_type = 'PRODUCTION_BATCH' AND pi.source_id = pb.id
                     AND pi.status = 'RECEIVED'
               )
             ORDER BY po.dispatch_date ASC, pb.id ASC",
            [$concernId]
        );
        foreach ($batches as &$batch) {
            $batch['items'] = array_values(array_map(
                static function (array $item): array {
                    $item['expected_qty'] = (int)$item['quantity'];
                    $item['piece_tags'] = array_values(array_map(
                        static fn($tag): string => strtoupper(trim((string)$tag)),
                        is_array($item['outward_piece_tags'] ?? null) ? $item['outward_piece_tags'] : []
                    ));
                    return $item;
                },
                array_filter(
                    ProductionBatch::getItems((int)$batch['id']),
                    static fn(array $item): bool => (int)($item['quantity'] ?? 0) > 0
                )
            ));
        }
        unset($batch);
        return $batches;
    }

    public static function receiveConcernBatch(
        int $batchId,
        string $destinationType,
        int $destinationId,
        array $submittedScans,
        string $notes,
        int $userId
    ): string {
        $destinationType = strtoupper(trim($destinationType));
        if (!in_array($destinationType, ['PRODUCTION_UNIT', 'CONCERN'], true)) {
            throw new InvalidArgumentException('Choose a valid destination inventory.');
        }
        $concernId = (int)currentConcernId();
        if ($destinationType === 'CONCERN' && $destinationId !== $concernId) {
            throw new InvalidArgumentException('The concern inventory must belong to the active concern.');
        }
        StockService::assertLocationBelongsToActiveConcern($destinationType, $destinationId);
        Database::beginTransaction();
        try {
            $batch = Database::fetchOne(
                "SELECT pb.* FROM production_batches pb
                 WHERE pb.id = ? AND pb.destination_concern_id = ? AND pb.status = 'DISPATCHED'
                 FOR UPDATE",
                [$batchId, $concernId]
            );
            if (!$batch) {
                throw new RuntimeException('This batch is not awaiting receipt for the active concern.');
            }
            if (Database::fetchOne(
                "SELECT id FROM production_inwards
                 WHERE source_type = 'PRODUCTION_BATCH' AND source_id = ? AND status = 'RECEIVED'
                 LIMIT 1",
                [$batchId]
            )) {
                throw new RuntimeException('This production batch has already been received.');
            }

            $batchItems = Database::fetchAll(
                "SELECT id, variant_id, quantity
                 FROM production_batch_items
                 WHERE production_batch_id = ? AND quantity > 0
                 FOR UPDATE",
                [$batchId]
            );
            if (!$batchItems) {
                throw new RuntimeException('This production batch has no items to receive.');
            }
            $batchDetails = [];
            foreach (ProductionBatch::getItems($batchId) as $item) {
                $batchDetails[(int)$item['id']] = $item;
            }
            $expectedTags = [];
            $extraTags = [];
            foreach ($batchItems as $batchItem) {
                $itemId = (int)$batchItem['id'];
                $item = $batchDetails[$itemId] ?? null;
                $tags = is_array($item['outward_piece_tags'] ?? null)
                    ? array_values(array_filter(array_map(
                        static fn($tag): string => strtoupper(trim((string)$tag)),
                        $item['outward_piece_tags']
                    )))
                    : [];
                if (count($tags) !== (int)$batchItem['quantity'] || count($tags) !== count(array_unique($tags))) {
                    throw new RuntimeException(
                        'This batch does not have one unique product QR label per dispatched piece. The sending concern must label and repack it before inward scanning.'
                    );
                }
                foreach ($tags as $tag) {
                    if (isset($expectedTags[$tag])) {
                        throw new RuntimeException('The dispatched batch contains duplicate product QR labels. Contact the sending concern.');
                    }
                    $expectedTags[$tag] = [
                        'item_id' => $itemId,
                        'variant_id' => (int)$batchItem['variant_id'],
                    ];
                }
                $approvedTags = is_array($item['approved_piece_tags'] ?? null) ? $item['approved_piece_tags'] : [];
                foreach ($approvedTags as $tag) {
                    $tag = self::normalizeScannedPieceTag((string)$tag);
                    if ($tag !== '' && !isset($expectedTags[$tag])) {
                        $extraTags[$tag] = [
                            'item_id' => $itemId,
                            'variant_id' => (int)$batchItem['variant_id'],
                        ];
                    }
                }
            }
            $extraTags = array_diff_key($extraTags, $expectedTags);
            if (count($expectedTags) === 0) {
                throw new RuntimeException('This batch has no unique product QR labels to scan.');
            }
            $scansByTag = self::validateConcernBatchScans($expectedTags, $submittedScans, $extraTags);
            $itemsByVariant = [];
            $totalReceived = count($scansByTag);
            $totalAccepted = 0;
            $totalDamaged = 0;
            $totalMissing = 0;
            foreach ($batchItems as $batchItem) {
                $variantId = (int)$batchItem['variant_id'];
                if (!isset($itemsByVariant[$variantId])) {
                    $itemsByVariant[$variantId] = [
                        'expected' => 0, 'missing' => 0, 'accepted_tags' => [],
                        'damaged_tags' => [], 'missing_tags' => [], 'excess_tags' => [],
                        'all_tags' => [],
                    ];
                }
                $itemsByVariant[$variantId]['expected'] += (int)$batchItem['quantity'];
            }
            foreach ($scansByTag as $tag => $scan) {
                $variantId = $scan['variant_id'];
                if (!isset($itemsByVariant[$variantId])) {
                    $itemsByVariant[$variantId] = [
                        'expected' => 0, 'missing' => 0, 'accepted_tags' => [],
                        'damaged_tags' => [], 'missing_tags' => [], 'excess_tags' => [],
                        'all_tags' => [],
                    ];
                }
                $itemsByVariant[$variantId]['all_tags'][] = $tag;
                if ($scan['is_excess']) {
                    $itemsByVariant[$variantId]['excess_tags'][] = $tag;
                }
                if ($scan['condition'] === 'DAMAGED') {
                    $itemsByVariant[$variantId]['damaged_tags'][] = $tag;
                    $totalDamaged++;
                } else {
                    $itemsByVariant[$variantId]['accepted_tags'][] = $tag;
                    $totalAccepted++;
                }
            }
            foreach ($batchItems as $batchItem) {
                $itemId = (int)$batchItem['id'];
                $variantId = (int)$batchItem['variant_id'];
                $acceptedTags = [];
                $damagedTags = [];
                $missingTags = [];
                foreach ($expectedTags as $tag => $expected) {
                    if ($expected['item_id'] !== $itemId) {
                        continue;
                    }
                    if (!isset($scansByTag[$tag])) {
                        $missingTags[] = $tag;
                    } elseif ($scansByTag[$tag]['condition'] === 'DAMAGED') {
                        $damagedTags[] = $tag;
                    } else {
                        $acceptedTags[] = $tag;
                    }
                }
                $itemsByVariant[$variantId]['missing'] += count($missingTags);
                $itemsByVariant[$variantId]['missing_tags'] = array_merge(
                    $itemsByVariant[$variantId]['missing_tags'],
                    $missingTags
                );
                $totalMissing += count($missingTags);
                Database::execute(
                    "UPDATE production_batch_items
                     SET received_qty = ?, damaged_qty = ?, missing_qty = ?,
                         received_qr_codes = ?, damaged_qr_codes = ?, missing_qr_codes = ?
                     WHERE id = ? AND production_batch_id = ?",
                    [count($acceptedTags), count($damagedTags), count($missingTags),
                        $acceptedTags ? implode(',', $acceptedTags) : null,
                        $damagedTags ? implode(',', $damagedTags) : null,
                        $missingTags ? implode(',', $missingTags) : null,
                        $itemId, $batchId]
                );
            }

            $temporaryNumber = 'TMP-PI-' . bin2hex(random_bytes(10));
            Database::execute(
                "INSERT INTO production_inwards
                    (grn_number, source_type, source_id, destination_type, destination_unit_id,
                     destination_concern_id, source_batch_number, notes, received_by, concern_id)
                 VALUES (?, 'PRODUCTION_BATCH', ?, ?, ?, ?, ?, ?, ?, ?)",
                [$temporaryNumber, $batchId, $destinationType,
                    $destinationType === 'PRODUCTION_UNIT' ? $destinationId : null,
                    $destinationType === 'CONCERN' ? $concernId : null,
                    $batch['batch_number'],
                    $notes !== '' ? $notes : null, $userId ?: null, $concernId]
            );
            $inwardId = (int)Database::lastInsertId();
            $grnNumber = 'PGRN-' . date('Y') . '-' . sprintf('%06d', $inwardId);
            Database::execute('UPDATE production_inwards SET grn_number = ? WHERE id = ?', [$grnNumber, $inwardId]);

            foreach ($itemsByVariant as $variantId => $item) {
                Database::execute(
                    "INSERT INTO production_inward_items
                        (inward_id, variant_id, quantity, dispatched_qty, damaged_qty, missing_qty,
                         excess_qty, excess_codes, scanned_codes, damaged_codes, missing_codes, concern_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$inwardId, $variantId, count($item['accepted_tags']), $item['expected'],
                        count($item['damaged_tags']), $item['missing'],
                        count($item['excess_tags']),
                        $item['excess_tags'] ? implode(',', $item['excess_tags']) : null,
                        json_encode($item['all_tags']),
                        $item['damaged_tags'] ? implode(',', $item['damaged_tags']) : null,
                        $item['missing_tags'] ? implode(',', $item['missing_tags']) : null,
                        $concernId]
                );
                $availableQty = count($item['accepted_tags']);
                if ($availableQty > 0) {
                    StockService::increase(
                        (int)$variantId, $destinationType, $destinationId, 'AVAILABLE',
                        $availableQty, 'INWARD', 'production_inwards', $inwardId, $userId ?: null,
                        'Production batch receipt ' . $grnNumber
                    );
                }
                if (!empty($item['damaged_tags'])) {
                    StockService::increase(
                        (int)$variantId, $destinationType, $destinationId, 'DAMAGED',
                        count($item['damaged_tags']), 'INWARD_DAMAGED', 'production_inwards', $inwardId, $userId ?: null,
                        'Damaged units in production batch receipt ' . $grnNumber
                    );
                }
            }

            Database::execute(
                "UPDATE production_batches
                 SET received_qty = ?, damaged_qty = ?, missing_qty = ?, status = 'RECEIVED'
                 WHERE id = ? AND destination_concern_id = ? AND status = 'DISPATCHED'",
                [$totalAccepted, $totalDamaged, $totalMissing, $batchId, $concernId]
            );
            $totalExcess = array_sum(array_map(
                static fn(array $item): int => count($item['excess_tags']),
                $itemsByVariant
            ));
            $outwardStatus = ($totalDamaged > 0 || $totalMissing > 0 || $totalExcess > 0) ? 'DISCREPANCY' : 'RECEIVED';
            Database::execute(
                "UPDATE production_outwards
                 SET status = ?, received_by = ?, received_date = NOW()
                 WHERE production_batch_id = ? AND status = 'DISPATCHED'",
                [$outwardStatus, $userId ?: null, $batchId]
            );
            AuditService::log('production', 'RECEIVE_GRN', 'production_batch', $batchId, null, [
                'grn_number' => $grnNumber,
                'destination_type' => $destinationType,
                'destination_id' => $destinationId,
                'total_received' => $totalReceived,
                'total_accepted' => $totalAccepted,
                'total_damaged' => $totalDamaged,
                'total_missing' => $totalMissing,
                'total_excess' => $totalExcess,
                'total_expected' => (int)$batch['total_qty'],
            ]);
            Database::commit();
            return $grnNumber;
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            throw $e;
        }
    }

    public static function getUnitStock(int $unitId): array {
        StockService::assertLocationBelongsToActiveConcern('PRODUCTION_UNIT', $unitId);
        $concernId = (int)currentConcernId();
        return Database::fetchAll(
            "SELECT sb.variant_id, sb.quantity, pv.sku, pv.mrp, pv.selling_price,
                    p.name AS product_name, s.name AS size_name, c.name AS color_name
             FROM stock_balances sb
             JOIN product_variants pv ON pv.id = sb.variant_id
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             LEFT JOIN colors c ON c.id = pv.color_id
             WHERE sb.location_type = 'PRODUCTION_UNIT' AND sb.location_id = ? AND sb.concern_id = ?
               AND sb.stock_state = 'AVAILABLE' AND sb.quantity > 0
             ORDER BY p.name, s.sort_order, c.name, pv.sku",
            [$unitId, $concernId]
        );
    }

    public static function getConcernStock(): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null || $concernId <= 0) {
            return [];
        }
        $concernId = (int)$concernId;
        StockService::assertLocationBelongsToActiveConcern('CONCERN', $concernId);
        return Database::fetchAll(
            "SELECT sb.variant_id, sb.quantity, pv.sku, pv.mrp, pv.selling_price,
                    p.name AS product_name, s.name AS size_name, c.name AS color_name
             FROM stock_balances sb
             JOIN product_variants pv ON pv.id = sb.variant_id
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             LEFT JOIN colors c ON c.id = pv.color_id
             WHERE sb.location_type = 'CONCERN' AND sb.location_id = ? AND sb.concern_id = ?
               AND sb.stock_state = 'AVAILABLE' AND sb.quantity > 0
             ORDER BY p.name, s.sort_order, c.name, pv.sku",
            [$concernId, $concernId]
        );
    }

    public static function normalizeScannedPieceTag(string $code): string {
        $code = trim($code);
        if ($code === '') {
            return '';
        }
        if (str_starts_with($code, '{') && str_ends_with($code, '}')) {
            $payload = json_decode($code, true);
            if (is_array($payload)) {
                $code = (string)($payload['tag_code'] ?? $payload['piece_tag'] ?? $payload['qr_code'] ?? $payload['code'] ?? '');
            }
        } elseif (str_contains($code, '|')) {
            $code = explode('|', $code, 2)[0];
        }
        return strtoupper(trim($code));
    }

    public static function validateConcernBatchScans(array $expectedTags, array $submittedScans, array $extraTags = []): array {
        $scansByTag = [];
        foreach ($submittedScans as $scan) {
            if (!is_array($scan)) {
                throw new InvalidArgumentException('A scanned product record is invalid. Scan the batch again.');
            }
            $tag = self::normalizeScannedPieceTag((string)($scan['tag'] ?? ''));
            $condition = strtoupper(trim((string)($scan['condition'] ?? '')));
            $isExcess = !isset($expectedTags[$tag]);
            $tagDetails = $isExcess ? ($extraTags[$tag] ?? null) : $expectedTags[$tag];
            if ($tag === '' || $tagDetails === null) {
                throw new InvalidArgumentException('A scanned QR code does not belong to this dispatched batch.');
            }
            if (isset($scansByTag[$tag])) {
                throw new InvalidArgumentException('A product QR code was scanned more than once.');
            }
            if (!in_array($condition, ['ACCEPTED', 'DAMAGED'], true)) {
                throw new InvalidArgumentException('Each scanned product must be marked accepted or damaged.');
            }
            $scansByTag[$tag] = [
                'item_id' => (int)$tagDetails['item_id'],
                'variant_id' => (int)$tagDetails['variant_id'],
                'condition' => $condition,
                'is_excess' => $isExcess,
            ];
        }
        return $scansByTag;
    }

    public static function getConcernBatchDiscrepancies(int $batchId): array {
        return Database::fetchAll(
            "SELECT pii.dispatched_qty, pii.quantity, pii.damaged_qty, pii.missing_qty,
                    pii.excess_qty, pii.missing_codes, pii.excess_codes,
                    pv.sku, p.name AS product_name, s.name AS size_name, c.name AS color_name
             FROM production_inwards pi
             JOIN production_inward_items pii ON pii.inward_id = pi.id
             JOIN product_variants pv ON pv.id = pii.variant_id
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             LEFT JOIN colors c ON c.id = pv.color_id
             WHERE pi.source_type = 'PRODUCTION_BATCH' AND pi.source_id = ? AND pi.status = 'RECEIVED'
             ORDER BY p.name, pv.sku",
            [$batchId]
        );
    }

    public static function updateConcernBatchPieceStatuses(int $inwardId, array $submittedStatuses, int $userId): void {
        $concernId = (int)currentConcernId();
        Database::beginTransaction();
        try {
            $receipt = Database::fetchOne(
                "SELECT pi.*, pb.id AS batch_id, pb.batch_number, pb.concern_id AS source_concern_id
                 FROM production_inwards pi
                 JOIN production_batches pb ON pi.source_type = 'PRODUCTION_BATCH' AND pb.id = pi.source_id
                 WHERE pi.id = ? AND pi.concern_id = ? AND pi.status = 'RECEIVED'
                   AND pb.destination_concern_id = ?
                 FOR UPDATE",
                [$inwardId, $concernId, $concernId]
            );
            if (!$receipt) {
                throw new RuntimeException('Received production batch not found for the active concern.');
            }

            $destinationType = strtoupper((string)$receipt['destination_type']);
            $destinationId = $destinationType === 'CONCERN'
                ? (int)$receipt['destination_concern_id']
                : (int)$receipt['destination_unit_id'];
            $assignedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
            if ($assignedUnitId === 0 || ($assignedUnitId !== null
                && $destinationType === 'PRODUCTION_UNIT' && $destinationId !== $assignedUnitId)) {
                throw new RuntimeException('This batch receipt is outside your assigned production inventory.');
            }
            StockService::assertLocationBelongsToActiveConcern($destinationType, $destinationId);

            $batchItems = Database::fetchAll(
                "SELECT id, variant_id, quantity, received_qr_codes, damaged_qr_codes, missing_qr_codes
                 FROM production_batch_items
                 WHERE production_batch_id = ? AND quantity > 0 FOR UPDATE",
                [(int)$receipt['batch_id']]
            );
            $batchDetails = [];
            foreach (ProductionBatch::getItems((int)$receipt['batch_id']) as $item) {
                $batchDetails[(int)$item['id']] = $item;
            }
            $expectedTags = [];
            $tagsByBatchItem = [];
            $oldStatusByTag = [];
            foreach ($batchItems as $batchItem) {
                $itemId = (int)$batchItem['id'];
                $tags = $batchDetails[$itemId]['outward_piece_tags'] ?? [];
                if (!is_array($tags) || count($tags) !== (int)$batchItem['quantity']) {
                    throw new RuntimeException('The batch piece labels are incomplete; receipt statuses cannot be updated safely.');
                }
                foreach ($tags as $tag) {
                    $tag = self::normalizeScannedPieceTag((string)$tag);
                    if ($tag === '' || isset($expectedTags[$tag])) {
                        throw new RuntimeException('The batch has missing or duplicate piece labels; receipt statuses cannot be updated safely.');
                    }
                    $expectedTags[$tag] = [
                        'item_id' => $itemId,
                        'variant_id' => (int)$batchItem['variant_id'],
                    ];
                    $tagsByBatchItem[$itemId][] = $tag;
                }
                $receivedCodes = array_fill_keys(self::decodePieceCodeList($batchItem['received_qr_codes'] ?? null), true);
                $damagedCodes = array_fill_keys(self::decodePieceCodeList($batchItem['damaged_qr_codes'] ?? null), true);
                $missingCodes = array_fill_keys(self::decodePieceCodeList($batchItem['missing_qr_codes'] ?? null), true);
                foreach ($tags as $tag) {
                    $tag = self::normalizeScannedPieceTag((string)$tag);
                    $oldStatusByTag[$tag] = isset($missingCodes[$tag])
                        ? 'MISSING'
                        : (isset($damagedCodes[$tag]) ? 'DAMAGED' : (isset($receivedCodes[$tag]) ? 'VERIFIED' : 'MISSING'));
                }
            }

            $inwardItems = Database::fetchAll(
                'SELECT * FROM production_inward_items WHERE inward_id = ? FOR UPDATE',
                [$inwardId]
            );
            $inwardByVariant = [];
            $oldScannedByVariant = [];
            $extraTags = [];
            foreach ($inwardItems as $inwardItem) {
                $variantId = (int)$inwardItem['variant_id'];
                $inwardByVariant[$variantId] = $inwardItem;
                $scanned = self::decodePieceCodeList($inwardItem['scanned_codes'] ?? null);
                $damaged = array_fill_keys(self::decodePieceCodeList($inwardItem['damaged_codes'] ?? null), true);
                $missing = array_fill_keys(self::decodePieceCodeList($inwardItem['missing_codes'] ?? null), true);
                $excess = self::decodePieceCodeList($inwardItem['excess_codes'] ?? null);
                $oldScannedByVariant[$variantId] = $scanned;
                foreach ($scanned as $tag) {
                    if (!isset($expectedTags[$tag])) {
                        $extraTags[$tag] = ['item_id' => 0, 'variant_id' => $variantId];
                    }
                    $oldStatusByTag[$tag] = isset($missing[$tag]) ? 'MISSING' : (isset($damaged[$tag]) ? 'DAMAGED' : 'VERIFIED');
                }
                foreach ($missing as $tag => $_) {
                    if (isset($expectedTags[$tag])) {
                        $oldStatusByTag[$tag] = 'MISSING';
                    }
                }
                foreach ($excess as $tag) {
                    $extraTags[$tag] = ['item_id' => 0, 'variant_id' => $variantId];
                    $oldStatusByTag[$tag] = isset($missing[$tag]) ? 'MISSING' : (isset($damaged[$tag]) ? 'DAMAGED' : 'VERIFIED');
                }
            }

            $allowedTags = array_merge(array_keys($expectedTags), array_keys($extraTags));
            $normalizedStatuses = [];
            foreach ($submittedStatuses as $rawTag => $rawStatus) {
                $tag = self::normalizeScannedPieceTag((string)$rawTag);
                $status = strtoupper(trim((string)$rawStatus));
                if (!in_array($tag, $allowedTags, true) || !in_array($status, ['VERIFIED', 'DAMAGED', 'MISSING'], true)) {
                    throw new InvalidArgumentException('A piece status is invalid or does not belong to this receipt.');
                }
                $normalizedStatuses[$tag] = $status;
            }
            foreach ($allowedTags as $tag) {
                if (!isset($normalizedStatuses[$tag])) {
                    throw new InvalidArgumentException('Choose a status for every dispatched or received piece.');
                }
            }

            $variantCounts = [];
            foreach ($expectedTags + $extraTags as $tag => $tagData) {
                $variantId = $tagData['variant_id'];
                $oldStatus = $oldStatusByTag[$tag] ?? 'MISSING';
                $newStatus = $normalizedStatuses[$tag];
                $variantCounts[$variantId] ??= ['verified' => 0, 'damaged' => 0, 'missing' => 0];
                if (in_array($oldStatus, ['VERIFIED', 'DAMAGED'], true)) {
                    $variantCounts[$variantId][strtolower($oldStatus)]--;
                }
                if (in_array($newStatus, ['VERIFIED', 'DAMAGED'], true)) {
                    $variantCounts[$variantId][strtolower($newStatus)]++;
                }
                $variantCounts[$variantId]['missing'] += $newStatus === 'MISSING' && isset($expectedTags[$tag]) ? 1 : 0;
            }

            foreach ($variantCounts as $variantId => $counts) {
                $inwardItem = $inwardByVariant[$variantId] ?? null;
                if (!$inwardItem) {
                    throw new RuntimeException('A receipt product line is missing; status changes were not applied.');
                }
                $oldVerified = (int)$inwardItem['quantity'];
                $oldDamaged = (int)$inwardItem['damaged_qty'];
                $newVerified = $oldVerified + $counts['verified'];
                $newDamaged = $oldDamaged + $counts['damaged'];
                if ($newVerified < 0 || $newDamaged < 0) {
                    throw new RuntimeException('The stored receipt status is inconsistent; status changes were not applied.');
                }
                self::applyReceiptStockDelta(
                    (int)$variantId, $destinationType, $destinationId, 'AVAILABLE',
                    $newVerified - $oldVerified, 'INWARD', $inwardId, $userId,
                    'Production batch receipt status correction ' . $receipt['batch_number']
                );
                self::applyReceiptStockDelta(
                    (int)$variantId, $destinationType, $destinationId, 'DAMAGED',
                    $newDamaged - $oldDamaged, 'INWARD_DAMAGED', $inwardId, $userId,
                    'Production batch receipt damage correction ' . $receipt['batch_number']
                );

                $scanned = [];
                $damagedCodes = [];
                $missingCodes = [];
                $excessCodes = [];
                foreach ($expectedTags + $extraTags as $tag => $tagData) {
                    if ($tagData['variant_id'] !== (int)$variantId) continue;
                    $status = $normalizedStatuses[$tag];
                    if ($status !== 'MISSING') $scanned[] = $tag;
                    if ($status === 'DAMAGED') $damagedCodes[] = $tag;
                    if ($status === 'MISSING' && isset($expectedTags[$tag])) $missingCodes[] = $tag;
                    if (isset($extraTags[$tag]) && $status !== 'MISSING') $excessCodes[] = $tag;
                }
                $expectedOnVariant = array_filter(
                    $expectedTags,
                    static fn(array $tagData): bool => $tagData['variant_id'] === (int)$variantId
                );
                Database::execute(
                    "UPDATE production_inward_items
                     SET quantity = ?, damaged_qty = ?, missing_qty = ?, excess_qty = ?,
                         excess_codes = ?, scanned_codes = ?, damaged_codes = ?, missing_codes = ?
                     WHERE id = ? AND inward_id = ?",
                    [$newVerified, $newDamaged, count($missingCodes), count($excessCodes),
                        $excessCodes ? implode(',', $excessCodes) : null,
                        json_encode(array_values(array_unique($scanned))),
                        $damagedCodes ? implode(',', $damagedCodes) : null,
                        $missingCodes ? implode(',', $missingCodes) : null,
                        (int)$inwardItem['id'], $inwardId]
                );

                $batchItemIds = array_keys(array_filter(
                    $tagsByBatchItem,
                    static fn(array $tags): bool => (bool)array_intersect($tags, array_keys($expectedOnVariant))
                ));
                foreach ($batchItemIds as $batchItemId) {
                    $itemTags = $tagsByBatchItem[$batchItemId] ?? [];
                    $receivedTags = array_values(array_filter($itemTags, static fn(string $tag): bool => $normalizedStatuses[$tag] === 'VERIFIED'));
                    $damagedTags = array_values(array_filter($itemTags, static fn(string $tag): bool => $normalizedStatuses[$tag] === 'DAMAGED'));
                    $missingItemTags = array_values(array_filter($itemTags, static fn(string $tag): bool => $normalizedStatuses[$tag] === 'MISSING'));
                    Database::execute(
                        "UPDATE production_batch_items
                         SET received_qty = ?, damaged_qty = ?, missing_qty = ?,
                             received_qr_codes = ?, damaged_qr_codes = ?, missing_qr_codes = ?
                         WHERE id = ? AND production_batch_id = ?",
                        [count($receivedTags), count($damagedTags), count($missingItemTags),
                            $receivedTags ? implode(',', $receivedTags) : null,
                            $damagedTags ? implode(',', $damagedTags) : null,
                            $missingItemTags ? implode(',', $missingItemTags) : null,
                            $batchItemId, (int)$receipt['batch_id']]
                    );
                }
            }

            $totalVerified = 0;
            $totalDamaged = 0;
            $totalMissing = 0;
            foreach ($expectedTags + $extraTags as $tag => $_) {
                if ($normalizedStatuses[$tag] === 'VERIFIED') $totalVerified++;
                if ($normalizedStatuses[$tag] === 'DAMAGED') $totalDamaged++;
                if (isset($expectedTags[$tag]) && $normalizedStatuses[$tag] === 'MISSING') $totalMissing++;
            }
            $totalExcess = count(array_filter(
                array_keys($extraTags),
                static fn(string $tag): bool => $normalizedStatuses[$tag] !== 'MISSING'
            ));
            Database::execute(
                "UPDATE production_batches SET received_qty = ?, damaged_qty = ?, missing_qty = ?
                 WHERE id = ? AND destination_concern_id = ?",
                [$totalVerified, $totalDamaged, $totalMissing, (int)$receipt['batch_id'], $concernId]
            );
            $outwardStatus = ($totalDamaged > 0 || $totalMissing > 0 || $totalExcess > 0) ? 'DISCREPANCY' : 'RECEIVED';
            Database::execute(
                "UPDATE production_outwards SET status = ? WHERE production_batch_id = ?",
                [$outwardStatus, (int)$receipt['batch_id']]
            );
            AuditService::log('production', 'UPDATE_INWARD_PIECE_STATUSES', 'production_inwards', $inwardId, null, [
                'batch_number' => $receipt['batch_number'],
                'verified_qty' => $totalVerified,
                'damaged_qty' => $totalDamaged,
                'missing_qty' => $totalMissing,
                'excess_qty' => $totalExcess,
            ]);
            Database::commit();
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            throw $e;
        }
    }

    private static function applyReceiptStockDelta(
        int $variantId,
        string $locationType,
        int $locationId,
        string $stockState,
        int $delta,
        string $transactionType,
        int $inwardId,
        int $userId,
        string $notes
    ): void {
        if ($delta > 0) {
            StockService::increase($variantId, $locationType, $locationId, $stockState, $delta, $transactionType, 'production_inwards', $inwardId, $userId ?: null, $notes);
        } elseif ($delta < 0) {
            StockService::decrease($variantId, $locationType, $locationId, $stockState, abs($delta), $transactionType, 'production_inwards', $inwardId, $userId ?: null, $notes);
        }
    }

    public static function decodePieceCodeList($raw): array {
        if (is_array($raw)) {
            return array_values(array_unique(array_filter(array_map(
                static function ($code): string {
                    if (is_array($code)) {
                        $code = $code['tag_code'] ?? $code['piece_tag'] ?? $code['qr_code'] ?? $code['code'] ?? '';
                    }
                    return self::normalizeScannedPieceTag((string)$code);
                },
                $raw
            ))));
        }
        $value = trim((string)$raw);
        if ($value === '') return [];
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return self::decodePieceCodeList($decoded);
        }
        return array_values(array_unique(array_filter(array_map(
            static fn($code): string => self::normalizeScannedPieceTag((string)$code),
            explode(',', $value)
        ))));
    }

    public static function getStockAcrossUnits(?int $unitId = null): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return [];
        }
        if ($unitId !== null) {
            StockService::assertLocationBelongsToActiveConcern('PRODUCTION_UNIT', $unitId);
        }
        $assignedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($assignedUnitId === 0 || ($assignedUnitId !== null && $unitId !== null && $unitId !== $assignedUnitId)) {
            return [];
        }
        $sql = "SELECT sb.variant_id,
                       pv.product_id,
                       p.category_id,
                       categories.name AS category_name,
                       CASE WHEN sb.location_type = 'PRODUCTION_UNIT' THEN sb.location_id ELSE NULL END AS production_unit_id,
                       sb.location_type,
                       COALESCE(pu.name, CONCAT(destination_concern.name, ' (Concern Inventory)')) AS production_unit_name,
                       sb.quantity, pv.sku, pv.mrp, pv.selling_price,
                       p.name AS product_name, s.name AS size_name, c.name AS color_name
                FROM stock_balances sb
                LEFT JOIN production_units pu ON sb.location_type = 'PRODUCTION_UNIT'
                    AND pu.id = sb.location_id AND pu.concern_id = ?
                LEFT JOIN concerns destination_concern ON sb.location_type = 'CONCERN'
                    AND destination_concern.id = sb.location_id AND destination_concern.id = ?
                JOIN product_variants pv ON pv.id = sb.variant_id
                JOIN products p ON p.id = pv.product_id
                LEFT JOIN categories ON categories.id = p.category_id
                LEFT JOIN sizes s ON s.id = pv.size_id
                LEFT JOIN colors c ON c.id = pv.color_id
                WHERE sb.stock_state = 'AVAILABLE' AND sb.quantity > 0 AND sb.concern_id = ?";
        $params = [$concernId, $concernId, $concernId];
        if ($unitId !== null) {
            $sql .= " AND sb.location_type = 'PRODUCTION_UNIT' AND pu.id IS NOT NULL AND sb.location_id = ?";
            $params[] = $unitId;
        } elseif ($assignedUnitId !== null) {
            $sql .= " AND ((sb.location_type = 'PRODUCTION_UNIT' AND pu.id IS NOT NULL AND sb.location_id = ?)
                      OR (sb.location_type = 'CONCERN' AND sb.location_id = ?))";
            $params[] = $assignedUnitId;
            $params[] = $concernId;
        } else {
            $sql .= " AND ((sb.location_type = 'PRODUCTION_UNIT' AND pu.id IS NOT NULL)
                      OR (sb.location_type = 'CONCERN' AND sb.location_id = ?))";
            $params[] = $concernId;
        }
        $sql .= ' ORDER BY production_unit_name, p.name, s.sort_order, c.name, pv.sku';
        return Database::fetchAll($sql, $params);
    }

    public static function lookup(string $sourceType, int $sourceId, string $code): array {
        self::assertSource($sourceType, $sourceId);
        $code = trim($code);
        if ($code === '') {
            throw new InvalidArgumentException('Scan a batch number or product QR code.');
        }

        $batch = self::resolveSourceBatch($sourceType, $sourceId, $code);
        if ($batch) {
            if (!$batch['items']) {
                throw new RuntimeException('This batch has no product lines to receive.');
            }
            return ['type' => 'batch', 'batch_number' => $batch['batch_number'], 'items' => $batch['items']];
        }

        $variant = self::findVariantForCode($code, $sourceType, $sourceId);
        if (!$variant) {
            throw new RuntimeException('No product matches this code at the selected source.');
        }
        $available = self::availableAt($sourceType, $sourceId, (int)$variant['id']);
        if ($available < 1) {
            throw new RuntimeException('This product has no available stock at the selected source.');
        }
        return [
            'type' => 'product',
            'variant' => $variant,
            'available' => $available,
        ];
    }

    public static function receive(
        string $sourceType,
        int $sourceId,
        int $destinationUnitId,
        ?string $batchNumber,
        array $submittedItems,
        string $notes,
        int $userId
    ): string {
        self::assertSource($sourceType, $sourceId);
        self::assertDestination($destinationUnitId);
        $concernId = (int)currentConcernId();

        if ($sourceType === 'PRODUCTION_UNIT' && $sourceId === $destinationUnitId) {
            throw new InvalidArgumentException('The source and destination production units must be different.');
        }

        $batchNumber = trim((string)$batchNumber);
        if ($batchNumber !== '') {
            $batch = self::resolveSourceBatch($sourceType, $sourceId, $batchNumber);
            if (!$batch) {
                throw new InvalidArgumentException('The batch or GRN was not found at the selected source.');
            }
            $duplicate = Database::fetchOne(
                "SELECT id FROM production_inwards
                 WHERE concern_id = ? AND source_type = ? AND source_id = ? AND UPPER(source_batch_number) = UPPER(?) AND status = 'RECEIVED'
                 LIMIT 1",
                [$concernId, $sourceType, $sourceId, $batch['batch_number']]
            );
            if ($duplicate) {
                throw new InvalidArgumentException('This source batch has already been received by Production.');
            }
            $batchNumber = $batch['batch_number'];
            $items = $batch['items'];
        } else {
            $items = self::normalizeSubmittedItems($submittedItems, $sourceType, $sourceId);
        }

        if (!$items) {
            throw new InvalidArgumentException('Scan at least one product or a dispatched batch.');
        }

        Database::beginTransaction();
        try {
            $temporaryNumber = 'TMP-PI-' . bin2hex(random_bytes(10));
            Database::execute(
                "INSERT INTO production_inwards
                    (grn_number, source_type, source_id, destination_unit_id, source_batch_number, notes, received_by, concern_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$temporaryNumber, $sourceType, $sourceId, $destinationUnitId,
                    $batchNumber !== '' ? $batchNumber : null, $notes !== '' ? $notes : null, $userId ?: null, $concernId]
            );
            $inwardId = (int)Database::lastInsertId();
            $grnNumber = 'PGRN-' . date('Y') . '-' . sprintf('%06d', $inwardId);
            Database::execute('UPDATE production_inwards SET grn_number = ? WHERE id = ?', [$grnNumber, $inwardId]);

            foreach ($items as $item) {
                $variantId = (int)$item['variant_id'];
                $quantity = (int)$item['quantity'];
                StockService::transfer(
                    $variantId,
                    $sourceType,
                    $sourceId,
                    'PRODUCTION_UNIT',
                    $destinationUnitId,
                    $quantity,
                    'production_inwards',
                    $inwardId,
                    $userId ?: null,
                    'Production inward ' . $grnNumber
                );
                Database::execute(
                    "INSERT INTO production_inward_items (inward_id, variant_id, quantity, scanned_codes, concern_id)
                     VALUES (?, ?, ?, ?, ?)",
                    [$inwardId, $variantId, $quantity, !empty($item['codes']) ? json_encode(array_values($item['codes'])) : null, $concernId]
                );
            }

            Database::commit();
            AuditService::log('production', 'CREATE', 'production_inwards', $inwardId, null, [
                'grn_number' => $grnNumber,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'destination_unit_id' => $destinationUnitId,
                'line_count' => count($items),
            ]);
            return $grnNumber;
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            throw $e;
        }
    }

    private static function normalizeSubmittedItems(array $submittedItems, string $sourceType, int $sourceId): array {
        $normalized = [];
        foreach ($submittedItems as $submitted) {
            if (!is_array($submitted)) {
                continue;
            }
            $variantId = (int)($submitted['variant_id'] ?? 0);
            $quantity = (int)($submitted['quantity'] ?? 0);
            $codes = $submitted['codes'] ?? [];
            if ($variantId <= 0 || $quantity <= 0 || !is_array($codes) || count($codes) !== $quantity) {
                throw new InvalidArgumentException('Each received unit must have a valid product scan.');
            }
            $variant = self::getVariant($variantId);
            if (!$variant) {
                throw new InvalidArgumentException('A scanned product no longer exists.');
            }
            foreach ($codes as $code) {
                $code = trim((string)$code);
                if ($code === '') {
                    throw new InvalidArgumentException('A scanned product code cannot be empty.');
                }
                $resolved = self::findVariantForCode($code, $sourceType, $sourceId);
                if (!$resolved || (int)$resolved['id'] !== $variantId) {
                    throw new InvalidArgumentException('A scanned code does not match the submitted product. Scan it again.');
                }
            }
            $normalized[$variantId] = [
                'variant_id' => $variantId,
                'quantity' => ($normalized[$variantId]['quantity'] ?? 0) + $quantity,
                'codes' => array_merge($normalized[$variantId]['codes'] ?? [], array_map('strval', $codes)),
            ];
        }
        return array_values($normalized);
    }

    private static function getBatchItems(int $batchId): array {
        $rows = Database::fetchAll(
            "SELECT pbi.variant_id,
                    CASE WHEN pbi.received_qty > 0 THEN pbi.received_qty ELSE pbi.quantity END AS quantity,
                    pv.sku, p.name AS product_name, s.name AS size_name, c.name AS color_name
             FROM production_batch_items pbi
             JOIN product_variants pv ON pv.id = pbi.variant_id
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             LEFT JOIN colors c ON c.id = pv.color_id
             WHERE pbi.production_batch_id = ? AND pbi.quantity > 0
             ORDER BY p.name, pv.sku",
            [$batchId]
        );
        $items = [];
        foreach ($rows as $row) {
            $variantId = (int)$row['variant_id'];
            if (!isset($items[$variantId])) {
                $items[$variantId] = [
                    'variant_id' => $variantId,
                    'quantity' => 0,
                    'codes' => [],
                    'sku' => $row['sku'],
                    'product_name' => $row['product_name'],
                    'size_name' => $row['size_name'],
                    'color_name' => $row['color_name'],
                ];
            }
            $items[$variantId]['quantity'] += (int)$row['quantity'];
        }
        return array_values($items);
    }

    private static function resolveSourceBatch(string $sourceType, int $sourceId, string $code): ?array {
        if ($sourceType === 'WAREHOUSE') {
            $batch = Database::fetchOne(
                "SELECT pb.id, pb.batch_number
                 FROM production_batches pb
                 WHERE UPPER(pb.batch_number) = UPPER(?)
                   AND pb.destination_warehouse_id = ? AND pb.concern_id = ?
                   AND pb.status IN ('DISPATCHED', 'RECEIVED')
                 LIMIT 1",
                [$code, $sourceId, (int)currentConcernId()]
            );
            if ($batch) {
                return ['batch_number' => $batch['batch_number'], 'items' => self::getBatchItems((int)$batch['id'])];
            }
        }

        if ($sourceType === 'SHOP') {
            $allocation = Database::fetchOne(
                "SELECT id, allocation_number
                 FROM warehouse_allocations
                WHERE UPPER(allocation_number) = UPPER(?) AND shop_id = ? AND concern_id = ?
                   AND status IN ('PARTIAL_RECEIVED', 'RECEIVED', 'COMPLETED')
                 LIMIT 1",
                [$code, $sourceId, (int)currentConcernId()]
            );
            if ($allocation) {
                $rows = Database::fetchAll(
                    "SELECT ai.variant_id, ai.received_qty AS quantity,
                            pv.sku, p.name AS product_name, s.name AS size_name, c.name AS color_name
                     FROM allocation_items ai
                     JOIN product_variants pv ON pv.id = ai.variant_id
                     JOIN products p ON p.id = pv.product_id
                     LEFT JOIN sizes s ON s.id = pv.size_id
                     LEFT JOIN colors c ON c.id = pv.color_id
                     WHERE ai.allocation_id = ? AND ai.received_qty > 0
                     ORDER BY p.name, pv.sku",
                    [(int)$allocation['id']]
                );
                return ['batch_number' => $allocation['allocation_number'], 'items' => self::groupBatchItems($rows)];
            }
        }

        if ($sourceType === 'PRODUCTION_UNIT') {
            $receipt = Database::fetchOne(
                "SELECT id, grn_number FROM production_inwards
                 WHERE UPPER(grn_number) = UPPER(?) AND destination_unit_id = ? AND concern_id = ? AND status = 'RECEIVED'
                 LIMIT 1",
                [$code, $sourceId, (int)currentConcernId()]
            );
            if ($receipt) {
                $rows = Database::fetchAll(
                    "SELECT pii.variant_id, pii.quantity, pv.sku, p.name AS product_name,
                            s.name AS size_name, c.name AS color_name
                     FROM production_inward_items pii
                     JOIN product_variants pv ON pv.id = pii.variant_id
                     JOIN products p ON p.id = pv.product_id
                     LEFT JOIN sizes s ON s.id = pv.size_id
                     LEFT JOIN colors c ON c.id = pv.color_id
                     WHERE pii.inward_id = ? ORDER BY p.name, pv.sku",
                    [(int)$receipt['id']]
                );
                return ['batch_number' => $receipt['grn_number'], 'items' => self::groupBatchItems($rows)];
            }
        }

        return null;
    }

    private static function groupBatchItems(array $rows): array {
        $items = [];
        foreach ($rows as $row) {
            $variantId = (int)$row['variant_id'];
            if (!isset($items[$variantId])) {
                $items[$variantId] = [
                    'variant_id' => $variantId,
                    'quantity' => 0,
                    'codes' => [],
                    'sku' => $row['sku'],
                    'product_name' => $row['product_name'],
                    'size_name' => $row['size_name'],
                    'color_name' => $row['color_name'],
                ];
            }
            $items[$variantId]['quantity'] += (int)$row['quantity'];
        }
        return array_values($items);
    }

    private static function findVariantForCode(string $code, string $sourceType, int $sourceId): ?array {
        if ($code === '') {
            return null;
        }
        $variant = Database::fetchOne(
            "SELECT pv.id, pv.sku, pv.qr_code, p.name AS product_name, s.name AS size_name, c.name AS color_name
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             LEFT JOIN colors c ON c.id = pv.color_id
             WHERE UPPER(pv.sku) = UPPER(?) OR UPPER(pv.qr_code) = UPPER(?)
             LIMIT 1",
            [$code, $code]
        );
        if ($variant) {
            return $variant;
        }

        if ($sourceType === 'WAREHOUSE') {
            $variant = Database::fetchOne(
                "SELECT pv.id, pv.sku, pv.qr_code, p.name AS product_name, s.name AS size_name, c.name AS color_name
                 FROM warehouse_qr_codes wq
                 JOIN product_variants pv ON pv.id = wq.variant_id
                 JOIN products p ON p.id = pv.product_id
                 LEFT JOIN sizes s ON s.id = pv.size_id
                 LEFT JOIN colors c ON c.id = pv.color_id
                 WHERE wq.warehouse_id = ? AND UPPER(wq.qr_code) = UPPER(?) LIMIT 1",
                [$sourceId, $code]
            );
            if ($variant) {
                return $variant;
            }
        }

        $candidateRows = Database::fetchAll(
            "SELECT pbi.variant_id, pbi.piece_tags, pbi.received_qr_codes, pbi.damaged_qr_codes
             FROM production_batch_items pbi
             JOIN production_batches pb ON pb.id = pbi.production_batch_id
             LEFT JOIN production_programs pp ON pp.id = pb.program_id
             WHERE pbi.piece_tags LIKE ? OR pbi.received_qr_codes LIKE ? OR pbi.damaged_qr_codes LIKE ?
             LIMIT 50",
            ['%' . $code . '%', '%' . $code . '%', '%' . $code . '%']
        );
        foreach ($candidateRows as $row) {
            foreach (['piece_tags', 'received_qr_codes', 'damaged_qr_codes'] as $field) {
                if (self::containsCode($row[$field] ?? '', $code)) {
                    return self::getVariant((int)$row['variant_id']);
                }
            }
        }

        $candidates = Database::fetchAll(
            "SELECT ai.variant_id, ai.selected_qr_codes, ai.received_qr_codes
             FROM allocation_items ai
             WHERE ai.selected_qr_codes LIKE ? OR ai.received_qr_codes LIKE ? LIMIT 50",
            ['%' . $code . '%', '%' . $code . '%']
        );
        foreach ($candidates as $candidate) {
            foreach (['selected_qr_codes', 'received_qr_codes'] as $field) {
                if (self::containsCode($candidate[$field] ?? '', $code)) {
                    return self::getVariant((int)$candidate['variant_id']);
                }
            }
        }
        return null;
    }

    private static function containsCode(string $raw, string $code): bool {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $values = [];
            array_walk_recursive($decoded, static function ($value) use (&$values): void {
                if (is_scalar($value)) {
                    $values[] = trim((string)$value);
                }
            });
            return in_array($code, $values, true);
        }
        return in_array($code, array_map('trim', explode(',', $raw)), true);
    }

    private static function getVariant(int $variantId): ?array {
        return Database::fetchOne(
            "SELECT pv.id, pv.sku, pv.qr_code, p.name AS product_name, s.name AS size_name, c.name AS color_name
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             LEFT JOIN colors c ON c.id = pv.color_id
             WHERE pv.id = ? LIMIT 1",
            [$variantId]
        ) ?: null;
    }

    private static function availableAt(string $sourceType, int $sourceId, int $variantId): float {
        $row = Database::fetchOne(
            "SELECT quantity FROM stock_balances
             WHERE variant_id = ? AND location_type = ? AND location_id = ? AND stock_state = 'AVAILABLE' AND concern_id = ?
             LIMIT 1",
            [$variantId, $sourceType, $sourceId, (int)currentConcernId()]
        );
        return (float)($row['quantity'] ?? 0);
    }

    private static function assertSource(string $sourceType, int $sourceId): void {
        if (!in_array($sourceType, self::SOURCE_TYPES, true) || $sourceId <= 0) {
            throw new InvalidArgumentException('Select a valid source concern.');
        }
        StockService::assertLocationBelongsToActiveConcern($sourceType, $sourceId);
    }

    private static function assertDestination(int $unitId): void {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        $assignedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($unitId <= 0 || $concernId === null || $assignedUnitId === 0
            || ($assignedUnitId !== null && $assignedUnitId !== $unitId)
            || !Database::fetchOne(
                "SELECT id FROM production_units WHERE id = ? AND concern_id = ? AND is_active = 1",
                [$unitId, $concernId]
            )) {
            throw new InvalidArgumentException('Select an active destination production unit.');
        }
    }
}