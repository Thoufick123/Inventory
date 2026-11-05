<?php
/**
 * Channel Damage Model
 * Handles damaged goods logging for Sales Channels (WhatsApp & Website),
 * including inward transit damage auto-capture, channel floor damage,
 * defect categorization, piece QR tracking, financial loss calculation,
 * and outward return-to-warehouse batching.
 */

class ChannelDamage {

    /**
     * Generate unique damage number: DMG-WA-YYYYMMDD-XXXX or DMG-CH-YYYYMMDD-XXXX
     */
    public static function generateDamageNumber(string $prefix = 'DMG-WA'): string {
        $today = date('Ymd');
        $fullPrefix = $prefix . '-' . $today . '-';
        $last = Database::fetchOne(
            "SELECT damage_number FROM channel_damages WHERE damage_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$fullPrefix . '%']
        );
        $seq = 1;
        if ($last && !empty($last['damage_number'])) {
            $parts = explode('-', $last['damage_number']);
            $seq = (int)end($parts) + 1;
        }
        return sprintf('%s%04d', $fullPrefix, $seq);
    }

    /**
     * Get all channel damage records with optional filters.
     */
    public static function getAll(int|array $channelIdOrFilters = 0, array $filters = []): array {
        $channelIds = [];
        if (is_array($channelIdOrFilters)) {
            if (isset($channelIdOrFilters[0]) && is_numeric($channelIdOrFilters[0])) {
                $channelIds = array_map('intval', $channelIdOrFilters);
            } else {
                $filters = $channelIdOrFilters;
                if (!empty($filters['channel_ids']) && is_array($filters['channel_ids'])) {
                    $channelIds = array_map('intval', $filters['channel_ids']);
                } elseif (!empty($filters['channel_id'])) {
                    if (is_array($filters['channel_id'])) {
                        $channelIds = array_map('intval', $filters['channel_id']);
                    } elseif ((int)$filters['channel_id'] > 0) {
                        $channelIds = [(int)$filters['channel_id']];
                    }
                }
            }
        } elseif ((int)$channelIdOrFilters > 0) {
            $channelIds = [(int)$channelIdOrFilters];
        }

        if (!empty($filters['channel_ids']) && is_array($filters['channel_ids'])) {
            $channelIds = array_unique(array_merge($channelIds, array_map('intval', $filters['channel_ids'])));
        }

        $sql = "SELECT cd.*, 
                       pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost,
                       pv.image_url as variant_image,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       cat.name as category_name, sz.name as size_name, cl.name as color_name,
                       u_rep.full_name as reported_by_name,
                       u_insp.full_name as inspected_by_name,
                       wa.allocation_number,
                       wr.return_number as outward_batch_number, wr.status as outward_batch_status
                FROM channel_damages cd
                JOIN product_variants pv ON cd.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN sizes sz ON pv.size_id = sz.id
                LEFT JOIN colors cl ON pv.color_id = cl.id
                LEFT JOIN users u_rep ON cd.reported_by = u_rep.id
                LEFT JOIN users u_insp ON cd.inspected_by = u_insp.id
                LEFT JOIN warehouse_allocations wa ON cd.allocation_id = wa.id
                LEFT JOIN warehouse_returns wr ON cd.outward_batch_id = wr.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($channelIds)) {
            $placeholders = implode(',', array_fill(0, count($channelIds), '?'));
            $sql .= " AND cd.channel_id IN ($placeholders)";
            $params = array_merge($params, $channelIds);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            $sql .= " AND cd.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['defect_type']) && $filters['defect_type'] !== 'ALL') {
            $sql .= " AND cd.defect_type = ?";
            $params[] = $filters['defect_type'];
        }

