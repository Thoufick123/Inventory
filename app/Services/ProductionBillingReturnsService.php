<?php

require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';
require_once BASE_PATH . '/app/Services/ProductionBillingService.php';
require_once BASE_PATH . '/app/Services/ProductionCashService.php';
require_once BASE_PATH . '/app/Services/StockService.php';

class ProductionBillingReturnsService {
    public static function getSchemaIssues(): array {
        $requiredColumns = [
            'production_bills' => ['id', 'bill_number', 'bill_date', 'buyer_type', 'customer_name', 'customer_phone', 'company_name', 'company_phone', 'total_amount'],
            'production_bill_items' => ['id', 'bill_id', 'qc_item_id', 'variant_id', 'product_name', 'sku', 'size_name', 'color_name', 'quantity', 'unit_price', 'total_amount'],
            'production_bill_item_tags' => ['id', 'bill_id', 'bill_item_id', 'qc_item_id', 'tag_code', 'status'],
            'production_bill_returns' => ['id', 'return_number', 'bill_id', 'action_type', 'reason', 'refund_amount', 'new_amount', 'difference_amount', 'payment_action', 'settlement_mode', 'notes', 'processed_by', 'created_at'],
            'production_bill_return_items' => ['id', 'return_id', 'original_bill_item_id', 'original_qc_item_id', 'original_variant_id', 'original_tag_code', 'item_condition', 'original_product_name', 'original_sku', 'original_size_name', 'original_color_name', 'original_unit_price', 'replacement_qc_item_id', 'replacement_variant_id', 'replacement_tag_code', 'replacement_product_name', 'replacement_sku', 'replacement_size_name', 'replacement_color_name', 'replacement_unit_price'],
            'production_return_inventory' => ['id', 'return_id', 'return_item_id', 'qc_batch_id', 'qc_item_id', 'new_tag_code', 'status'],
            'users' => ['id', 'full_name'],
        ];
        $requiredTables = array_keys($requiredColumns);
        $rows = Database::fetchAll(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (" . implode(', ', array_fill(0, count($requiredTables), '?')) . ")",
            $requiredTables
        );
        $existingTables = array_fill_keys(array_column($rows, 'TABLE_NAME'), true);
        $issues = [];
        foreach ($requiredTables as $table) {
            if (!isset($existingTables[$table])) {
                $issues[] = "Missing database table: {$table}";
                continue;
            }
            $columnRows = Database::fetchAll(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$table]
            );
            $existingColumns = array_fill_keys(array_column($columnRows, 'COLUMN_NAME'), true);
            foreach ($requiredColumns[$table] as $column) {
                if (!isset($existingColumns[$column])) {
                    $issues[] = "Missing database column: {$table}.{$column}";
                }
            }
        }
        return $issues;
    }

    public static function getSummary(): array {
        try {
            $concernId = ConcernTransactionService::activeConcernId();
            $summary = Database::fetchOne(
                "SELECT COUNT(*) AS total_transactions,
                        SUM(DATE(created_at) = CURDATE()) AS today_transactions,
                        SUM(action_type = 'RETURN') AS total_returns_count,
                        SUM(action_type = 'EXCHANGE') AS total_exchanges_count,
                        COALESCE(SUM(CASE WHEN action_type = 'RETURN' THEN refund_amount ELSE 0 END), 0) AS total_refund_amount
                 FROM production_bill_returns
                 WHERE concern_id = ?",
                [$concernId]
            ) ?: [];
            $items = Database::fetchOne(
                "SELECT COUNT(*) AS returned_items,
                        SUM(item_condition = 'DAMAGED') AS damaged_items
                 FROM production_bill_return_items pri
                 JOIN production_bill_returns pr ON pr.id = pri.return_id
                 WHERE pr.concern_id = ?",
                [$concernId]
            ) ?: [];
            return array_merge([
                'total_transactions' => 0,
                'today_transactions' => 0,
                'total_returns_count' => 0,
                'total_exchanges_count' => 0,
                'total_refund_amount' => 0,
            ], $summary, [
                'total_items_returned' => (int)($items['returned_items'] ?? 0),
                'total_damaged_pcs' => (int)($items['damaged_items'] ?? 0),
            ]);
        } catch (Throwable $e) {
            error_log('ProductionBillingReturnsService::getSummary failed: ' . $e->getMessage());
            return [
                'total_transactions' => 0,
                'today_transactions' => 0,
                'total_returns_count' => 0,
                'total_exchanges_count' => 0,
                'total_refund_amount' => 0,
                'total_items_returned' => 0,
                'total_damaged_pcs' => 0,
            ];
        }
    }

    public static function getHistory(string $tab = 'all'): array {
        $where = ' WHERE pb.concern_id = ?';
        $params = [ConcernTransactionService::activeConcernId()];
        if ($tab === 'returns') {
            $where .= " AND pr.action_type = 'RETURN'";
        } elseif ($tab === 'exchanges') {
            $where .= " AND pr.action_type = 'EXCHANGE'";
        }

        try {
            return Database::fetchAll(
                "SELECT pr.*, pb.bill_number, pb.bill_date, pb.buyer_type,
                        COALESCE(pb.customer_name, pb.company_name, 'Production Customer') AS customer_name,
                        COALESCE(pb.customer_phone, pb.company_phone, '') AS customer_phone,
                        u.full_name AS processed_by_name,
                        (SELECT COUNT(*) FROM production_bill_return_items pri WHERE pri.return_id = pr.id) AS item_count
                 FROM production_bill_returns pr
                 JOIN production_bills pb ON pb.id = pr.bill_id
                 LEFT JOIN users u ON u.id = pr.processed_by
                 {$where}
                 ORDER BY pr.created_at DESC, pr.id DESC
                 LIMIT 200",
                 $params
            );
        } catch (Throwable $e) {
            error_log('ProductionBillingReturnsService::getHistory failed: ' . $e->getMessage());
            return [];
        }
    }

    public static function lookupBill(string $query): ?array {
        $query = trim($query);
        if ($query === '') {
            return null;
        }
        if (str_starts_with($query, '{') && str_ends_with($query, '}')) {
            $decoded = json_decode($query, true);
            $query = trim((string)($decoded['bill_number'] ?? $decoded['invoice_number'] ?? $query));
        }
        $bill = Database::fetchOne(
            "SELECT pb.*,
                    COALESCE(pb.customer_name, pb.company_name, 'Production Customer') AS display_customer_name,
                    COALESCE(pb.customer_phone, pb.company_phone, '') AS display_customer_phone
             FROM production_bills pb
             WHERE pb.concern_id = ? AND (pb.bill_number = ? OR pb.customer_phone = ? OR pb.company_phone = ? OR pb.id = ?)
             ORDER BY pb.id DESC LIMIT 1",
            [ConcernTransactionService::activeConcernId(), $query, $query, $query, ctype_digit($query) ? (int)$query : 0]
        );
        if (!$bill) {
            return null;
        }
        self::assertBillUnitScope($bill);

        $items = Database::fetchAll(
            "SELECT pbi.*,
                (SELECT COUNT(*) FROM production_bill_item_tags tag
                 WHERE tag.bill_item_id = pbi.id AND tag.bill_id = pbi.bill_id AND tag.status = 'BILLED') AS active_tag_count,
                (SELECT GROUP_CONCAT(tag.tag_code ORDER BY tag.id SEPARATOR ',')
                 FROM production_bill_item_tags tag
                 WHERE tag.bill_item_id = pbi.id AND tag.bill_id = pbi.bill_id AND tag.status = 'BILLED') AS active_tags
             FROM production_bill_items pbi
             WHERE pbi.bill_id = ?
             ORDER BY pbi.id",
            [(int)$bill['id']]
        );
        foreach ($items as &$item) {
            $item['tags'] = array_values(array_filter(array_map('trim', explode(',', (string)($item['active_tags'] ?? '')))));
            $item['unit_total'] = (int)$item['quantity'] > 0
                ? round((float)$item['total_amount'] / (int)$item['quantity'], 2)
                : (float)$item['unit_price'];
        }
        unset($item);

        return [
            'bill' => $bill,
            'items' => $items,
            'history' => Database::fetchAll(
                "SELECT return_number, action_type, refund_amount, new_amount, difference_amount, created_at
                 FROM production_bill_returns WHERE bill_id = ? AND concern_id = ? ORDER BY id DESC",
                [(int)$bill['id'], ConcernTransactionService::activeConcernId()]
            ),
        ];
    }

    public static function getDetails(int $returnId): ?array {
        $return = Database::fetchOne(
            "SELECT pr.*, pb.production_unit_id, pb.bill_number, pb.bill_date, pb.buyer_type, pb.customer_name, pb.customer_phone,
                    pb.customer_address, pb.company_name, pb.company_address, pb.company_gstin,
                    u.full_name AS processed_by_name
             FROM production_bill_returns pr
             JOIN production_bills pb ON pb.id = pr.bill_id
             LEFT JOIN users u ON u.id = pr.processed_by
             WHERE pr.id = ? AND pr.concern_id = ? AND pb.concern_id = ?",
            [$returnId, ConcernTransactionService::activeConcernId(), ConcernTransactionService::activeConcernId()]
        );
        if (!$return) {
            return null;
        }
        self::assertBillUnitScope($return);
        $items = Database::fetchAll(
            "SELECT pri.*, r.id AS returned_inventory_id, r.new_tag_code AS returned_inventory_tag,
                    r.status AS returned_inventory_status
             FROM production_bill_return_items pri
             LEFT JOIN production_return_inventory r ON r.return_item_id = pri.id
             WHERE pri.return_id = ? ORDER BY pri.id",
            [$returnId]
        );
        return ['return' => $return, 'items' => $items];
    }

    public static function process(array $payload, int $userId): array {
        $billId = (int)($payload['bill_id'] ?? 0);
        $action = strtoupper(trim((string)($payload['action_type'] ?? 'RETURN')));
        $reason = trim((string)($payload['reason'] ?? 'Customer Return'));
        $settlementMode = strtoupper(trim((string)($payload['settlement_mode'] ?? 'CASH')));
        $items = $payload['items'] ?? [];
        if ($billId <= 0 || !in_array($action, ['RETURN', 'EXCHANGE'], true) || !$items || !is_array($items)) {
            throw new InvalidArgumentException('Select a Production Bill and at least one billed QR code.');
        }
        if (!in_array($settlementMode, ['CASH', 'UPI', 'CREDIT_NOTE', 'NONE'], true)) {
            throw new InvalidArgumentException('Select a valid settlement method.');
        }
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw new InvalidArgumentException('Enter a return reason up to 500 characters.');
        }

        $concernId = ConcernTransactionService::activeConcernId();
        Database::beginTransaction();
        try {
            $bill = Database::fetchOne(
                'SELECT * FROM production_bills WHERE id = ? AND concern_id = ? FOR UPDATE',
                [$billId, $concernId]
            );
            if (!$bill) {
                throw new InvalidArgumentException('Production Bill was not found.');
            }
            self::assertBillUnitScope($bill);

            $processed = [];
            $seenOriginal = [];
            $seenReplacement = [];
            $refundAmount = 0.0;
            $newAmount = 0.0;
            foreach ($items as $submitted) {
                if (!is_array($submitted)) {
                    continue;
                }
                $tagCode = trim((string)($submitted['tag_code'] ?? ''));
                if ($tagCode === '' || isset($seenOriginal[strtoupper($tagCode)])) {
                    throw new InvalidArgumentException('Each returned QR code must be valid and unique.');
                }
                $seenOriginal[strtoupper($tagCode)] = true;
                $tag = Database::fetchOne(
                    "SELECT t.*, bi.product_name, bi.sku, bi.size_name, bi.color_name, bi.unit_price,
                            bi.total_amount, bi.quantity, pv.id AS variant_id,
                            COALESCE(qi.qc_batch_id, 0) AS qc_batch_id
                     FROM production_bill_item_tags t
                     JOIN production_bill_items bi ON bi.id = t.bill_item_id
                     LEFT JOIN product_variants pv ON pv.id = bi.variant_id
                     LEFT JOIN qc_items qi ON qi.id = t.qc_item_id
                     WHERE t.bill_id = ? AND t.tag_code = ? AND t.status = 'BILLED'
                     ORDER BY t.id DESC LIMIT 1 FOR UPDATE",
                    [$billId, $tagCode]
                );
                if (!$tag) {
                    throw new InvalidArgumentException("QR {$tagCode} is not currently billed on this invoice, or it was already returned.");
                }
                $tag['production_unit_id'] = !empty($bill['production_unit_id']) ? (int)$bill['production_unit_id'] : null;
                $condition = strtoupper(trim((string)($submitted['condition'] ?? 'GOOD')));
                if (!in_array($condition, ['GOOD', 'DAMAGED'], true)) {
                    throw new InvalidArgumentException('Choose whether each returned item is good or damaged.');
                }
                $unitTotal = (int)$tag['quantity'] > 0
                    ? round((float)$tag['total_amount'] / (int)$tag['quantity'], 2)
                    : (float)$tag['unit_price'];
                $entry = [
                    'tag' => $tag,
                    'condition' => $condition,
                    'unit_total' => $unitTotal,
                    'replacement' => null,
                ];
                $refundAmount += $unitTotal;

                if ($action === 'EXCHANGE') {
                    if ($condition !== 'GOOD') {
                        throw new InvalidArgumentException('Damaged items cannot be exchanged. Process them as a return.');
                    }
                    $replacementCode = trim((string)($submitted['replacement_tag_code'] ?? ''));
                    if ($replacementCode === '' || isset($seenReplacement[strtoupper($replacementCode)])) {
                        throw new InvalidArgumentException('Scan one unique available replacement QR for every exchanged item.');
                    }
                    $seenReplacement[strtoupper($replacementCode)] = true;
                    $replacement = self::findAvailablePiece($replacementCode);
                    if (!$replacement) {
                        throw new InvalidArgumentException("Replacement QR {$replacementCode} is unavailable or already billed.");
                    }
                    $replacementPrice = (float)$replacement['unit_price'];
                    $replacementTotal = round($replacementPrice * (1 + (float)$replacement['gst_percentage'] / 100), 2);
                    $entry['replacement'] = $replacement;
                    $entry['replacement_total'] = $replacementTotal;
                    $newAmount += $replacementTotal;
                }
                $processed[] = $entry;
            }

            if (!$processed) {
                throw new InvalidArgumentException('Scan at least one billed QR code to return.');
            }

            $difference = round($newAmount - $refundAmount, 2);
            $paymentAction = $action !== 'EXCHANGE' || abs($difference) < 0.01
                ? 'NONE'
                : ($difference > 0 ? 'COLLECT' : 'REFUND');
            if ($action === 'RETURN') {
                $newAmount = 0.0;
                $difference = 0.0;
                $paymentAction = 'REFUND';
            }

            $returnNumber = 'PBR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            Database::execute(
                "INSERT INTO production_bill_returns
                    (return_number, concern_id, bill_id, action_type, reason, refund_amount, new_amount, difference_amount,
                     payment_action, settlement_mode, notes, processed_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$returnNumber, ConcernTransactionService::activeConcernId(), $billId, $action, $reason, $refundAmount, $newAmount, abs($difference),
                    $paymentAction, $settlementMode, trim((string)($payload['notes'] ?? '')) ?: null, $userId ?: null]
            );
            $returnId = (int)Database::lastInsertId();

            foreach ($processed as $entry) {
                $tag = $entry['tag'];
                $replacement = $entry['replacement'];
                Database::execute(
                    "INSERT INTO production_bill_return_items
                        (return_id, original_bill_item_id, original_qc_item_id, original_variant_id, original_tag_code,
                         item_condition, original_product_name, original_sku, original_size_name, original_color_name,
                         original_unit_price, replacement_qc_item_id, replacement_variant_id, replacement_tag_code,
                         replacement_product_name, replacement_sku, replacement_size_name, replacement_color_name,
                         replacement_unit_price)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$returnId, (int)$tag['bill_item_id'], (int)$tag['qc_item_id'], $tag['variant_id'] ?: null,
                        $tag['tag_code'], $entry['condition'], $tag['product_name'], $tag['sku'], $tag['size_name'],
                        $tag['color_name'], $entry['unit_total'], $replacement['qc_item_id'] ?? null,
                        $replacement['variant_id'] ?? null, $replacement['tag_code'] ?? null,
                        $replacement['product_name'] ?? null, $replacement['sku'] ?? null,
                        $replacement['size_name'] ?? null, $replacement['color_name'] ?? null,
                        $entry['replacement_total'] ?? 0]
                );
                $returnItemId = (int)Database::lastInsertId();
                if ($entry['condition'] === 'GOOD') {
                    self::createReturnedInventoryItem($returnId, $returnItemId, $tag, $userId);
                }

                $originalVariantId = (int)($tag['variant_id'] ?? 0);
                if ($originalVariantId > 0) {
                    $originalLoc = ProductionBillingService::resolveTagStockLocation(
                        $concernId,
                        $billId,
                        $originalVariantId,
                        (string)$tag['tag_code']
                    );
                    if ($originalLoc !== null) {
                        $stockState = $entry['condition'] === 'GOOD' ? 'AVAILABLE' : 'DAMAGED';
                        $txnType = $entry['condition'] === 'GOOD' ? 'PRODUCTION_RETURN' : 'PRODUCTION_RETURN_DAMAGED';
                        StockService::increase(
                            $originalVariantId,
                            $originalLoc['location_type'],
                            (int)$originalLoc['location_id'],
                            $stockState,
                            1,
                            $txnType,
                            'production_bill_returns',
                            $returnId,
                            $userId ?: null,
                            'Production return ' . $returnNumber . ' (' . $tag['tag_code'] . ')'
                        );
                    }
                }

                Database::execute(
                    "UPDATE production_bill_item_tags SET status = ? WHERE id = ? AND status = 'BILLED'",
                    [$entry['condition'] === 'GOOD' ? 'RETURNED' : 'DAMAGED', (int)$tag['id']]
                );
                Database::execute(
                    "UPDATE production_return_inventory SET status = 'RETIRED', updated_at = NOW()
                     WHERE new_tag_code = ? AND status IN ('AVAILABLE', 'BILLED')",
                    [$tag['tag_code']]
                );
                if ($replacement) {
                    $returnedReplacement = Database::fetchOne(
                        "SELECT id FROM production_return_inventory WHERE new_tag_code = ? AND status = 'AVAILABLE' FOR UPDATE",
                        [$replacement['tag_code']]
                    );
                    $replacementClaim = Database::fetchOne(
                        'SELECT id, status FROM production_bill_item_tags WHERE tag_code = ? FOR UPDATE',
                        [$replacement['tag_code']]
                    );
                    if ($replacementClaim) {
                        if ($replacementClaim['status'] !== 'RETURNED' || !$returnedReplacement) {
                            throw new RuntimeException('The replacement QR was claimed by another transaction. Scan another item.');
                        }
                        Database::execute(
                            "UPDATE production_bill_item_tags SET bill_id = ?, bill_item_id = ?, qc_item_id = ?, status = 'BILLED' WHERE id = ? AND status = 'RETURNED'",
                            [$billId, (int)$tag['bill_item_id'], (int)$replacement['qc_item_id'], (int)$replacementClaim['id']]
                        );
                    } else {
                        Database::execute(
                            "INSERT INTO production_bill_item_tags (bill_id, bill_item_id, qc_item_id, tag_code, status)
                             VALUES (?, ?, ?, ?, 'BILLED')",
                            [$billId, (int)$tag['bill_item_id'], (int)$replacement['qc_item_id'], $replacement['tag_code']]
                        );
                    }
                    if ($returnedReplacement) {
                        Database::execute(
                            "UPDATE production_return_inventory SET status = 'BILLED', updated_at = NOW()
                             WHERE id = ? AND status = 'AVAILABLE'",
                            [(int)$returnedReplacement['id']]
                        );
                    }
                    if (($replacement['source_kind'] ?? '') === 'INWARD'
                        && !empty($replacement['stock_location_type'])
                        && !empty($replacement['stock_location_id'])
                        && !empty($replacement['variant_id'])) {
                        StockService::decrease(
                            (int)$replacement['variant_id'],
                            (string)$replacement['stock_location_type'],
                            (int)$replacement['stock_location_id'],
                            'AVAILABLE',
                            1,
                            'PRODUCTION_BILL_EXCHANGE',
                            'production_bill_returns',
                            $returnId,
                            $userId ?: null,
                            'Exchange replacement ' . $returnNumber . ' (' . $replacement['tag_code'] . ')'
                        );
                    }
                }
            }

            $settlementAmount = $action === 'RETURN' ? $refundAmount : abs($difference);
            $billUnitId = !empty($bill['production_unit_id']) ? (int)$bill['production_unit_id'] : null;
            ProductionCashService::recordBillReturnSettlement($returnId, [
                'production_unit_id' => $billUnitId,
                'return_number' => $returnNumber,
                'bill_number' => (string)($bill['bill_number'] ?? ('PB-' . $billId)),
                'payment_action' => $paymentAction,
                'settlement_mode' => $settlementMode,
                'amount' => $settlementAmount,
            ], $userId);

            AuditService::log('production_billing', 'PROCESS_RETURN', 'production_bill_returns', $returnId, null, [
                'return_number' => $returnNumber,
                'bill_id' => $billId,
                'action_type' => $action,
                'refund_amount' => $refundAmount,
                'new_amount' => $newAmount,
                'item_count' => count($processed),
            ], $userId);

            Database::commit();
            return ['return_id' => $returnId, 'return_number' => $returnNumber, 'action_type' => $action];
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            throw $e;
        }
    }

    private static function createReturnedInventoryItem(int $returnId, int $returnItemId, array $originalTag, int $userId): string
    {
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
                $returnId,
                $returnItemId,
                !empty($originalTag['production_unit_id']) ? (int)$originalTag['production_unit_id'] : null,
                (int)$originalTag['qc_batch_id'],
                (int)$originalTag['qc_item_id'],
                !empty($originalTag['variant_id']) ? (int)$originalTag['variant_id'] : null,
                $originalTag['tag_code'],
                $newTag,
                $originalTag['product_name'],
                $originalTag['sku'],
                $originalTag['size_name'],
                $originalTag['color_name'],
                (float)$originalTag['unit_price'],
                $userId ?: null,
            ]
        );

        return $newTag;
    }

    private static function findAvailablePiece(string $tagCode): ?array
    {
        return ProductionBillingService::findAvailablePieceByTag($tagCode);
    }

    private static function assertBillUnitScope(array $bill): void
    {
        $assignedUnitId = ProductionBillingService::resolveAssignedUnitId();
        if ($assignedUnitId === 0) {
            throw new InvalidArgumentException('The active user does not have access to this production unit.');
        }
        $billUnitId = !empty($bill['production_unit_id']) ? (int)$bill['production_unit_id'] : null;
        if ($billUnitId !== null) {
            ConcernTransactionService::assertFacility('PRODUCTION', $billUnitId);
            if ($assignedUnitId !== null && $billUnitId !== $assignedUnitId) {
                throw new InvalidArgumentException('The selected bill belongs to another production unit.');
            }
        }
    }
}