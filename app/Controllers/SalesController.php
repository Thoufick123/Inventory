<?php
/**
 * SalesController
 * Handles Website and WhatsApp sales channel orders creation, viewing, and processing.
 */

require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Services/CloudinaryService.php';
require_once BASE_PATH . '/app/Models/ShopDamage.php';
require_once BASE_PATH . '/app/Models/ChannelDamage.php';

class SalesController {

    public function websiteOrderList(): void {
        Response::redirect(url('warehouse/website-orders'));
    }

    private function getSalesChannelLocationIds(string $channelType): array {
        if ($channelType === 'WEBSITE') {
            $channels = Database::fetchAll(
                "SELECT id FROM sales_channels
                 WHERE LOWER(name) LIKE '%website%' OR LOWER(code) LIKE '%website%'
                    OR channel_type = 'WEBSITE' OR code IN ('online 2', 'WE-01')"
            );
            $fallbackIds = [1, 2, 4];
        } else {
            $channels = Database::fetchAll(
                "SELECT id FROM sales_channels
                 WHERE LOWER(name) LIKE '%whatsapp%' OR LOWER(code) LIKE '%whatsapp%'
                    OR channel_type = 'WHATSAPP' OR code IN ('online 1', 'WA-01')"
            );
            $fallbackIds = [3];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', array_column($channels, 'id')))));
        return $ids ?: $fallbackIds;
    }

    private function getSalesChannelAvailableStock(array $channelIds): array {
        if (empty($channelIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($channelIds), '?'));
        $rows = Database::fetchAll(
            "SELECT variant_id, SUM(quantity) AS available_stock
             FROM stock_balances
             WHERE location_type = 'CHANNEL'
               AND location_id IN ($placeholders)
               AND stock_state = 'AVAILABLE'
             GROUP BY variant_id",
            $channelIds
        );

        $stockByVariant = [];
        foreach ($rows as $row) {
            $stockByVariant[(int)$row['variant_id']] = (float)$row['available_stock'];
        }
        return $stockByVariant;
    }

    private function findPreviousSaleForPieceTag(string $tag): ?array {
        return Database::fetchOne(
            "SELECT s.invoice_number
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             WHERE COALESCE(s.status, '') != 'CANCELLED'
               AND FIND_IN_SET(?, REPLACE(si.scanned_qr_codes, ', ', ',')) > 0
             LIMIT 1",
            [$tag]
        );
    }

    private function assertSalesChannelTagsAvailable(array $tags, bool $isSeries): void {
        if ($isSeries) {
            return;
        }

        foreach ($tags as $tag) {
            $alreadySold = $this->findPreviousSaleForPieceTag($tag);
            if ($alreadySold) {
                throw new RuntimeException("Piece Serial Tag '{$tag}' was already sold in Invoice #{$alreadySold['invoice_number']}. It cannot be billed again.");
            }
        }
    }

    private function getAllocationPieceCodes(?string $rawCodes): array {
        if (!$rawCodes) {
            return [];
        }

        $decoded = json_decode($rawCodes, true);
        $values = is_array($decoded) ? $decoded : explode(',', $rawCodes);
        $codes = [];
        foreach ($values as $value) {
            $code = is_array($value)
                ? (string)($value['tag_code'] ?? $value['qr_code'] ?? $value['code'] ?? '')
                : (string)$value;
            $code = strtoupper(trim($code));
            if ($code !== '' && !in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }
        return $codes;
    }

    private function assertChannelPieceTagsReceived(array $tags, int $variantId, string $channelType, array $channelIds): void {
        if (empty($tags)) {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $channelIds))));
        if (!$ids) {
            throw new RuntimeException("No {$channelType} stock channel is configured for piece QR validation.");
        }
        $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $scopeParts = [
            "wa.channel_id IN ({$idPlaceholders})",
            "(wa.destination_type = 'CHANNEL' AND wa.destination_id IN ({$idPlaceholders}))",
        ];
        $params = [$variantId, ...$ids, ...$ids];
        $destinationAliases = array_map(static fn(int $id): string => 'CHANNEL_' . $id, $ids);
        if ($destinationAliases) {
            $aliasPlaceholders = implode(',', array_fill(0, count($destinationAliases), '?'));
            $scopeParts[] = "wa.destination_type IN ({$aliasPlaceholders})";
            array_push($params, ...$destinationAliases);
        }
        $channelType = strtoupper($channelType);
        if (in_array($channelType, ['WHATSAPP', 'WEBSITE'], true)) {
            $scopeParts[] = 'wa.destination_type = ?';
            $params[] = $channelType;
        }

        $rows = Database::fetchAll(
            "SELECT ai.selected_qr_codes, ai.received_qr_codes, ai.received_qty, ai.damaged_qty
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON wa.id = ai.allocation_id
             WHERE ai.variant_id = ? AND wa.status != 'CANCELLED'
               AND (" . implode(' OR ', $scopeParts) . ")",
            $params
        );

        $availableReceivedTags = [];
        foreach ($rows as $row) {
            $selectedTags = $this->getAllocationPieceCodes($row['selected_qr_codes'] ?? null);
            $receivedTags = $this->getAllocationPieceCodes($row['received_qr_codes'] ?? null);
            if (!$receivedTags && $selectedTags) {
                $receivedCount = max(0, (int)($row['received_qty'] ?? 0));
                $damagedCount = max(0, (int)($row['damaged_qty'] ?? 0));
                $receivedTags = array_slice($selectedTags, 0, $receivedCount + $damagedCount);
            }
            foreach ($receivedTags as $tag) {
                $availableReceivedTags[$tag] = true;
            }
        }

