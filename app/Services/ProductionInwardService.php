<?php

class ProductionInwardService {
    private const SOURCE_TYPES = ['WAREHOUSE', 'SHOP', 'PRODUCTION_UNIT'];

    public static function getLocations(): array {
        return [
            'WAREHOUSE' => Database::fetchAll("SELECT id, name, code FROM warehouses WHERE is_active = 1 ORDER BY name"),
            'SHOP' => Database::fetchAll("SELECT id, name, code FROM shops WHERE is_active = 1 ORDER BY name"),
            'PRODUCTION_UNIT' => Database::fetchAll("SELECT id, name, code FROM production_units WHERE is_active = 1 ORDER BY name"),
        ];
    }

    public static function getReceipts(?int $unitId = null, string $search = ''): array {
        $sql = "SELECT pi.*, dest.name AS destination_name,
                       CASE pi.source_type
                           WHEN 'WAREHOUSE' THEN wh.name
                           WHEN 'SHOP' THEN sh.name
                           WHEN 'PRODUCTION_UNIT' THEN src_unit.name
                       END AS source_name,
                       u.full_name AS received_by_name,
                       COUNT(pii.id) AS line_count,
                       COALESCE(SUM(pii.quantity), 0) AS total_quantity
                FROM production_inwards pi
                JOIN production_units dest ON dest.id = pi.destination_unit_id
                LEFT JOIN warehouses wh ON pi.source_type = 'WAREHOUSE' AND wh.id = pi.source_id
                LEFT JOIN shops sh ON pi.source_type = 'SHOP' AND sh.id = pi.source_id
                LEFT JOIN production_units src_unit ON pi.source_type = 'PRODUCTION_UNIT' AND src_unit.id = pi.source_id
                LEFT JOIN users u ON u.id = pi.received_by
                LEFT JOIN production_inward_items pii ON pii.inward_id = pi.id
                WHERE pi.status = 'RECEIVED'";
        $params = [];
        if ($unitId !== null) {
            $sql .= ' AND pi.destination_unit_id = ?';
            $params[] = $unitId;
        }
        if ($search !== '') {
            $sql .= ' AND (pi.grn_number LIKE ? OR pi.source_batch_number LIKE ? OR dest.name LIKE ? OR wh.name LIKE ? OR sh.name LIKE ? OR src_unit.name LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like, $like);
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
        }
        unset($receipt);
        return $receipts;
    }

    public static function getUnitStock(int $unitId): array {
        return Database::fetchAll(
            "SELECT sb.variant_id, sb.quantity, pv.sku, pv.mrp, pv.selling_price,
                    p.name AS product_name, s.name AS size_name, c.name AS color_name
             FROM stock_balances sb
             JOIN product_variants pv ON pv.id = sb.variant_id
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             LEFT JOIN colors c ON c.id = pv.color_id
             WHERE sb.location_type = 'PRODUCTION_UNIT' AND sb.location_id = ?
               AND sb.stock_state = 'AVAILABLE' AND sb.quantity > 0
             ORDER BY p.name, s.sort_order, c.name, pv.sku",
            [$unitId]
        );
    }

    public static function getStockAcrossUnits(?int $unitId = null): array {
        $sql = "SELECT sb.variant_id, sb.location_id AS production_unit_id, pu.name AS production_unit_name,
                       sb.quantity, pv.sku, pv.mrp, pv.selling_price,
                       p.name AS product_name, s.name AS size_name, c.name AS color_name
                FROM stock_balances sb
                JOIN production_units pu ON pu.id = sb.location_id
                JOIN product_variants pv ON pv.id = sb.variant_id
                JOIN products p ON p.id = pv.product_id
                LEFT JOIN sizes s ON s.id = pv.size_id
                LEFT JOIN colors c ON c.id = pv.color_id
                WHERE sb.location_type = 'PRODUCTION_UNIT' AND sb.stock_state = 'AVAILABLE' AND sb.quantity > 0";
        $params = [];
        if ($unitId !== null) {
            $sql .= ' AND sb.location_id = ?';
            $params[] = $unitId;
        }
        $sql .= ' ORDER BY pu.name, p.name, s.sort_order, c.name, pv.sku';
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
                 WHERE source_type = ? AND source_id = ? AND UPPER(source_batch_number) = UPPER(?) AND status = 'RECEIVED'
                 LIMIT 1",
                [$sourceType, $sourceId, $batch['batch_number']]
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
                    (grn_number, source_type, source_id, destination_unit_id, source_batch_number, notes, received_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$temporaryNumber, $sourceType, $sourceId, $destinationUnitId,
                    $batchNumber !== '' ? $batchNumber : null, $notes !== '' ? $notes : null, $userId ?: null]
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
                    "INSERT INTO production_inward_items (inward_id, variant_id, quantity, scanned_codes)
                     VALUES (?, ?, ?, ?)",
                    [$inwardId, $variantId, $quantity, !empty($item['codes']) ? json_encode(array_values($item['codes'])) : null]
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
                   AND pb.destination_warehouse_id = ?
                   AND pb.status IN ('DISPATCHED', 'RECEIVED')
                 LIMIT 1",
                [$code, $sourceId]
            );
            if ($batch) {
                return ['batch_number' => $batch['batch_number'], 'items' => self::getBatchItems((int)$batch['id'])];
            }
        }

        if ($sourceType === 'SHOP') {
            $allocation = Database::fetchOne(
                "SELECT id, allocation_number
                 FROM warehouse_allocations
                 WHERE UPPER(allocation_number) = UPPER(?) AND shop_id = ?
                   AND status IN ('PARTIAL_RECEIVED', 'RECEIVED', 'COMPLETED')
                 LIMIT 1",
                [$code, $sourceId]
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
                 WHERE UPPER(grn_number) = UPPER(?) AND destination_unit_id = ? AND status = 'RECEIVED'
                 LIMIT 1",
                [$code, $sourceId]
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
             WHERE variant_id = ? AND location_type = ? AND location_id = ? AND stock_state = 'AVAILABLE'
             LIMIT 1",
            [$variantId, $sourceType, $sourceId]
        );
        return (float)($row['quantity'] ?? 0);
    }

    private static function assertSource(string $sourceType, int $sourceId): void {
        if (!in_array($sourceType, self::SOURCE_TYPES, true) || $sourceId <= 0) {
            throw new InvalidArgumentException('Select a valid source concern.');
        }
        $table = match ($sourceType) {
            'WAREHOUSE' => 'warehouses',
            'SHOP' => 'shops',
            'PRODUCTION_UNIT' => 'production_units',
        };
        $source = Database::fetchOne("SELECT id FROM {$table} WHERE id = ? AND is_active = 1", [$sourceId]);
        if (!$source) {
            throw new InvalidArgumentException('The selected source concern is not active.');
        }
    }

    private static function assertDestination(int $unitId): void {
        if ($unitId <= 0 || !Database::fetchOne("SELECT id FROM production_units WHERE id = ? AND is_active = 1", [$unitId])) {
            throw new InvalidArgumentException('Select an active destination production unit.');
        }
    }
}