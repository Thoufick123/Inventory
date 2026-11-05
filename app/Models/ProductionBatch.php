<?php
/**
 * Production Batch Model
 * Handles final packed boxes from QC-approved goods, warehouse destination, and approval.
 */

class ProductionBatch {

    /**
     * Find production batch by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne(
            "SELECT pb.*, 
                    pp.program_number, pp.target_date, pp.name as program_name, pp.production_unit_id,
                    COALESCE(p.name, pp.name, 'Finished Product') as product_name, p.code as product_code,
                    w.name as warehouse_name, w.code as warehouse_code,
                    COALESCE(pu.name, 'Main Production Facility') as production_unit_name,
                    COALESCE(pu.code, 'PU1') as production_unit_code,
                    pu.manager_name as production_unit_manager,
                    pu.phone as production_unit_phone,
                    pu.address as production_unit_address,
                    u_prep.full_name as prepared_by_name,
                    u_appr.full_name as approved_by_name,
                    po.id as outward_id,
                    po.outward_number,
                    po.vehicle_number,
                    po.dispatch_date,
                    po.notes as outward_notes,
                    u_disp.full_name as dispatched_by_name
             FROM production_batches pb
             LEFT JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
             LEFT JOIN users u_prep ON pb.prepared_by = u_prep.id
             LEFT JOIN users u_appr ON pb.approved_by = u_appr.id
             LEFT JOIN production_outwards po ON po.production_batch_id = pb.id
             LEFT JOIN users u_disp ON po.created_by = u_disp.id
             WHERE pb.id = ?",
            [$id]
        );
    }

    /**
     * Find production batch by batch number.
     */
    public static function findByNumber(string $batchNumber): ?array {
        return Database::fetchOne(
            "SELECT pb.*, pp.program_number, pp.name as program_name, pp.production_unit_id,
                    COALESCE(p.name, pp.name, 'Finished Product') as product_name,
                    w.name as warehouse_name, w.code as warehouse_code,
                    COALESCE(pu.name, 'Main Production Facility') as production_unit_name,
                    COALESCE(pu.code, 'PU1') as production_unit_code,
                    pu.manager_name as production_unit_manager,
                    po.id as outward_id,
                    po.outward_number,
                    po.vehicle_number,
                    po.dispatch_date,
                    po.notes as outward_notes,
                    u_disp.full_name as dispatched_by_name
             FROM production_batches pb
             LEFT JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
             LEFT JOIN production_outwards po ON po.production_batch_id = pb.id
             LEFT JOIN users u_disp ON po.created_by = u_disp.id
             WHERE pb.batch_number = ?",
            [$batchNumber]
        );
    }

