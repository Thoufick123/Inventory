<?php

require_once BASE_PATH . '/app/Models/ProductionBatch.php';
require_once BASE_PATH . '/app/Models/ProductionBatchItem.php';
require_once BASE_PATH . '/app/Models/ProductionOutward.php';
require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';
require_once BASE_PATH . '/app/Services/ProductionBillingService.php';
require_once BASE_PATH . '/app/Services/ProductionCashService.php';
require_once BASE_PATH . '/app/Services/ProductionInwardService.php';
require_once BASE_PATH . '/app/Services/ProductionService.php';
require_once BASE_PATH . '/app/Services/StockService.php';

class BatchBillingService
{
    /**
     * Resolve packed quantities per variant for a QC batch, including SCAN-mode batches.
     */
    public static function getPackedQtyMapForQCBatch(int $qcBatchId, int $concernId): array
    {
        $rows = Database::fetchAll(
            "SELECT pbi.variant_id, COALESCE(SUM(pbi.quantity), 0) AS packed_qty
             FROM production_batch_items pbi
             JOIN production_batches pb ON pb.id = pbi.production_batch_id
             WHERE pb.concern_id = ?
               AND pb.status != 'CANCELLED'
               AND (pb.qc_batch_id = ?
                    OR (pb.source_type = 'SCAN' AND pbi.source_kind = 'QC'
                        AND pbi.qc_item_id IN (SELECT id FROM qc_items WHERE qc_batch_id = ?)))
             GROUP BY pbi.variant_id",
            [$concernId, $qcBatchId, $qcBatchId]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['variant_id']] = (int)$row['packed_qty'];
        }
        return $map;
    }

    /**
     * Create a packed production batch across qc/inward/scan modes with optional Sale Bill & immediate Outward Dispatch.
     */
    public static function createBatchFromRequest(array $post, int $userId): array
    {
        $sourceConcernId = ConcernTransactionService::activeConcernId();
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if (!$sourceConcernId || $scopedUnitId === 0) {
            throw new InvalidArgumentException('Your active concern or unit scope does not permit packing batches.');
        }

        $sourceMode = strtolower(trim((string)($post['source_mode'] ?? (!empty($post['qc_batch_id']) ? 'qc' : 'inward'))));
        if (!in_array($sourceMode, ['qc', 'inward', 'scan'], true)) {
            $sourceMode = 'qc';
        }

        $destinationConcernId = (int)($post['destination_concern_id'] ?? 0);
        if ($destinationConcernId <= 0 || $destinationConcernId === $sourceConcernId) {
            throw new InvalidArgumentException('Choose an active receiving concern other than the active concern.');
        }
        $destinationConcern = Database::fetchOne(
            "SELECT c.id, c.name
             FROM concerns c
             WHERE c.id = ? AND c.is_active = 1
               AND EXISTS (
                   SELECT 1 FROM concern_modules cm
                   WHERE cm.concern_id = c.id AND cm.module_key = 'production_inward'
               )",
            [$destinationConcernId]
        );
        if (!$destinationConcern) {
            throw new InvalidArgumentException('Choose an active receiving concern with Production Inward enabled.');
        }

        $boxCount = (int)($post['box_count'] ?? 0);
        if ($boxCount < 1) {
            throw new InvalidArgumentException('Total Box / Carton Count must be at least 1.');
        }

        $generateBill = !empty($post['generate_sale_bill']) && (string)$post['generate_sale_bill'] !== '0';
        $dispatchNow = $generateBill && !empty($post['dispatch_now']) && (string)$post['dispatch_now'] !== '0';
        if ($dispatchNow && function_exists('hasPermission') && !hasPermission('outward', 'create')) {
            throw new RuntimeException('You do not have permission to dispatch Production Outwards immediately.');
        }

        $outwardPayload = null;
        if ($dispatchNow) {
            $vehicleNumber = strtoupper(trim((string)($post['vehicle_number'] ?? '')));
            $driverName = trim((string)($post['driver_name'] ?? ''));
            $driverContact = preg_replace('/[^0-9]/', '', (string)($post['driver_contact'] ?? ''));
            $transporterName = trim((string)($post['transporter_name'] ?? ''));
            $dispatchInput = trim((string)($post['dispatch_date'] ?? ''));
            $dispatchTs = $dispatchInput !== '' ? strtotime($dispatchInput) : false;
            if ($vehicleNumber === '' || $driverName === '' || strlen($driverContact) !== 10 || $transporterName === '' || $dispatchTs === false) {
                throw new InvalidArgumentException('Enter vehicle number, driver name, valid 10-digit driver contact, transporter, and a valid dispatch date.');
            }
            $outwardPayload = [
                'dispatch_date' => date('Y-m-d H:i:s', $dispatchTs),
                'vehicle_number' => $vehicleNumber,
                'driver_name' => $driverName,
                'driver_contact' => $driverContact,
                'transporter_name' => $transporterName,
                'lr_number' => trim((string)($post['lr_number'] ?? '')),
                'total_boxes' => $boxCount,
                'notes' => trim((string)($post['outward_notes'] ?? '')),
            ];
        }

        // Normalize submitted packing lines by source mode
        $programId = null;
        $qcBatchId = null;
        $sourceLocType = null;
        $sourceLocId = null;
        $batchItems = [];
        $billingCandidates = [];
        $billUnitId = $scopedUnitId;

        if ($sourceMode === 'scan') {
            $submittedItems = is_array($post['scan_items'] ?? null) ? $post['scan_items'] : [];
            $inventoryList = ProductionBillingService::getAvailableInventory(0);
            $inventoryByKey = [];
            foreach ($inventoryList as $entry) {
                $inventoryByKey[(string)$entry['item_key']] = $entry;
            }
            $seenTags = [];
            foreach ($submittedItems as $sub) {
                if (!is_array($sub)) {
                    continue;
                }
                $tags = array_values(array_unique(array_filter(array_map(
                    static fn($t): string => strtoupper(trim((string)$t)),
                    (array)($sub['tags'] ?? $sub['piece_tags'] ?? [])
                ))));
                if (!$tags) {
                    continue;
                }
                $entry = ProductionBillingService::resolveBillItemEntry($sub, $inventoryByKey, $inventoryList);
                if (!$entry) {
                    throw new InvalidArgumentException('One or more scanned QR items are no longer available.');
                }
                $availMap = array_fill_keys(array_map('strtoupper', $entry['available_tags']), true);
                foreach ($tags as $tag) {
                    if (!isset($availMap[$tag]) || isset($seenTags[$tag])) {
                        throw new InvalidArgumentException("Scanned QR code {$tag} is unavailable or duplicated.");
                    }
                    $seenTags[$tag] = true;
                }
                if (empty($entry['variant_id'])) {
                    throw new InvalidArgumentException('A scanned item is missing its product variant mapping.');
                }
                $batchItems[] = [
                    'item_key' => (string)$entry['item_key'],
                    'variant_id' => (int)$entry['variant_id'],
                    'qc_item_id' => !empty($entry['qc_item_id']) ? (int)$entry['qc_item_id'] : 0,
                    'source_kind' => strtoupper((string)($entry['source_kind'] ?? 'QC')),
                    'source_location_type' => $entry['stock_location_type'] ?? null,
                    'source_location_id' => $entry['stock_location_id'] ?? null,
                    'quantity' => count($tags),
                    'piece_tags' => $tags,
                ];
                $billingCandidates[] = [
                    'submitted' => $sub,
                    'stock' => $entry,
                    'tags' => $tags,
                ];
            }
            if (!$batchItems) {
                throw new InvalidArgumentException('Scan at least 1 available QR code to pack and dispatch.');
            }
            if ($scopedUnitId !== null && $scopedUnitId > 0) {
                $sourceLocType = 'PRODUCTION_UNIT';
                $sourceLocId = $scopedUnitId;
            } else {
                $sourceLocType = 'CONCERN';
                $sourceLocId = $sourceConcernId;
            }
        } elseif ($sourceMode === 'inward') {
            $rawSourceLoc = trim((string)($post['source_location'] ?? ''));
            if (!preg_match('/^(CONCERN|PRODUCTION_UNIT):(\d+)$/i', $rawSourceLoc, $locMatch)) {
                throw new InvalidArgumentException('Select a valid inward inventory source location.');
            }
            $sourceLocType = strtoupper($locMatch[1]);
            $sourceLocId = (int)$locMatch[2];
            StockService::assertLocationBelongsToActiveConcern($sourceLocType, $sourceLocId);
            if ($scopedUnitId !== null && $sourceLocType === 'PRODUCTION_UNIT' && $sourceLocId !== $scopedUnitId) {
                throw new InvalidArgumentException('Selected inward inventory belongs to another production unit.');
            }
            $inwardMetaByVariant = [];
            foreach (ProductionBillingService::getAvailableInventory(0) as $entry) {
                if (($entry['source_kind'] ?? '') === 'INWARD'
                    && strtoupper((string)($entry['stock_location_type'] ?? '')) === $sourceLocType
                    && (int)($entry['stock_location_id'] ?? 0) === $sourceLocId) {
                    $inwardMetaByVariant[(int)$entry['variant_id']] = $entry;
                }
            }
            $submittedItems = is_array($post['items'] ?? null) ? $post['items'] : [];
            foreach ($submittedItems as $vals) {
                if (!is_array($vals)) {
                    continue;
                }
                $qty = (int)($vals['quantity'] ?? 0);
                $variantId = (int)($vals['variant_id'] ?? 0);
                if ($qty <= 0 || $variantId <= 0) {
                    continue;
                }
                $tags = array_values(array_unique(array_filter(array_map(
                    static fn($t): string => strtoupper(trim((string)$t)),
                    is_array($vals['piece_tags'] ?? null) ? $vals['piece_tags'] : []
                ))));
                $batchItems[] = [
                    'variant_id' => $variantId,
                    'qc_item_id' => 0,
                    'source_kind' => 'INWARD',
                    'source_location_type' => $sourceLocType,
                    'source_location_id' => $sourceLocId,
                    'quantity' => $qty,
                    'piece_tags' => $tags,
                ];
                if ($generateBill) {
                    $meta = $inwardMetaByVariant[$variantId] ?? self::loadVariantBillingMeta($variantId);
                    $billingCandidates[] = [
                        'submitted' => $vals,
                        'stock' => $meta,
                        'tags' => $tags,
                    ];
                }
            }
            if (!$batchItems) {
                throw new InvalidArgumentException('Please select at least 1 inward item and QR code(s) to pack.');
            }
            $billUnitId = $sourceLocType === 'PRODUCTION_UNIT' ? $sourceLocId : $scopedUnitId;
        } else {
            $qcBatchId = (int)($post['qc_batch_id'] ?? 0);
            if ($qcBatchId <= 0) {
                throw new InvalidArgumentException('Select a QC Inspection Batch.');
            }
            $ownedQC = Database::fetchOne(
                "SELECT qb.id, pp.production_unit_id
                 FROM qc_batches qb
                 JOIN production_programs pp ON pp.id = qb.program_id
                 JOIN production_units pu ON pu.id = pp.production_unit_id
                 WHERE qb.id = ? AND pu.concern_id = ?"
                 . ($scopedUnitId !== null ? ' AND pp.production_unit_id = ' . (int)$scopedUnitId : ''),
                [$qcBatchId, $sourceConcernId]
            );
            $qcBatch = $ownedQC ? QCBatch::find($qcBatchId) : null;
            if (!$qcBatch) {
                throw new InvalidArgumentException('Selected QC Inspection Batch does not belong to the active concern.');
            }
            $programId = (int)($qcBatch['program_id'] ?? 0);
            $billUnitId = $billUnitId ?? (!empty($ownedQC['production_unit_id']) ? (int)$ownedQC['production_unit_id'] : null);
            $productId = ProductionService::findOrCreateProductForProgram($programId);

            $qcMetaByItem = [];
            foreach (ProductionBillingService::getAvailableInventory(0) as $entry) {
                if (($entry['source_kind'] ?? '') === 'QC' && (int)($entry['qc_batch_id'] ?? 0) === $qcBatchId) {
                    $qcMetaByItem[(int)$entry['qc_item_id']] = $entry;
                }
            }

            $submittedItems = is_array($post['items'] ?? null) ? $post['items'] : [];
            foreach ($submittedItems as $key => $vals) {
                if (!is_array($vals)) {
                    continue;
                }
                $qty = (int)($vals['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $variantId = 0;
                if (!empty($vals['variant_id']) && is_numeric($vals['variant_id'])) {
                    $variantId = (int)$vals['variant_id'];
                } elseif (is_numeric($key) && (int)$key > 0 && empty($vals['size_name'])) {
                    $variantId = (int)$key;
                }
                if ($variantId > 0) {
                    $exists = Database::fetchOne('SELECT id FROM product_variants WHERE id = ?', [$variantId]);
                    if (!$exists) {
                        $variantId = 0;
                    }
                }
                if ($variantId <= 0 && $productId > 0) {
                    $variantId = ProductionService::findOrCreateVariant(
                        $productId,
                        $vals['size_name'] ?? '',
                        $vals['color_name'] ?? '',
                        $vals['sku'] ?? ''
                    );
                    if (!empty($vals['qc_item_id']) && $variantId > 0) {
                        Database::execute('UPDATE qc_items SET variant_id = ? WHERE id = ?', [$variantId, (int)$vals['qc_item_id']]);
                    }
                }
                if ($variantId <= 0) {
                    continue;
                }
                $qcItemId = (int)($vals['qc_item_id'] ?? 0);
                $tags = array_values(array_unique(array_filter(array_map(
                    static fn($t): string => trim((string)$t),
                    is_array($vals['piece_tags'] ?? null) ? $vals['piece_tags'] : []
                ))));
                if ($qcItemId > 0) {
                    $qcItem = Database::fetchOne('SELECT base_price, discount_price FROM qc_items WHERE id = ?', [$qcItemId]);
                    if ($qcItem && ((float)$qcItem['base_price'] > 0 || (float)$qcItem['discount_price'] > 0)) {
                        Database::execute('UPDATE product_variants SET mrp = ?, selling_price = ? WHERE id = ?', [
                            (float)$qcItem['base_price'],
                            (float)$qcItem['discount_price'],
                            $variantId,
                        ]);
                    }
                }
                $batchItems[] = [
                    'variant_id' => $variantId,
                    'qc_item_id' => $qcItemId,
                    'source_kind' => 'QC',
                    'source_location_type' => null,
                    'source_location_id' => null,
                    'quantity' => $qty,
                    'piece_tags' => $tags,
                ];
                if ($generateBill) {
                    if (count($tags) !== $qty) {
                        throw new InvalidArgumentException("Select exact QR codes ({$qty}) for each billed variant.");
                    }
                    $meta = $qcMetaByItem[$qcItemId] ?? self::loadVariantBillingMeta($variantId, $qcItemId);
                    $billingCandidates[] = [
                        'submitted' => $vals,
                        'stock' => $meta,
                        'tags' => array_map('strtoupper', $tags),
                    ];
                }
            }
            if (!$batchItems) {
                throw new InvalidArgumentException('Please pack at least 1 variant item with quantity greater than 0.');
            }
        }

        $billingSpec = null;
        if ($generateBill) {
            $billingSpec = self::validateAndBuildBillingPayload($post, $billingCandidates);
        }

        Database::beginTransaction();
        try {
            $batchPayload = [
                'program_id' => $programId,
                'qc_batch_id' => $qcBatchId,
                'source_type' => strtoupper($sourceMode),
                'source_location_type' => $sourceLocType,
                'source_location_id' => $sourceLocId,
                'concern_id' => $sourceConcernId,
                'destination_warehouse_id' => null,
                'destination_concern_id' => $destinationConcernId,
                'box_count' => $boxCount,
                'status' => 'PREPARING',
            ];
            $batchId = ProductionService::createProductionBatch($batchPayload, $batchItems, $userId);

            $saleBillId = null;
            $billNumber = null;
            if ($generateBill && $billingSpec !== null) {
                if ($billUnitId === null) {
                    $billUnitId = ProductionBillingService::defaultProductionUnitId();
                }
                [$saleBillId, $billNumber] = self::insertBatchSaleBill(
                    $batchId,
                    $sourceConcernId,
                    $destinationConcernId,
                    $billUnitId,
                    $billingSpec,
                    $userId
                );
                Database::execute(
                    'UPDATE production_batches SET sale_bill_id = ? WHERE id = ? AND concern_id = ?',
                    [$saleBillId, $batchId, $sourceConcernId]
                );
            }

            $outwardId = null;
            if ($dispatchNow && $outwardPayload !== null) {
                ProductionService::approveProductionBatch($batchId, $userId);
                $outwardPayload['production_batch_id'] = $batchId;
                $outwardId = ProductionService::createOutward($outwardPayload, $userId);
            }

            Database::commit();
            return [
                'batch_id' => $batchId,
                'sale_bill_id' => $saleBillId,
                'bill_number' => $billNumber,
                'outward_id' => $outwardId,
            ];
        } catch (Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            throw $e;
        }
    }

    private static function loadVariantBillingMeta(int $variantId, int $qcItemId = 0): array
    {
        $row = Database::fetchOne(
            "SELECT pv.id AS variant_id, pv.sku, pv.mrp AS variant_mrp, pv.selling_price,
                    p.name AS product_name, p.code AS product_code, p.gst_percentage,
                    s.name AS size_name, c.name AS color_name
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN sizes s ON s.id = pv.size_id
             LEFT JOIN colors c ON c.id = pv.color_id
             WHERE pv.id = ?",
            [$variantId]
        ) ?: [];
        $mrp = (float)($row['variant_mrp'] ?? 0);
        $selling = (float)($row['selling_price'] ?? 0);
        return [
            'qc_item_id' => $qcItemId,
            'variant_id' => $variantId,
            'product_name' => (string)($row['product_name'] ?? 'Finished Product'),
            'sku' => (string)($row['sku'] ?? ($row['product_code'] ?? '')),
            'size_name' => (string)($row['size_name'] ?? ''),
            'color_name' => (string)($row['color_name'] ?? ''),
            'mrp' => $mrp > 0 ? $mrp : $selling,
            'unit_price' => $selling > 0 ? $selling : $mrp,
            'gst_percentage' => max(0, (float)($row['gst_percentage'] ?? 0)),
        ];
    }

    private static function validateAndBuildBillingPayload(array $post, array $candidates): array
    {
        $buyerType = strtoupper(trim((string)($post['buyer_type'] ?? 'COMPANY')));
        if (!in_array($buyerType, ['COMPANY', 'CUSTOMER'], true)) {
            throw new InvalidArgumentException('Select whether the Sale Bill is for a Company or Customer.');
        }
        $companyName = trim((string)($post['company_name'] ?? ''));
        $companyAddress = trim((string)($post['company_address'] ?? ''));
        $companyPhone = trim((string)($post['company_phone'] ?? ''));
        $companyEmail = trim((string)($post['company_email'] ?? ''));
        $companyGstin = strtoupper(trim((string)($post['company_gstin'] ?? '')));
        $customerName = trim((string)($post['customer_name'] ?? ''));
        $customerPhone = preg_replace('/\s+/', '', (string)($post['customer_phone'] ?? ''));
        $customerAddress = trim((string)($post['customer_address'] ?? ''));
        $customerCity = trim((string)($post['customer_city'] ?? ''));
        $customerState = trim((string)($post['customer_state'] ?? ''));
        $customerGstin = strtoupper(trim((string)($post['customer_gstin'] ?? '')));

        if ($buyerType === 'COMPANY' && $companyName === '') {
            throw new InvalidArgumentException('Company name is required to generate the Sale Bill.');
        }
        if ($buyerType === 'CUSTOMER' && ($customerName === '' || $customerPhone === '')) {
            throw new InvalidArgumentException('Customer name and phone are required to generate the Sale Bill.');
        }
        if ($buyerType === 'COMPANY') {
            $customerName = $customerPhone = $customerAddress = $customerCity = $customerState = $customerGstin = null;
        } else {
            $companyName = $companyAddress = $companyPhone = $companyEmail = $companyGstin = null;
        }

        $billDate = trim((string)($post['bill_date'] ?? date('Y-m-d')));
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $billDate, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            throw new InvalidArgumentException('The Sale Bill date is invalid.');
        }

        $paymentMethod = strtoupper(trim((string)($post['payment_method'] ?? 'CASH')));
        if (!in_array($paymentMethod, ['CASH', 'UPI', 'CASH_UPI', 'CARD', 'NET_BANKING', 'CREDIT'], true)) {
            throw new InvalidArgumentException('Select a valid Sale Bill payment method.');
        }

        $validGstTypes = ['SGST', 'IGST', 'CGST', 'BOTH', 'EXEMPTED'];
        $validTaxInputTypes = ['PERCENT', 'FIXED'];
        $itemPayload = [];
        $totals = [
            'gross_amount' => 0.0, 'discount_amount' => 0.0, 'subtotal' => 0.0,
            'cgst_amount' => 0.0, 'sgst_amount' => 0.0, 'igst_amount' => 0.0,
            'tax_amount' => 0.0, 'total_amount' => 0.0,
        ];

        foreach ($candidates as $candidate) {
            $sub = $candidate['submitted'];
            $stock = $candidate['stock'];
            $tags = $candidate['tags'];
            $qty = count($tags);
            if ($qty <= 0) {
                continue;
            }
            $defaultPrice = (float)($stock['unit_price'] ?? $stock['mrp'] ?? 0);
            $unitPriceRaw = array_key_exists('unit_price', $sub) ? $sub['unit_price'] : $defaultPrice;
            $unitPrice = filter_var($unitPriceRaw, FILTER_VALIDATE_FLOAT);
            $gstType = strtoupper(trim((string)($sub['gst_type'] ?? ((float)($stock['gst_percentage'] ?? 0) > 0 ? 'BOTH' : 'EXEMPTED'))));
            $taxInputType = strtoupper(trim((string)($sub['tax_input_type'] ?? 'PERCENT')));
            $defaultHalfGst = round(max(0, (float)($stock['gst_percentage'] ?? 0)) / 2, 3);
            $sgstRate = filter_var($sub['sgst_rate'] ?? ($gstType === 'BOTH' ? $defaultHalfGst : 0), FILTER_VALIDATE_FLOAT);
            $cgstRate = filter_var($sub['cgst_rate'] ?? ($gstType === 'BOTH' ? $defaultHalfGst : 0), FILTER_VALIDATE_FLOAT);
            $igstRate = filter_var($sub['igst_rate'] ?? 0, FILTER_VALIDATE_FLOAT);

            if (
                $unitPrice === false || $unitPrice < 0 ||
                !in_array($gstType, $validGstTypes, true) ||
                !in_array($taxInputType, $validTaxInputTypes, true) ||
                $sgstRate === false || $cgstRate === false || $igstRate === false ||
                min($sgstRate, $cgstRate, $igstRate) < 0 ||
                ($taxInputType === 'PERCENT' && max($sgstRate, $cgstRate, $igstRate) > 100)
            ) {
                throw new InvalidArgumentException('A Sale Bill product price or GST rate is invalid.');
            }
            if ($gstType === 'EXEMPTED') {
                $sgstRate = $cgstRate = $igstRate = 0.0;
            } elseif ($gstType === 'SGST') {
                $cgstRate = $igstRate = 0.0;
            } elseif ($gstType === 'CGST') {
                $sgstRate = $igstRate = 0.0;
            } elseif ($gstType === 'IGST') {
                $sgstRate = $cgstRate = 0.0;
            } else {
                $igstRate = 0.0;
            }

            $mrp = max(0.0, (float)($stock['mrp'] ?? 0));
            $unitPrice = round((float)$unitPrice, 2);
            $gross = round($mrp * $qty, 2);
            $taxable = round($unitPrice * $qty, 2);
            $discount = max(0.0, round($gross - $taxable, 2));
            $calcTax = static function (float $rate) use ($taxInputType, $taxable, $qty): float {
                return $rate <= 0 ? 0.0 : round($taxInputType === 'PERCENT' ? ($taxable * $rate / 100) : ($rate * $qty), 2);
            };
            $cgst = $calcTax((float)$cgstRate);
            $sgst = $calcTax((float)$sgstRate);
            $igst = $calcTax((float)$igstRate);
            $tax = round($cgst + $sgst + $igst, 2);
            $lineTotal = round($taxable + $tax, 2);
            $gstPct = $taxInputType === 'PERCENT' ? round((float)$cgstRate + (float)$sgstRate + (float)$igstRate, 3) : 0.0;

            $itemPayload[] = [
                'stock' => $stock,
                'tags' => $tags,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'gst_type' => $gstType,
                'tax_input_type' => $taxInputType,
                'sgst_rate' => round((float)$sgstRate, 3),
                'cgst_rate' => round((float)$cgstRate, 3),
                'igst_rate' => round((float)$igstRate, 3),
                'gst_percentage' => $gstPct,
                'amounts' => [
                    'mrp' => $mrp,
                    'discount_percentage' => $gross > 0 ? round(($discount / $gross) * 100, 3) : 0.0,
                    'discount_amount' => $discount,
                    'taxable_amount' => $taxable,
                    'cgst_amount' => $cgst,
                    'sgst_amount' => $sgst,
                    'igst_amount' => $igst,
                    'tax_amount' => $tax,
                    'total_amount' => $lineTotal,
                ],
            ];
            $totals['gross_amount'] += $gross;
            $totals['discount_amount'] += $discount;
            $totals['subtotal'] += $taxable;
            $totals['cgst_amount'] += $cgst;
            $totals['sgst_amount'] += $sgst;
            $totals['igst_amount'] += $igst;
            $totals['tax_amount'] += $tax;
            $totals['total_amount'] += $lineTotal;
        }

        if (!$itemPayload) {
            throw new InvalidArgumentException('At least one billed item with QR tags is required.');
        }

        foreach ($totals as &$amount) {
            $amount = round($amount, 2);
        }
        unset($amount);

        $taxType = count(array_unique(array_column($itemPayload, 'gst_type'))) === 1
            && $itemPayload[0]['gst_type'] === 'IGST'
            ? 'IGST'
            : 'CGST_SGST';

        $cashAmount = 0.0;
        $upiAmount = 0.0;
        if ($paymentMethod === 'CASH') {
            $cashAmount = $totals['total_amount'];
        } elseif ($paymentMethod === 'UPI') {
            $upiAmount = $totals['total_amount'];
        } elseif ($paymentMethod === 'CASH_UPI') {
            $cashInput = filter_var($post['cash_amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $upiInput = filter_var($post['upi_amount'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($cashInput === false || $upiInput === false || $cashInput < 0 || $upiInput < 0) {
                throw new InvalidArgumentException('Enter valid non-negative cash and UPI amounts.');
            }
            $cashAmount = round((float)$cashInput, 2);
            $upiAmount = round((float)$upiInput, 2);
            if (abs(($cashAmount + $upiAmount) - $totals['total_amount']) > 0.01) {
                throw new InvalidArgumentException('Cash and UPI amounts must add up to the Sale Bill total.');
            }
        }

        return [
            'buyer_type' => $buyerType,
            'company_name' => $companyName,
            'company_address' => $companyAddress,
            'company_phone' => $companyPhone,
            'company_email' => $companyEmail,
            'company_gstin' => $companyGstin,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_address' => $customerAddress,
            'customer_city' => $customerCity,
            'customer_state' => $customerState,
            'customer_gstin' => $customerGstin,
            'bill_date' => $billDate,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentMethod === 'CREDIT' ? 'PENDING' : 'PAID',
            'tax_type' => $taxType,
            'cash_amount' => $cashAmount,
            'upi_amount' => $upiAmount,
            'upi_reference' => trim((string)($post['upi_reference'] ?? '')),
            'notes' => trim((string)($post['billing_notes'] ?? $post['notes'] ?? '')),
            'totals' => $totals,
            'items' => $itemPayload,
        ];
    }

    private static function insertBatchSaleBill(
        int $batchId,
        int $sourceConcernId,
        int $destinationConcernId,
        ?int $unitId,
        array $spec,
        int $userId
    ): array {
        $billNumber = 'PB-' . date('Y') . '-' . date('mdHis') . '-' . random_int(10, 99);
        Database::execute(
            "INSERT INTO production_bills
             (bill_number, concern_id, bill_source, source_batch_id, destination_concern_id, production_unit_id,
              buyer_type, company_name, company_address, company_phone, company_email, company_gstin,
              customer_name, customer_phone, customer_address, customer_city, customer_state, customer_gstin,
              bill_date, payment_method, payment_status, tax_type, cash_amount, upi_amount, upi_reference,
              gross_amount, discount_amount, subtotal, cgst_amount, sgst_amount, igst_amount, tax_amount,
              total_amount, notes, created_by)
             VALUES (?, ?, 'BATCH_OUTWARD', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $billNumber,
                $sourceConcernId,
                $batchId,
                $destinationConcernId,
                $unitId,
                $spec['buyer_type'],
                $spec['company_name'],
                $spec['company_address'],
                $spec['company_phone'],
                $spec['company_email'],
                $spec['company_gstin'],
                $spec['customer_name'],
                $spec['customer_phone'],
                $spec['customer_address'],
                $spec['customer_city'],
                $spec['customer_state'],
                $spec['customer_gstin'],
                $spec['bill_date'],
                $spec['payment_method'],
                $spec['payment_status'],
                $spec['tax_type'],
                $spec['cash_amount'],
                $spec['upi_amount'],
                $spec['upi_reference'],
                $spec['totals']['gross_amount'],
                $spec['totals']['discount_amount'],
                $spec['totals']['subtotal'],
                $spec['totals']['cgst_amount'],
                $spec['totals']['sgst_amount'],
                $spec['totals']['igst_amount'],
                $spec['totals']['tax_amount'],
                $spec['totals']['total_amount'],
                $spec['notes'],
                $userId ?: null,
            ]
        );
        $billId = (int)Database::lastInsertId();

        foreach ($spec['items'] as $item) {
            $stock = $item['stock'];
            $amounts = $item['amounts'];
            Database::execute(
                "INSERT INTO production_bill_items
                 (bill_id, qc_item_id, variant_id, product_name, sku, size_name, color_name, quantity, mrp,
                  unit_price, discount_percentage, discount_amount, gst_percentage, gst_type, tax_input_type,
                  sgst_rate, cgst_rate, igst_rate, taxable_amount, cgst_amount, sgst_amount, igst_amount,
                  tax_amount, total_amount)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $billId,
                    (int)($stock['qc_item_id'] ?? 0),
                    !empty($stock['variant_id']) ? (int)$stock['variant_id'] : null,
                    (string)$stock['product_name'],
                    (string)($stock['sku'] ?? ''),
                    (string)($stock['size_name'] ?? ''),
                    (string)($stock['color_name'] ?? ''),
                    $item['quantity'],
                    $amounts['mrp'],
                    $item['unit_price'],
                    $amounts['discount_percentage'],
                    $amounts['discount_amount'],
                    $item['gst_percentage'],
                    $item['gst_type'],
                    $item['tax_input_type'],
                    $item['sgst_rate'],
                    $item['cgst_rate'],
                    $item['igst_rate'],
                    $amounts['taxable_amount'],
                    $amounts['cgst_amount'],
                    $amounts['sgst_amount'],
                    $amounts['igst_amount'],
                    $amounts['tax_amount'],
                    $amounts['total_amount'],
                ]
            );
            $billItemId = (int)Database::lastInsertId();

            foreach ($item['tags'] as $tag) {
                $returnedInventory = Database::fetchOne(
                    "SELECT id FROM production_return_inventory WHERE new_tag_code = ? AND status = 'AVAILABLE' FOR UPDATE",
                    [$tag]
                );
                $existingClaim = Database::fetchOne(
                    'SELECT id, status FROM production_bill_item_tags WHERE concern_id = ? AND tag_code = ? FOR UPDATE',
                    [$sourceConcernId, $tag]
                );
                if ($existingClaim) {
                    if ($existingClaim['status'] !== 'RETURNED' || !$returnedInventory) {
                        throw new RuntimeException("QR code {$tag} is already billed in this concern.");
                    }
                    Database::execute(
                        "UPDATE production_bill_item_tags
                         SET bill_id = ?, bill_item_id = ?, qc_item_id = ?, status = 'BILLED'
                         WHERE id = ? AND concern_id = ?",
                        [$billId, $billItemId, (int)($stock['qc_item_id'] ?? 0), (int)$existingClaim['id'], $sourceConcernId]
                    );
                } else {
                    Database::execute(
                        "INSERT INTO production_bill_item_tags
                         (bill_id, bill_item_id, concern_id, qc_item_id, tag_code, status)
                         VALUES (?, ?, ?, ?, ?, 'BILLED')",
                        [$billId, $billItemId, $sourceConcernId, (int)($stock['qc_item_id'] ?? 0), $tag]
                    );
                }
                if ($returnedInventory) {
                    Database::execute(
                        "UPDATE production_return_inventory SET status = 'BILLED', updated_at = NOW()
                         WHERE id = ? AND status = 'AVAILABLE'",
                        [(int)$returnedInventory['id']]
                    );
                }
            }
        }

        ProductionCashService::syncBillCollections($billId, [
            'production_unit_id' => $unitId,
            'bill_number' => $billNumber,
            'payment_method' => $spec['payment_method'],
            'payment_status' => $spec['payment_status'],
            'total_amount' => $spec['totals']['total_amount'],
            'cash_amount' => $spec['cash_amount'],
            'upi_amount' => $spec['upi_amount'],
            'upi_reference' => $spec['upi_reference'],
        ], $userId);

        return [$billId, $billNumber];
    }

    /**
     * Load a batch-linked Sale Bill authorized for either the sending or receiving concern.
     */
    public static function findAuthorizedBatchSaleBill(int $batchId): ?array
    {
        $concernId = ConcernTransactionService::activeConcernId();
        if ($batchId <= 0 || $concernId <= 0) {
            return null;
        }
        $batch = Database::fetchOne(
            "SELECT pb.id AS batch_id, pb.batch_number, pb.concern_id AS source_concern_id,
                    pb.destination_concern_id, pb.sale_bill_id, pb.status AS batch_status,
                    sc.name AS source_concern_name, dc.name AS destination_concern_name
             FROM production_batches pb
             LEFT JOIN concerns sc ON sc.id = pb.concern_id
             LEFT JOIN concerns dc ON dc.id = pb.destination_concern_id
             WHERE pb.id = ?
               AND (pb.concern_id = ? OR pb.destination_concern_id = ?)
               AND pb.sale_bill_id IS NOT NULL",
            [$batchId, $concernId, $concernId]
        );
        if (!$batch || empty($batch['sale_bill_id'])) {
            return null;
        }

        $bill = Database::fetchOne(
            "SELECT pb.*, u.full_name AS created_by_name, c.name AS concern_name
             FROM production_bills pb
             LEFT JOIN users u ON u.id = pb.created_by
             LEFT JOIN concerns c ON c.id = pb.concern_id
             WHERE pb.id = ? AND pb.concern_id = ?",
            [(int)$batch['sale_bill_id'], (int)$batch['source_concern_id']]
        );
        if (!$bill) {
            return null;
        }

        $items = Database::fetchAll(
            "SELECT pbi.*, p.hsn_code
             FROM production_bill_items pbi
             LEFT JOIN product_variants pv ON pv.id = pbi.variant_id
             LEFT JOIN products p ON p.id = pv.product_id
             WHERE pbi.bill_id = ?
             ORDER BY pbi.id",
            [(int)$bill['id']]
        );
        $tagRows = Database::fetchAll(
            'SELECT bill_item_id, tag_code FROM production_bill_item_tags WHERE bill_id = ? ORDER BY id',
            [(int)$bill['id']]
        );
        $tagsByItem = [];
        foreach ($tagRows as $tagRow) {
            $tagsByItem[(int)$tagRow['bill_item_id']][] = $tagRow['tag_code'];
        }
        foreach ($items as &$item) {
            $item['tags'] = $tagsByItem[(int)$item['id']] ?? [];
        }
        unset($item);

        $existingPurchase = null;
        if ((int)$batch['destination_concern_id'] === $concernId) {
            $existingPurchase = Database::fetchOne(
                "SELECT id, purchase_number FROM production_purchases
                 WHERE concern_id = ? AND source_batch_id = ?
                 ORDER BY id DESC LIMIT 1",
                [$concernId, $batchId]
            );
        }

        return [
            'batch' => $batch,
            'bill' => $bill,
            'billItems' => $items,
            'isReceiver' => (int)$batch['destination_concern_id'] === $concernId,
            'existingPurchase' => $existingPurchase,
        ];
    }

    /**
     * Load batches dispatched/received into the active concern with a Sale Bill that do not yet have a Purchase Bill.
     */
    public static function getAvailableBatchesForPurchase(?int $concernId = null): array
    {
        $concernId = (int)($concernId ?? ConcernTransactionService::activeConcernId());
        if ($concernId <= 0) {
            return [];
        }
        return Database::fetchAll(
            "SELECT pb.id AS batch_id, pb.batch_number, pb.status AS batch_status, pb.total_qty,
                    pb.received_qty, pb.concern_id AS source_concern_id,
                    sc.name AS source_concern_name,
                    sb.id AS sale_bill_id, sb.bill_number AS sale_bill_number,
                    sb.bill_date AS sale_bill_date, sb.total_amount AS sale_bill_total,
                    pi.id AS inward_id, pi.grn_number
             FROM production_batches pb
             JOIN concerns sc ON sc.id = pb.concern_id
             JOIN production_bills sb ON sb.id = pb.sale_bill_id
             LEFT JOIN production_inwards pi ON pi.source_type = 'PRODUCTION_BATCH'
                  AND pi.source_id = pb.id AND pi.concern_id = ? AND pi.status = 'RECEIVED'
             WHERE pb.destination_concern_id = ?
               AND pb.status IN ('DISPATCHED', 'RECEIVED')
               AND NOT EXISTS (
                   SELECT 1 FROM production_purchases pp
                   WHERE pp.concern_id = ? AND pp.source_batch_id = pb.id
               )
             ORDER BY pb.id DESC",
            [$concernId, $concernId, $concernId]
        );
    }

    /**
     * Build pre-filled purchase form data for a receiving concern from an inter-concern batch + Sale Bill.
     */
    public static function getBatchPurchasePrefill(int $batchId, ?int $inwardId = null): ?array
    {
        $concernId = ConcernTransactionService::activeConcernId();
        if ($batchId <= 0 || $concernId <= 0) {
            return null;
        }
        $batch = Database::fetchOne(
            "SELECT pb.*, sc.name AS source_concern_name, sc.description AS source_concern_description,
                    sb.id AS sale_bill_id, sb.bill_number, sb.bill_date, sb.buyer_type,
                    sb.company_name, sb.company_address, sb.company_phone, sb.company_email, sb.company_gstin,
                    sb.total_amount AS sale_bill_total, sb.notes AS sale_bill_notes
             FROM production_batches pb
             JOIN concerns sc ON sc.id = pb.concern_id
             LEFT JOIN production_bills sb ON sb.id = pb.sale_bill_id
             WHERE pb.id = ? AND pb.destination_concern_id = ?
               AND pb.status IN ('DISPATCHED', 'RECEIVED')",
            [$batchId, $concernId]
        );
        if (!$batch) {
            return null;
        }

        $existingPurchase = Database::fetchOne(
            "SELECT id, purchase_number FROM production_purchases
             WHERE concern_id = ? AND source_batch_id = ?
             ORDER BY id DESC LIMIT 1",
            [$concernId, $batchId]
        );

        $inward = $inwardId
            ? Database::fetchOne(
                "SELECT * FROM production_inwards
                 WHERE id = ? AND concern_id = ? AND source_type = 'PRODUCTION_BATCH' AND source_id = ? AND status = 'RECEIVED'",
                [$inwardId, $concernId, $batchId]
            )
            : Database::fetchOne(
                "SELECT * FROM production_inwards
                 WHERE concern_id = ? AND source_type = 'PRODUCTION_BATCH' AND source_id = ? AND status = 'RECEIVED'
                 ORDER BY id DESC LIMIT 1",
                [$concernId, $batchId]
            );

        $receivedByVariant = [];
        if ($inward) {
            $inwardItems = Database::fetchAll(
                'SELECT variant_id, quantity, damaged_qty, missing_qty, excess_qty FROM production_inward_items WHERE inward_id = ?',
                [(int)$inward['id']]
            );
            foreach ($inwardItems as $inItem) {
                $receivedByVariant[(int)$inItem['variant_id']] = (int)$inItem['quantity'];
            }
        }

        $prefillItems = [];
        if (!empty($batch['sale_bill_id'])) {
            $billItems = Database::fetchAll(
                'SELECT * FROM production_bill_items WHERE bill_id = ? ORDER BY id',
                [(int)$batch['sale_bill_id']]
            );
            foreach ($billItems as $bi) {
                $variantId = (int)($bi['variant_id'] ?? 0);
                $billedQty = (int)($bi['quantity'] ?? 0);
                $acceptedQty = $inward && $variantId > 0 && array_key_exists($variantId, $receivedByVariant)
                    ? $receivedByVariant[$variantId]
                    : $billedQty;
                $taxMode = strtoupper((string)($bi['tax_input_type'] ?? 'PERCENT')) === 'FIXED' ? 'AMOUNT' : 'PERCENT';
                $gstType = strtoupper((string)($bi['gst_type'] ?? 'EXEMPTED'));
                $taxValue = $gstType === 'IGST'
                    ? (float)($bi['igst_rate'] ?? 0)
                    : ($gstType === 'CGST' ? (float)($bi['cgst_rate'] ?? 0) : (float)($bi['sgst_rate'] ?? 0));
                $cgstValue = $gstType === 'BOTH' ? (float)($bi['cgst_rate'] ?? 0) : 0.0;
                $prefillItems[] = [
                    'variant_id' => $variantId,
                    'item_name' => (string)($bi['product_name'] ?? 'Finished Garment'),
                    'sku' => (string)($bi['sku'] ?? ''),
                    'size_name' => (string)($bi['size_name'] ?? ''),
                    'color_name' => (string)($bi['color_name'] ?? ''),
                    'billed_qty' => $billedQty,
                    'received_good_qty' => $acceptedQty,
                    'quantity' => $acceptedQty > 0 ? $acceptedQty : $billedQty,
                    'unit' => 'PCS',
                    'unit_price' => round((float)($bi['unit_price'] ?? 0), 2),
                    'gst_type' => $gstType,
                    'tax_mode' => $taxMode,
                    'tax_value' => $taxValue,
                    'cgst_tax_value' => $cgstValue,
                ];
            }
        } else {
            foreach (ProductionBatch::getItems($batchId) as $bi) {
                $variantId = (int)($bi['variant_id'] ?? 0);
                $expectedQty = (int)($bi['quantity'] ?? 0);
                $acceptedQty = $inward && $variantId > 0 && array_key_exists($variantId, $receivedByVariant)
                    ? $receivedByVariant[$variantId]
                    : $expectedQty;
                $unitPrice = (float)($bi['qc_discount_price'] ?? $bi['selling_price'] ?? $bi['mrp'] ?? 0);
                $prefillItems[] = [
                    'variant_id' => $variantId,
                    'item_name' => (string)($bi['product_name'] ?? 'Finished Garment'),
                    'sku' => (string)($bi['sku'] ?? ''),
                    'size_name' => (string)($bi['size_name'] ?? ''),
                    'color_name' => (string)($bi['color_name'] ?? ''),
                    'billed_qty' => $expectedQty,
                    'received_good_qty' => $acceptedQty,
                    'quantity' => $acceptedQty > 0 ? $acceptedQty : $expectedQty,
                    'unit' => 'PCS',
                    'unit_price' => round($unitPrice, 2),
                    'gst_type' => 'EXEMPTED',
                    'tax_mode' => 'PERCENT',
                    'tax_value' => 0.0,
                    'cgst_tax_value' => 0.0,
                ];
            }
        }

        $matchedSeller = Database::fetchOne(
            "SELECT id, name, company
             FROM sellers
             WHERE concern_id = ? AND seller_type = 'MATERIAL_SUPPLIER' AND is_active = 1
               AND (linked_concern_id = ? OR UPPER(COALESCE(company, name)) = UPPER(?))
             ORDER BY CASE WHEN linked_concern_id = ? THEN 0 ELSE 1 END, id ASC
             LIMIT 1",
            [$concernId, (int)$batch['concern_id'], (string)$batch['source_concern_name'], (int)$batch['concern_id']]
        );

        return [
            'batch' => $batch,
            'inward' => $inward,
            'existingPurchase' => $existingPurchase,
            'matchedSeller' => $matchedSeller,
            'prefillItems' => $prefillItems,
        ];
    }

    /**
     * Find or create an inter-concern supplier inside the receiving concern for a validated source concern.
     */
    public static function resolveOrCreateConcernSupplier(int $receivingConcernId, int $sourceConcernId): int
    {
        if ($receivingConcernId <= 0 || $sourceConcernId <= 0 || $receivingConcernId === $sourceConcernId) {
            throw new InvalidArgumentException('Invalid inter-concern supplier selection.');
        }
        $existing = Database::fetchOne(
            "SELECT id FROM sellers
             WHERE concern_id = ? AND seller_type = 'MATERIAL_SUPPLIER' AND is_active = 1 AND linked_concern_id = ?
             ORDER BY id ASC LIMIT 1",
            [$receivingConcernId, $sourceConcernId]
        );
        if ($existing) {
            return (int)$existing['id'];
        }
        $sourceConcern = Database::fetchOne(
            'SELECT id, name, description FROM concerns WHERE id = ? AND is_active = 1',
            [$sourceConcernId]
        );
        if (!$sourceConcern) {
            throw new InvalidArgumentException('Sending concern was not found or is inactive.');
        }
        Database::execute(
            "INSERT INTO sellers
             (concern_id, linked_concern_id, name, company, seller_type, is_active)
             VALUES (?, ?, ?, ?, 'MATERIAL_SUPPLIER', 1)",
            [
                $receivingConcernId,
                $sourceConcernId,
                (string)$sourceConcern['name'],
                (string)$sourceConcern['name'],
            ]
        );
        return (int)Database::lastInsertId();
    }
}

