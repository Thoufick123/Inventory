<?php
/**
 * Warehouse Service
 * Central business logic engine for Warehouse operations:
 * Inward GRN (Production & Supplier), Stock Balance Auditing, PO Receiving, Adjustments, and KPIs.
 */

require_once BASE_PATH . '/app/Models/Warehouse.php';
require_once BASE_PATH . '/app/Models/Location.php';
require_once BASE_PATH . '/app/Models/PurchaseOrder.php';
require_once BASE_PATH . '/app/Models/PurchaseItem.php';
require_once BASE_PATH . '/app/Models/PurchaseInward.php';
require_once BASE_PATH . '/app/Models/PurchaseInwardItem.php';
require_once BASE_PATH . '/app/Models/StockAdjustment.php';
require_once BASE_PATH . '/app/Models/ProductionBatch.php';
require_once BASE_PATH . '/app/Models/ProductionOutward.php';
require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class WarehouseService {

    /**
     * Generate unique sequence number with prefix.
     */
    public static function generateSequenceNumber(string $table, string $column, string $prefix): string {
        $today = date('Ymd');
        $pattern = $prefix . '-' . $today . '-%';
        
        $lastRecord = Database::fetchOne(
            "SELECT $column FROM $table WHERE $column LIKE ? ORDER BY id DESC LIMIT 1",
            [$pattern]
        );

        if ($lastRecord && !empty($lastRecord[$column])) {
            $parts = explode('-', $lastRecord[$column]);
            $sequence = (int)end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $today, $sequence);
    }

    // =========================================================================
    // 1. PRODUCTION INWARD (GRN) & CROSS-CHECK SCANNER
    // =========================================================================

    /**
     * Get Outward Dispatch and Variant Details for Cross-Check Inward.
     */
    public static function getOutwardDispatchDetails(int $batchId): ?array {
        $batch = ProductionBatch::find($batchId);
        if (!$batch) return null;

        $outward = ProductionOutward::findByBatchId($batchId);
        $items = ProductionBatch::getItems($batchId);

        return [
            'batch'   => $batch,
            'outward' => $outward,
            'items'   => $items,
        ];
    }

    /**
     * Process a real-time QR / SKU barcode scan during Inward Cross-Checking.
     * Matches scanned barcode to batch items, increments count, and returns difference & missing QRs.
     */
    public static function processCrossCheckScan(int $batchId, string $scannedCode, array $scannedMap = []): array {
        $details = self::getOutwardDispatchDetails($batchId);
        if (!$details) {
            throw new Exception("Production batch #$batchId not found.");
        }

        $batch = $details['batch'];
        $items = $details['items'];
        $scannedCode = trim($scannedCode);

        $extractedCode = $scannedCode;
        $extractedSku = '';
        $extractedTag = '';

        if (str_starts_with($scannedCode, '{') && str_ends_with($scannedCode, '}')) {
            $json = json_decode($scannedCode, true);
            if (!empty($json['sku'])) {
                $extractedSku = trim($json['sku']);
            }
            if (!empty($json['qr_code'])) {
                $extractedCode = trim($json['qr_code']);
            }
            if (!empty($json['tag_code'])) {
                $extractedTag = trim($json['tag_code']);
            }
            if (!empty($json['batch_number'])) {
                $extractedCode = trim($json['batch_number']);
            }
        } elseif (str_contains($scannedCode, '|')) {
            $parts = explode('|', $scannedCode);
            $extractedTag = trim($parts[0]);
            if (!empty($parts[1])) {
                $extractedSku = trim($parts[1]);
            }
        }

        // Check if scanned code is the Batch/Outward Master Carton QR
        $isMasterCarton = (
            $scannedCode === $batch['batch_number'] ||
            $extractedCode === $batch['batch_number'] ||
            (!empty($details['outward']) && ($scannedCode === $details['outward']['outward_number'] || $extractedCode === $details['outward']['outward_number']))
        );

        $matchedVariantId = null;
        $matchedItem = null;

        if ($isMasterCarton) {
            // Master carton QR received: verify only the quantities assigned to this production batch.
            foreach ($items as $item) {
                $vid = (int)$item['variant_id'];
                $scannedMap[$vid] = (int)$item['quantity'];
            }
        } else {
            // Individual variant barcode / Exact QR / SKU scan
            foreach ($items as $item) {
                $itemSku = trim($item['sku'] ?? '');
                $itemQr = trim($item['qr_code'] ?? '');
                $varId = (string)($item['variant_id'] ?? '');
                $outwardPieceTags = array_values(array_filter(array_map(
                    static fn($tag) => strtoupper(trim((string)$tag)),
                    is_array($item['outward_piece_tags'] ?? null) ? $item['outward_piece_tags'] : []
                )));

                $scannedPieceTag = $extractedTag !== '' ? $extractedTag : $scannedCode;
                $looksLikePieceTag = preg_match('/^[A-Z]{2,}\d{4,}$/i', $scannedPieceTag) === 1;
                if ($extractedTag !== '' || $looksLikePieceTag) {
                    if (!empty($outwardPieceTags)) {
                        $isMatch = in_array(strtoupper($scannedPieceTag), $outwardPieceTags, true);
                    } else {
                        $legacyPieceTags = array_values(array_filter(array_map(
                            static fn($tag) => strtoupper(trim((string)$tag)),
                            is_array($item['piece_tags'] ?? null) ? $item['piece_tags'] : []
                        )));
                        $isMatch = in_array(strtoupper($scannedPieceTag), $legacyPieceTags, true);
                    }
                } else {
                    $isMatch = (
                        ($itemQr !== '' && strcasecmp($itemQr, $scannedCode) === 0) ||
                        ($itemQr !== '' && strcasecmp($itemQr, $extractedCode) === 0) ||
                        ($itemSku !== '' && strcasecmp($itemSku, $scannedCode) === 0) ||
                        ($itemSku !== '' && strcasecmp($itemSku, $extractedCode) === 0) ||
                        ($extractedSku !== '' && strcasecmp($itemSku, $extractedSku) === 0) ||
                        $varId === $scannedCode ||
                        $varId === $extractedCode
                    );
                }

                // Legacy batches without an assigned tag list may still match their saved sequence range.
                if (!$isMatch && empty($outwardPieceTags) && $looksLikePieceTag && !empty($item['start_seq']) && !empty($item['end_seq'])) {
                    if (preg_match('/(\d+)$/', $scannedPieceTag, $mNum)) {
                        $seq = (int)$mNum[1];
                        $isMatch = $seq >= (int)$item['start_seq'] && $seq <= (int)$item['end_seq'];
                    }
                }

                if ($isMatch) {
                    $matchedVariantId = (int)$item['variant_id'];
                    $matchedItem = $item;
                    break;
                }
            }

            // Fallback match for single-item batches or partial matches
            if (!$matchedVariantId && count($items) === 1 && !$looksLikePieceTag) {
                $matchedVariantId = (int)$items[0]['variant_id'];
                $matchedItem = $items[0];
            }

            if (!$matchedVariantId) {
                return [
                    'success'       => false,
                    'message'       => "Scanned code '$scannedCode' does not match any product variant or QR code in Batch #{$batch['batch_number']}.",
                    'matched_item'  => null,
                    'scanned_map'   => $scannedMap,
                ];
            }

            $expectedCount = max(0, (int)$matchedItem['quantity']);
            if ($expectedCount === 0) {
                return [
                    'success' => false,
                    'message' => "No outwarded pieces are available for {$matchedItem['sku']} in this batch.",
                    'matched_item' => $matchedItem,
                    'scanned_map' => $scannedMap,
                ];
            }

            $isSeriesOrComp = (!empty($matchedItem['is_series']) || !empty($matchedItem['is_complementary']) || ($matchedItem['qr_type'] ?? '') === 'SERIES_BATCH' || (int)($matchedItem['pack_quantity'] ?? 1) > 1);
            if ($isSeriesOrComp) {
                // Single scan inwards ALL counts for series and complementary products
                $scannedMap[$matchedVariantId] = (int)$matchedItem['quantity'];
                $scanMessage = "Scanned {$matchedItem['sku']} (All counts inwarded: +{$matchedItem['quantity']} pcs)";
            } else {
                // Normal products: 1 piece per scan
                $currentCount = max(0, min($expectedCount, (int)($scannedMap[$matchedVariantId] ?? 0)));
                if ($currentCount >= $expectedCount) {
                    return [
                        'success' => false,
                        'message' => "All {$expectedCount} outwarded pieces for {$matchedItem['sku']} have already been scanned.",
                        'matched_item' => $matchedItem,
                        'scanned_map' => $scannedMap,
                    ];
                }
                $scannedMap[$matchedVariantId] = $currentCount + 1;
                $scanMessage = "Scanned {$matchedItem['sku']} (+1 pc)";
            }
        }

        // Build comparison breakdown and missing QR list
        $totalExpected = 0;
        $totalScanned = 0;
        $comparison = [];
        $missingItems = [];

        foreach ($items as $item) {
            $vid = (int)$item['variant_id'];
            $expected = (int)$item['quantity'];
            $scanned = max(0, min($expected, (int)($scannedMap[$vid] ?? 0)));
            $diff = $scanned - $expected;

            $totalExpected += $expected;
            $totalScanned += $scanned;

            $status = 'MATCHED';
            if ($diff < 0) {
                $status = 'SHORTAGE';
                $missingItems[] = [
                    'variant_id'   => $vid,
                    'sku'          => $item['sku'],
                    'qr_code'      => $item['qr_code'] ?? $item['sku'],
                    'size_name'    => $item['size_name'] ?? 'Free Size',
                    'color_name'   => $item['color_name'] ?? 'Standard',
                    'color_hex'    => $item['color_hex'] ?? '#6c757d',
                    'expected_qty' => $expected,
                    'scanned_qty'  => $scanned,
                    'missing_qty'  => abs($diff),
                ];
            } elseif ($diff > 0) {
                $status = 'EXCESS';
            }

            $comparison[] = [
                'variant_id'   => $vid,
                'sku'          => $item['sku'],
                'qr_code'      => $item['qr_code'] ?? $item['sku'],
                'size_name'    => $item['size_name'] ?? 'Free Size',
                'color_name'   => $item['color_name'] ?? 'Standard',
                'color_hex'    => $item['color_hex'] ?? '#6c757d',
                'selling_price'=> (float)($item['selling_price'] ?? 0),
                'expected_qty' => $expected,
                'scanned_qty'  => $scanned,
                'diff'         => $diff,
                'status'       => $status,
            ];
        }

        $overallDiff = $totalScanned - $totalExpected;
        $isComplete = empty($missingItems) && $totalScanned === $totalExpected;

        return [
            'success'          => true,
            'message'          => $isMasterCarton ? "Master Carton QR scanned! All items verified." : ($scanMessage ?? "Scanned {$matchedItem['sku']}"),
            'is_master_carton' => $isMasterCarton,
            'matched_item'     => $matchedItem,
            'total_expected'   => $totalExpected,
            'total_scanned'    => $totalScanned,
            'overall_diff'     => $overallDiff,
            'is_complete'      => $isComplete,
            'comparison'       => $comparison,
            'missing_items'    => $missingItems,
            'scanned_map'      => $scannedMap,
        ];
    }

    /**
     * Receive and Verify Production Inward Batch.
     * Supports both 100% full inward and custom cross-checked itemized quantities (with shortages/damages).
     * Updates batch status to 'RECEIVED' and increases warehouse stock via StockService.
     */
    public static function receiveProductionBatch(int $batchId, int $userId, ?string $notes = null, ?array $receivedItems = null): bool {
        Database::beginTransaction();
        try {
            $batch = ProductionBatch::find($batchId);
            if (!$batch) {
                throw new Exception("Production batch #$batchId not found.");
            }

            if ($batch['status'] !== 'DISPATCHED' && $batch['status'] !== 'RECEIVED') {
                throw new Exception("Batch status is '{$batch['status']}'. Batches in this status cannot be received.");
            }

            $items = ProductionBatch::getItems($batchId);
            if (empty($items)) {
                throw new Exception("No variant items found in batch #$batchId.");
            }

            $warehouseId = (int)$batch['destination_warehouse_id'];
            $warehouse = Warehouse::find($warehouseId);
            if (!$warehouse) {
                throw new Exception("Destination warehouse #$warehouseId not found.");
            }

            $totalAccepted = 0;
            $totalDamaged = 0;
            $totalExpected = 0;
            $totalIncrementalAccepted = 0;
            $totalIncrementalDamaged = 0;
            $discrepancyReport = [];

            // Process stock inward for each variant in batch
            foreach ($items as $item) {
                $vid = (int)$item['variant_id'];
                $itemReceivedData = $receivedItems[$vid] ?? [];
                $expectedQty = (float)$item['quantity'];
                $totalExpected += $expectedQty;

                $prevReceivedQty = (float)($item['received_qty'] ?? 0);
                $prevDamagedQty = (float)($item['damaged_qty'] ?? 0);

                if ($receivedItems !== null && isset($receivedItems[$vid])) {
                    $acceptedQty = (float)($itemReceivedData['received_qty'] ?? $itemReceivedData['accepted_qty'] ?? 0);
                    $damagedQty = (float)($itemReceivedData['damaged_qty'] ?? 0);
                } else {
                    // Default to full received qty
                    $acceptedQty = $expectedQty;
                    $damagedQty = 0;
                }

                if ($acceptedQty < 0 || $damagedQty < 0 || ($acceptedQty + $damagedQty) > $expectedQty) {
                    throw new Exception("Inward quantity for SKU {$item['sku']} cannot exceed the {$expectedQty} pieces dispatched for this batch.");
                }
                if (floor($acceptedQty) !== $acceptedQty || floor($damagedQty) !== $damagedQty) {
                    throw new Exception("Inward quantities for SKU {$item['sku']} must be whole pieces.");
                }

                $receivedTagList = array_values(array_filter(array_map('trim', explode(',', (string)($itemReceivedData['received_tags'] ?? '')))));
                $damagedTagList = array_values(array_filter(array_map('trim', explode(',', (string)($itemReceivedData['damaged_tags'] ?? '')))));
                $outwardPieceTags = array_values(array_filter(array_map(
                    static fn($tag) => strtoupper(trim((string)$tag)),
                    is_array($item['outward_piece_tags'] ?? null) ? $item['outward_piece_tags'] : []
                )));
                $reportedPieceTags = array_map('strtoupper', array_merge($receivedTagList, $damagedTagList));
                if (
                    count($receivedTagList) !== count(array_unique(array_map('strtoupper', $receivedTagList)))
                    || count($damagedTagList) !== count(array_unique(array_map('strtoupper', $damagedTagList)))
                    || count($receivedTagList) > $acceptedQty
                    || count($damagedTagList) > $damagedQty
                    || array_intersect(array_map('strtoupper', $receivedTagList), array_map('strtoupper', $damagedTagList))
                    || (!empty($outwardPieceTags) && array_diff($reportedPieceTags, $outwardPieceTags))
                ) {
                    throw new Exception("Piece QR codes for SKU {$item['sku']} do not match the verified inward quantities.");
                }

                $missingQty = max(0, (int)($expectedQty - ($acceptedQty + $damagedQty)));
                $totalAccepted += $acceptedQty;
                $totalDamaged += $damagedQty;
                $diff = ($acceptedQty + $damagedQty) - $expectedQty;

                if ($diff !== 0.0) {
                    $discrepancyReport[] = "SKU {$item['sku']}: Expected $expectedQty, Accepted $acceptedQty, Damaged $damagedQty (Diff: $diff)";
                }

                // Extract or resolve actual piece QR codes for received and damaged garments
                $itemRecvTags = trim($itemReceivedData['received_tags'] ?? '');
                $itemDmgTags = trim($itemReceivedData['damaged_tags'] ?? '');

                $pieceTags = !empty($item['outward_piece_tags'])
                    ? $item['outward_piece_tags']
                    : (!empty($item['piece_tags']) ? $item['piece_tags'] : []);
                if (empty($itemRecvTags) && !empty($pieceTags) && $acceptedQty > 0) {
                    $recvList = array_slice($pieceTags, 0, (int)$acceptedQty);
                    $itemRecvTags = implode(',', $recvList);
                }
                if (empty($itemDmgTags) && !empty($pieceTags) && $damagedQty > 0) {
                    $dmgList = array_slice($pieceTags, (int)$acceptedQty, (int)$damagedQty);
                    $itemDmgTags = implode(',', $dmgList);
                }

                // Persist variant-level received, damaged, missing quantities, and exact QR codes
                Database::execute(
                    "UPDATE production_batch_items 
                     SET received_qty = ?, damaged_qty = ?, missing_qty = ?, received_qr_codes = ?, damaged_qr_codes = ? 
                     WHERE id = ?",
                    [(int)$acceptedQty, (int)$damagedQty, (int)$missingQty, $itemRecvTags ?: null, $itemDmgTags ?: null, $item['id']]
                );

                // Calculate incremental deltas for AVAILABLE stock
                $deltaAccepted = $acceptedQty - $prevReceivedQty;
                if ($deltaAccepted > 0) {
                    $totalIncrementalAccepted += $deltaAccepted;
                    StockService::increase(
                        variantId: $vid,
                        locationType: 'WAREHOUSE',
                        locationId: $warehouseId,
                        stockState: 'AVAILABLE',
                        quantity: $deltaAccepted,
                        transactionType: 'PRODUCTION_GRN',
                        referenceType: 'production_batch',
                        referenceId: $batchId,
                        userId: $userId,
                        notes: "Production Inward GRN from Batch #" . $batch['batch_number'] . ($notes ? " ($notes)" : "") . ($prevReceivedQty > 0 ? " [Incremental Inward: +{$deltaAccepted} pcs]" : "")
                    );
                } elseif ($deltaAccepted < 0) {
                    // Decrement if previously over-credited
                    $decrementQty = abs($deltaAccepted);
                    StockService::decrease(
                        variantId: $vid,
                        locationType: 'WAREHOUSE',
                        locationId: $warehouseId,
                        stockState: 'AVAILABLE',
                        quantity: $decrementQty,
                        transactionType: 'PRODUCTION_GRN',
                        referenceType: 'production_batch',
                        referenceId: $batchId,
                        userId: $userId,
                        notes: "Production Inward GRN Correction for Batch #" . $batch['batch_number'] . " [Adjustment: -{$decrementQty} pcs]"
                    );
                }

                // Calculate incremental deltas for DAMAGED quarantine stock
                $deltaDamaged = $damagedQty - $prevDamagedQty;
                if ($deltaDamaged > 0) {
                    $totalIncrementalDamaged += $deltaDamaged;
                    // Calculate unit valuation with robust fallback across production_cost, purchase_price, selling_price, mrp
                    $costPerUnit = 0.0;
                    if (!empty($item['production_cost']) && (float)$item['production_cost'] > 0) {
                        $costPerUnit = (float)$item['production_cost'];
                    } elseif (!empty($item['purchase_price']) && (float)$item['purchase_price'] > 0) {
                        $costPerUnit = (float)$item['purchase_price'];
                    } elseif (!empty($item['selling_price']) && (float)$item['selling_price'] > 0) {
                        $costPerUnit = (float)$item['selling_price'];
                    } elseif (!empty($item['mrp']) && (float)$item['mrp'] > 0) {
                        $costPerUnit = (float)$item['mrp'];
                    }
                    $totalLoss = $deltaDamaged * $costPerUnit;

                    $actualDamageTag = !empty($itemDmgTags) ? $itemDmgTags : '';
                    if (empty($actualDamageTag)) {
                        $catInitial = !empty($item['category_name']) ? strtoupper(substr(trim($item['category_name']), 0, 1)) : 'M';
                        $subInitial = !empty($item['subcategory_name']) ? strtoupper(substr(trim($item['subcategory_name']), 0, 1)) : 'T';
                        $subSubInitial = !empty($item['sub_subcategory_name']) ? strtoupper(substr(trim($item['sub_subcategory_name']), 0, 1)) : 'P';
                        $datePart = date('my');
                        $baseTagPrefix = $catInitial . $subInitial . $subSubInitial . $datePart;
                        $damageTagList = [];
                        for ($ti = 1; $ti <= $deltaDamaged; $ti++) {
                            $damageTagList[] = $baseTagPrefix . str_pad((string)$ti, 2, '0', STR_PAD_LEFT);
                        }
                        $actualDamageTag = implode(', ', $damageTagList);
                    }

                    $damageNumber = WarehouseDamage::generateDamageNumber('DMG');

                    $damageId = WarehouseDamage::create([
                        'damage_number'      => $damageNumber,
                        'warehouse_id'       => $warehouseId,
                        'variant_id'         => $vid,
                        'tag_code'           => $actualDamageTag,
                        'source_type'        => 'PRODUCTION_INWARD',
                        'reference_id'       => $batchId,
                        'quantity'           => $deltaDamaged,
                        'cost_per_unit'      => $costPerUnit,
                        'total_loss'         => $totalLoss,
                        'damage_reason'      => 'Transit / Inward Verification Damage',
                        'defect_notes'       => "Quarantined during Inward GRN for Batch #{$batch['batch_number']} (Program: {$batch['program_number']}, Facility: " . ($batch['production_unit_name'] ?? 'Main Facility') . ")",
                        'status'             => 'LOGGED',
                        'reported_by'        => $userId,
                        'production_unit_id' => !empty($batch['production_unit_id']) ? (int)$batch['production_unit_id'] : null,
                    ]);

                    StockService::increase(
                        variantId: $vid,
                        locationType: 'WAREHOUSE',
                        locationId: $warehouseId,
                        stockState: 'DAMAGED',
                        quantity: $deltaDamaged,
                        transactionType: 'PRODUCTION_GRN',
                        referenceType: 'warehouse_damage',
                        referenceId: $damageId,
                        userId: $userId,
                        notes: "Damaged in transit from Batch #" . $batch['batch_number'] . " (Case #{$damageNumber})"
                    );

                    AuditService::log('warehouse', 'inward_damage_logged', 'warehouse_damages', $damageId, null, [
                        'damage_number'        => $damageNumber,
                        'batch_id'             => $batchId,
                        'batch_number'         => $batch['batch_number'],
                        'production_unit_name' => $batch['production_unit_name'] ?? 'Main Production Facility',
                        'production_unit_code' => $batch['production_unit_code'] ?? 'PU1',
                        'variant_id'           => $vid,
                        'sku'                  => $item['sku'],
                        'damaged_qty'          => $deltaDamaged,
                        'warehouse_id'         => $warehouseId,
                        'cost_per_unit'        => $costPerUnit,
                        'total_loss'           => $totalLoss,
                    ], $userId);
                }
            }

            $totalMissing = max(0, (int)($totalExpected - ($totalAccepted + $totalDamaged)));

            // Update batch quantities and status
            Database::execute(
                "UPDATE production_batches 
                 SET received_qty = ?, damaged_qty = ?, missing_qty = ?, status = 'RECEIVED' 
                 WHERE id = ?",
                [(int)$totalAccepted, (int)$totalDamaged, (int)$totalMissing, $batchId]
            );

            // Formulate audit log summary
            $auditNotes = $notes ?? 'Production Inward GRN Verified';
            if ($totalIncrementalAccepted > 0 || $totalIncrementalDamaged > 0) {
                $auditNotes .= " (Incremental Inward: +{$totalIncrementalAccepted} accepted, +{$totalIncrementalDamaged} damaged)";
            }
            if (!empty($discrepancyReport)) {
                $auditNotes .= " | Discrepancy logged: " . implode("; ", $discrepancyReport);
            }

            AuditService::log('warehouse', 'receive_grn', 'production_batch', $batchId, 
                ['status' => $batch['status']], 
                [
                    'status' => 'RECEIVED', 
                    'warehouse_id' => $warehouseId, 
                    'total_expected' => $totalExpected,
                    'total_accepted' => $totalAccepted,
                    'total_damaged'  => $totalDamaged,
                    'total_missing'  => $totalMissing,
                    'notes' => $auditNotes
                ]
            );

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            error_log("WarehouseService::receiveProductionBatch failed: " . $e->getMessage());
            throw $e;
        }
    }

    // =========================================================================
    // 2. EXTERNAL PURCHASES & SUPPLIER GRN
    // =========================================================================

    private static function isDirectPurchaseFulfilled(array $poData): bool {
        return in_array(strtoupper(trim((string)($poData['status'] ?? ''))), ['COMPLETED', 'DISPATCHED', 'RECEIVED'], true);
    }

    private static function applyDirectPurchaseStock(array $items, int $poId, int $warehouseId, int $userId, string $poNumber): void {
        foreach ($items as $item) {
            $quantity = (float)($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            StockService::decrease(
                variantId: (int)$item['variant_id'],
                locationType: 'WAREHOUSE',
                locationId: $warehouseId,
                stockState: 'AVAILABLE',
                quantity: $quantity,
                transactionType: 'WAREHOUSE_DIRECT_SALE',
                referenceType: 'purchase_orders',
                referenceId: $poId,
                userId: $userId,
                notes: "Customer Direct Purchase #" . $poNumber
            );
        }
    }

    /**
     * Create Direct Customer Purchase / Order with tax & discount calculation.
     */
    public static function createPurchaseOrder(array $poData, array $items, int $userId): int {
        Database::beginTransaction();
        try {
            if (empty($poData['po_number'])) {
                $poData['po_number'] = self::generateSequenceNumber('purchase_orders', 'po_number', 'DPO');
            }
            $poData['created_by'] = $userId;

            // Calculate totals matching Shop POS (Inclusive GST and item discount)
            $subtotal = 0.0;
            $totalDiscount = 0.0;
            $totalCgst = 0.0;
            $totalSgst = 0.0;
            $totalIgst = 0.0;
            $grandTotal = 0.0;
            $gstType = $poData['gst_type'] ?? 'CGST_SGST';

            $processedItems = [];
            foreach ($items as $item) {
                $qty = (int)($item['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $vInfo = Database::fetchOne(
                    "SELECT pv.id, pv.qr_type, pv.pack_quantity, COALESCE(pv.is_complementary, p.is_complementary, 0) as is_comp,
                            COALESCE(wc.category_type, 'NORMAL') as cat_type
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
                     WHERE pv.id = ?",
                    [(int)$item['variant_id']]
                );
                $isComp = ($vInfo && ($vInfo['is_comp'] == 1 || strtoupper($vInfo['cat_type'] ?? '') === 'COMPLEMENTARY'));

                $unitPrice = $isComp ? 0.0 : (float)($item['unit_price'] ?? 0.0);
                $mrp = $isComp ? 0.0 : (float)($item['mrp'] ?? $unitPrice);
                if (!$isComp) {
                    if ($mrp <= 0) $mrp = $unitPrice;
                    if ($unitPrice <= 0 && $mrp > 0) $unitPrice = $mrp;
                }

                $discountPct = $isComp ? 0.0 : max(0.0, min(100.0, (float)($item['discount_percentage'] ?? 0.0)));
                $gstRate = $isComp ? 0.0 : max(0.0, (float)($item['gst_percentage'] ?? 5.0));

                $lineMrpGross = $qty * $mrp;
                $lineBilledTotal = round($qty * $unitPrice, 2);
                
                // If unitPrice < mrp, compute discount amount from mrp
                if ($lineBilledTotal < $lineMrpGross) {
                    $lineDiscount = round($lineMrpGross - $lineBilledTotal, 2);
                    $discountPct = $lineMrpGross > 0 ? round(($lineDiscount / $lineMrpGross) * 100, 2) : 0.0;
                } else if ($discountPct > 0) {
                    $lineDiscount = round(($lineMrpGross * $discountPct) / 100, 2);
                    $lineDiscount = min($lineMrpGross, max(0.0, $lineDiscount));
                    $lineBilledTotal = round($lineMrpGross - $lineDiscount, 2);
                    $unitPrice = $qty > 0 ? round($lineBilledTotal / $qty, 2) : $unitPrice;
                } else {
                    $lineDiscount = 0.0;
                    $discountPct = 0.0;
                }

                // Inclusive GST calculation (Base Taxable = Net Billed / (1 + GST%))
                $lineTaxable = round($lineBilledTotal / (1 + ($gstRate / 100)), 2);
                $lineTax = round($lineBilledTotal - $lineTaxable, 2);

                $lineCgst = 0.0;
                $lineSgst = 0.0;
                $lineIgst = 0.0;

                if ($gstType === 'IGST') {
                    $lineIgst = $lineTax;
                } else {
                    $lineCgst = round($lineTax / 2, 2);
                    $lineSgst = round($lineTax - $lineCgst, 2);
                }

                $lineTotal = $lineBilledTotal;

                $subtotal += $lineTaxable;
                $totalDiscount += $lineDiscount;
                $totalCgst += $lineCgst;
                $totalSgst += $lineSgst;
                $totalIgst += $lineIgst;
                $grandTotal += $lineTotal;

                $processedItems[] = [
                    'variant_id'          => (int)$item['variant_id'],
                    'quantity'            => $qty,
                    'unit_price'          => $unitPrice,
                    'discount_percentage' => $discountPct,
                    'gst_percentage'      => $gstRate,
                    'expiry_date'         => !empty($item['expiry_date']) ? $item['expiry_date'] : null,
                    'mfg_date'            => !empty($item['mfg_date']) ? $item['mfg_date'] : null,
                    'batch_lot_no'        => !empty($item['batch_lot_no']) ? trim($item['batch_lot_no']) : (!empty($item['scanned_qr_codes']) ? trim($item['scanned_qr_codes']) : null),
                    'notes'               => !empty($item['notes']) ? trim($item['notes']) : (!empty($item['scanned_qr_codes']) ? ('QR: ' . trim($item['scanned_qr_codes'])) : null),
                    'mrp'                 => $mrp,
                    'cgst_amount'         => $lineCgst,
                    'sgst_amount'         => $lineSgst,
                    'igst_amount'         => $lineIgst,
                    'total_amount'        => $lineTotal,
                ];
            }

            $poData['subtotal'] = $subtotal;
            $poData['discount_amount'] = $totalDiscount;
            $poData['cgst_amount'] = $totalCgst;
            $poData['sgst_amount'] = $totalSgst;
            $poData['igst_amount'] = $totalIgst;
            $poData['tax_amount'] = $totalCgst + $totalSgst + $totalIgst;
            $poData['total_amount'] = $grandTotal;

            $poId = PurchaseOrder::create($poData);

            foreach ($processedItems as $pItem) {
                $pItem['purchase_order_id'] = $poId;
                PurchaseItem::create($pItem);
            }

            if (self::isDirectPurchaseFulfilled($poData)) {
                self::applyDirectPurchaseStock(
                    $processedItems,
                    $poId,
                    (int)$poData['warehouse_id'],
                    $userId,
                    (string)$poData['po_number']
                );
            }

            AuditService::log('warehouse', 'create', 'purchase_orders', $poId, null, $poData);

            Database::commit();
            return $poId;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Update Direct Customer Purchase Order.
     */
    public static function updatePurchaseOrder(int $id, array $poData, array $items, int $userId): bool {
        Database::beginTransaction();
        try {
            $existing = PurchaseOrder::find($id);
            if (!$existing) {
                throw new Exception("Purchase Order #$id not found.");
            }

            $isDirectCustomerPurchase = empty($existing['seller_id']) && empty($existing['warehouse_supplier_id']);
            $lockProductItems = false;
            if ($isDirectCustomerPurchase) {
                $editState = Database::fetchOne(
                    "SELECT product_edit_count
                     FROM purchase_orders
                     WHERE id = ?
                     FOR UPDATE",
                    [$id]
                );
                $lockProductItems = (int)($editState['product_edit_count'] ?? 0) > 0;
            }
            if ($lockProductItems) {
                $items = PurchaseOrder::getItems($id);
            }

            // Calculate totals matching Shop POS (Inclusive GST and item discount)
            $subtotal = 0.0;
            $totalDiscount = 0.0;
            $totalCgst = 0.0;
            $totalSgst = 0.0;
            $totalIgst = 0.0;
            $grandTotal = 0.0;
            $gstType = $poData['gst_type'] ?? 'CGST_SGST';

            $processedItems = [];
            foreach ($items as $item) {
                $qty = (int)($item['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $vInfo = Database::fetchOne(
                    "SELECT pv.id, pv.qr_type, pv.pack_quantity, COALESCE(pv.is_complementary, p.is_complementary, 0) as is_comp,
                            COALESCE(wc.category_type, 'NORMAL') as cat_type
                     FROM product_variants pv
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
                     WHERE pv.id = ?",
                    [(int)$item['variant_id']]
                );
                $isComp = ($vInfo && ($vInfo['is_comp'] == 1 || strtoupper($vInfo['cat_type'] ?? '') === 'COMPLEMENTARY'));

                $unitPrice = $isComp ? 0.0 : (float)($item['unit_price'] ?? 0.0);
                $mrp = $isComp ? 0.0 : (float)($item['mrp'] ?? $unitPrice);
                if (!$isComp) {
                    if ($mrp <= 0) $mrp = $unitPrice;
                    if ($unitPrice <= 0 && $mrp > 0) $unitPrice = $mrp;
                }

                $discountPct = $isComp ? 0.0 : max(0.0, min(100.0, (float)($item['discount_percentage'] ?? 0.0)));
                $gstRate = $isComp ? 0.0 : max(0.0, (float)($item['gst_percentage'] ?? 5.0));

                $lineMrpGross = $qty * $mrp;
                $lineBilledTotal = round($qty * $unitPrice, 2);
                
                // If unitPrice < mrp, compute discount amount from mrp
                if ($lineBilledTotal < $lineMrpGross) {
                    $lineDiscount = round($lineMrpGross - $lineBilledTotal, 2);
                    $discountPct = $lineMrpGross > 0 ? round(($lineDiscount / $lineMrpGross) * 100, 2) : 0.0;
                } else if ($discountPct > 0) {
                    $lineDiscount = round(($lineMrpGross * $discountPct) / 100, 2);
                    $lineDiscount = min($lineMrpGross, max(0.0, $lineDiscount));
                    $lineBilledTotal = round($lineMrpGross - $lineDiscount, 2);
                    $unitPrice = $qty > 0 ? round($lineBilledTotal / $qty, 2) : $unitPrice;
                } else {
                    $lineDiscount = 0.0;
                    $discountPct = 0.0;
                }

                // Inclusive GST calculation (Base Taxable = Net Billed / (1 + GST%))
                $lineTaxable = round($lineBilledTotal / (1 + ($gstRate / 100)), 2);
                $lineTax = round($lineBilledTotal - $lineTaxable, 2);

                $lineCgst = 0.0;
                $lineSgst = 0.0;
                $lineIgst = 0.0;

                if ($gstType === 'IGST') {
                    $lineIgst = $lineTax;
                } else {
                    $lineCgst = round($lineTax / 2, 2);
                    $lineSgst = round($lineTax - $lineCgst, 2);
                }

                $lineTotal = $lineBilledTotal;

                $subtotal += $lineTaxable;
                $totalDiscount += $lineDiscount;
                $totalCgst += $lineCgst;
                $totalSgst += $lineSgst;
                $totalIgst += $lineIgst;
                $grandTotal += $lineTotal;

                $processedItems[] = [
                    'variant_id'          => (int)$item['variant_id'],
                    'quantity'            => $qty,
                    'unit_price'          => $unitPrice,
                    'discount_percentage' => $discountPct,
                    'gst_percentage'      => $gstRate,
                    'expiry_date'         => !empty($item['expiry_date']) ? $item['expiry_date'] : null,
                    'mfg_date'            => !empty($item['mfg_date']) ? $item['mfg_date'] : null,
                    'batch_lot_no'        => !empty($item['batch_lot_no']) ? trim($item['batch_lot_no']) : (!empty($item['scanned_qr_codes']) ? trim($item['scanned_qr_codes']) : null),
                    'notes'               => !empty($item['notes']) ? trim($item['notes']) : (!empty($item['scanned_qr_codes']) ? ('QR: ' . trim($item['scanned_qr_codes'])) : null),
                    'mrp'                 => $mrp,
                    'cgst_amount'         => $lineCgst,
                    'sgst_amount'         => $lineSgst,
                    'igst_amount'         => $lineIgst,
                    'total_amount'        => $lineTotal,
                ];
            }

            $poData['subtotal'] = $subtotal;
            $poData['discount_amount'] = $totalDiscount;
            $poData['cgst_amount'] = $totalCgst;
            $poData['sgst_amount'] = $totalSgst;
            $poData['igst_amount'] = $totalIgst;
            $poData['tax_amount'] = $totalCgst + $totalSgst + $totalIgst;
            $poData['total_amount'] = $grandTotal;

            if (!$lockProductItems) {
                $previousStockTransactions = Database::fetchAll(
                    "SELECT variant_id, location_type, location_id, SUM(quantity) as net_quantity
                     FROM stock_transactions
                     WHERE reference_type = 'purchase_orders'
                       AND reference_id = ?
                       AND transaction_type IN ('WAREHOUSE_DIRECT_SALE', 'WAREHOUSE_DIRECT_SALE_REVERSAL')
                       AND stock_state = 'AVAILABLE'
                     GROUP BY variant_id, location_type, location_id",
                    [$id]
                );
                foreach ($previousStockTransactions as $transaction) {
                    $netQuantity = (float)$transaction['net_quantity'];
                    if ($netQuantity < 0) {
                        StockService::increase(
                            variantId: (int)$transaction['variant_id'],
                            locationType: $transaction['location_type'],
                            locationId: (int)$transaction['location_id'],
                            stockState: 'AVAILABLE',
                            quantity: abs($netQuantity),
                            transactionType: 'WAREHOUSE_DIRECT_SALE_REVERSAL',
                            referenceType: 'purchase_orders',
                            referenceId: $id,
                            userId: $userId,
                            notes: "Reconcile edited Direct Purchase #" . ($existing['po_number'] ?? $id)
                        );
                    } elseif ($netQuantity > 0) {
                        StockService::decrease(
                            variantId: (int)$transaction['variant_id'],
                            locationType: $transaction['location_type'],
                            locationId: (int)$transaction['location_id'],
                            stockState: 'AVAILABLE',
                            quantity: $netQuantity,
                            transactionType: 'WAREHOUSE_DIRECT_SALE',
                            referenceType: 'purchase_orders',
                            referenceId: $id,
                            userId: $userId,
                            notes: "Reconcile edited Direct Purchase #" . ($existing['po_number'] ?? $id)
                        );
                    }
                }
            }

            PurchaseOrder::update($id, $poData);
            if ($isDirectCustomerPurchase) {
                Database::execute(
                    "UPDATE purchase_orders SET product_edit_count = 1 WHERE id = ?",
                    [$id]
                );
            }
            if (!$lockProductItems) {
                PurchaseItem::deleteByPurchaseOrder($id);

                foreach ($processedItems as $pItem) {
                    $pItem['purchase_order_id'] = $id;
                    PurchaseItem::create($pItem);
                }
            }

            if (!$lockProductItems && self::isDirectPurchaseFulfilled($poData)) {
                self::applyDirectPurchaseStock(
                    $processedItems,
                    $id,
                    (int)($poData['warehouse_id'] ?? $existing['warehouse_id']),
                    $userId,
                    (string)($existing['po_number'] ?? $id)
                );
            }

            AuditService::log('warehouse', 'edit', 'purchase_orders', $id, $existing, $poData);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Receive Supplier Purchase Order (Supplier GRN).
     * Creates purchase_inwards, records received/damaged/accepted items, and credits stock.
     */
    public static function receivePurchaseOrder(int $poId, array $inwardData, array $receivedItems, int $userId): int {
        Database::beginTransaction();
        try {
            $po = PurchaseOrder::find($poId);
            if (!$po) {
                throw new Exception("Purchase Order #$poId not found.");
            }

            if (empty($inwardData['grn_number'])) {
                $inwardData['grn_number'] = self::generateSequenceNumber('purchase_inwards', 'grn_number', 'GRN');
            }
            $inwardData['purchase_order_id'] = $poId;
            $inwardData['warehouse_id'] = (int)$po['warehouse_id'];
            $inwardData['received_by'] = $userId;
            $inwardData['verified_by'] = $userId;
            $inwardData['received_date'] = $inwardData['received_date'] ?? date('Y-m-d');

            $inwardId = PurchaseInward::create($inwardData);

            $allAccepted = true;
            $hasAccepted = false;

            foreach ($receivedItems as $item) {
                $variantId = (int)$item['variant_id'];
                $orderedQty = (int)$item['ordered_qty'];
                $receivedQty = (int)($item['received_qty'] ?? 0);
                $damagedQty = (int)($item['damaged_qty'] ?? 0);
                $acceptedQty = max(0, $receivedQty - $damagedQty);
                $locationId = !empty($item['location_id']) ? (int)$item['location_id'] : null;

                PurchaseInwardItem::create([
                    'purchase_inward_id' => $inwardId,
                    'variant_id'         => $variantId,
                    'ordered_qty'        => $orderedQty,
                    'received_qty'       => $receivedQty,
                    'damaged_qty'        => $damagedQty,
                    'accepted_qty'       => $acceptedQty,
                    'location_id'        => $locationId,
                    'notes'              => $item['notes'] ?? null,
                ]);

                // Increase stock for accepted pieces
                if ($acceptedQty > 0) {
                    $hasAccepted = true;
                    StockService::increase(
                        variantId: $variantId,
                        locationType: 'WAREHOUSE',
                        locationId: (int)$po['warehouse_id'],
                        stockState: 'AVAILABLE',
                        quantity: (float)$acceptedQty,
                        transactionType: 'PURCHASE_GRN',
                        referenceType: 'purchase_inwards',
                        referenceId: $inwardId,
                        userId: $userId,
                        notes: "Supplier GRN #{$inwardData['grn_number']} for PO #{$po['po_number']}"
                    );
                }

                // If any damaged items, store in DAMAGED state
                if ($damagedQty > 0) {
                    StockService::increase(
                        variantId: $variantId,
                        locationType: 'WAREHOUSE',
                        locationId: (int)$po['warehouse_id'],
                        stockState: 'DAMAGED',
                        quantity: (float)$damagedQty,
                        transactionType: 'DAMAGE',
                        referenceType: 'purchase_inwards',
                        referenceId: $inwardId,
                        userId: $userId,
                        notes: "Damaged during shipment in Supplier GRN #{$inwardData['grn_number']}"
                    );
                }

                if ($acceptedQty < $orderedQty) {
                    $allAccepted = false;
                }
            }

            // Update PO Status
            $newStatus = $allAccepted ? 'RECEIVED' : ($hasAccepted ? 'PARTIAL_RECEIVED' : 'CONFIRMED');
            PurchaseOrder::updateStatus($poId, $newStatus);

            AuditService::log('warehouse', 'receive_grn', 'purchase_inwards', $inwardId, null, [
                'po_id' => $poId,
                'grn_number' => $inwardData['grn_number'],
                'status' => $newStatus
            ]);

            Database::commit();
            return $inwardId;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    // =========================================================================
    // 3. STOCK ADJUSTMENTS & PHYSICAL AUDITS
    // =========================================================================

    /**
     * Create Stock Adjustment Request.
     */
    public static function createAdjustment(array $data, int $userId): int {
        if (($data['adjustment_type'] ?? '') === 'DECREASE') {
            self::validateAdjustmentQrCodes(
                (int)$data['variant_id'],
                (int)$data['warehouse_id'],
                $data['qr_codes'] ?? [],
                (int)$data['quantity']
            );
        }

        if (empty($data['adjustment_number'])) {
            $data['adjustment_number'] = self::generateSequenceNumber('stock_adjustments', 'adjustment_number', 'ADJ');
        }
        $data['requested_by'] = $userId;
        $data['status'] = 'PENDING';

        $id = StockAdjustment::create($data);
        AuditService::log('warehouse', 'request_adjustment', 'stock_adjustments', $id, null, $data);
        return $id;
    }

    public static function validateAdjustmentQrCodes(
        int $variantId,
        int $warehouseId,
        array $tagCodes,
        int $quantity,
        int $excludeAdjustmentId = 0
    ): void {
        $tagCodes = array_values(array_unique(array_map(
            static fn($tag): string => strtoupper(trim((string)$tag)),
            $tagCodes
        )));
        if ($variantId <= 0 || $warehouseId <= 0 || empty($tagCodes)) {
            throw new InvalidArgumentException('Scan at least one product QR code before requesting a stock decrease.');
        }

        foreach ($tagCodes as $tag) {
            if ($tag === '' || !preg_match('/^[A-Z0-9_-]+$/', $tag)) {
                throw new InvalidArgumentException('The scanned QR code is invalid.');
            }
        }

        $variant = Database::fetchOne(
            "SELECT pv.qr_type, pv.pack_quantity, pv.is_complementary,
                    p.is_complementary as product_is_complementary,
                    wc.category_type
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN warehouse_categories wc ON wc.id = p.warehouse_category_id
             WHERE pv.id = ?",
            [$variantId]
        );
        if (!$variant) {
            throw new RuntimeException('The selected product variant could not be found.');
        }

        $isPack = strtoupper((string)($variant['qr_type'] ?? '')) === 'SERIES_BATCH'
            || strtoupper((string)($variant['qr_type'] ?? '')) === 'SERIES'
            || strtoupper((string)($variant['category_type'] ?? '')) === 'SERIES'
            || strtoupper((string)($variant['category_type'] ?? '')) === 'COMPLEMENTARY'
            || (int)($variant['pack_quantity'] ?? 0) > 1
            || !empty($variant['is_complementary'])
            || !empty($variant['product_is_complementary']);
        $quantityPerTag = $isPack ? max(1, (int)$variant['pack_quantity']) : 1;
        if ($quantity !== count($tagCodes) * $quantityPerTag) {
            throw new InvalidArgumentException("The scanned QR codes represent " . (count($tagCodes) * $quantityPerTag) . " pcs; the adjustment quantity does not match.");
        }

        $available = (float)(Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as quantity
             FROM stock_balances
             WHERE variant_id = ? AND location_type = 'WAREHOUSE'
               AND location_id = ? AND stock_state = 'AVAILABLE'",
            [$variantId, $warehouseId]
        )['quantity'] ?? 0);
        if ($available < $quantity) {
            throw new RuntimeException("Insufficient available stock. Available: {$available} pcs; requested: {$quantity} pcs.");
        }

        foreach ($tagCodes as $tag) {
            $used = StockAdjustment::findQrCodeUsage($tag, $excludeAdjustmentId);
            if ($used) {
                throw new RuntimeException("QR code '{$tag}' is already recorded in adjustment #{$used['adjustment_number']} ({$used['status']}).");
            }

            $allocation = Database::fetchOne(
                "SELECT wa.allocation_number, wa.status
                 FROM allocation_items ai
                 JOIN warehouse_allocations wa ON wa.id = ai.allocation_id
                 WHERE FIND_IN_SET(?, REPLACE(UPPER(COALESCE(ai.selected_qr_codes, '')), ' ', '')) > 0
                   AND wa.status IN ('REQUESTED', 'APPROVED', 'PICKED', 'PACKED', 'DISPATCHED', 'DELIVERED', 'RECEIVED')
                 LIMIT 1",
                [$tag]
            );
            if ($allocation) {
                throw new RuntimeException("QR code '{$tag}' is allocated in pass #{$allocation['allocation_number']} ({$allocation['status']}) and cannot be removed from warehouse stock.");
            }

            $damaged = Database::fetchOne(
                "SELECT damage_number
                 FROM warehouse_damages
                 WHERE variant_id = ? AND UPPER(TRIM(COALESCE(tag_code, ''))) = ?
                 LIMIT 1",
                [$variantId, $tag]
            );
            if ($damaged) {
                throw new RuntimeException("QR code '{$tag}' is already recorded as damaged (#{$damaged['damage_number']}).");
            }

            $productionDamage = Database::fetchOne(
                "SELECT pb.batch_number
                 FROM production_batch_items pbi
                 JOIN production_batches pb ON pb.id = pbi.production_batch_id
                 WHERE pbi.variant_id = ? AND pb.destination_warehouse_id = ?
                   AND pb.status IN ('RECEIVED', 'DISPATCHED')
                   AND FIND_IN_SET(?, REPLACE(UPPER(COALESCE(pbi.damaged_qr_codes, '')), ' ', '')) > 0
                 LIMIT 1",
                [$variantId, $warehouseId, $tag]
            );
            if ($productionDamage) {
                throw new RuntimeException("QR code '{$tag}' is recorded as damaged in production batch #{$productionDamage['batch_number']}.");
            }

            $sold = Database::fetchOne(
                "SELECT pi.variant_id, po.po_number, po.status
                 FROM purchase_items pi
                 JOIN purchase_orders po ON po.id = pi.purchase_order_id
                 WHERE (
                        FIND_IN_SET(?, REPLACE(UPPER(COALESCE(pi.batch_lot_no, '')), ' ', '')) > 0
                        OR UPPER(TRIM(COALESCE(pi.batch_lot_no, ''))) = ?
                        OR pi.notes LIKE ?
                   )
                   AND po.status NOT IN ('CANCELLED', 'VOID')
                 ORDER BY po.id DESC, pi.id DESC
                 LIMIT 1",
                [$tag, $tag, '%' . $tag . '%']
            );
            if ($sold) {
                $restocked = Database::fetchOne(
                    "SELECT r.id
                     FROM return_items ri
                     JOIN `returns` r ON r.id = ri.return_id
                     WHERE ri.variant_id = ? AND ri.reason LIKE ?
                       AND ri.item_condition = 'GOOD' AND ri.destination = 'AVAILABLE'
                       AND r.status NOT IN ('CANCELLED', 'REJECTED')
                     ORDER BY r.id DESC LIMIT 1",
                    [(int)$sold['variant_id'], '%' . $tag . '%']
                );
                if (!$restocked) {
                    throw new RuntimeException("QR code '{$tag}' was sold in direct purchase #{$sold['po_number']} and cannot be removed from warehouse stock.");
                }
            }

            $directTag = Database::fetchOne(
                "SELECT id FROM product_variants
                 WHERE id = ? AND (UPPER(COALESCE(qr_code, '')) = ? OR UPPER(COALESCE(series_start_code, '')) = ?)",
                [$variantId, $tag, $tag]
            );
            $tagRecorded = (bool)$directTag;
            if (!$tagRecorded) {
                $productionReceipt = Database::fetchOne(
                    "SELECT pbi.id
                     FROM production_batch_items pbi
                     JOIN production_batches pb ON pb.id = pbi.production_batch_id
                     WHERE pbi.variant_id = ? AND pb.destination_warehouse_id = ?
                       AND pb.status IN ('RECEIVED', 'DISPATCHED')
                       AND FIND_IN_SET(?, REPLACE(UPPER(COALESCE(pbi.received_qr_codes, '')), ' ', '')) > 0
                     LIMIT 1",
                    [$variantId, $warehouseId, $tag]
                );
                $tagRecorded = (bool)$productionReceipt;
            }
            if (!$tagRecorded) {
                $transactions = Database::fetchAll(
                    "SELECT notes FROM stock_transactions
                     WHERE variant_id = ? AND location_type = 'WAREHOUSE' AND location_id = ?
                       AND notes LIKE ? AND notes LIKE '%Tags:%'
                     ORDER BY id DESC LIMIT 100",
                    [$variantId, $warehouseId, '%' . $tag . '%']
                );
                foreach ($transactions as $transaction) {
                    $notes = (string)($transaction['notes'] ?? '');
                    if (preg_match('/(?<![A-Z0-9])' . preg_quote($tag, '/') . '(?![A-Z0-9])/i', $notes)) {
                        $tagRecorded = true;
                        break;
                    }
                    if (preg_match('/Tags:\s*([A-Z0-9_-]+)\s*-\s*([A-Z0-9_-]+)/i', $notes, $range)
                        && preg_match('/^(.+?)(\d+)$/', $range[1], $start)
                        && preg_match('/^(.+?)(\d+)$/', $range[2], $end)
                        && preg_match('/^(.+?)(\d+)$/', $tag, $scanned)
                        && strtoupper($start[1]) === strtoupper($end[1])
                        && strtoupper($start[1]) === strtoupper($scanned[1])
                        && strlen($start[2]) === strlen($scanned[2])
                        && (int)$scanned[2] >= (int)$start[2]
                        && (int)$scanned[2] <= (int)$end[2]) {
                        $tagRecorded = true;
                        break;
                    }
                    if (preg_match('/Tags:\s*([A-Z0-9_-]+).*?\(total\s+(\d+)\)/i', $notes, $summary)
                        && preg_match('/^(.+?)(\d+)$/', $summary[1], $start)
                        && preg_match('/^(.+?)(\d+)$/', $tag, $scanned)
                        && strtoupper($start[1]) === strtoupper($scanned[1])
                        && strlen($start[2]) === strlen($scanned[2])
                        && (int)$scanned[2] >= (int)$start[2]
                        && (int)$scanned[2] < (int)$start[2] + (int)$summary[2]) {
                        $tagRecorded = true;
                        break;
                    }
                }
            }
            if (!$tagRecorded) {
                throw new RuntimeException("QR code '{$tag}' is not recorded for this variant in the selected warehouse.");
            }
        }
    }

    /**
     * Approve or Reject Stock Adjustment.
     */
    public static function approveAdjustment(int $id, string $action, int $userId): bool {
        Database::beginTransaction();
        try {
            $adj = StockAdjustment::find($id);
            if (!$adj) {
                throw new Exception("Adjustment #$id not found.");
            }
            if ($adj['status'] !== 'PENDING') {
                throw new Exception("Adjustment is already {$adj['status']}.");
            }

            if ($action === 'APPROVE') {
                if ($adj['adjustment_type'] === 'DECREASE') {
                    $tagCodes = json_decode((string)($adj['qr_codes'] ?? '[]'), true);
                    self::validateAdjustmentQrCodes(
                        (int)$adj['variant_id'],
                        (int)$adj['warehouse_id'],
                        is_array($tagCodes) ? $tagCodes : [],
                        (int)$adj['quantity'],
                        $id
                    );
                }

                // Execute stock adjustment through StockService
                $qrNote = !empty($adj['qr_codes'])
                    ? ' Removed QR codes: ' . implode(', ', json_decode((string)$adj['qr_codes'], true) ?: [])
                    : '';
                StockService::adjust(
                    variantId: (int)$adj['variant_id'],
                    locationType: 'WAREHOUSE',
                    locationId: (int)$adj['warehouse_id'],
                    adjustmentType: $adj['adjustment_type'],
                    quantity: (float)$adj['quantity'],
                    reason: $adj['reason'],
                    userId: $userId,
                    notes: "Audit Ref: " . $adj['adjustment_number'] . $qrNote
                );

                StockAdjustment::updateStatus($id, 'APPROVED', $userId);
                AuditService::log('warehouse', 'approve_adjustment', 'stock_adjustments', $id, ['status' => 'PENDING'], ['status' => 'APPROVED']);
            } else {
                StockAdjustment::updateStatus($id, 'REJECTED', $userId);
                AuditService::log('warehouse', 'reject_adjustment', 'stock_adjustments', $id, ['status' => 'PENDING'], ['status' => 'REJECTED']);
            }

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    // =========================================================================
    // 4. WAREHOUSE DASHBOARD & KPIS
    // =========================================================================

    /**
     * Get Complete Warehouse Dashboard Metrics.
     */
    public static function getDashboardStats(?int $warehouseId = null): array {
        $whLocationClause = $warehouseId ? " AND location_id = $warehouseId" : "";
        $whDestClause = $warehouseId ? " AND pb.destination_warehouse_id = $warehouseId" : "";
        $whTxnClause = $warehouseId ? " AND st.location_id = $warehouseId" : "";
        $whPoClause = $warehouseId ? " AND warehouse_id = $warehouseId" : "";
        $whAdjClause = $warehouseId ? " AND warehouse_id = $warehouseId" : "";

        // Total Stock Quantity across all warehouses (or scoped warehouse)
        $totalStock = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as total_qty,
                    COUNT(DISTINCT variant_id) as total_skus
             FROM stock_balances
             WHERE location_type = 'WAREHOUSE' AND stock_state = 'AVAILABLE'$whLocationClause"
        );

        // Reserved Stock Quantity
        $reservedStock = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as total_qty
             FROM stock_balances
             WHERE location_type = 'WAREHOUSE' AND stock_state = 'RESERVED'$whLocationClause"
        );

        // Damaged Stock Quantity
        $damagedStock = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as total_qty
             FROM stock_balances
             WHERE location_type = 'WAREHOUSE' AND stock_state = 'DAMAGED'$whLocationClause"
        );

        // Stock Valuation in INR
        $stockValuation = Database::fetchOne(
            "SELECT COALESCE(SUM(sb.quantity * pv.selling_price), 0) as total_value
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             WHERE sb.location_type = 'WAREHOUSE' AND sb.stock_state = 'AVAILABLE'" . ($warehouseId ? " AND sb.location_id = $warehouseId" : "")
        );

        // Pending Inward Batches from Production (Status = DISPATCHED)
        $pendingInwards = Database::fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(pb.total_qty), 0) as total_qty
             FROM production_batches pb
             WHERE pb.status = 'DISPATCHED'$whDestClause"
        );

        // Pending Purchase Orders awaiting GRN
        $pendingPurchases = Database::fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total_amount
             FROM purchase_orders
             WHERE status IN ('CONFIRMED', 'PARTIAL_RECEIVED')$whPoClause"
        );

        // Pending Stock Adjustments
        $pendingAdjustments = Database::fetchOne(
            "SELECT COUNT(*) as count FROM stock_adjustments WHERE status = 'PENDING'$whAdjClause"
        );

        // Recent Dispatched Batches awaiting receipt
        $recentInwardQueue = Database::fetchAll(
            "SELECT pb.*, pp.program_number, COALESCE(p.name, pp.name, 'General Apparel') as product_name, w.name as warehouse_name,
                    po.outward_number, po.dispatch_date, po.vehicle_number
             FROM production_batches pb
             JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN production_outwards po ON po.production_batch_id = pb.id
             WHERE pb.status = 'DISPATCHED'$whDestClause
             ORDER BY po.dispatch_date DESC LIMIT 5"
        );

        // Recent Transactions Ledger
        $recentTransactions = Database::fetchAll(
            "SELECT st.*, pv.sku, p.name as product_name, w.name as warehouse_name, u.full_name as user_name
             FROM stock_transactions st
             JOIN product_variants pv ON st.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN warehouses w ON st.location_type = 'WAREHOUSE' AND st.location_id = w.id
             LEFT JOIN users u ON st.user_id = u.id
             WHERE st.location_type = 'WAREHOUSE'$whTxnClause
             ORDER BY st.created_at DESC LIMIT 6"
        );

        // Third-party Sellers & Procurement Metrics
        $sellerStats = [
            'total_sellers'      => (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM sellers WHERE is_active = 1")['cnt'] ?? 0),
            'total_orders'       => (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM purchase_orders WHERE seller_id IS NOT NULL")['cnt'] ?? 0),
            'total_spend'        => (float)(Database::fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM purchase_orders WHERE seller_id IS NOT NULL")['total'] ?? 0),
            'total_inward_units' => (int)(Database::fetchOne("SELECT COALESCE(SUM(pi.quantity), 0) as total FROM purchase_items pi JOIN purchase_orders po ON pi.purchase_order_id = po.id WHERE po.seller_id IS NOT NULL")['total'] ?? 0),
            'paid_spend'         => (float)(Database::fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM purchase_orders WHERE seller_id IS NOT NULL AND payment_status = 'PAID'")['total'] ?? 0),
            'pending_spend'      => (float)(Database::fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM purchase_orders WHERE seller_id IS NOT NULL AND payment_status != 'PAID'")['total'] ?? 0),
        ];

        // Complementary Products Stats (Stock & Sales Revenue)
        $whLocationClause = $warehouseId ? " AND sb.location_id = $warehouseId" : "";
        $compStock = Database::fetchOne(
            "SELECT COALESCE(SUM(sb.quantity), 0) as total_qty,
                    COUNT(DISTINCT sb.variant_id) as total_skus,
                    COALESCE(SUM(sb.quantity * COALESCE(NULLIF(pv.selling_price, 0), pv.mrp, 0)), 0) as total_valuation
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             WHERE sb.location_type = 'WAREHOUSE' AND sb.stock_state = 'AVAILABLE'
               AND (p.is_complementary = 1 OR pv.is_complementary = 1)$whLocationClause"
        );

        $compSales = Database::fetchOne(
            "SELECT COALESCE(SUM(si.quantity), 0) as total_sold_qty,
                    COALESCE(SUM(si.total_amount), 0) as total_sold_revenue,
                    COUNT(DISTINCT si.sale_id) as total_orders
             FROM sale_items si
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             WHERE (p.is_complementary = 1 OR pv.is_complementary = 1)"
        );

        return [
            'totalStock'         => $totalStock,
            'reservedStock'      => $reservedStock,
            'damagedStock'       => $damagedStock,
            'stockValuation'     => $stockValuation,
            'pendingInwards'     => $pendingInwards,
            'pendingPurchases'   => $pendingPurchases,
            'pendingAdjustments' => $pendingAdjustments,
            'recentInwardQueue'  => $recentInwardQueue,
            'recentTransactions' => $recentTransactions,
            'sellerStats'        => $sellerStats,
            'compStock'          => $compStock,
            'compSales'          => $compSales,
        ];
    }

    // =========================================================================
    // 7. WAREHOUSE DIRECT PURCHASES — CUSTOMER RETURNS & EXCHANGES ENGINE
    // =========================================================================

    /**
     * Look up Direct Purchase Order for Customer Returns / Exchanges.
     */
    public static function lookupPurchaseForReturn(string $query, int $warehouseId = 1): ?array {
        $query = trim($query);
        if (empty($query)) return null;

        if (str_starts_with($query, '{') && str_ends_with($query, '}')) {
            $json = json_decode($query, true);
            if (!empty($json['po_number'])) {
                $query = $json['po_number'];
            } elseif (!empty($json['invoice_number'])) {
                $query = $json['invoice_number'];
            }
        }

        $po = Database::fetchOne(
            "SELECT po.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                    c.address as customer_address, c.city as customer_city, c.state as customer_state,
                    w.name as warehouse_name, w.code as warehouse_code
             FROM purchase_orders po
             LEFT JOIN customers c ON po.customer_id = c.id
             LEFT JOIN warehouses w ON po.warehouse_id = w.id
             WHERE (po.po_number = ? OR c.phone = ? OR po.id = ?)
             ORDER BY po.id DESC LIMIT 1",
            [$query, $query, is_numeric($query) ? (int)$query : 0]
        );

        if (!$po) return null;

        // Validation: If this invoice/PO has already been scanned and processed for return/exchange, block scanning it again
        $existingReturn = Database::fetchOne(
            "SELECT return_number, created_at FROM returns WHERE purchase_order_id = ? ORDER BY id DESC LIMIT 1",
            [$po['id']]
        );
        if ($existingReturn) {
            throw new \InvalidArgumentException("This Invoice/PO ({$po['po_number']}) has already been processed for Return/Exchange ({$existingReturn['return_number']}). This invoice cannot be scanned again.");
        }

        $targetWarehouseId = (int)($po['warehouse_id'] ?? $warehouseId);

        $items = Database::fetchAll(
            "SELECT pi.*, pv.sku, pv.qr_code, pv.product_id, pv.size_id, pv.color_id, 
                    pv.selling_price as variant_selling_price, pv.mrp as variant_mrp,
                    p.name as product_name, p.code as product_code, 
                    sz.name as size_name, cl.name as color_name,
                    COALESCE((SELECT SUM(ri.quantity) 
                              FROM return_items ri 
                              JOIN returns r ON ri.return_id = r.id 
                              WHERE r.purchase_order_id = pi.purchase_order_id 
                                AND ri.variant_id = pi.variant_id), 0) as already_returned_qty
             FROM purchase_items pi
             JOIN product_variants pv ON pi.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             WHERE pi.purchase_order_id = ?",
            [$po['id']]
        );

        foreach ($items as &$item) {
            $item['returnable_qty'] = max(0, (int)$item['quantity'] - (int)$item['already_returned_qty']);

            // Effective paid unit price (tax-inclusive matching total paid on invoice)
            if ((int)$item['quantity'] > 0 && (float)($item['total_amount'] ?? 0) > 0) {
                $item['unit_price'] = round((float)$item['total_amount'] / (int)$item['quantity'], 2);
            }

            // Sibling variants (other sizes/colors of the same product) in warehouse available stock
            $siblings = Database::fetchAll(
                "SELECT pv.id, pv.sku, pv.qr_code, pv.selling_price, pv.mrp,
                        sz.name as size_name, cl.name as color_name,
                        COALESCE((SELECT SUM(quantity) FROM stock_balances sb 
                                  WHERE sb.variant_id = pv.id 
                                    AND sb.location_type = 'WAREHOUSE' 
                                    AND sb.location_id = ? 
                                    AND sb.stock_state = 'AVAILABLE'), 0) as warehouse_stock
                 FROM product_variants pv
                 LEFT JOIN sizes sz ON pv.size_id = sz.id
                 LEFT JOIN colors cl ON pv.color_id = cl.id
                 WHERE pv.product_id = ? AND pv.is_active = 1
                 ORDER BY sz.sort_order ASC, sz.name ASC, cl.name ASC",
                [$targetWarehouseId, $item['product_id']]
            );
            $item['sibling_variants'] = $siblings;
        }

        $totalReturnable = 0;
        foreach ($items as $item) {
            $totalReturnable += $item['returnable_qty'];
        }

        if ($totalReturnable <= 0) {
            throw new \InvalidArgumentException("This purchase order (PO-SUPP-{$po['id']}) has already been fully returned or exchanged. No items left to return.");
        }

        $invoiceCount = null;
        if (preg_match('/^\d{10,15}$/', $query)) {
            $invoiceCount = (int)(Database::fetchOne(
                "SELECT COUNT(*) AS invoice_count
                 FROM purchase_orders po
                 JOIN customers c ON c.id = po.customer_id
                 WHERE c.phone = ?
                   AND po.seller_id IS NULL
                   AND po.warehouse_supplier_id IS NULL",
                [$query]
            )['invoice_count'] ?? 0);
        }

        return [
            'purchase' => $po,
            'items'    => $items,
            'invoice_count' => $invoiceCount,
        ];
    }

    /**
     * Process Customer Return / Exchange for Warehouse Direct Purchase.
     */
    public static function processPurchaseReturn(array $payload, int $userId): array {
        $poId = (int)($payload['purchase_order_id'] ?? $payload['po_id'] ?? 0);
        $warehouseId = (int)($payload['warehouse_id'] ?? 1);
        $items = $payload['items'] ?? [];
        $actionType = $payload['action_type'] ?? 'RETURN'; // 'RETURN', 'EXCHANGE_SAME', 'EXCHANGE_DIFFERENT', 'CREDIT_NOTE'
        $reason = $payload['reason'] ?? 'Customer Return / Exchange';
        $refundMode = $payload['refund_mode'] ?? $payload['settlement_mode'] ?? 'CASH';
        $settlementMode = $payload['settlement_mode'] ?? $refundMode ?? 'CASH';

        if ($actionType === 'CREDIT_NOTE') {
            $refundMode = 'CREDIT_NOTE';
            $settlementMode = 'CREDIT_NOTE';
        }

        $returnType = in_array($actionType, ['EXCHANGE_SAME', 'EXCHANGE_DIFFERENT']) ? 'EXCHANGE' : 'RETURN';

        if (empty($items)) {
            throw new InvalidArgumentException("Please select items to return or exchange.");
        }

        $po = Database::fetchOne(
            "SELECT po.*, c.name as customer_name, c.phone as customer_phone 
             FROM purchase_orders po 
             LEFT JOIN customers c ON po.customer_id = c.id 
             WHERE po.id = ?",
            [$poId]
        );

        if (!$po) {
            throw new InvalidArgumentException("Direct purchase order #$poId not found.");
        }

        $existingReturn = Database::fetchOne(
            "SELECT return_number FROM returns WHERE purchase_order_id = ? ORDER BY id DESC LIMIT 1",
            [$poId]
        );
        if ($existingReturn) {
            throw new InvalidArgumentException("This Invoice/PO ({$po['po_number']}) has already been processed for return/exchange ({$existingReturn['return_number']}).");
        }

        $targetWarehouseId = (int)($po['warehouse_id'] ?: $warehouseId);

        Database::beginTransaction();
        try {
            $today = date('Ymd');
            $prefix = ($returnType === 'EXCHANGE' ? 'EXC-WH-' : 'RET-WH-') . $today . '-';
            $lastRet = Database::fetchOne(
                "SELECT return_number FROM returns WHERE return_number LIKE ? ORDER BY id DESC LIMIT 1",
                [$prefix . '%']
            );
            $seq = 1;
            if ($lastRet && !empty($lastRet['return_number'])) {
                $parts = explode('-', $lastRet['return_number']);
                $seq = (int)end($parts) + 1;
            }
            $returnNumber = sprintf('%s%04d', $prefix, $seq);

            $totalRefund = 0.0;
            $processedItems = [];

            foreach ($items as $it) {
                if (isset($it['selected']) && empty($it['selected'])) {
                    continue;
                }

                $qty = (int)($it['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $purchaseItemId = !empty($it['purchase_item_id']) ? (int)$it['purchase_item_id'] : null;
                $variantId = (int)($it['variant_id'] ?? 0);

                // Determine accurate unit price (tax-inclusive effective price)
                $unitPrice = 0.0;
                if ($purchaseItemId) {
                    $pi = Database::fetchOne("SELECT unit_price, total_amount, quantity FROM purchase_items WHERE id = ? AND purchase_order_id = ?", [$purchaseItemId, $poId]);
                    if ($pi) {
                        if ((int)$pi['quantity'] > 0 && (float)($pi['total_amount'] ?? 0) > 0) {
                            $unitPrice = round((float)$pi['total_amount'] / (int)$pi['quantity'], 2);
                        } else {
                            $unitPrice = (float)$pi['unit_price'];
                        }
                    }
                }
                if ($unitPrice <= 0 && !empty($it['unit_price'])) {
                    $unitPrice = (float)$it['unit_price'];
                }
                if ($unitPrice <= 0 && $variantId > 0) {
                    $pv = Database::fetchOne("SELECT selling_price FROM product_variants WHERE id = ?", [$variantId]);
                    $unitPrice = (float)($pv['selling_price'] ?? 0);
                }

                $itemRefund = $unitPrice * $qty;
                $totalRefund += $itemRefund;

                $replacementVariantId = !empty($it['replacement_variant_id']) ? (int)$it['replacement_variant_id'] : null;
                $replacementPieceTag = trim((string)($it['replacement_piece_tag'] ?? ''));
                $pieceTags = !empty($it['piece_tags']) && is_array($it['piece_tags']) ? implode(', ', array_filter($it['piece_tags'])) : (is_string($it['piece_tags'] ?? null) ? trim($it['piece_tags']) : '');

                $processedItems[] = [
                    'purchase_item_id'       => $purchaseItemId,
                    'variant_id'             => $variantId,
                    'quantity'               => $qty,
                    'condition'              => $it['condition'] ?? 'GOOD',
                    'unit_price'             => $unitPrice,
                    'refund_amount'          => $itemRefund,
                    'replacement_variant_id' => $replacementVariantId,
                    'replacement_piece_tag'  => $replacementPieceTag,
                    'piece_tags'             => $pieceTags,
                ];
            }

            if (empty($processedItems)) {
                throw new InvalidArgumentException("Please select at least one valid item to process.");
            }

            $notes = "Warehouse Direct Action: " . $actionType;
            if ($actionType === 'EXCHANGE_SAME') {
                $notes .= " (Same-product size/color exchange)";
            } elseif ($actionType === 'EXCHANGE_DIFFERENT') {
                $notes .= " (Different-product catalog exchange)";
            }

            Database::execute(
                "INSERT INTO returns (return_number, source_type, purchase_order_id, customer_id, warehouse_id, 
                                     return_type, reason, inspection_status, refund_amount, total_amount, refund_mode, 
                                     refund_status, status, inspected_by, approved_by, notes, created_at)
                 VALUES (?, 'WAREHOUSE_PURCHASE', ?, ?, ?, ?, ?, 'APPROVED', ?, ?, ?, 'COMPLETED', 'APPROVED', ?, ?, ?, NOW())",
                [
                    $returnNumber,
                    $poId,
                    $po['customer_id'] ?? null,
                    $targetWarehouseId,
                    $returnType,
                    $reason,
                    $totalRefund,
                    $totalRefund,
                    $refundMode,
                    $userId,
                    $userId,
                    $notes,
                ]
            );
            $returnId = (int)Database::lastInsertId();

            $totalNewExchangeAmount = 0.0;
            $hasSameProductExchange = false;

            foreach ($processedItems as $it) {
                $variantId = (int)$it['variant_id'];
                $qty = (int)$it['quantity'];
                $condition = $it['condition'] ?? 'GOOD';
                $itemRefund = (float)$it['refund_amount'];
                $pieceTagReason = !empty($it['piece_tags']) ? 'Piece Tags: ' . $it['piece_tags'] : null;

                Database::execute(
                    "INSERT INTO return_items (return_id, variant_id, quantity, item_condition, destination, refund_amount, reason)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$returnId, $variantId, $qty, $condition, $condition === 'GOOD' ? 'AVAILABLE' : 'DAMAGE', $itemRefund, $pieceTagReason]
                );

                // Restock returned item
                if ($condition === 'GOOD') {
                    StockService::increase(
                        $variantId,
                        'WAREHOUSE',
                        $targetWarehouseId,
                        'AVAILABLE',
                        (float)$qty,
                        'RETURN_GOOD',
                        'returns',
                        $returnId,
                        $userId,
                        "Direct Purchase Return Restock: $returnNumber"
                    );
                } else {
                    // DAMAGED pool
                    StockService::increase(
                        $variantId,
                        'WAREHOUSE',
                        $targetWarehouseId,
                        'DAMAGED',
                        (float)$qty,
                        'RETURN_DAMAGED',
                        'returns',
                        $returnId,
                        $userId,
                        "Direct Purchase Damaged Return: $returnNumber"
                    );

                    // Log in warehouse_damages table
                    try {
                        Database::execute(
                            "INSERT INTO warehouse_damages (damage_number, warehouse_id, variant_id, source_type, reference_id,
                                                           quantity, cost_per_unit, total_loss, damage_reason, defect_notes, status, reported_by, created_at)
                             VALUES (?, ?, ?, 'CUSTOMER_RETURN', ?, ?, ?, ?, ?, ?, 'LOGGED', ?, NOW())",
                            [
                                'DAM-WH-' . date('Ymd') . '-' . rand(1000, 9999),
                                $targetWarehouseId,
                                $variantId,
                                $returnId,
                                $qty,
                                $it['unit_price'],
                                $it['refund_amount'],
                                "Customer Return Defect: $returnNumber",
                                "Condition: {$condition}",
                                $userId
                            ]
                        );
                    } catch (Throwable $e) {}
                }

                // If Same-Product Exchange: deduct replacement variant stock
                if ($actionType === 'EXCHANGE_SAME' && !empty($it['replacement_variant_id'])) {
                    $hasSameProductExchange = true;
                    $repVarId = (int)$it['replacement_variant_id'];
                    $repVar = Database::fetchOne("SELECT pv.*, p.name as product_name, sz.name as size_name, cl.name as color_name 
                                                 FROM product_variants pv 
                                                 JOIN products p ON pv.product_id = p.id 
                                                 LEFT JOIN sizes sz ON pv.size_id = sz.id 
                                                 LEFT JOIN colors cl ON pv.color_id = cl.id 
                                                 WHERE pv.id = ?", [$repVarId]);
                    $availStock = (int)(Database::fetchOne("SELECT COALESCE(SUM(quantity), 0) as stock FROM stock_balances WHERE variant_id = ? AND location_type = 'WAREHOUSE' AND location_id = ? AND stock_state = 'AVAILABLE'", [$repVarId, $targetWarehouseId])['stock'] ?? 0);
                    if ($availStock < $qty) {
                        $pName = $repVar['product_name'] ?? 'Product';
                        throw new InvalidArgumentException("Insufficient stock in warehouse for replacement item '{$pName}'. Available: {$availStock} pcs, Requested: {$qty} pcs.");
                    }

                    $repPrice = (float)($repVar['selling_price'] ?? $it['unit_price']);
                    $repTotal = $repPrice * $qty;
                    $totalNewExchangeAmount += $repTotal;

                    StockService::decrease(
                        $repVarId,
                        'WAREHOUSE',
                        $targetWarehouseId,
                        'AVAILABLE',
                        (float)$qty,
                        'EXCHANGE_OUT',
                        'returns',
                        $returnId,
                        $userId,
                        "Direct Purchase Exchange Replacement for: $returnNumber"
                    );
                }
            }

            // If Different Product Exchange with in-place selected new items:
            $newItems = $payload['new_items'] ?? [];
            $hasDifferentProductExchange = false;

            if ($actionType === 'EXCHANGE_DIFFERENT' && !empty($newItems)) {
                foreach ($newItems as $nItem) {
                    $nVarId = (int)($nItem['variant_id'] ?? 0);
                    $nQty = (int)($nItem['quantity'] ?? 0);
                    if ($nVarId <= 0 || $nQty <= 0) continue;

                    $nVar = Database::fetchOne("SELECT pv.*, p.name as product_name, sz.name as size_name, cl.name as color_name 
                                               FROM product_variants pv 
                                               JOIN products p ON pv.product_id = p.id 
                                               LEFT JOIN sizes sz ON pv.size_id = sz.id 
                                               LEFT JOIN colors cl ON pv.color_id = cl.id 
                                               WHERE pv.id = ?", [$nVarId]);
                    $availStock = (int)(Database::fetchOne("SELECT COALESCE(SUM(quantity), 0) as stock FROM stock_balances WHERE variant_id = ? AND location_type = 'WAREHOUSE' AND location_id = ? AND stock_state = 'AVAILABLE'", [$nVarId, $targetWarehouseId])['stock'] ?? 0);
                    if ($availStock < $nQty) {
                        $pName = $nVar['product_name'] ?? 'Product';
                        throw new InvalidArgumentException("Insufficient warehouse stock for alternate item '{$pName}'. Available: {$availStock} pcs, Requested: {$nQty} pcs.");
                    }

                    $hasDifferentProductExchange = true;
                    $nPrice = (float)($nVar['selling_price'] ?? 0);
                    $nLineTotal = $nPrice * $nQty;
                    $totalNewExchangeAmount += $nLineTotal;

                    StockService::decrease(
                        $nVarId,
                        'WAREHOUSE',
                        $targetWarehouseId,
                        'AVAILABLE',
                        (float)$nQty,
                        'EXCHANGE_OUT',
                        'returns',
                        $returnId,
                        $userId,
                        "Direct Purchase Product Exchange Replacement for: $returnNumber"
                    );
                }
            }

            // If Exchange with replacement items: record in `exchanges` and `exchange_items`
            if (($actionType === 'EXCHANGE_SAME' && $hasSameProductExchange) || ($actionType === 'EXCHANGE_DIFFERENT' && $hasDifferentProductExchange)) {
                $diffAmount = $totalNewExchangeAmount - $totalRefund;
                $paymentAction = 'NONE';
                if ($diffAmount > 0.01) {
                    $paymentAction = 'COLLECT';
                } elseif ($diffAmount < -0.01) {
                    $paymentAction = 'REFUND';
                }

                $exchangeNumber = 'EXC-WH-' . date('Ymd') . '-' . sprintf('%04d', rand(1000, 9999));

                Database::execute(
                    "INSERT INTO exchanges (exchange_number, return_id, source_type, purchase_order_id, original_amount, 
                                           new_amount, difference_amount, payment_action, settlement_mode, status, created_at)
                     VALUES (?, ?, 'WAREHOUSE_PURCHASE', ?, ?, ?, ?, ?, ?, 'COMPLETED', NOW())",
                    [
                        $exchangeNumber,
                        $returnId,
                        $poId,
                        $totalRefund,
                        $totalNewExchangeAmount,
                        abs($diffAmount),
                        $paymentAction,
                        $settlementMode
                    ]
                );
                $exchangeId = (int)Database::lastInsertId();

                // Record OLD returned variants
                foreach ($processedItems as $it) {
                    Database::execute(
                        "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount)
                         VALUES (?, 'OLD', ?, ?, ?, ?)",
                        [$exchangeId, $it['variant_id'], $it['quantity'], $it['unit_price'], $it['refund_amount']]
                    );
                }

                // Record NEW variants for Same Product
                if ($actionType === 'EXCHANGE_SAME') {
                    foreach ($processedItems as $it) {
                        $repVarId = !empty($it['replacement_variant_id']) ? (int)$it['replacement_variant_id'] : null;
                        if ($repVarId) {
                            $repVar = Database::fetchOne("SELECT selling_price FROM product_variants WHERE id = ?", [$repVarId]);
                            $repPrice = (float)($repVar['selling_price'] ?? $it['unit_price']);
                            Database::execute(
                                "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount, piece_tag)
                                 VALUES (?, 'NEW', ?, ?, ?, ?, ?)",
                                [$exchangeId, $repVarId, $it['quantity'], $repPrice, $repPrice * $it['quantity'], $it['replacement_piece_tag'] ?: null]
                            );
                        }
                    }
                }

                // Record NEW variants for Different Product
                if ($actionType === 'EXCHANGE_DIFFERENT' && !empty($newItems)) {
                    foreach ($newItems as $nItem) {
                        $nVarId = (int)($nItem['variant_id'] ?? 0);
                        $nQty = (int)($nItem['quantity'] ?? 0);
                        if ($nVarId <= 0 || $nQty <= 0) continue;
                        $nVar = Database::fetchOne("SELECT selling_price, sku FROM product_variants WHERE id = ?", [$nVarId]);
                        $nPrice = (float)($nVar['selling_price'] ?? 0);
                        $rawPieceTags = is_array($nItem['scanned_tags'] ?? null)
                            ? $nItem['scanned_tags']
                            : (is_string($nItem['piece_tag'] ?? null) ? [$nItem['piece_tag']] : (is_string($nItem['qr_code'] ?? null) ? explode(',', $nItem['qr_code']) : []));
                        $sku = strtoupper(trim((string)($nVar['sku'] ?? '')));
                        $pieceTags = array_values(array_unique(array_filter(array_map(static fn($tag) => trim((string)$tag), $rawPieceTags), static function ($tag) use ($sku) {
                            $normalizedTag = strtoupper($tag);
                            return $normalizedTag !== ''
                                && $normalizedTag !== $sku
                                && !str_starts_with($normalizedTag, 'QR-');
                        })));
                        $pieceTag = $pieceTags ? implode(', ', $pieceTags) : null;
                        Database::execute(
                            "INSERT INTO exchange_items (exchange_id, item_type, variant_id, quantity, unit_price, total_amount, piece_tag)
                             VALUES (?, 'NEW', ?, ?, ?, ?, ?)",
                            [$exchangeId, $nVarId, $nQty, $nPrice, $nPrice * $nQty, $pieceTag]
                        );
                    }
                }
            }

            AuditService::log('warehouse_purchases', 'PROCESS_RETURN', 'returns', $returnId, null, [
                'return_number' => $returnNumber,
                'po_id'         => $poId,
                'action_type'   => $actionType,
                'total_refund'  => $totalRefund,
                'total_new'     => $totalNewExchangeAmount,
            ], $userId);

            Database::commit();

            return [
                'success'       => true,
                'return_id'     => $returnId,
                'return_number' => $returnNumber,
                'return_type'   => $returnType,
                'refund_amount' => $totalRefund,
                'new_amount'    => $totalNewExchangeAmount,
                'diff_amount'   => $totalNewExchangeAmount - $totalRefund,
            ];
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Get Returns & Exchanges KPI Summary for Warehouse Direct Purchases.
     */
    public static function getPurchaseReturnsSummary(int $warehouseId = 0): array {
        $whClause = $warehouseId ? " AND r.warehouse_id = $warehouseId" : "";

        $totalTxn = (int)(Database::fetchOne(
            "SELECT COUNT(*) as count FROM returns r WHERE r.source_type = 'WAREHOUSE_PURCHASE'$whClause"
        )['count'] ?? 0);

        $todayTxn = (int)(Database::fetchOne(
            "SELECT COUNT(*) as count FROM returns r WHERE r.source_type = 'WAREHOUSE_PURCHASE' AND DATE(r.created_at) = CURDATE()$whClause"
        )['count'] ?? 0);

        $totalRefund = (float)(Database::fetchOne(
            "SELECT COALESCE(SUM(r.refund_amount), 0) as total FROM returns r WHERE r.source_type = 'WAREHOUSE_PURCHASE' AND r.return_type = 'RETURN'$whClause"
        )['total'] ?? 0);

        $returnsCount = (int)(Database::fetchOne(
            "SELECT COUNT(*) as count FROM returns r WHERE r.source_type = 'WAREHOUSE_PURCHASE' AND r.return_type = 'RETURN'$whClause"
        )['count'] ?? 0);

        $exchangesCount = (int)(Database::fetchOne(
            "SELECT COUNT(*) as count FROM returns r WHERE r.source_type = 'WAREHOUSE_PURCHASE' AND r.return_type = 'EXCHANGE'$whClause"
        )['count'] ?? 0);

        $totalItemsReturned = (int)(Database::fetchOne(
            "SELECT COALESCE(SUM(ri.quantity), 0) as total 
             FROM return_items ri 
             JOIN returns r ON ri.return_id = r.id 
             WHERE r.source_type = 'WAREHOUSE_PURCHASE'$whClause"
        )['total'] ?? 0);

        $totalDamagedPcs = (int)(Database::fetchOne(
            "SELECT COALESCE(SUM(ri.quantity), 0) as total 
             FROM return_items ri 
             JOIN returns r ON ri.return_id = r.id 
             WHERE r.source_type = 'WAREHOUSE_PURCHASE' AND ri.item_condition = 'DAMAGED'$whClause"
        )['total'] ?? 0);

        return [
            'total_transactions'   => $totalTxn,
            'today_transactions'   => $todayTxn,
            'total_refund_amount'  => $totalRefund,
            'total_returns_count'  => $returnsCount,
            'total_exchanges_count'=> $exchangesCount,
            'total_items_returned' => $totalItemsReturned,
            'total_damaged_pcs'    => $totalDamagedPcs,
        ];
    }

    /**
     * Get list of Warehouse Direct Purchase Returns & Exchanges.
     */
    public static function getPurchaseReturnsList(array $filters = [], int $warehouseId = 0): array {
        $where = ["r.source_type = 'WAREHOUSE_PURCHASE'"];
        $params = [];

        if ($warehouseId > 0) {
            $where[] = "r.warehouse_id = ?";
            $params[] = $warehouseId;
        }

        $search = trim($filters['search'] ?? '');
        if (!empty($search)) {
            $where[] = "(r.return_number LIKE ? OR po.po_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR r.notes LIKE ?)";
            $sWild = "%{$search}%";
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
            $params[] = $sWild;
        }

        $tab = $filters['tab'] ?? 'all';
        if ($tab === 'returns') {
            $where[] = "r.return_type = 'RETURN'";
        } elseif ($tab === 'exchanges') {
            $where[] = "r.return_type = 'EXCHANGE'";
        }

        $dateFrom = trim($filters['date_from'] ?? '');
        if (!empty($dateFrom)) {
            $where[] = "DATE(r.created_at) >= ?";
            $params[] = $dateFrom;
        }

        $dateTo = trim($filters['date_to'] ?? '');
        if (!empty($dateTo)) {
            $where[] = "DATE(r.created_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = implode(' AND ', $where);

        return Database::fetchAll(
            "SELECT r.*, po.po_number, po.order_date, po.total_amount as original_po_amount,
                    COALESCE(c.name, 'Direct Customer') as customer_name,
                    c.phone as customer_phone, c.city as customer_city,
                    u.full_name as inspected_by_name,
                    w.name as warehouse_name,
                    (SELECT COALESCE(SUM(quantity), 0) FROM return_items WHERE return_id = r.id) as total_items_count,
                    (SELECT COUNT(DISTINCT variant_id) FROM return_items WHERE return_id = r.id) as distinct_variants_count,
                    e.id as exchange_id, e.new_amount, e.difference_amount, e.payment_action, e.settlement_mode
             FROM returns r
             LEFT JOIN purchase_orders po ON r.purchase_order_id = po.id
             LEFT JOIN customers c ON r.customer_id = c.id
             LEFT JOIN warehouses w ON r.warehouse_id = w.id
             LEFT JOIN users u ON r.inspected_by = u.id
             LEFT JOIN exchanges e ON e.return_id = r.id
             WHERE {$whereClause}
             ORDER BY r.created_at DESC",
            $params
        );
    }

    /**
     * Get Complete Details for a Single Direct Purchase Return / Exchange.
     */
    public static function getPurchaseReturnDetails(int $id): ?array {
        $return = Database::fetchOne(
            "SELECT r.*, po.po_number, po.order_date, po.total_amount as original_po_amount,
                    po.payment_method as po_payment_method, po.payment_status as po_payment_status,
                    c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                    c.address as customer_address, c.city as customer_city, c.state as customer_state,
                    w.name as warehouse_name, w.code as warehouse_code, w.address as warehouse_address, w.phone as warehouse_phone,
                    u.full_name as inspected_by_name
             FROM returns r
             LEFT JOIN purchase_orders po ON r.purchase_order_id = po.id
             LEFT JOIN customers c ON r.customer_id = c.id
             LEFT JOIN warehouses w ON r.warehouse_id = w.id
             LEFT JOIN users u ON r.inspected_by = u.id
             WHERE r.id = ? AND r.source_type = 'WAREHOUSE_PURCHASE'",
            [$id]
        );

        if (!$return) return null;

        $returnItems = Database::fetchAll(
            "SELECT ri.*, 
                    COALESCE(NULLIF(ri.refund_amount / NULLIF(ri.quantity, 0), 0), pv.selling_price, 0) as unit_price,
                    pv.sku, COALESCE(NULLIF(pv.qr_code, ''), CONCAT('QR-', pv.sku)) as qr_code, pv.selling_price as current_selling_price,
                    p.name as product_name, p.code as product_code,
                    sz.name as size_name, cl.name as color_name
             FROM return_items ri
             JOIN product_variants pv ON ri.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors cl ON pv.color_id = cl.id
             WHERE ri.return_id = ?",
            [$id]
        );

        $exchange = Database::fetchOne(
            "SELECT e.*, 
                    (SELECT COUNT(*) FROM exchange_items WHERE exchange_id = e.id) as item_count
             FROM exchanges e 
             WHERE e.return_id = ?",
            [$id]
        );

        $exchangeItems = [];
        if ($exchange) {
            $exchangeItems = Database::fetchAll(
                "SELECT ei.*, pv.sku, COALESCE(NULLIF(pv.qr_code, ''), CONCAT('QR-', pv.sku)) as qr_code, pv.selling_price,
                        p.name as product_name, p.code as product_code,
                        sz.name as size_name, cl.name as color_name
                 FROM exchange_items ei
                 JOIN product_variants pv ON ei.variant_id = pv.id
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN sizes sz ON pv.size_id = sz.id
                 LEFT JOIN colors cl ON pv.color_id = cl.id
                 WHERE ei.exchange_id = ?
                 ORDER BY ei.item_type ASC, ei.id ASC",
                [$exchange['id']]
            );
        }

        return [
            'return'        => $return,
            'return_items'  => $returnItems,
            'exchange'      => $exchange,
            'exchange_items'=> $exchangeItems,
        ];
    }
}
