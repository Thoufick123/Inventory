<?php

class ProductionBillingReturnsService {
    public static function getSummary(): array {
        $summary = Database::fetchOne(
            "SELECT COUNT(*) AS total_transactions,
                    SUM(DATE(created_at) = CURDATE()) AS today_transactions,
                    SUM(action_type = 'RETURN') AS total_returns_count,
                    SUM(action_type = 'EXCHANGE') AS total_exchanges_count,
                    COALESCE(SUM(CASE WHEN action_type = 'RETURN' THEN refund_amount ELSE 0 END), 0) AS total_refund_amount
             FROM production_bill_returns"
        ) ?: [];
        $items = Database::fetchOne(
            "SELECT COUNT(*) AS returned_items,
                    SUM(item_condition = 'DAMAGED') AS damaged_items
             FROM production_bill_return_items"
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
    }

    public static function getHistory(string $tab = 'all'): array {
        $where = '';
        if ($tab === 'returns') {
            $where = " WHERE pr.action_type = 'RETURN'";
        } elseif ($tab === 'exchanges') {
            $where = " WHERE pr.action_type = 'EXCHANGE'";
        }
        return Database::fetchAll(
            "SELECT pr.*, pb.bill_number, pb.bill_date, pb.buyer_type,
                    COALESCE(pb.customer_name, pb.company_name, 'Production Customer') AS customer_name,
                    COALESCE(pb.customer_phone, pb.company_phone, '') AS customer_phone,
                    u.full_name AS processed_by_name,
                    COUNT(pri.id) AS item_count
             FROM production_bill_returns pr
             JOIN production_bills pb ON pb.id = pr.bill_id
             LEFT JOIN users u ON u.id = pr.processed_by
             LEFT JOIN production_bill_return_items pri ON pri.return_id = pr.id
             {$where}
             GROUP BY pr.id
             ORDER BY pr.created_at DESC, pr.id DESC
             LIMIT 200"
        );
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
             WHERE pb.bill_number = ? OR pb.customer_phone = ? OR pb.company_phone = ? OR pb.id = ?
             ORDER BY pb.id DESC LIMIT 1",
            [$query, $query, $query, ctype_digit($query) ? (int)$query : 0]
        );
        if (!$bill) {
            return null;
        }

        $items = Database::fetchAll(
            "SELECT pbi.*,
                    COUNT(CASE WHEN tag.status = 'BILLED' THEN 1 END) AS active_tag_count,
                    GROUP_CONCAT(CASE WHEN tag.status = 'BILLED' THEN tag.tag_code END ORDER BY tag.id SEPARATOR ',') AS active_tags
             FROM production_bill_items pbi
             LEFT JOIN production_bill_item_tags tag ON tag.bill_item_id = pbi.id AND tag.bill_id = pbi.bill_id
             WHERE pbi.bill_id = ?
             GROUP BY pbi.id
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
                 FROM production_bill_returns WHERE bill_id = ? ORDER BY id DESC",
                [(int)$bill['id']]
            ),
        ];
    }

    public static function getDetails(int $returnId): ?array {
        $return = Database::fetchOne(
            "SELECT pr.*, pb.bill_number, pb.bill_date, pb.buyer_type, pb.customer_name, pb.customer_phone,
                    pb.customer_address, pb.company_name, pb.company_address, pb.company_gstin,
                    u.full_name AS processed_by_name
             FROM production_bill_returns pr
             JOIN production_bills pb ON pb.id = pr.bill_id
             LEFT JOIN users u ON u.id = pr.processed_by
             WHERE pr.id = ?",
            [$returnId]
        );
        if (!$return) {
            return null;
        }
        $items = Database::fetchAll(
            "SELECT * FROM production_bill_return_items WHERE return_id = ? ORDER BY id",
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

        Database::beginTransaction();
        try {
            $bill = Database::fetchOne('SELECT * FROM production_bills WHERE id = ? FOR UPDATE', [$billId]);
            if (!$bill) {
                throw new InvalidArgumentException('Production Bill was not found.');
            }

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
                            bi.total_amount, bi.quantity, pv.id AS variant_id
                     FROM production_bill_item_tags t
                     JOIN production_bill_items bi ON bi.id = t.bill_item_id
                     LEFT JOIN product_variants pv ON pv.id = bi.variant_id
                     WHERE t.bill_id = ? AND t.tag_code = ? AND t.status = 'BILLED'
                     ORDER BY t.id DESC LIMIT 1 FOR UPDATE",
                    [$billId, $tagCode]
                );
                if (!$tag) {
                    throw new InvalidArgumentException("QR {$tagCode} is not currently billed on this invoice, or it was already returned.");
                }
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
                    (return_number, bill_id, action_type, reason, refund_amount, new_amount, difference_amount,
                     payment_action, settlement_mode, notes, processed_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$returnNumber, $billId, $action, $reason, $refundAmount, $newAmount, abs($difference),
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

                Database::execute(
                    "UPDATE production_bill_item_tags SET status = ? WHERE id = ? AND status = 'BILLED'",
                    [$entry['condition'] === 'GOOD' ? 'RETURNED' : 'DAMAGED', (int)$tag['id']]
                );
                if ($replacement) {
                    $replacementClaim = Database::fetchOne(
                        'SELECT id, status FROM production_bill_item_tags WHERE tag_code = ? FOR UPDATE',
                        [$replacement['tag_code']]
                    );
                    if ($replacementClaim) {
                        if ($replacementClaim['status'] !== 'RETURNED') {
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
                }
            }

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

    private static function findAvailablePiece(string $tagCode): ?array {
        $activeClaim = Database::fetchOne(
            "SELECT id FROM production_bill_item_tags WHERE tag_code = ? AND status IN ('BILLED','DAMAGED') LIMIT 1",
            [$tagCode]
        );
        if ($activeClaim) {
            return null;
        }

        $rows = Database::fetchAll(
            "SELECT qi.id AS qc_item_id, qi.qc_batch_id, qi.variant_id, qi.size_name, qi.color_name,
                    qi.base_price, qi.discount_price, pp.program_number,
                    COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                    p.code AS product_code, p.gst_percentage,
                    pv.sku, pv.selling_price, pv.mrp,
                    c.name AS color_from_variant, sz.name AS size_from_variant
             FROM qc_items qi
             JOIN qc_batches qb ON qb.id = qi.qc_batch_id
             JOIN production_programs pp ON pp.id = qb.program_id
             LEFT JOIN products p ON p.id = pp.product_id
             LEFT JOIN product_variants pv ON pv.id = qi.variant_id
             LEFT JOIN colors c ON c.id = pv.color_id
             LEFT JOIN sizes sz ON sz.id = pv.size_id
             WHERE qi.passed_qty > 0 ORDER BY qb.created_at DESC, qi.id DESC"
        );
        $batchIds = array_values(array_unique(array_map(static fn(array $row): int => (int)$row['qc_batch_id'], $rows)));
        foreach ($batchIds as $batchId) {
            $tagsByItem = ProductionService::getAvailableQCPieceTags($batchId);
            foreach ($rows as $row) {
                $qcItemId = (int)$row['qc_item_id'];
                $itemTags = $tagsByItem[$qcItemId] ?? [];
                if (!in_array(strtoupper($tagCode), array_map('strtoupper', $itemTags), true)) {
                    continue;
                }
                $price = (float)($row['selling_price'] ?? 0);
                if ($price <= 0) {
                    $price = (float)($row['discount_price'] ?? $row['base_price'] ?? $row['mrp'] ?? 0);
                }
                return [
                    'qc_item_id' => $qcItemId,
                    'variant_id' => (int)($row['variant_id'] ?? 0),
                    'tag_code' => $tagCode,
                    'product_name' => $row['product_name'],
                    'sku' => $row['sku'] ?: $row['product_code'],
                    'size_name' => $row['size_name'] ?: $row['size_from_variant'],
                    'color_name' => $row['color_name'] ?: $row['color_from_variant'],
                    'unit_price' => $price,
                    'gst_percentage' => max(0, (float)($row['gst_percentage'] ?? 0)),
                ];
            }
        }
        return null;
    }
}