        foreach ($tags as $tag) {
            if (!isset($availableReceivedTags[strtoupper(trim($tag))])) {
                throw new RuntimeException("Piece QR '{$tag}' is not an inwarded piece for this {$channelType} product. Scan a QR tag assigned to received {$channelType} stock.");
            }
        }
    }

    private function validateSalesChannelItems(array $items, string $channelType, array $channelIds): array {
        if (empty($items)) {
            throw new RuntimeException('Please select at least one item for the order.');
        }

        $preparedItems = [];
        $requestedByVariant = [];
        $seenPieceTags = [];
        $damageTagsByVariant = [];
        $availableStockByVariant = $this->getSalesChannelAvailableStock($channelIds);

        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new RuntimeException('The order contains an invalid product row. Please remove it and try again.');
            }

            $variantId = (int)($item['variant_id'] ?? 0);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($variantId <= 0 || $quantity === false || $quantity <= 0) {
                throw new RuntimeException('Each order item must have a valid product and a quantity greater than zero.');
            }

            $variant = Database::fetchOne(
                "SELECT pv.id, pv.sku, pv.qr_type, pv.pack_quantity, pv.is_complementary AS variant_is_complementary,
                        pv.mrp, pv.selling_price, p.is_complementary AS product_is_complementary,
                        COALESCE(wc.category_type, 'NORMAL') AS category_type
                 FROM product_variants pv
                 JOIN products p ON p.id = pv.product_id
                 LEFT JOIN warehouse_categories wc ON wc.id = p.warehouse_category_id
                 WHERE pv.id = ? AND pv.is_active = 1",
                [$variantId]
            );
            if (!$variant) {
                throw new RuntimeException("Product variant #{$variantId} is no longer active. Refresh the order and try again.");
            }

            $categoryType = strtoupper((string)$variant['category_type']);
            $isComplementary = $categoryType === 'COMPLEMENTARY'
                || !empty($variant['variant_is_complementary'])
                || !empty($variant['product_is_complementary']);
            $isSeries = $categoryType === 'SERIES'
                || strtoupper((string)$variant['qr_type']) === 'SERIES_BATCH'
                || (int)($variant['pack_quantity'] ?? 1) > 1;
            $isAggregateStock = $isComplementary || $isSeries;

            $tagValue = $item['scanned_qr_codes'] ?? '';
            if (!is_string($tagValue)) {
                throw new RuntimeException("Invalid piece QR data for product '{$variant['sku']}'.");
            }
            $tagValue = trim($tagValue);
            $tags = array_values(array_unique(array_filter(array_map('trim', explode(',', $tagValue)))));
            if (!$isAggregateStock && count($tags) !== $quantity) {
                throw new RuntimeException("Scan one available piece QR for each unit of normal product '{$variant['sku']}'.");
            }
            if (!$isAggregateStock) {
                $this->assertChannelPieceTagsReceived($tags, $variantId, $channelType, $channelIds);
            }
            foreach ($tags as $tag) {
                if (!$isAggregateStock && isset($seenPieceTags[strtoupper($tag)])) {
                    throw new RuntimeException("Piece QR '{$tag}' is included more than once in this order.");
                }

                if (!$isAggregateStock && !isset($damageTagsByVariant[$variantId])) {
                    $damageTagsByVariant[$variantId] = [];
                    $damageRecords = Database::fetchAll(
                        "SELECT 'CHANNEL' AS damage_source, damage_number, tag_code FROM channel_damages
                         WHERE variant_id = ? AND tag_code IS NOT NULL AND tag_code != ''
                         UNION ALL
                         SELECT 'SHOP' AS damage_source, damage_number, tag_code FROM shop_damages
                         WHERE variant_id = ? AND tag_code IS NOT NULL AND tag_code != ''",
                        [$variantId, $variantId]
                    );
                    foreach ($damageRecords as $damageRecord) {
                        $resolvedTags = ($damageRecord['damage_source'] ?? '') === 'CHANNEL'
                            ? ChannelDamage::resolvePieceTags($damageRecord)
                            : ShopDamage::resolvePieceTags($damageRecord);
                        foreach ($resolvedTags as $damageTag) {
                            $damageTagsByVariant[$variantId][strtoupper($damageTag)] = $damageRecord['damage_number'] ?? 'recorded damage';
                        }
                    }
                }

                if (!$isAggregateStock && isset($damageTagsByVariant[$variantId][strtoupper($tag)])) {
                    $damageNumber = $damageTagsByVariant[$variantId][strtoupper($tag)];
                    throw new RuntimeException("Piece QR '{$tag}' is recorded as damaged in {$damageNumber}. Damaged products cannot be billed.");
                }

                if (!$isAggregateStock) {
                    $alreadySold = $this->findPreviousSaleForPieceTag($tag);
                    if ($alreadySold) {
                        throw new RuntimeException("Piece Serial Tag '{$tag}' was already sold in Invoice #{$alreadySold['invoice_number']}. It cannot be billed again.");
                    }
                    $seenPieceTags[strtoupper($tag)] = true;
                }
            }

            $requestedByVariant[$variantId] = ($requestedByVariant[$variantId] ?? 0) + $quantity;
            $availableStock = $availableStockByVariant[$variantId] ?? 0.0;
            if ($requestedByVariant[$variantId] > $availableStock) {
                $sku = $variant['sku'] ?: "#{$variantId}";
                throw new RuntimeException("Insufficient {$channelType} stock for '{$sku}'. Available: {$availableStock}, Required: {$requestedByVariant[$variantId]}.");
            }

            $price = $item['unit_price'] ?? 0;
            $mrp = $item['mrp'] ?? $price;
            if (!is_numeric($price) || !is_finite((float)$price) || (float)$price < 0
                || !is_numeric($mrp) || !is_finite((float)$mrp) || (float)$mrp < 0) {
                throw new RuntimeException("Invalid price entered for product '{$variant['sku']}'.");
            }
            $price = $isComplementary ? 0.0 : (float)$price;
            $mrp = $isComplementary ? 0.0 : (float)$mrp;
            $gstRate = $item['gst_percentage'] ?? 5.0;
            if (!is_numeric($gstRate) || !is_finite((float)$gstRate) || (float)$gstRate < 0 || (float)$gstRate > 100) {
                throw new RuntimeException("Invalid GST percentage for product '{$variant['sku']}'. Enter a rate from 0 to 100.");
            }
            $gstRate = $isComplementary ? 0.0 : (float)$gstRate;

            $preparedItems[] = [
                'variant_id'       => $variantId,
                'quantity'         => $quantity,
                'unit_price'        => $price,
                'mrp'               => $mrp,
                'gst_percentage'    => $gstRate,
                'is_series'         => $isSeries,
                'scanned_qr_codes' => $tags ? implode(',', $tags) : '',
            ];
        }

        if (empty($preparedItems)) {
            throw new RuntimeException('The order contains no valid items.');
        }
        return $preparedItems;
    }

    /** Validate one scanned item against current channel stock before placing it in the billing cart. */
    public function validateSalesScan(): void {
        $channelType = strtoupper(trim((string)($_POST['channel_type'] ?? '')));
        if (!in_array($channelType, ['WHATSAPP', 'WEBSITE'], true)) {
            Response::jsonError('Invalid sales channel.', [], 400);
            return;
        }

        $variantId = (int)($_POST['variant_id'] ?? 0);
        $pieceTag = strtoupper(trim((string)($_POST['piece_tag'] ?? '')));
        if ($variantId <= 0) {
            Response::jsonError('Scan a valid product piece QR.', [], 422);
            return;
        }

        try {
            $channelIds = $this->getSalesChannelLocationIds($channelType);
            $availableByVariant = $this->getSalesChannelAvailableStock($channelIds);
            $available = (float)($availableByVariant[$variantId] ?? 0);
            if ($available < 1) {
                Response::jsonError("Out of stock in {$channelType} inventory.", ['available_stock' => $available], 422);
                return;
            }

            $variant = Database::fetchOne(
                "SELECT pv.selling_price, pv.mrp, COALESCE(p.gst_percentage, 0) AS gst_percentage
                 FROM product_variants pv JOIN products p ON p.id = pv.product_id
                 WHERE pv.id = ? AND pv.is_active = 1",
                [$variantId]
            );
            if (!$variant) {
                Response::jsonError('This product variant is no longer active.', [], 422);
                return;
            }

            $this->validateSalesChannelItems([[
                'variant_id'       => $variantId,
                'quantity'         => 1,
                'unit_price'       => (float)$variant['selling_price'],
                'mrp'              => (float)$variant['mrp'],
                'gst_percentage'   => (float)$variant['gst_percentage'],
                'scanned_qr_codes' => $pieceTag,
            ]], $channelType, $channelIds);

            Response::jsonSuccess('Piece is available for billing.', ['available_stock' => $available]);
        } catch (RuntimeException $e) {
            Response::jsonError($e->getMessage(), [], 422);
        } catch (Throwable $e) {
            error_log("{$channelType} scan validation failed: " . $e->getMessage());
            Response::jsonError('Unable to verify this piece against channel inventory. Please try again.', [], 500);
        }
    }

    private function deductSalesChannelStock(
        int $variantId,
        int $quantity,
        array $channelIds,
        string $channelType,
        int $saleId,
        ?int $userId,
        string $notes
    ): void {
        $placeholders = implode(',', array_fill(0, count($channelIds), '?'));
        $balances = Database::fetchAll(
            "SELECT location_id, quantity
             FROM stock_balances
             WHERE variant_id = ? AND location_type = 'CHANNEL'
               AND location_id IN ($placeholders) AND stock_state = 'AVAILABLE' AND quantity > 0
             ORDER BY location_id ASC
             FOR UPDATE",
            array_merge([$variantId], $channelIds)
        );

        $remaining = $quantity;
        foreach ($balances as $balance) {
            if ($remaining <= 0) {
                break;
            }
            $deduct = min($remaining, (int)$balance['quantity']);
            if ($deduct <= 0) {
                continue;
            }
            StockService::decrease(
                $variantId,
                'CHANNEL',
                (int)$balance['location_id'],
                'AVAILABLE',
                (float)$deduct,
                'SALE',
                'sales',
                $saleId,
                $userId,
                $notes
            );
            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new RuntimeException("{$channelType} stock changed while the order was being saved. Please refresh stock and retry.");
        }
    }

    private function enhanceVariantsWithDynamicTokens(array $variants, array $channelIds, string $channelType): array {
        $channelIds = array_values(array_unique(array_filter(array_map('intval', $channelIds))));
        if (!$channelIds) {
            return $variants;
        }
        $idPlaceholders = implode(',', array_fill(0, count($channelIds), '?'));
        $scopeParts = [
            "wa.channel_id IN ({$idPlaceholders})",
            "(wa.destination_type = 'CHANNEL' AND wa.destination_id IN ({$idPlaceholders}))",
        ];
        $params = [...$channelIds, ...$channelIds];
        $destinationAliases = array_map(static fn(int $id): string => 'CHANNEL_' . $id, $channelIds);
        $aliasPlaceholders = implode(',', array_fill(0, count($destinationAliases), '?'));
        $scopeParts[] = "wa.destination_type IN ({$aliasPlaceholders})";
        array_push($params, ...$destinationAliases);
        $channelType = strtoupper($channelType);
        if (in_array($channelType, ['WHATSAPP', 'WEBSITE'], true)) {
            $scopeParts[] = 'wa.destination_type = ?';
            $params[] = $channelType;
        }

        $allocTags = Database::fetchAll(
            "SELECT ai.variant_id, ai.selected_qr_codes, ai.received_qr_codes, ai.received_qty, ai.damaged_qty
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
             WHERE ai.selected_qr_codes IS NOT NULL AND ai.selected_qr_codes != ''
               AND wa.status != 'CANCELLED' AND (" . implode(' OR ', $scopeParts) . ")",
            $params
        );

        $variantTagsMap = [];
        foreach ($allocTags as $at) {
            $vid = (int)$at['variant_id'];
            if (!isset($variantTagsMap[$vid])) {
                $variantTagsMap[$vid] = [];
            }
            $selectedTags = $this->getAllocationPieceCodes($at['selected_qr_codes'] ?? null);
            $receivedTags = $this->getAllocationPieceCodes($at['received_qr_codes'] ?? null);
            if (!$receivedTags && $selectedTags) {
                $receivedCount = max(0, (int)($at['received_qty'] ?? 0));
                $damagedCount = max(0, (int)($at['damaged_qty'] ?? 0));
                $receivedTags = array_slice($selectedTags, 0, $receivedCount + $damagedCount);
            }
            foreach ($receivedTags as $tag) {
                if (!in_array($tag, $variantTagsMap[$vid], true)) {
                    $variantTagsMap[$vid][] = $tag;
                }
            }
        }

        foreach ($variants as &$v) {
            $vid = (int)$v['id'];
            $pId = (int)($v['product_id'] ?? 0);
            $tagsList = $variantTagsMap[$vid] ?? [];

            // Dynamic Prefix from category/subcategory/sub-subcategory initials (e.g. Girls + Tops -> GT)
            $c1 = !empty($v['category_name']) ? trim($v['category_name']) : (!empty($v['category_code']) ? trim($v['category_code']) : '');
            $c2 = !empty($v['subcategory_name']) ? trim($v['subcategory_name']) : (!empty($v['subcategory_code']) ? trim($v['subcategory_code']) : '');
            $c3 = !empty($v['sub_subcategory_name']) ? trim($v['sub_subcategory_name']) : (!empty($v['sub_subcategory_code']) ? trim($v['sub_subcategory_code']) : '');

            $catPrefix = '';
            if ($c1 !== '') $catPrefix .= strtoupper($c1[0]);
            if ($c2 !== '') $catPrefix .= strtoupper($c2[0]);
            if ($c3 !== '') $catPrefix .= strtoupper($c3[0]);
            if (strlen($catPrefix) < 2 && !empty($v['qr_code_prefix'])) {
                $clean = preg_replace('/[^A-Za-z0-9]/', '', $v['qr_code_prefix']);
                $catPrefix = strtoupper(substr($clean, 0, 3));
            }
            if (empty($catPrefix)) {
                $catPrefix = 'TTS';
            }
            $v['dynamic_prefix'] = strtolower($catPrefix);

            $v['piece_tags'] = $tagsList;

            $tokens = [];
            if (!empty($v['sku'])) {
                $tokens[] = strtolower($v['sku']);
                $cleanSku = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $v['sku']));
                if ($cleanSku) $tokens[] = $cleanSku;
            }
            if (!empty($v['qr_code'])) {
                $tokens[] = strtolower($v['qr_code']);
                $cleanQr = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $v['qr_code']));
                if ($cleanQr) $tokens[] = $cleanQr;
            }
            if (!empty($v['product_code'])) {
                $tokens[] = strtolower($v['product_code']);
                $cleanPc = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $v['product_code']));
                if ($cleanPc) $tokens[] = $cleanPc;
            }
            if (!empty($v['category_name'])) $tokens[] = strtolower($v['category_name']);
            if (!empty($v['category_code'])) $tokens[] = strtolower($v['category_code']);
            if (!empty($v['subcategory_name'])) $tokens[] = strtolower($v['subcategory_name']);
            if (!empty($v['subcategory_code'])) $tokens[] = strtolower($v['subcategory_code']);
            if (!empty($v['sub_subcategory_name'])) $tokens[] = strtolower($v['sub_subcategory_name']);
            if (!empty($v['sub_subcategory_code'])) $tokens[] = strtolower($v['sub_subcategory_code']);
            if (!empty($v['prod_category_code'])) $tokens[] = strtolower($v['prod_category_code']);
            if (!empty($v['qr_code_prefix'])) $tokens[] = strtolower($v['qr_code_prefix']);
            if (!empty($v['size_name'])) $tokens[] = strtolower($v['size_name']);
            if (!empty($v['color_name'])) $tokens[] = strtolower($v['color_name']);

            if (!empty($catPrefix)) {
                $tokens[] = strtolower($catPrefix);
            }
            if ($c1 !== '' && $c2 !== '') {
                $tokens[] = strtolower($c1[0] . $c2[0]);
            }

            $concatCatCode = strtolower(($v['category_code'] ?? '') . ($v['subcategory_code'] ?? '') . ($v['sub_subcategory_code'] ?? ''));
            if (!empty($concatCatCode)) $tokens[] = $concatCatCode;
            $concatProdCatCode = strtolower(($v['prod_category_code'] ?? '') . ($v['subcategory_code'] ?? '') . ($v['sub_subcategory_code'] ?? ''));
            if (!empty($concatProdCatCode)) $tokens[] = $concatProdCatCode;
            
            $words = array_filter(preg_split('/[^a-zA-Z0-9]+/', strtolower(($v['product_name'] ?? '') . ' ' . ($v['category_name'] ?? ''))));
            $initials = '';
            foreach ($words as $w) {
                if (strlen($w) > 0) {
                    $initials .= $w[0];
                    $tokens[] = $w;
                    if (strlen($w) >= 2) $tokens[] = substr($w, 0, 2);
                    if (strlen($w) >= 3) $tokens[] = substr($w, 0, 3);
                    if (strlen($w) >= 4) $tokens[] = substr($w, 0, 4);
                }
            }
            if (!empty($initials)) {
                $tokens[] = $initials;
                for ($len = 1; $len <= strlen($initials); $len++) {
                    $tokens[] = substr($initials, 0, $len);
                }
            }

            $tokens[] = 'wmf';
            $tokens[] = 'tp';
            $tokens[] = 'ret';

            // Also add all piece tags directly into search tokens for instant lookup
            if (!empty($v['piece_tags'])) {
                foreach ($v['piece_tags'] as $pt) {
                    $tokens[] = strtolower($pt);
                    $tokens[] = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $pt));
                }
            }

            $v['search_tokens'] = array_values(array_unique(array_filter($tokens)));
        }
        unset($v);
        return $variants;
    }

    public function createWebsiteOrder(): void {
        if (!hasPermission('website_orders', 'create')) {
            Session::flash('error', 'Access denied: You do not have permission to create website orders.');
            Response::redirect(url('warehouse/website-orders/history'));
            return;
        }

        $customers = Database::fetchAll("SELECT * FROM customers ORDER BY name ASC");
        $users = Database::fetchAll("SELECT id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name ASC");
        $variants = Database::fetchAll(
            "SELECT pv.id, pv.product_id, pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.qr_type, pv.pack_quantity,
                    p.code as product_code, p.name as product_name,
                    p.is_complementary as p_is_comp,
                    pv.is_complementary as pv_is_comp,
                    wc.category_type as category_type,
                    COALESCE(c.name, 'General') as category_name,
                    c.code as category_code,
                    COALESCE(sc.name, '') as subcategory_name,
                    sc.code as subcategory_code,
                    COALESCE(ssc.name, '') as sub_subcategory_name,
                    ssc.code as sub_subcategory_code,
                    p.category_code as prod_category_code,
                    p.qr_code_prefix,
                    COALESCE(sz.name, 'Standard') as size_name,
                    COALESCE(cl.name, '') as color_name,
                    COALESCE(p.gst_percentage, 0.00) as gst_percentage
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
             LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             WHERE pv.is_active = 1
             ORDER BY p.name ASC, pv.sku ASC"
        );
        foreach ($variants as &$v) {
            $isComp = ((isset($v['category_type']) && $v['category_type'] === 'COMPLEMENTARY') || !empty($v['pv_is_comp']) || !empty($v['p_is_comp']));
            $isSeries = ((isset($v['category_type']) && $v['category_type'] === 'SERIES') || ($v['qr_type'] ?? '') === 'SERIES_BATCH' || (int)($v['pack_quantity'] ?? 1) > 1);
            $v['is_complementary'] = $isComp ? 1 : 0;
            $v['is_series'] = $isSeries ? 1 : 0;
            if ($isComp) {
                $v['selling_price'] = 0.00;
                $v['mrp'] = 0.00;
                $v['gst_percentage'] = 0.00;
            }
        }
        unset($v);
        $channelIds = $this->getSalesChannelLocationIds('WEBSITE');
        $availableStockByVariant = $this->getSalesChannelAvailableStock($channelIds);
        foreach ($variants as &$variant) {
            $variant['available_stock'] = $availableStockByVariant[(int)$variant['id']] ?? 0;
        }
        unset($variant);
        $variants = $this->enhanceVariantsWithDynamicTokens($variants, $channelIds, 'WEBSITE');

        $currentUserId = Session::get('user_id');
        $currentUser = $currentUserId ? Database::fetchOne("SELECT full_name FROM users WHERE id = ?", [$currentUserId]) : null;
        $defaultBillerName = $currentUser['full_name'] ?? 'System Administrator';

        Response::view('sales.website.create', [
            'pageTitle'         => 'Create Website Order',
            'activeModule'      => 'website',
            'activeMenu'        => 'wh-website',
            'breadcrumb'        => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => 'Create Website Order']
            ],
            'customers'         => $customers,
            'users'             => $users,
            'variants'          => $variants,
            'defaultBillerName' => $defaultBillerName,
            'currentUserId'     => $currentUserId,
        ]);
    }

    public function storeWebsiteOrder(): void {
        if (!hasPermission('website_orders', 'create')) {
            Session::flash('error', 'Access denied: You do not have permission to create website orders.');
            Response::redirect(url('warehouse/website-orders/history'));
            return;
        }

        CSRF::verify();

        $customerId      = (int)($_POST['customer_id'] ?? 0);
        $customerName    = trim($_POST['customer_name'] ?? '');
        $customerPhone   = trim($_POST['customer_phone'] ?? '');
        $customerAddress = trim($_POST['customer_address'] ?? '');
        $customerState   = trim($_POST['customer_state'] ?? '');
        $customerPincode = trim($_POST['customer_pincode'] ?? '');
        $employeeName    = trim($_POST['employee_name'] ?? '');
        $employeeCode    = trim($_POST['employee_code'] ?? '');
        $billerName      = trim($_POST['biller_name'] ?? '');
        $billingStaffId  = (int)($_POST['billing_staff_id'] ?? 0);
        $billerId        = $billingStaffId ?: (int)($_POST['biller_id'] ?? Session::get('user_id') ?? 0);
        $paymentMode     = trim($_POST['payment_mode'] ?? 'ONLINE');
        $paymentStatus   = trim($_POST['payment_status'] ?? 'PAID');
        $shippingCost    = (float)($_POST['shipping_cost'] ?? 0);
        $discountAmount  = max(0.0, (float)($_POST['discount_amount'] ?? 0));
        $gstPercentage   = (float)($_POST['gst_percentage'] ?? 5.0);
        $notes           = trim($_POST['notes'] ?? 'Website E-Commerce Order');
        $items           = $_POST['items'] ?? [];

        if (empty($customerName) || empty($customerPhone) || empty($customerAddress)) {
            Session::flash('error', 'Customer Name, Mobile Number, and Customer Address are required.');
            Response::redirect(url('sales/website/create'));
            return;
        }

        if (!preg_match('/^[a-zA-Z\s.]+$/', $customerName)) {
            Session::flash('error', 'Customer Name must contain only alphabets.');
            Response::redirect(url('sales/website/create'));
            return;
        }

        if (!preg_match('/^\d{10}$/', $customerPhone)) {
            Session::flash('error', 'Customer Phone Number must contain only numbers and be exactly 10 digits.');
            Response::redirect(url('sales/website/create'));
            return;
        }

        if (!empty($customerPincode) && !preg_match('/^\d{6}$/', $customerPincode)) {
            Session::flash('error', 'Pincode must contain only numbers and be exactly 6 digits.');
            Response::redirect(url('sales/website/create'));
            return;
        }

        if (empty($items) || !is_array($items)) {
            Session::flash('error', 'Please select at least one item for the order.');
            Response::redirect(url('sales/website/create'));
            return;
        }

        $channelIds = $this->getSalesChannelLocationIds('WEBSITE');
        try {
            $items = $this->validateSalesChannelItems($items, 'WEBSITE', $channelIds);
        } catch (\PDOException $e) {
            error_log("Website order validation failed: " . $e->getMessage());
            Session::flash('error', 'Unable to verify website order stock right now. Please try again.');
            Response::redirect(url('sales/website/create'));
            return;
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('sales/website/create'));
            return;
        }

        foreach ($items as $itm) {
            $itemGst = $itm['gst_percentage'] ?? 5.0;
            if (!is_numeric($itemGst) || (float)$itemGst < 0 || (float)$itemGst > 100) {
                Session::flash('error', 'Each product GST rate must be between 0% and 100%.');
                Response::redirect(url('sales/website/create'));
                return;
            }
        }

        if (!$customerId && !empty($customerName) && !empty($customerPhone)) {
            Database::execute(
                "INSERT INTO customers (name, phone, address, state, pincode, source_channel, created_at) VALUES (?, ?, ?, ?, ?, 'WEBSITE', NOW())",
                [$customerName, $customerPhone, $customerAddress, $customerState, $customerPincode]
            );
            $customerId = (int)Database::lastInsertId();
        } elseif ($customerId > 0) {
            Database::execute(
                "UPDATE customers SET address = ?, state = ?, pincode = ? WHERE id = ?",
                [$customerAddress, $customerState, $customerPincode, $customerId]
            );
        }

        $fullAddress = $customerAddress;
        if (!empty($customerState)) $fullAddress .= ', ' . $customerState;
        if (!empty($customerPincode)) $fullAddress .= ' - ' . $customerPincode;

        // Upload payment screenshot to Cloudinary only
        $screenshotUrl = null;
        if (isset($_FILES['payment_screenshot']) && ($_FILES['payment_screenshot']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $screenshotUrl = CloudinaryService::upload(
                $_FILES['payment_screenshot'],
                'grm_inventory/website_sales/screenshots'
            );
        }

        // Build payment notes & split details
        $paymentDetails = [];
        if ($paymentMode === 'CASH_UPI') {
            $cashAmt = (float)($_POST['cash_amount'] ?? 0);
            $upiAmt  = (float)($_POST['upi_amount'] ?? 0);
            $upiRef  = trim($_POST['upi_reference'] ?? '');
            $paymentDetails[] = "Split Payment [Cash: ₹{$cashAmt}, UPI: ₹{$upiAmt}" . ($upiRef ? ", Ref: {$upiRef}" : "") . "]";
        }
        if (!empty($screenshotUrl)) {
            $paymentDetails[] = "Screenshot (Cloudinary): {$screenshotUrl}";
        }
        if (!empty($paymentDetails)) {
            $notes = implode(" | ", $paymentDetails) . (!empty($notes) ? " -- {$notes}" : "");
        }

        $prefix = 'WEB-' . date('Ymd') . '-';
        $last = Database::fetchOne("SELECT invoice_number FROM sales WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1", [$prefix . '%']);
        $seq = $last ? ((int)substr($last['invoice_number'], -4) + 1) : 1;
        $invoiceNumber = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        $grossTotal = 0.0;
        $totalMrp = 0.0;
        $orderItems = [];

        foreach ($items as $itm) {
            $variantId = (int)($itm['variant_id'] ?? 0);
            $qty       = (int)($itm['quantity'] ?? 0);
            $price     = (float)($itm['unit_price'] ?? 0);
            $scannedQrs = trim($itm['scanned_qr_codes'] ?? '');

            if ($variantId > 0 && $qty > 0) {
                $vRow = Database::fetchOne(
                    "SELECT pv.*, p.is_complementary as p_is_comp, wc.category_type as warehouse_category_type
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
                     WHERE pv.id = ?",
                    [$variantId]
                );
                $isComp = $vRow && ((isset($vRow['warehouse_category_type']) && $vRow['warehouse_category_type'] === 'COMPLEMENTARY') || !empty($vRow['is_complementary']) || !empty($vRow['p_is_comp']));

                if ($isComp) {
                    $price = 0.00;
                }

                $lineGross = $qty * $price;
                $grossTotal += $lineGross;

                $mrp = $isComp ? 0.00 : (float)($itm['mrp'] ?? $price);
                if (!$isComp && $mrp <= 0) $mrp = $price;
                $totalMrp += ($qty * $mrp);

                $rawItemGst = $itm['gst_percentage'] ?? 5.0;
                $itemGstPercentage = $isComp ? 0.0 : (float)$rawItemGst;

                // Inclusive GST is calculated per item using its editable rate.
                $unitTaxable = $isComp ? 0.00 : round($price / (1 + ($itemGstPercentage / 100)), 2);
                $lineTaxable = $unitTaxable * $qty;
                $lineTax = $isComp ? 0.00 : round(($price - $unitTaxable) * $qty, 2);
                $lineCgst = $isComp ? 0.00 : round($lineTax / 2, 2);
                $lineSgst = $isComp ? 0.00 : round($lineTax - $lineCgst, 2);

                $orderItems[] = [
                    'variant_id'       => $variantId,
                    'quantity'         => $qty,
                    'unit_price'       => $price,
                    'mrp'              => $mrp,
                    'gst_percentage'   => $itemGstPercentage,
                    'is_series'        => $itm['is_series'],
                    'total'            => $lineGross,
                    'taxable_amount'   => $lineTaxable,
                    'cgst_amount'      => $lineCgst,
                    'sgst_amount'      => $lineSgst,
                    'scanned_qr_codes' => !empty($scannedQrs) ? $scannedQrs : null,
                ];
            }
        }

        $calcDiscount = max(0.0, $totalMrp - $grossTotal);
        $discountAmount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : $calcDiscount;
        if ($discountAmount <= 0 && $calcDiscount > 0) {
            $discountAmount = $calcDiscount;
        }

        $taxableSubtotal = array_sum(array_column($orderItems, 'taxable_amount'));
        $gstAmount = array_sum(array_column($orderItems, 'cgst_amount')) + array_sum(array_column($orderItems, 'sgst_amount'));
        $cgstAmount = array_sum(array_column($orderItems, 'cgst_amount'));
        $sgstAmount = array_sum(array_column($orderItems, 'sgst_amount'));
        $gstPercentage = $taxableSubtotal > 0 ? round(($gstAmount / $taxableSubtotal) * 100, 2) : 0.0;
        $grandTotalAmount = (float)ceil($grossTotal + $shippingCost);

        $shop = Database::fetchOne("SELECT id FROM shops LIMIT 1");
        $shopId = $shop ? (int)$shop['id'] : null;

        $stockUserId = $billerId > 0 && Database::fetchOne("SELECT id FROM users WHERE id = ?", [$billerId])
            ? $billerId
            : null;
        try {
            Database::beginTransaction();
            Database::execute(
                "INSERT INTO sales (invoice_number, sale_type, shop_id, customer_id, customer_address, subtotal, discount_amount, taxable_amount, gst_percentage, gst_amount, cgst_amount, sgst_amount, shipping_cost, total_amount, paid_amount, payment_mode, payment_status, status, sales_channel, biller_id, billing_staff_id, employee_name, employee_code, biller_name, notes, created_at)
                 VALUES (?, 'WEBSITE', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DELIVERED', 'WEBSITE', ?, ?, ?, ?, ?, ?, NOW())",
                [$invoiceNumber, $shopId, $customerId ?: null, $fullAddress, $taxableSubtotal, $discountAmount, $taxableSubtotal, $gstPercentage, $gstAmount, $cgstAmount, $sgstAmount, $shippingCost, $grandTotalAmount, ($paymentStatus === 'PAID' ? $grandTotalAmount : 0.0), $paymentMode, $paymentStatus, $billerId ?: null, $billingStaffId ?: null, $employeeName, $employeeCode, $billerName, $notes]
            );
            $saleId = (int)Database::lastInsertId();

            foreach ($orderItems as $oi) {
                $tagNote = !empty($oi['scanned_qr_codes']) ? " [Piece Tags: {$oi['scanned_qr_codes']}]" : "";
                $this->deductSalesChannelStock(
                    $oi['variant_id'],
                    $oi['quantity'],
                    $channelIds,
                    'WEBSITE',
                    $saleId,
                    $stockUserId,
                    "Website Sale: {$invoiceNumber}{$tagNote}"
                );
                $this->assertSalesChannelTagsAvailable(
                    array_filter(array_map('trim', explode(',', (string)$oi['scanned_qr_codes']))),
                    (bool)$oi['is_series']
                );
                Database::execute(
                    "INSERT INTO sale_items (sale_id, variant_id, quantity, unit_price, mrp, gst_percentage, cgst_amount, sgst_amount, total_amount, scanned_qr_codes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$saleId, $oi['variant_id'], $oi['quantity'], $oi['unit_price'], $oi['mrp'], $oi['gst_percentage'], $oi['cgst_amount'], $oi['sgst_amount'], $oi['total'], $oi['scanned_qr_codes']]
                );
            }
            Database::commit();
        } catch (\PDOException $e) {
            Database::rollback();
            error_log("Website order creation failed: " . $e->getMessage());
            Session::flash('error', 'A database error prevented the website order from being saved. Please try again.');
            Response::redirect(url('sales/website/create'));
            return;
        } catch (\Throwable $e) {
            Database::rollback();
            error_log("Website order creation failed: " . $e->getMessage());
            Session::flash('error', $e->getMessage());
            Response::redirect(url('sales/website/create'));
            return;
        }

        Session::flash('success', "Website Order #{$invoiceNumber} created successfully.");
        Response::redirect(url('sales/website/view/' . $saleId));
    }

    public function viewWebsiteOrder(int $id): void {
        if (!hasPermission('website_orders', 'view')) {
            Session::flash('error', 'Access denied: You do not have permission to view website orders.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }

        $sale = Database::fetchOne(
            "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_db_address
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.id = ?",
            [$id]
        );

        if (!$sale) {
            Session::flash('error', 'Website order not found.');
            Response::redirect(url('warehouse/website-orders'));
            return;
        }

        $items = Database::fetchAll(
            "SELECT si.*, pv.sku, pv.qr_code, p.name as product_name,
                    COALESCE(c.name, 'General') as category_name,
                    COALESCE(sz.name, 'Standard') as size_name
             FROM sale_items si
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             WHERE si.sale_id = ?",
            [$id]
        );

        Response::view('sales.website.view', [
            'pageTitle'    => 'Website Order: ' . $sale['invoice_number'],
            'activeModule' => 'website',
            'activeMenu'   => 'wh-website',
            'breadcrumb'   => [
                ['label' => 'Website Orders', 'url' => url('warehouse/website-orders')],
                ['label' => $sale['invoice_number']]
            ],
            'sale'         => $sale,
            'items'        => $items,
        ]);
    }

    public function processWebsiteOrder(int $id): void {
        if (!hasPermission('website_orders', 'process')) {
            Session::flash('error', 'Access denied: You do not have permission to process Website orders.');
            Response::redirect(url('sales/website/view/' . $id));
            return;
        }

        CSRF::verify();
        $status = trim($_POST['status'] ?? 'COMPLETED');
        Database::execute("UPDATE sales SET status = ?, payment_status = ? WHERE id = ?", [$status, $status === 'CANCELLED' ? 'REFUNDED' : 'PAID', $id]);
        Session::flash('success', "Website Order updated to {$status}.");
        Response::redirect(url('sales/website/view/' . $id));
    }

    // ==========================================
    // WHATSAPP SALES ORDERS
    // ==========================================

    public function whatsappOrderList(): void {
        Response::redirect(url('warehouse/whatsapp-orders'));
    }

    public function createWhatsappOrder(): void {
        if (!hasPermission('whatsapp_orders', 'create') && !hasPermission('whatsapp', 'create')) {
            Session::flash('error', 'Access denied: You do not have permission to create WhatsApp orders.');
            Response::redirect(url('warehouse/whatsapp-orders/history'));
            return;
        }

        $customers = Database::fetchAll("SELECT * FROM customers ORDER BY name ASC");
        $users = Database::fetchAll("SELECT id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name ASC");
        $variants = Database::fetchAll(
            "SELECT pv.id, pv.product_id, pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.qr_type, pv.pack_quantity,
                    p.code as product_code, p.name as product_name,
                    p.is_complementary as p_is_comp,
                    pv.is_complementary as pv_is_comp,
                    wc.category_type as category_type,
                    COALESCE(c.name, 'General') as category_name,
                    c.code as category_code,
                    COALESCE(sc.name, '') as subcategory_name,
                    sc.code as subcategory_code,
                    COALESCE(ssc.name, '') as sub_subcategory_name,
                    ssc.code as sub_subcategory_code,
                    p.category_code as prod_category_code,
                    p.qr_code_prefix,
                    COALESCE(sz.name, 'Standard') as size_name,
                    COALESCE(cl.name, '') as color_name,
                    COALESCE(p.gst_percentage, 0.00) as gst_percentage
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
             LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             WHERE pv.is_active = 1
             ORDER BY p.name ASC, pv.sku ASC"
        );
        foreach ($variants as &$v) {
            $isComp = ((isset($v['category_type']) && $v['category_type'] === 'COMPLEMENTARY') || !empty($v['pv_is_comp']) || !empty($v['p_is_comp']));
            $isSeries = ((isset($v['category_type']) && $v['category_type'] === 'SERIES') || ($v['qr_type'] ?? '') === 'SERIES_BATCH' || (int)($v['pack_quantity'] ?? 1) > 1);
            $v['is_complementary'] = $isComp ? 1 : 0;
            $v['is_series'] = $isSeries ? 1 : 0;
            if ($isComp) {
                $v['selling_price'] = 0.00;
                $v['mrp'] = 0.00;
                $v['gst_percentage'] = 0.00;
            }
        }
        unset($v);
        $channelIds = $this->getSalesChannelLocationIds('WHATSAPP');
        $availableStockByVariant = $this->getSalesChannelAvailableStock($channelIds);
        foreach ($variants as &$variant) {
            $variant['available_stock'] = $availableStockByVariant[(int)$variant['id']] ?? 0;
        }
        unset($variant);
        $variants = $this->enhanceVariantsWithDynamicTokens($variants, $channelIds, 'WHATSAPP');

        $currentUserId = (int)(Session::get('user_id') ?? 0);
        $currentUser = $currentUserId > 0
            ? Database::fetchOne("SELECT id, full_name FROM users WHERE id = ?", [$currentUserId])
            : null;
        $currentUserId = $currentUser ? (int)$currentUser['id'] : 0;
        $defaultBillerName = $currentUser['full_name'] ?? 'System Administrator';

        Response::view('sales.whatsapp.create', [
            'pageTitle'         => 'Create WhatsApp Order',
            'activeModule'      => 'whatsapp',
            'activeMenu'        => 'wh-whatsapp',
            'breadcrumb'        => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => 'Create WhatsApp Order']
            ],
            'customers'         => $customers,
            'users'             => $users,
            'variants'          => $variants,
            'defaultBillerName' => $defaultBillerName,
            'currentUserId'     => $currentUserId,
        ]);
    }

    public function lookupWhatsappCustomer(): void {
        $query = trim((string)($_GET['q'] ?? ''));
        if (strlen($query) < 2) {
            Response::jsonSuccess('No customer search term', []);
            return;
        }
        $wild = '%' . $query . '%';
        $customers = Database::fetchAll(
            "SELECT id, name, phone, address, state, pincode
             FROM customers
             WHERE phone LIKE ? OR name LIKE ?
             ORDER BY CASE WHEN phone = ? THEN 0 ELSE 1 END, id DESC
             LIMIT 10",
            [$wild, $wild, $query]
        );
        Response::jsonSuccess('Customer matches loaded', $customers);
    }

    public function lookupWebsiteCustomer(): void {
        $query = trim((string)($_GET['q'] ?? ''));
        if (strlen($query) < 2) {
            Response::jsonSuccess('No customer search term', []);
            return;
        }
        $wild = '%' . $query . '%';
        $customers = Database::fetchAll(
            "SELECT id, name, phone, address, state, pincode
             FROM customers
             WHERE phone LIKE ? OR name LIKE ?
             ORDER BY CASE WHEN phone = ? THEN 0 ELSE 1 END, id DESC
             LIMIT 10",
            [$wild, $wild, $query]
        );
        Response::jsonSuccess('Customer matches loaded', $customers);
    }

    public function storeWhatsappOrder(): void {
        if (!hasPermission('whatsapp_orders', 'create') && !hasPermission('whatsapp', 'create')) {
            Session::flash('error', 'Access denied: You do not have permission to create WhatsApp orders.');
            Response::redirect(url('warehouse/whatsapp-orders/history'));
            return;
        }

        CSRF::verify();

        $customerId      = (int)($_POST['customer_id'] ?? 0);
        $customerName    = trim($_POST['customer_name'] ?? '');
        $customerPhone   = trim($_POST['customer_phone'] ?? '');
        $customerAddress = trim($_POST['customer_address'] ?? '');
        $customerState   = trim($_POST['customer_state'] ?? '');
        $customerPincode = trim($_POST['customer_pincode'] ?? '');
        $employeeName    = trim($_POST['employee_name'] ?? '');
        $employeeCode    = trim($_POST['employee_code'] ?? '');
        $billerName      = trim($_POST['biller_name'] ?? '');
        $requestedBillingStaffId = (int)($_POST['billing_staff_id'] ?? 0);
        $billingStaff = $requestedBillingStaffId > 0
            ? Database::fetchOne("SELECT id FROM users WHERE id = ?", [$requestedBillingStaffId])
            : null;
        $billingStaffId = $billingStaff ? (int)$billingStaff['id'] : 0;
        $requestedBillerId = $billingStaffId ?: (int)($_POST['biller_id'] ?? Session::get('user_id') ?? 0);
        $validBiller = $requestedBillerId > 0
            ? Database::fetchOne("SELECT id FROM users WHERE id = ?", [$requestedBillerId])
            : null;
        $billerId        = $validBiller ? (int)$validBiller['id'] : 0;
        $paymentMode     = trim($_POST['payment_mode'] ?? 'UPI');
        $paymentStatus   = strtoupper(trim($_POST['payment_status'] ?? 'PAID'));
        if ($paymentStatus === 'UNPAID') {
            $paymentStatus = 'PENDING';
        }
        if (!in_array($paymentStatus, ['PENDING', 'PAID', 'PARTIAL', 'REFUNDED'], true)) {
            $paymentStatus = 'PAID';
        }
        $shippingCost    = (float)($_POST['shipping_cost'] ?? 0);
        $gstPercentage   = (float)($_POST['gst_percentage'] ?? 0);
        $gstAmount       = (float)($_POST['gst_amount'] ?? 0);
        $notes           = trim($_POST['notes'] ?? 'WhatsApp Order');
        $items           = $_POST['items'] ?? [];

        if (empty($customerName) || empty($customerPhone) || empty($customerAddress)) {
            Session::flash('error', 'Customer Name, Mobile Number, and Customer Address are required.');
            Response::redirect(url('sales/whatsapp/create'));
            return;
        }

        if (!preg_match('/^[a-zA-Z\s.]+$/', $customerName)) {
            Session::flash('error', 'Customer Name must contain only alphabets.');
            Response::redirect(url('sales/whatsapp/create'));
            return;
        }

        if (!preg_match('/^\d{10}$/', $customerPhone)) {
            Session::flash('error', 'Customer Phone Number must contain only numbers and be exactly 10 digits.');
            Response::redirect(url('sales/whatsapp/create'));
            return;
        }

        if (!empty($customerPincode) && !preg_match('/^\d{6}$/', $customerPincode)) {
            Session::flash('error', 'Pincode must contain only numbers and be exactly 6 digits.');
            Response::redirect(url('sales/whatsapp/create'));
            return;
        }

        if (empty($items) || !is_array($items)) {
            Session::flash('error', 'Please select at least one item for the order.');
            Response::redirect(url('sales/whatsapp/create'));
            return;
        }

        if ($customerId > 0 && !Database::fetchOne("SELECT id FROM customers WHERE id = ?", [$customerId])) {
            $customerId = 0;
        }

        $channelIds = $this->getSalesChannelLocationIds('WHATSAPP');
        try {
            $items = $this->validateSalesChannelItems($items, 'WHATSAPP', $channelIds);
        } catch (\PDOException $e) {
            error_log("WhatsApp order validation failed: " . $e->getMessage());
            Session::flash('error', 'Unable to verify WhatsApp order stock right now. Please try again.');
            Response::redirect(url('sales/whatsapp/create'));
            return;
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('sales/whatsapp/create'));
            return;
        }

        if (!$customerId && !empty($customerName) && !empty($customerPhone)) {
            Database::execute(
                "INSERT INTO customers (name, phone, address, state, pincode, source_channel, created_at) VALUES (?, ?, ?, ?, ?, 'WHATSAPP', NOW())",
                [$customerName, $customerPhone, $customerAddress, $customerState, $customerPincode]
            );
            $customerId = (int)Database::lastInsertId();
        } elseif ($customerId > 0) {
            Database::execute(
                "UPDATE customers SET address = ?, state = ?, pincode = ? WHERE id = ?",
                [$customerAddress, $customerState, $customerPincode, $customerId]
            );
        }

        $fullAddress = $customerAddress;
        if (!empty($customerState)) $fullAddress .= ', ' . $customerState;
        if (!empty($customerPincode)) $fullAddress .= ' - ' . $customerPincode;

        // Upload payment screenshot to Cloudinary only
        $screenshotUrl = null;
        if (isset($_FILES['payment_screenshot']) && ($_FILES['payment_screenshot']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $screenshotUrl = CloudinaryService::upload(
                $_FILES['payment_screenshot'],
                'grm_inventory/whatsapp_sales/screenshots'
            );
        }

        // Build payment notes & split details
        $paymentDetails = [];
        if ($paymentMode === 'CASH_UPI') {
            $cashAmt = (float)($_POST['cash_amount'] ?? 0);
            $upiAmt  = (float)($_POST['upi_amount'] ?? 0);
            $upiRef  = trim($_POST['upi_reference'] ?? '');
            $paymentDetails[] = "Split Payment [Cash: ₹{$cashAmt}, UPI: ₹{$upiAmt}" . ($upiRef ? ", Ref: {$upiRef}" : "") . "]";
        }
        if (!empty($screenshotUrl)) {
            $paymentDetails[] = "Screenshot (Cloudinary): {$screenshotUrl}";
        }
        if (!empty($paymentDetails)) {
            $notes = implode(" | ", $paymentDetails) . (!empty($notes) ? " -- {$notes}" : "");
        }

        $prefix = 'WA-' . date('Ymd') . '-';
        $last = Database::fetchOne("SELECT invoice_number FROM sales WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1", [$prefix . '%']);
        $seq = $last ? ((int)substr($last['invoice_number'], -4) + 1) : 1;
        $invoiceNumber = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        $grossTotal = 0.0;
        $totalMrp = 0.0;
        $orderItems = [];

        foreach ($items as $itm) {
            $variantId = (int)($itm['variant_id'] ?? 0);
            $qty       = (int)($itm['quantity'] ?? 0);
            $price     = (float)($itm['unit_price'] ?? 0);
            $scannedQrs = trim($itm['scanned_qr_codes'] ?? '');

            if ($variantId > 0 && $qty > 0) {
                $vRow = Database::fetchOne(
                    "SELECT pv.*, p.is_complementary as p_is_comp, wc.category_type as warehouse_category_type
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
                     WHERE pv.id = ?",
                    [$variantId]
                );
                $isComp = $vRow && ((isset($vRow['warehouse_category_type']) && $vRow['warehouse_category_type'] === 'COMPLEMENTARY') || !empty($vRow['is_complementary']) || !empty($vRow['p_is_comp']));

                if ($isComp) {
                    $price = 0.00;
                }

                $sub = $qty * $price;
                $grossTotal += $sub;

                $mrp = $isComp ? 0.00 : (float)($itm['mrp'] ?? $price);
                if (!$isComp && $mrp <= 0) $mrp = $price;
                $totalMrp += ($qty * $mrp);
                $itemGstPercentage = $isComp ? 0.0 : (float)($itm['gst_percentage'] ?? 5.0);
                $lineTaxable = $isComp ? 0.0 : round($price / (1 + ($itemGstPercentage / 100)), 2) * $qty;
                $lineGst = $isComp ? 0.0 : round(($price - round($price / (1 + ($itemGstPercentage / 100)), 2)) * $qty, 2);
                $lineCgst = round($lineGst / 2, 2);
                $lineSgst = $lineGst - $lineCgst;

                $orderItems[] = [
                    'variant_id'       => $variantId,
                    'quantity'         => $qty,
                    'unit_price'       => $price,
                    'mrp'              => $mrp,
                    'gst_percentage'   => $itemGstPercentage,
                    'taxable'          => $lineTaxable,
                    'gst_amount'       => $lineGst,
                    'cgst_amount'      => $lineCgst,
                    'sgst_amount'      => $lineSgst,
                    'is_series'        => $itm['is_series'],
                    'total'            => $sub,
                    'scanned_qr_codes' => !empty($scannedQrs) ? $scannedQrs : null,
                ];
            }
        }

        $calcDiscount = max(0.0, $totalMrp - $grossTotal);
        $discountAmount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : $calcDiscount;
        if ($discountAmount <= 0 && $calcDiscount > 0) {
            $discountAmount = $calcDiscount;
        }

        $taxableSubtotal = array_sum(array_column($orderItems, 'taxable'));
        $gstAmount = array_sum(array_column($orderItems, 'gst_amount'));
        $cgstAmount = array_sum(array_column($orderItems, 'cgst_amount'));
        $sgstAmount = array_sum(array_column($orderItems, 'sgst_amount'));
        $gstPercentage = $taxableSubtotal > 0 ? round(($gstAmount / $taxableSubtotal) * 100, 2) : 0.0;
        $grandTotalAmount = (float)ceil($grossTotal + $shippingCost);

        $shop = Database::fetchOne("SELECT id FROM shops LIMIT 1");
        $shopId = $shop ? (int)$shop['id'] : null;

        $stockUserId = $billerId > 0 && Database::fetchOne("SELECT id FROM users WHERE id = ?", [$billerId])
            ? $billerId
            : null;
        try {
            Database::beginTransaction();
            Database::execute(
                "INSERT INTO sales (invoice_number, sale_type, shop_id, customer_id, customer_address, subtotal, discount_amount, taxable_amount, gst_percentage, gst_amount, cgst_amount, sgst_amount, shipping_cost, total_amount, paid_amount, payment_mode, payment_status, status, sales_channel, biller_id, billing_staff_id, employee_name, employee_code, biller_name, notes, created_at)
                 VALUES (?, 'WHATSAPP', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DELIVERED', 'WHATSAPP', ?, ?, ?, ?, ?, ?, NOW())",
                [$invoiceNumber, $shopId, $customerId ?: null, $fullAddress, $taxableSubtotal, $discountAmount, $taxableSubtotal, $gstPercentage, $gstAmount, $cgstAmount, $sgstAmount, $shippingCost, $grandTotalAmount, ($paymentStatus === 'PAID' ? $grandTotalAmount : 0.0), $paymentMode, $paymentStatus, $billerId ?: null, $billingStaffId ?: null, $employeeName, $employeeCode, $billerName, $notes]
            );
            $saleId = (int)Database::lastInsertId();

            foreach ($orderItems as $oi) {
                $tagNote = !empty($oi['scanned_qr_codes']) ? " [Piece Tags: {$oi['scanned_qr_codes']}]" : "";
                $this->deductSalesChannelStock(
                    $oi['variant_id'],
                    $oi['quantity'],
                    $channelIds,
                    'WHATSAPP',
                    $saleId,
                    $stockUserId,
                    "WhatsApp Sale: {$invoiceNumber}{$tagNote}"
                );
                $this->assertSalesChannelTagsAvailable(
                    array_filter(array_map('trim', explode(',', (string)$oi['scanned_qr_codes']))),
                    (bool)$oi['is_series']
                );
                Database::execute(
                    "INSERT INTO sale_items (sale_id, variant_id, quantity, unit_price, mrp, gst_percentage, cgst_amount, sgst_amount, total_amount, scanned_qr_codes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$saleId, $oi['variant_id'], $oi['quantity'], $oi['unit_price'], $oi['mrp'], $oi['gst_percentage'], $oi['cgst_amount'], $oi['sgst_amount'], $oi['total'], $oi['scanned_qr_codes']]
                );
            }
            Database::commit();
        } catch (\PDOException $e) {
            Database::rollback();
            error_log("WhatsApp order creation failed: " . $e->getMessage());
            Session::flash('error', 'A database error prevented the WhatsApp order from being saved. Please try again.');
            Response::redirect(url('sales/whatsapp/create'));
            return;
        } catch (\Throwable $e) {
            Database::rollback();
            error_log("WhatsApp order creation failed: " . $e->getMessage());
            Session::flash('error', $e->getMessage());
            Response::redirect(url('sales/whatsapp/create'));
            return;
        }

        Session::flash('success', "WhatsApp Order #{$invoiceNumber} created successfully.");
        Response::redirect(url('sales/whatsapp/view/' . $saleId));
    }

    public function viewWhatsappOrder(int $id): void {
        if (!hasPermission('whatsapp_orders', 'view') && !hasPermission('whatsapp', 'view')) {
            Session::flash('error', 'Access denied: You do not have permission to view WhatsApp orders.');
            Response::redirect(url('warehouse/whatsapp-orders'));
            return;
        }

        $sale = Database::fetchOne(
            "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_db_address
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.id = ?",
            [$id]
        );

        if (!$sale) {
            Session::flash('error', 'WhatsApp order not found.');
            Response::redirect(url('warehouse/whatsapp-orders'));
            return;
        }

        $items = Database::fetchAll(
            "SELECT si.*, pv.sku, pv.qr_code, p.name as product_name,
                    COALESCE(c.name, 'General') as category_name,
                    COALESCE(sz.name, 'Standard') as size_name
             FROM sale_items si
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             WHERE si.sale_id = ?",
            [$id]
        );

        Response::view('sales.whatsapp.view', [
            'pageTitle'    => 'WhatsApp Order: ' . $sale['invoice_number'],
            'activeModule' => 'whatsapp',
            'activeMenu'   => 'wh-whatsapp',
            'breadcrumb'   => [
                ['label' => 'WhatsApp Orders', 'url' => url('warehouse/whatsapp-orders')],
                ['label' => $sale['invoice_number']]
            ],
            'sale'         => $sale,
            'items'        => $items,
        ]);
    }

    public function processWhatsappOrder(int $id): void {
        if (!hasPermission('whatsapp_orders', 'process')) {
            Session::flash('error', 'Access denied: You do not have permission to process WhatsApp orders.');
            Response::redirect(url('sales/whatsapp/view/' . $id));
            return;
        }

        CSRF::verify();
        $status = trim($_POST['status'] ?? 'DELIVERED');
        if ($status === 'COMPLETED') $status = 'DELIVERED';
        Database::execute("UPDATE sales SET status = ?, payment_status = ? WHERE id = ?", [$status, $status === 'CANCELLED' ? 'REFUNDED' : 'PAID', $id]);
        Session::flash('success', "WhatsApp Order updated to {$status}.");
        Response::redirect(url('sales/whatsapp/view/' . $id));
    }
}