    /**
     * Get all production batches with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT pb.*, 
                       pp.program_number,
                       pp.name as program_name,
                       pp.production_unit_id,
                       COALESCE(p.name, pp.name, 'Finished Product') as product_name,
                       p.code as product_code,
                       w.name as warehouse_name,
                       w.code as warehouse_code,
                       COALESCE(pu.name, 'Main Production Facility') as production_unit_name,
                       COALESCE(pu.code, 'PU1') as production_unit_code,
                       pu.manager_name as production_unit_manager,
                       u_prep.full_name as prepared_by_name,
                       u_appr.full_name as approved_by_name,
                       po.id as outward_id,
                       po.outward_number,
                       po.vehicle_number,
                       po.dispatch_date,
                       po.notes as outward_notes,
                       u_disp.full_name as dispatched_by_name
                FROM production_batches pb
                LEFT JOIN production_programs pp ON pb.program_id = pp.id
                LEFT JOIN products p ON pp.product_id = p.id
                LEFT JOIN warehouses w ON pb.destination_warehouse_id = w.id
                LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
                LEFT JOIN users u_prep ON pb.prepared_by = u_prep.id
                LEFT JOIN users u_appr ON pb.approved_by = u_appr.id
                LEFT JOIN production_outwards po ON po.production_batch_id = pb.id
                LEFT JOIN users u_disp ON po.created_by = u_disp.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND pb.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['program_id'])) {
            $sql .= " AND pb.program_id = ?";
            $params[] = (int)$filters['program_id'];
        }

        if (!empty($filters['production_unit_id'])) {
            $sql .= " AND pp.production_unit_id = ?";
            $params[] = (int)$filters['production_unit_id'];
        }

        $wId = $filters['destination_warehouse_id'] ?? $filters['warehouse_id'] ?? '';
        if (!empty($wId)) {
            $sql .= " AND pb.destination_warehouse_id = ?";
            $params[] = (int)$wId;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (
                pb.batch_number LIKE ? 
                OR pp.program_number LIKE ? 
                OR pp.name LIKE ? 
                OR p.name LIKE ? 
                OR p.code LIKE ? 
                OR w.name LIKE ? 
                OR w.code LIKE ? 
                OR pu.name LIKE ?
                OR pu.code LIKE ?
                OR u_prep.full_name LIKE ? 
                OR pb.status LIKE ? 
                OR CAST(pb.id AS CHAR) LIKE ? 
                OR CAST(pb.qc_batch_id AS CHAR) LIKE ?
            )";
            $search = '%' . trim($filters['search']) . '%';
            $params = array_merge($params, [$search, $search, $search, $search, $search, $search, $search, $search, $search, $search, $search, $search, $search]);
        }

        $sql .= " ORDER BY pb.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get end-to-end lifecycle audit and history logs for a batch.
     */
    public static function getLifecycleHistory(int|string $batchIdentifier): ?array {
        if (is_numeric($batchIdentifier)) {
            $batch = self::find((int)$batchIdentifier);
        } else {
            $batch = self::findByNumber((string)$batchIdentifier);
        }

        if (!$batch) {
            return null;
        }

        $batchId = (int)$batch['id'];
        $progId = (int)($batch['program_id'] ?? 0);

        // 1. Program details
        $program = null;
        if ($progId > 0) {
            $program = Database::fetchOne(
                "SELECT pp.*, 
                        p.name as product_name, p.code as product_code, p.image_url as product_image,
                        pu.name as unit_name, pu.code as unit_code, pu.manager_name as unit_manager, 
                        pu.phone as unit_phone, pu.address as unit_address,
                        u_assign.full_name as assigned_to_name, 
                        u_appr.full_name as approved_by_name, 
                        u_create.full_name as created_by_name
                 FROM production_programs pp
                 LEFT JOIN products p ON pp.product_id = p.id
                 LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
                 LEFT JOIN users u_assign ON pp.assigned_to = u_assign.id
                 LEFT JOIN users u_appr ON pp.approved_by = u_appr.id
                 LEFT JOIN users u_create ON pp.created_by = u_create.id
                 WHERE pp.id = ?",
                [$progId]
            );
        }

        // 2. Fabric / Seller Rolls
        $rolls = [];
        if ($progId > 0) {
            $rolls = Database::fetchAll(
                "SELECT ppr.*, 
                        sr.roll_number, sr.color as sr_color, sr.design_name as sr_design, 
                        sr.weight as sr_weight, sr.price as sr_price, sr.photo_url as roll_photo,
                        s.name as seller_name, s.company as seller_company, s.phone as seller_phone
                 FROM production_program_rolls ppr
                 LEFT JOIN seller_rolls sr ON ppr.seller_roll_id = sr.id
                 LEFT JOIN sellers s ON (ppr.seller_id = s.id OR sr.seller_id = s.id)
                 WHERE ppr.production_program_id = ?",
                [$progId]
            );
        }

        // 3. Cutting Batches
        $cutting = [];
        if ($progId > 0) {
            $cutting = Database::fetchAll(
                "SELECT cb.*, 
                        u.full_name as operator_name, 
                        pu.name as unit_name, pu.code as unit_code
                 FROM cutting_batches cb
                 LEFT JOIN users u ON cb.operator_id = u.id
                 LEFT JOIN production_units pu ON cb.production_unit_id = pu.id
                 WHERE cb.program_id = ?
                 ORDER BY cb.id ASC",
                [$progId]
            );
        }

        // 4. Stitching Batches
        $stitching = [];
        if ($progId > 0) {
            $stitching = Database::fetchAll(
                "SELECT sb.*, 
                        u.full_name as worker_name
                 FROM stitching_batches sb
                 LEFT JOIN users u ON sb.worker_id = u.id
                 WHERE sb.program_id = ?
                 ORDER BY sb.id ASC",
                [$progId]
            );
        }

        // 5. Label Batches
        $labels = [];
        if ($progId > 0) {
            $labels = Database::fetchAll(
                "SELECT lb.*
                 FROM label_batches lb
                 WHERE lb.program_id = ?
                 ORDER BY lb.id ASC",
                [$progId]
            );
        }

        // 6. QC Inspection Batch
        $qc = null;
        $qcBatchId = (int)($batch['qc_batch_id'] ?? 0);
        if ($qcBatchId > 0 || $progId > 0) {
            $qc = Database::fetchOne(
                "SELECT qb.*, 
                        COALESCE(u.full_name, 'System Administrator') as inspector_name
                 FROM qc_batches qb
                 LEFT JOIN users u ON qb.inspector_id = u.id
                 WHERE qb.id = ? OR qb.program_id = ?
                 ORDER BY (qb.id = ?) DESC, qb.id DESC LIMIT 1",
                [$qcBatchId, $progId, $qcBatchId]
            );
        }

        // 7. Outward Logistics Manifest
        $outward = Database::fetchOne(
            "SELECT po.*, 
                    u_disp.full_name as dispatcher_name, 
                    u_recv.full_name as receiver_name,
                    pu.name as source_unit_name, pu.code as source_unit_code,
                    w.name as dest_warehouse_name, w.code as dest_warehouse_code
             FROM production_outwards po
             LEFT JOIN production_units pu ON po.production_unit_id = pu.id
             LEFT JOIN warehouses w ON po.warehouse_id = w.id
             LEFT JOIN users u_disp ON po.dispatched_by = u_disp.id
             LEFT JOIN users u_recv ON po.received_by = u_recv.id
             WHERE po.production_batch_id = ?
             ORDER BY po.id DESC LIMIT 1",
            [$batchId]
        );

        // 8. Stock Transaction Ledger Records
        $transactions = Database::fetchAll(
            "SELECT st.*, 
                    u.full_name as user_name, 
                    w.name as warehouse_name, w.code as warehouse_code,
                    pv.sku, pv.qr_code,
                    s.name as size_name, c.name as color_name
             FROM stock_transactions st
             LEFT JOIN product_variants pv ON st.variant_id = pv.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN users u ON st.user_id = u.id
             LEFT JOIN warehouses w ON (st.location_type = 'WAREHOUSE' AND st.location_id = w.id)
             WHERE (st.reference_type IN ('production_batch', 'production_batches') AND st.reference_id = ?)
                OR (st.reference_type IN ('production_outward', 'production_outwards') AND st.reference_id = ?)
             ORDER BY st.created_at ASC, st.id ASC",
            [$batchId, (int)($outward['id'] ?? 0)]
        );

        // 8b. Inward Verification Audit Sessions (Every time stock was credited / updated)
        $inwardAuditLogs = Database::fetchAll(
            "SELECT al.*, u.full_name as user_name, u.username
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE al.reference_type IN ('production_batch', 'production_batches')
               AND al.reference_id = ?
               AND al.action = 'RECEIVE_GRN'
             ORDER BY al.id ASC",
            [$batchId]
        );

        $inwardSessions = [];
        $prevAccepted = 0;
        $prevDamaged = 0;

        foreach ($inwardAuditLogs as $idx => $sess) {
            $newVal = json_decode($sess['new_value'] ?? '{}', true) ?: [];
            $curAccepted = (int)($newVal['total_accepted'] ?? 0);
            $curDamaged = (int)($newVal['total_damaged'] ?? 0);
            $expected = (int)($newVal['total_expected'] ?? (int)$batch['total_qty']);

            $incAccepted = max(0, $curAccepted - $prevAccepted);
            $incDamaged = max(0, $curDamaged - $prevDamaged);

            $inwardSessions[] = [
                'session_num'         => $idx + 1,
                'session_title'       => 'Inward Session #' . ($idx + 1),
                'incremental_qty'     => $incAccepted,
                'incremental_damaged' => $incDamaged,
                'cumulative_accepted' => $curAccepted,
                'cumulative_damaged'  => $curDamaged,
                'expected_qty'        => $expected,
                'missing_qty'         => max(0, $expected - ($curAccepted + $curDamaged)),
                'match_pct'           => $expected > 0 ? round(($curAccepted / $expected) * 100) : 100,
                'verified_by'         => $sess['user_name'] ?? 'System Administrator',
                'created_at'          => $sess['created_at'],
                'notes'               => $newVal['notes'] ?? '',
            ];

            $prevAccepted = $curAccepted;
            $prevDamaged = $curDamaged;
        }

        // If no audit records found but batch has received stock, synthesize session 1
        if (empty($inwardSessions) && (int)($batch['received_qty'] ?? 0) > 0) {
            $rec = (int)$batch['received_qty'];
            $dmg = (int)($batch['damaged_qty'] ?? 0);
            $exp = (int)$batch['total_qty'];
            $inwardSessions[] = [
                'session_num'         => 1,
                'session_title'       => 'Inward Session #1 (Initial Inward)',
                'incremental_qty'     => $rec,
                'incremental_damaged' => $dmg,
                'cumulative_accepted' => $rec,
                'cumulative_damaged'  => $dmg,
                'expected_qty'        => $exp,
                'missing_qty'         => max(0, $exp - ($rec + $dmg)),
                'match_pct'           => $exp > 0 ? round(($rec / $exp) * 100) : 100,
                'verified_by'         => 'Warehouse Admin',
                'created_at'          => $batch['updated_at'] ?? $batch['created_at'],
                'notes'               => 'Initial Inward Verification',
            ];
        }

        // 9. Batch Variant Items
        $items = self::getItems($batchId);

        return [
            'batch'           => $batch,
            'program'         => $program,
            'rolls'           => $rolls,
            'cutting'         => $cutting,
            'stitching'       => $stitching,
            'labels'          => $labels,
            'qc'              => $qc,
            'outward'         => $outward,
            'transactions'    => $transactions,
            'inward_sessions' => $inwardSessions,
            'items'           => $items,
        ];
    }