        if (!empty($filters['source_type']) && $filters['source_type'] !== 'ALL') {
            $sql .= " AND cd.source_type = ?";
            $params[] = $filters['source_type'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(cd.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(cd.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $sql .= " AND (cd.damage_number LIKE ? OR p.name LIKE ? OR pv.sku LIKE ? OR cd.tag_code LIKE ? OR cd.damage_reason LIKE ? OR wa.allocation_number LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search, $search, $search]);
        }

        $sql .= " ORDER BY cd.created_at DESC, cd.id DESC";

        $records = Database::fetchAll($sql, $params);
        foreach ($records as &$r) {
            $r['image'] = !empty($r['variant_image']) ? $r['variant_image'] : ($r['product_image'] ?? null);
            $r['resolved_tags'] = self::resolvePieceTags($r);
        }
        return $records;
    }

    /**
     * Get summary KPI stats for Channel Damaged Goods.
     */
    public static function getSummaryStats(int|array $channelId): array {
        $channelIds = is_array($channelId) ? array_map('intval', $channelId) : [(int)$channelId];
        $channelIds = array_filter($channelIds);
        if (empty($channelIds)) {
            $channelIds = [0];
        }
        $placeholders = implode(',', array_fill(0, count($channelIds), '?'));
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    COALESCE(SUM(quantity), 0) as total_damaged_units,
                    COALESCE(SUM(total_loss), 0) as total_estimated_loss,
                    COALESCE(SUM(CASE WHEN source_type = 'INWARD_TRANSIT' THEN quantity ELSE 0 END), 0) as transit_damaged_units,
                    COALESCE(SUM(CASE WHEN status IN ('LOGGED', 'REPORTED', 'INSPECTED') THEN quantity ELSE 0 END), 0) as quarantined_units,
                    COALESCE(SUM(CASE WHEN status IN ('OUTWARD_BATCHED', 'DISPATCHED', 'RECEIVED_AT_WH') THEN quantity ELSE 0 END), 0) as outward_dispatched_units
                FROM channel_damages
                WHERE channel_id IN ($placeholders)";
        
        $stats = Database::fetchOne($sql, $channelIds);
        return $stats ?: [
            'total_records'            => 0,
            'total_damaged_units'      => 0,
            'total_estimated_loss'     => 0.00,
            'transit_damaged_units'    => 0,
            'quarantined_units'        => 0,
            'outward_dispatched_units' => 0,
        ];
    }

    /**
     * Get pending damaged items awaiting outward batch dispatch.
     */
    public static function getPendingOutward(int|array $channelId): array {
        $channelIds = is_array($channelId) ? array_map('intval', $channelId) : [(int)$channelId];
        $channelIds = array_filter($channelIds);
        if (empty($channelIds)) {
            $channelIds = [0];
        }
        $placeholders = implode(',', array_fill(0, count($channelIds), '?'));
        $sql = "SELECT cd.*, 
                       pv.sku as variant_sku, pv.sku, pv.qr_code, pv.mrp, pv.selling_price,
                       p.name as product_name, p.code as product_code,
                       sz.name as size, cl.name as color
                FROM channel_damages cd
                JOIN product_variants pv ON cd.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN sizes sz ON pv.size_id = sz.id
                LEFT JOIN colors cl ON pv.color_id = cl.id
                WHERE cd.channel_id IN ($placeholders)
                  AND (cd.outward_batch_id IS NULL OR cd.outward_batch_id = 0)
                  AND cd.status IN ('LOGGED', 'REPORTED', 'INSPECTED')
                ORDER BY cd.created_at DESC";
        return Database::fetchAll($sql, $channelIds);
    }

    /**
     * Create manual or automated channel damage record.
     */
    public static function create(array $data): string {
        $channelId = (int)($data['channel_id'] ?? 3);
        $variantId = (int)($data['variant_id'] ?? 0);
        $quantity  = max(1, (int)($data['quantity'] ?? 1));
        
        $damageNumber = $data['damage_number'] ?? self::generateDamageNumber('DMG-WA');
        
        $variant = Database::fetchOne(
            "SELECT pv.*, p.id as prod_id 
             FROM product_variants pv 
             JOIN products p ON pv.product_id = p.id 
             WHERE pv.id = ?",
            [$variantId]
        );
        
        $productId = $variant['prod_id'] ?? ($data['product_id'] ?? null);
        if (isset($data['cost_per_unit']) && (float)$data['cost_per_unit'] > 0) {
            $unitCost = (float)$data['cost_per_unit'];
        } else {
            $unitCost = (float)(($variant['selling_price'] ?? 0) > 0 ? $variant['selling_price'] : (($variant['mrp'] ?? 0) > 0 ? $variant['mrp'] : (($variant['production_cost'] ?? 0) > 0 ? $variant['production_cost'] : 100)));
        }
        $totalLoss = round($unitCost * $quantity, 2);
        
        $tagCode = '';
        if (!empty($data['piece_tags'])) {
            $tagCode = is_array($data['piece_tags']) ? implode(', ', $data['piece_tags']) : (string)$data['piece_tags'];
        } elseif (!empty($data['tag_code'])) {
            $tagCode = (string)$data['tag_code'];
        }
        
        $defectTypeMap = [
            'FABRIC_DEFECT'    => 'FABRIC_DEFECT',
            'STITCHING_DEFECT' => 'STITCHING_ERROR',
            'STITCHING_ERROR'  => 'STITCHING_ERROR',
            'COLOR_MISMATCH'   => 'COLOR_FADE',
            'COLOR_FADE'       => 'COLOR_FADE',
            'PACKAGING_DAMAGED'=> 'PACKAGING_DAMAGE',
            'PACKAGING_DAMAGE' => 'PACKAGING_DAMAGE',
            'TRANSIT_DAMAGE'   => 'TRANSIT_DAMAGE',
            'INWARD_DEFECT'    => 'TRANSIT_DAMAGE',
            'INWARD_DAMAGE'    => 'TRANSIT_DAMAGE',
            'DIRTY_SOILED'     => 'WATER_STAIN',
            'WATER_STAIN'      => 'WATER_STAIN',
            'EXPIRED'          => 'EXPIRED',
        ];
        $rawDefect = strtoupper(trim($data['defect_type'] ?? 'OTHER'));
        $defectType = $defectTypeMap[$rawDefect] ?? 'OTHER';
        
        $sourceTypeMap = [
            'INWARD_TRANSIT'   => 'INWARD_TRANSIT',
            'INWARD_SCAN'      => 'INWARD_TRANSIT',
            'STORAGE_HANDLING' => 'STORAGE_HANDLING',
            'MANUAL_INSPECTION'=> 'STORAGE_HANDLING',
            'CUSTOMER_RETURN'  => 'CUSTOMER_RETURN',
        ];
        $rawSource = strtoupper(trim($data['source_type'] ?? $data['discovered_stage'] ?? 'STORAGE_HANDLING'));
        $sourceType = $sourceTypeMap[$rawSource] ?? 'STORAGE_HANDLING';
        
        $statusMap = [
            'LOGGED'           => 'LOGGED',
            'REPORTED'         => 'LOGGED',
            'INSPECTED'        => 'INSPECTED',
            'OUTWARD_PENDING'  => 'INSPECTED',
            'OUTWARD_BATCHED'  => 'OUTWARD_BATCHED',
            'DISPATCHED'       => 'DISPATCHED',
        ];
        $rawStatus = strtoupper(trim($data['status'] ?? 'LOGGED'));
        $status = $statusMap[$rawStatus] ?? 'LOGGED';
        
        $reason = $data['damage_reason'] ?? $data['notes'] ?? 'Manual defect quarantine';
        $notes = $data['defect_notes'] ?? $data['notes'] ?? '';

        Database::execute(
            "INSERT INTO channel_damages 
             (damage_number, channel_id, variant_id, product_id, tag_code, source_type, reference_id, allocation_id, defect_type, quantity, cost_per_unit, estimated_cost, total_loss, damage_reason, defect_notes, status, reported_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $damageNumber,
                $channelId,
                $variantId,
                $productId,
                $tagCode,
                $sourceType,
                $data['reference_id'] ?? null,
                $data['allocation_id'] ?? null,
                $defectType,
                $quantity,
                $unitCost,
                $unitCost,
                $totalLoss,
                $reason,
                $notes,
                $status,
                $data['reported_by'] ?? null
            ]
        );

        return $damageNumber;
    }

    /**
     * Find channel damage by ID.
     */
    public static function find(int $id): ?array {
        $sql = "SELECT cd.*, 
                       sc.name as channel_name, sc.code as channel_code,
                       pv.sku, pv.sku as variant_sku, pv.qr_code, pv.qr_type, pv.is_complementary as variant_is_complementary, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost,
                       ai.selected_qr_codes, ai.received_qr_codes,
                       pv.image_url as variant_image,
                       p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                       p.category_code, p.material, p.hsn_code, p.is_complementary as product_is_complementary,
                       cat.name as category_name, sz.name as size_name, sz.name as size, cl.name as color_name, cl.name as color,
                       u_rep.full_name as reported_by_name,
                       u_insp.full_name as inspected_by_name,
                       wa.allocation_number,
                       w.name as origin_warehouse_name,
                       wr.return_number as outward_batch_number, wr.status as outward_batch_status,
                       dest_w.name as dest_warehouse_name
                FROM channel_damages cd
                JOIN sales_channels sc ON cd.channel_id = sc.id
                JOIN product_variants pv ON cd.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN sizes sz ON pv.size_id = sz.id
                LEFT JOIN colors cl ON pv.color_id = cl.id
                LEFT JOIN users u_rep ON cd.reported_by = u_rep.id
                LEFT JOIN users u_insp ON cd.inspected_by = u_insp.id
                LEFT JOIN warehouse_allocations wa ON cd.allocation_id = wa.id
                LEFT JOIN allocation_items ai ON ai.allocation_id = cd.allocation_id AND ai.variant_id = cd.variant_id
                LEFT JOIN warehouses w ON wa.warehouse_id = w.id
                LEFT JOIN warehouse_returns wr ON cd.outward_batch_id = wr.id
                LEFT JOIN warehouses dest_w ON wr.warehouse_id = dest_w.id
                WHERE cd.id = ?";
        
        $rec = Database::fetchOne($sql, [$id]);
        if (!$rec) return null;
        $rec['image'] = !empty($rec['variant_image']) ? $rec['variant_image'] : ($rec['product_image'] ?? null);
        $rec['resolved_tags'] = self::resolvePieceTags($rec);
        $rec['piece_tags'] = $rec['resolved_tags'];
        if (empty($rec['piece_tags']) && !empty($rec['allocation_id'])) {
            $qrList = json_decode((string)($rec['received_qr_codes'] ?? ''), true);
            if (!is_array($qrList)) {
                $qrList = array_filter(array_map('trim', explode(',', (string)($rec['received_qr_codes'] ?: $rec['selected_qr_codes'] ?? ''))));
            }
            $rec['piece_tags'] = array_slice(array_values($qrList), 0, max(1, (int)$rec['quantity']));
        }
        $rec['notes'] = $rec['damage_reason'] . (!empty($rec['defect_notes']) ? ("\n" . $rec['defect_notes']) : '');
        return $rec;
    }

    /**
     * Resolve piece tags array.
     */
    public static function resolvePieceTags(array $d): array {
        $rawTags = $d['tag_code'] ?? '';
        $tagsList = [];
        if (!empty($rawTags)) {
            $parts = explode(',', $rawTags);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p) $tagsList[] = $p;
            }
        }
        return $tagsList;
    }

    /**
     * Auto-log damaged item during WhatsApp Inward receiving.
     */
    public static function logInwardDamage(
        int $channelId,
        int $allocationId = 0,
        int $variantId = 0,
        int $quantity = 0,
        string $tagCodes = '',
        ?int $reportedBy = null,
        ?int $userId = null,
        string $notes = '',
        string $defectType = 'TRANSIT_DAMAGE',
        string $severity = 'MODERATE',
        array $pieceTags = []
    ): int {
        if ($quantity <= 0 || $variantId <= 0) return 0;

        $user = $reportedBy ?: $userId;
        if (!empty($pieceTags)) {
            $tagCodes = implode(', ', $pieceTags);
        }

        // Check if an entry already exists for this allocation & variant
        $existing = Database::fetchOne(
            "SELECT id, quantity, tag_code, total_loss FROM channel_damages 
             WHERE channel_id = ? AND allocation_id = ? AND variant_id = ? AND source_type = 'INWARD_TRANSIT' AND status = 'LOGGED'
             LIMIT 1",
            [$channelId, $allocationId, $variantId]
        );

        $variant = Database::fetchOne(
            "SELECT pv.*, p.id as prod_id, p.name as prod_name, p.code as prod_code 
             FROM product_variants pv 
             JOIN products p ON pv.product_id = p.id 
             WHERE pv.id = ?",
            [$variantId]
        );

        $unitCost = (float)(($variant['production_cost'] ?? 0) > 0 ? $variant['production_cost'] : (($variant['purchase_price'] ?? 0) > 0 ? $variant['purchase_price'] : ($variant['selling_price'] ?? 100)));
        $totalLoss = round($unitCost * $quantity, 2);

        $alloc = Database::fetchOne("SELECT allocation_number FROM warehouse_allocations WHERE id = ?", [$allocationId]);
        $allocNum = $alloc['allocation_number'] ?? ('ALC-' . $allocationId);

        if ($existing) {
            $newQty = (int)$existing['quantity'] + $quantity;
            $newLoss = round($unitCost * $newQty, 2);
            $mergedTags = $existing['tag_code'];
            if ($tagCodes) {
                $mergedTags = $mergedTags ? ($mergedTags . ', ' . $tagCodes) : $tagCodes;
            }
            Database::execute(
                "UPDATE channel_damages SET quantity = ?, tag_code = ?, total_loss = ?, updated_at = NOW() WHERE id = ?",
                [$newQty, $mergedTags, $newLoss, $existing['id']]
            );
            return (int)$existing['id'];
        }

        $damageNumber = self::generateDamageNumber('DMG-WA');
        $reason = $notes ?: "Damaged during inward transit for Allocation #$allocNum";

        Database::execute(
            "INSERT INTO channel_damages 
             (damage_number, channel_id, variant_id, product_id, tag_code, source_type, reference_id, allocation_id, defect_type, quantity, cost_per_unit, estimated_cost, total_loss, damage_reason, status, reported_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'INWARD_TRANSIT', ?, ?, 'TRANSIT_DAMAGE', ?, ?, ?, ?, ?, 'LOGGED', ?, NOW(), NOW())",
            [
                $damageNumber,
                $channelId,
                $variantId,
                $variant['prod_id'] ?? null,
                $tagCodes,
                $allocationId,
                $allocationId,
                $quantity,
                $unitCost,
                $unitCost,
                $totalLoss,
                $reason,
                $user
            ]
        );

        return (int)Database::lastInsertId();
    }
}