    /**
     * Create production batch.
     */
    public static function create(array $data): int {
        if (empty($data['batch_number'])) {
            if (class_exists('ProductionService')) {
                $data['batch_number'] = ProductionService::generateSequenceNumber('production_batches', 'batch_number', 'BAT');
            } else {
                $data['batch_number'] = 'BAT-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            }
        }
        Database::execute(
            "INSERT INTO production_batches 
             (batch_number, program_id, qc_batch_id, total_qty, box_count, destination_warehouse_id, status, prepared_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['batch_number'],
                $data['program_id'],
                $data['qc_batch_id'] ?? null,
                $data['total_qty'] ?? 0,
                $data['box_count'] ?? 0,
                $data['destination_warehouse_id'],
                $data['status'] ?? 'PREPARING',
                $data['prepared_by'] ?? null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Approve production batch.
     */
    public static function approve(int $id, int $userId): bool {
        return Database::execute(
            "UPDATE production_batches 
             SET status = 'APPROVED', approved_by = ?, approved_at = NOW() 
             WHERE id = ? AND status = 'PREPARING'",
            [$userId, $id]
        ) > 0;
    }

    /**
     * Update batch status.
     */
    public static function updateStatus(int $id, string $status): bool {
        return Database::execute(
            "UPDATE production_batches SET status = ? WHERE id = ?",
            [$status, $id]
        ) > 0;
    }

    /**
     * Get variant items for this production batch.
     */
    public static function getItems(int $batchId): array {
        $items = Database::fetchAll(
            "SELECT pbi.*, pbi.piece_tags as packed_piece_tags,
                    pv.sku, pv.qr_code, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost,
                    pv.qr_type, pv.pack_quantity, pv.is_complementary as pv_is_comp,
                    p.is_complementary as p_is_comp, wc.category_type as warehouse_category_type,
                    pv.image_url as variant_image, pv.mfg_date as variant_mfg_date, pv.expiry_date as variant_expiry_date,
                    cat.name as category_name, cat.code as category_code,
                    subcat.name as subcategory_name, subcat.code as subcategory_code,
                    subsubcat.name as sub_subcategory_name, subsubcat.code as sub_subcategory_code,
                    p.name as product_name, p.product_code, p.qr_code_prefix, p.mfg_date, p.expiry_date,
                    s.name as size_name, s.code as size_code,
                    c.name as color_name, c.hex_code as color_hex,
                    (SELECT discount_price FROM qc_items WHERE qc_batch_id = pb.qc_batch_id AND variant_id = pbi.variant_id LIMIT 1) as qc_discount_price,
                    (SELECT base_price FROM qc_items WHERE qc_batch_id = pb.qc_batch_id AND variant_id = pbi.variant_id LIMIT 1) as qc_base_price,
                    (SELECT SUM(rejected_qty) FROM qc_items WHERE qc_batch_id = pb.qc_batch_id AND variant_id = pbi.variant_id) as qc_rejected_qty,
                    (SELECT SUM(rework_qty) FROM qc_items WHERE qc_batch_id = pb.qc_batch_id AND variant_id = pbi.variant_id) as qc_rework_qty
             FROM production_batch_items pbi
             JOIN product_variants pv ON pbi.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN production_batches pb ON pb.id = pbi.production_batch_id
             LEFT JOIN warehouse_categories wc ON p.warehouse_category_id = wc.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE pbi.production_batch_id = ?
             ORDER BY c.name ASC, s.sort_order ASC",
            [$batchId]
        );

        if (empty($items)) {
            return [];
        }

        foreach ($items as &$it) {
            $isSeries = ((isset($it['warehouse_category_type']) && $it['warehouse_category_type'] === 'SERIES') || ($it['qr_type'] ?? '') === 'SERIES_BATCH' || (int)($it['pack_quantity'] ?? 1) > 1);
            $isComp = ((isset($it['warehouse_category_type']) && $it['warehouse_category_type'] === 'COMPLEMENTARY') || !empty($it['pv_is_comp']) || !empty($it['p_is_comp']));
            $it['is_series'] = $isSeries ? 1 : 0;
            $it['is_complementary'] = $isComp ? 1 : 0;
        }
        unset($it);

        // Fetch batch program info to resolve roll fabric details from seller_rolls
        $batch = Database::fetchOne("SELECT program_id, qc_batch_id FROM production_batches WHERE id = ?", [$batchId]);
        $progId = (int)($batch['program_id'] ?? 0);
        $qcBatchId = (int)($batch['qc_batch_id'] ?? 0);
        if ($progId <= 0 && !empty($batch['qc_batch_id'])) {
            $qb = Database::fetchOne("SELECT program_id FROM qc_batches WHERE id = ?", [(int)$batch['qc_batch_id']]);
            $progId = (int)($qb['program_id'] ?? 0);
        }

        $previouslyPackedByVariant = [];
        if ($qcBatchId > 0) {
            $packedRows = Database::fetchAll(
                "SELECT pbi.variant_id, SUM(pbi.quantity) as packed_qty
                 FROM production_batch_items pbi
                 JOIN production_batches pb ON pbi.production_batch_id = pb.id
                 WHERE pb.qc_batch_id = ? AND pb.id < ? AND pb.status != 'CANCELLED'
                 GROUP BY pbi.variant_id",
                [$qcBatchId, $batchId]
            );
            foreach ($packedRows as $packedRow) {
                $previouslyPackedByVariant[(int)$packedRow['variant_id']] = (int)$packedRow['packed_qty'];
            }
        }

        $fabricMap = [];
        $rolls = [];
        if ($progId > 0) {
            $rolls = Database::fetchAll(
                "SELECT ppr.production_program_id, ppr.seller_roll_id, ppr.roll_color, ppr.roll_design,
                        sr.roll_number, sr.color as sr_color, sr.design_name as sr_design, sr.photo_url,
                        s.name as seller_name
                 FROM production_program_rolls ppr
                 LEFT JOIN seller_rolls sr ON ppr.seller_roll_id = sr.id
                 LEFT JOIN sellers s ON (ppr.seller_id = s.id OR sr.seller_id = s.id)
                 WHERE ppr.production_program_id = ?",
                [$progId]
            );

            if (empty($rolls)) {
                $singleRoll = Database::fetchOne(
                    "SELECT pp.seller_roll_id, sr.roll_number, sr.color as sr_color, sr.design_name as sr_design, sr.photo_url,
                            s.name as seller_name
                     FROM production_programs pp
                     LEFT JOIN seller_rolls sr ON pp.seller_roll_id = sr.id
                     LEFT JOIN sellers s ON (pp.seller_id = s.id OR sr.seller_id = s.id)
                     WHERE pp.id = ? AND pp.seller_roll_id IS NOT NULL",
                    [$progId]
                );
                if ($singleRoll) {
                    $rolls[] = $singleRoll;
                }
            }

            if (empty($rolls)) {
                $cuttingRolls = Database::fetchAll(
                    "SELECT cbr.seller_roll_id, sr.roll_number, sr.color as sr_color, sr.design_name as sr_design, sr.photo_url,
                            s.name as seller_name
                     FROM cutting_batches cb
                     JOIN cutting_batch_rolls cbr ON cb.id = cbr.cutting_batch_id
                     JOIN seller_rolls sr ON cbr.seller_roll_id = sr.id
                     LEFT JOIN sellers s ON sr.seller_id = s.id
                     WHERE cb.program_id = ?",
                    [$progId]
                );
                if (!empty($cuttingRolls)) {
                    $rolls = $cuttingRolls;
                }
            }

            $firstRoll = null;
            foreach ($rolls as $r) {
                $rollPhoto = !empty($r['photo_url']) ? $r['photo_url'] : null;
                $rollData = [
                    'roll_number' => $r['roll_number'] ?? null,
                    'design_name' => $r['roll_design'] ?: ($r['sr_design'] ?? null),
                    'seller_name' => $r['seller_name'] ?? null,
                    'photo_url'   => $rollPhoto,
                ];
                if ($firstRoll === null) {
                    $firstRoll = $rollData;
                }
                $colKey = strtoupper(trim($r['roll_color'] ?: ($r['sr_color'] ?? '')));
                if ($colKey && !isset($fabricMap[$colKey])) {
                    $fabricMap[$colKey] = $rollData;
                }
            }
        }

        // Resolve production QR code tag ranges and piece tags from QC and Label Batches
        $qcBatchId = (int)($batch['qc_batch_id'] ?? 0);
        $qcItems = [];
        $piecesData = [];
        $labelItems = [];

        if ($qcBatchId > 0) {
            $qbRow = Database::fetchOne("SELECT id, pieces_data, label_batch_id FROM qc_batches WHERE id = ?", [$qcBatchId]);
            if ($qbRow) {
                $qcItems = Database::fetchAll("SELECT * FROM qc_items WHERE qc_batch_id = ?", [$qcBatchId]);
                $rawPiecesData = (string)($qbRow['pieces_data'] ?? '');
                $piecesData = json_decode($rawPiecesData, true);
                if (!is_array($piecesData)) {
                    $piecesData = json_decode(html_entity_decode($rawPiecesData, ENT_QUOTES, 'UTF-8'), true);
                }
                if (!is_array($piecesData)) {
                    $piecesData = json_decode(stripslashes($rawPiecesData), true);
                }
                if (!is_array($piecesData)) {
                    $piecesData = [];
                }
                $lblBatchId = (int)($qbRow['label_batch_id'] ?? 0);
                if ($lblBatchId > 0) {
                    $labelItems = Database::fetchAll("SELECT * FROM label_items WHERE label_batch_id = ?", [$lblBatchId]);
                }
            }
        }
        if (empty($labelItems) && $progId > 0) {
            $labelItems = Database::fetchAll("SELECT li.* FROM label_items li JOIN label_batches lb ON lb.id = li.label_batch_id WHERE lb.program_id = ? ORDER BY lb.id DESC", [$progId]);
        }

        foreach ($items as &$it) {
            $c = strtoupper(trim($it['color_name'] ?? ''));
            $matched = null;
            if ($c && isset($fabricMap[$c])) {
                $matched = $fabricMap[$c];
            } elseif (count($rolls) === 1 && !empty($firstRoll)) {
                $matched = $firstRoll;
            }

            if ($matched) {
                $it['fabric_photo']       = !empty($matched['photo_url']) ? $matched['photo_url'] : ($it['variant_image'] ?? null);
                $it['fabric_design']      = $matched['design_name'] ?? null;
                $it['fabric_roll_number'] = $matched['roll_number'] ?? null;
                $it['fabric_seller_name'] = $matched['seller_name'] ?? null;
            } else {
                $it['fabric_photo']       = $it['variant_image'] ?? null;
                $it['fabric_design']      = null;
                $it['fabric_roll_number'] = null;
                $it['fabric_seller_name'] = null;
            }

            $vid = (int)$it['variant_id'];
            $sizeName = trim($it['size_name'] ?? '');
            $colorName = trim($it['color_name'] ?? '');
            $qty = (int)$it['quantity'];

            $matchedQcItem = null;
            foreach ($qcItems as $qi) {
                if (!empty($qi['variant_id']) && (int)$qi['variant_id'] === $vid) {
                    $matchedQcItem = $qi;
                    break;
                }
                if (strcasecmp(trim($qi['size_name'] ?? ''), $sizeName) === 0 && strcasecmp(trim($qi['color_name'] ?? ''), $colorName) === 0) {
                    $matchedQcItem = $qi;
                    break;
                }
            }

            $matchedLabelItem = null;
            foreach ($labelItems as $li) {
                if (!empty($li['variant_id']) && (int)$li['variant_id'] === $vid) {
                    $matchedLabelItem = $li;
                    break;
                }
                if (strcasecmp(trim($li['size_name'] ?? ''), $sizeName) === 0 && strcasecmp(trim($li['color_name'] ?? ''), $colorName) === 0) {
                    $matchedLabelItem = $li;
                    break;
                }
            }

            $startSeq = $matchedLabelItem ? (int)$matchedLabelItem['start_seq'] : null;
            $endSeq = $matchedLabelItem ? (int)$matchedLabelItem['end_seq'] : null;
            $startCode = $matchedLabelItem['start_code'] ?? ($matchedQcItem['tag_code'] ?? null);
            $endCode = $matchedLabelItem['end_code'] ?? null;

            $variantPieces = [];
            if (!empty($piecesData)) {
                foreach ($piecesData as $p) {
                    $tag = trim($p['tag_code'] ?? '');
                    if (!$tag) continue;
                    $isMatch = false;
                    if ($matchedQcItem && isset($p['item_id']) && (int)$p['item_id'] === (int)$matchedQcItem['id']) {
                        $isMatch = true;
                    } elseif ($startSeq && $endSeq && preg_match('/(\d+)$/', $tag, $m)) {
                        $seq = (int)$m[1];
                        if ($seq >= $startSeq && $seq <= $endSeq) {
                            $isMatch = true;
                        }
                    }
                    if ($isMatch) {
                        $variantPieces[] = [
                            'tag_code' => $tag,
                            'status'   => $p['status'] ?? 'APPROVED',
                        ];
                    }
                }
            }

            if (empty($variantPieces) && $startCode && $startSeq) {
                $prefix = preg_match('/^([A-Z0-9]+?)(\d{2,4})$/i', $startCode, $m) ? $m[1] : substr($startCode, 0, -2);
                $calcEndSeq = $endSeq ?: ($startSeq + $qty - 1);
                for ($s = $startSeq; $s <= $calcEndSeq; $s++) {
                    $variantPieces[] = [
                        'tag_code' => $prefix . sprintf('%02d', $s),
                        'status'   => 'APPROVED',
                    ];
                }
            }

            if (!empty($variantPieces)) {
                if (!$startCode) $startCode = $variantPieces[0]['tag_code'];
                if (!$endCode) $endCode = end($variantPieces)['tag_code'];
                if (!$startSeq && preg_match('/(\d+)$/', $startCode, $m)) $startSeq = (int)$m[1];
                if (!$endSeq && preg_match('/(\d+)$/', $endCode, $m)) $endSeq = (int)$m[1];
            }

            $it['start_seq']   = $startSeq;
            $it['end_seq']     = $endSeq;
            $it['start_code']  = $startCode;
            $it['end_code']    = $endCode;
            $it['piece_tags']  = !empty($variantPieces) ? array_column($variantPieces, 'tag_code') : [];
            $approvedPieceTags = [];
            foreach ($variantPieces as $piece) {
                if (strtoupper((string)($piece['status'] ?? 'APPROVED')) === 'APPROVED') {
                    $approvedPieceTags[] = $piece['tag_code'];
                }
            }
            $previouslyPacked = $previouslyPackedByVariant[$vid] ?? 0;
            $outwardStart = min($previouslyPacked, count($approvedPieceTags));
            $outwardQty = min(max(0, $qty), count($approvedPieceTags) - $outwardStart);
            $it['outward_piece_tags'] = array_slice($approvedPieceTags, $outwardStart, $outwardQty);
            $storedPieceTags = json_decode((string)($it['packed_piece_tags'] ?? ''), true);
            if (is_array($storedPieceTags)) {
                $it['outward_piece_tags'] = array_values(array_filter(array_map(
                    static fn($tag) => trim((string)$tag),
                    $storedPieceTags
                ), static fn($tag) => $tag !== ''));
            }

            $recTags = !empty($it['received_qr_codes']) ? array_filter(array_map('trim', explode(',', $it['received_qr_codes']))) : [];
            $dmgTags = !empty($it['damaged_qr_codes']) ? array_filter(array_map('trim', explode(',', $it['damaged_qr_codes']))) : [];
            $it['received_tags'] = array_values($recTags);
            $it['damaged_tags']  = array_values($dmgTags);
        }
        unset($it);

        return $items;
    }
}
