<?php
/**
 * Production Service
 * Central business logic engine for the Production Module.
 * Enforces production lifecycle workflow, status transitions, and data integrity.
 */

require_once BASE_PATH . '/app/Models/ProductionProgram.php';
require_once BASE_PATH . '/app/Models/ProductionProgramItem.php';
require_once BASE_PATH . '/app/Models/CuttingBatch.php';
require_once BASE_PATH . '/app/Models/CuttingItem.php';
require_once BASE_PATH . '/app/Models/StitchingBatch.php';
require_once BASE_PATH . '/app/Models/StitchingItem.php';
require_once BASE_PATH . '/app/Models/LabelBatch.php';
require_once BASE_PATH . '/app/Models/LabelItem.php';
require_once BASE_PATH . '/app/Models/QCBatch.php';
require_once BASE_PATH . '/app/Models/QCItem.php';
require_once BASE_PATH . '/app/Models/ProductionBatch.php';
require_once BASE_PATH . '/app/Models/ProductionBatchItem.php';
require_once BASE_PATH . '/app/Models/ProductionOutward.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class ProductionService {

    /**
     * Generate unique sequential number with prefix.
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

    /**
     * Get the next continuous global sequence for fabric rolls in a given month/year.
     * Format: FB{MM}{YY}{count} (e.g., FB102601)
     */
    public static function getNextFabricRollSequence(int $qty = 1, ?string $date = null): array {
        $timestamp = !empty($date) ? strtotime($date) : time();
        $month = date('m', $timestamp);
        $year = date('y', $timestamp);
        $prefix = 'FB' . $month . $year;

        $row = Database::fetchOne(
            "SELECT MAX(end_seq) as max_seq FROM seller_rolls WHERE start_code LIKE ? OR qr_code LIKE ?",
            [$prefix . '%', $prefix . '%']
        );
        $startSeq = !empty($row['max_seq']) ? ((int)$row['max_seq'] + 1) : 1;
        $qty = max(1, $qty);
        $endSeq = $startSeq + $qty - 1;

        $startCode = sprintf('%s%02d', $prefix, $startSeq);
        $endCode = sprintf('%s%02d', $prefix, $endSeq);
        $qrCode = ($qty > 1) ? ($startCode . '-' . $endCode) : $startCode;

        $codes = [];
        for ($i = 0; $i < $qty; $i++) {
            $s = $startSeq + $i;
            $codes[] = [
                'seq'  => $s,
                'code' => sprintf('%s%02d', $prefix, $s),
            ];
        }

        return [
            'prefix'     => $prefix,
            'start_seq'  => $startSeq,
            'end_seq'    => $endSeq,
            'start_code' => $startCode,
            'end_code'   => $endCode,
            'qr_code'    => $qrCode,
            'codes'      => $codes,
        ];
    }

    /**
     * Create a complete production program with variant items.
     */
    public static function createProgram(array $programData, array $items, int $userId): int {
        Database::beginTransaction();
        try {
            if (empty($programData['program_number'])) {
                $programData['program_number'] = self::generateSequenceNumber('production_programs', 'program_number', 'PRG');
            }
            $programData['created_by'] = $userId;

            $totalPlanned = (int)($programData['planned_quantity'] ?? 0);
            if (!empty($items)) {
                $calcPlanned = 0;
                foreach ($items as $item) {
                    $calcPlanned += (int)($item['planned_quantity'] ?? 0);
                }
                if ($calcPlanned > 0) {
                    $totalPlanned = $calcPlanned;
                }
            }
            $programData['planned_quantity'] = $totalPlanned;

            $programId = ProductionProgram::create($programData);

            foreach ($items as $item) {
                if ((int)($item['planned_quantity'] ?? 0) > 0) {
                    ProductionProgramItem::create([
                        'program_id'       => $programId,
                        'variant_id'       => (int)$item['variant_id'],
                        'planned_quantity' => (int)$item['planned_quantity'],
                        'completed_quantity' => 0,
                    ]);
                }
            }

            AuditService::log('production', 'create', 'production_program', $programId, null, $programData);
            Database::commit();
            return $programId;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Update a production program and its items.
     */
    public static function updateProgram(int $id, array $programData, ?array $items = null): bool {
        Database::beginTransaction();
        try {
            $existing = ProductionProgram::find($id);
            if (!$existing) {
                throw new Exception("Program not found: $id");
            }

            if ($items !== null) {
                $totalPlanned = 0;
                foreach ($items as $item) {
                    $totalPlanned += (int)($item['planned_quantity'] ?? 0);
                }
                $programData['planned_quantity'] = $totalPlanned;

                // Replace items
                ProductionProgramItem::deleteByProgram($id);
                foreach ($items as $item) {
                    if ((int)($item['planned_quantity'] ?? 0) > 0) {
                        ProductionProgramItem::create([
                            'program_id'       => $id,
                            'variant_id'       => (int)$item['variant_id'],
                            'planned_quantity' => (int)$item['planned_quantity'],
                            'completed_quantity' => (int)($item['completed_quantity'] ?? 0),
                        ]);
                    }
                }
            }

            ProductionProgram::update($id, $programData);
            AuditService::log('production', 'edit', 'production_program', $id, $existing, $programData);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Approve production program.
     */
    public static function approveProgram(int $id, int $userId): bool {
        $program = ProductionProgram::find($id);
        if (!$program) {
            throw new Exception("Program not found: $id");
        }
        if ($program['status'] !== 'DRAFT') {
            throw new Exception("Only DRAFT programs can be approved.");
        }

        $result = ProductionProgram::approve($id, $userId);
        if ($result) {
            AuditService::log('production', 'approve', 'production_program', $id, ['status' => 'DRAFT'], ['status' => 'APPROVED']);
        }
        return $result;
    }

    /**
     * Create Cutting Batch from Program.
     */
    public static function createCuttingBatch(array $batchData, array $items, int $userId): int {
        Database::beginTransaction();
        try {
            $programId = (int)($batchData['program_id'] ?? 0);
            $program = ProductionProgram::find($programId);
            if (!$program) {
                throw new Exception("Production program not found.");
            }

            // Prevent duplicate cutting of the same program
            $existingCutting = Database::fetchOne(
                "SELECT id, batch_number FROM cutting_batches WHERE program_id = ?",
                [$programId]
            );
            if ($existingCutting) {
                $progNum = $program['program_number'] ?? ('#' . $programId);
                throw new Exception("Validation Error: Production Program ({$progNum}) has already been assigned to Cutting Batch {$existingCutting['batch_number']}. Duplicate cutting of the same program is not allowed.");
            }

            if (empty($batchData['batch_number'])) {
                $batchData['batch_number'] = self::generateSequenceNumber('cutting_batches', 'batch_number', 'CUT');
            }

            $batchId = CuttingBatch::create($batchData);

            foreach ($items as $item) {
                CuttingItem::create([
                    'cutting_batch_id'   => $batchId,
                    'variant_id'         => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                    'category_id'        => !empty($item['category_id']) ? (int)$item['category_id'] : null,
                    'subcategory_id'     => !empty($item['subcategory_id']) ? (int)$item['subcategory_id'] : null,
                    'sub_subcategory_id' => !empty($item['sub_subcategory_id']) ? (int)$item['sub_subcategory_id'] : null,
                    'catalog_hierarchy'  => $item['catalog_hierarchy'] ?? null,
                    'size_name'          => $item['size_name'] ?? null,
                    'color_name'         => $item['color_name'] ?? null,
                    'planned_qty'        => (int)($item['planned_qty'] ?? 0),
                    'cut_qty'            => (int)($item['cut_qty'] ?? 0),
                    'rejected_qty'       => (int)($item['rejected_qty'] ?? 0),
                    'wastage_qty'        => (int)($item['wastage_qty'] ?? 0),
                ]);
            }

            CuttingBatch::recalculateTotals($batchId);

            if ($program['status'] === 'DRAFT' || $program['status'] === 'APPROVED' || $program['status'] === 'SCHEDULED') {
                ProductionProgram::updateStatus($program['id'], 'IN_PROGRESS');
            }

            AuditService::log('production', 'create', 'cutting_batches', $batchId, null, $batchData);
            Database::commit();
            return $batchId;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Update Cutting Batch and Items.
     */
    public static function updateCuttingBatch(int $id, array $batchData, array $items): bool {
        Database::beginTransaction();
        try {
            $existing = CuttingBatch::find($id);
            if (!$existing) {
                throw new Exception("Cutting batch not found: $id");
            }

            CuttingBatch::update($id, $batchData);

            CuttingItem::deleteByBatch($id);
            foreach ($items as $item) {
                CuttingItem::create([
                    'cutting_batch_id'   => $id,
                    'variant_id'         => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                    'category_id'        => !empty($item['category_id']) ? (int)$item['category_id'] : null,
                    'subcategory_id'     => !empty($item['subcategory_id']) ? (int)$item['subcategory_id'] : null,
                    'sub_subcategory_id' => !empty($item['sub_subcategory_id']) ? (int)$item['sub_subcategory_id'] : null,
                    'catalog_hierarchy'  => $item['catalog_hierarchy'] ?? null,
                    'size_name'          => $item['size_name'] ?? null,
                    'color_name'         => $item['color_name'] ?? null,
                    'planned_qty'        => (int)($item['planned_qty'] ?? 0),
                    'cut_qty'            => (int)($item['cut_qty'] ?? 0),
                    'rejected_qty'       => (int)($item['rejected_qty'] ?? 0),
                    'wastage_qty'        => (int)($item['wastage_qty'] ?? 0),
                ]);
            }

            CuttingBatch::recalculateTotals($id);
            AuditService::log('production', 'edit', 'cutting_batches', $id, $existing, $batchData);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Create Stitching Batch from Cutting Output.
     */
    public static function createStitchingBatch(array $batchData, array $items, int $userId): int {
        Database::beginTransaction();
        try {
            if (empty($batchData['batch_number'])) {
                $batchData['batch_number'] = self::generateSequenceNumber('stitching_batches', 'batch_number', 'STCH');
            }

            if (!empty($batchData['cutting_batch_id'])) {
                $cuttingBatch = CuttingBatch::find((int)$batchData['cutting_batch_id']);
                if ($cuttingBatch && empty($batchData['program_id'])) {
                    $batchData['program_id'] = $cuttingBatch['program_id'];
                }
            }

            if (empty($batchData['program_id'])) {
                throw new Exception("Production Program is required.");
            }

            // Prevent duplicate stitching
            if (!empty($batchData['cutting_batch_id'])) {
                $existingStitching = Database::fetchOne(
                    "SELECT id, batch_number FROM stitching_batches WHERE cutting_batch_id = ? AND (is_active = 1 OR is_active IS NULL) AND status != 'CANCELLED'",
                    [(int)$batchData['cutting_batch_id']]
                );
                if ($existingStitching) {
                    throw new Exception("Validation Error: This Cutting Batch has already been assigned to Stitching Batch {$existingStitching['batch_number']}. Duplicate stitching is not allowed.");
                }
            }
            if (empty($batchData['cutting_batch_id']) && !empty($batchData['program_id'])) {
                $existingStitchingProg = Database::fetchOne(
                    "SELECT id, batch_number FROM stitching_batches WHERE program_id = ? AND cutting_batch_id IS NULL AND (is_active = 1 OR is_active IS NULL) AND status != 'CANCELLED'",
                    [(int)$batchData['program_id']]
                );
                if ($existingStitchingProg) {
                    throw new Exception("Validation Error: This Production Program has already been assigned to Stitching Batch {$existingStitchingProg['batch_number']}. Duplicate stitching is not allowed.");
                }
            }

            $batchId = StitchingBatch::create($batchData);

            foreach ($items as $item) {
                $itemStitchedQty = (int)($item['stitched_qty'] ?? 0);
                $itemWeight = (float)($item['weight'] ?? 0.00);
                $itemUnitWeight = isset($item['weight_per_piece']) ? (float)$item['weight_per_piece'] : ($itemStitchedQty > 0 ? round($itemWeight / $itemStitchedQty, 3) : 0.000);

                StitchingItem::create([
                    'stitching_batch_id' => $batchId,
                    'variant_id'         => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                    'category_id'        => !empty($item['category_id']) ? (int)$item['category_id'] : null,
                    'subcategory_id'     => !empty($item['subcategory_id']) ? (int)$item['subcategory_id'] : null,
                    'sub_subcategory_id' => !empty($item['sub_subcategory_id']) ? (int)$item['sub_subcategory_id'] : null,
                    'catalog_hierarchy'  => $item['catalog_hierarchy'] ?? null,
                    'size_name'          => $item['size_name'] ?? null,
                    'color_name'         => $item['color_name'] ?? null,
                    'received_qty'       => (int)($item['received_qty'] ?? 0),
                    'stitched_qty'       => $itemStitchedQty,
                    'weight_per_piece'   => $itemUnitWeight,
                    'weight'             => $itemWeight,
                    'base_price'         => (float)($item['base_price'] ?? 0.00),
                    'discount_price'     => (float)($item['discount_price'] ?? 0.00),
                    'rejected_qty'       => (int)($item['rejected_qty'] ?? 0),
                    'rework_qty'         => (int)($item['rework_qty'] ?? 0),
                ]);
            }

            StitchingBatch::recalculateTotals($batchId);
            AuditService::log('production', 'create', 'stitching_batch', $batchId, null, $batchData);
            Database::commit();
            return $batchId;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Update Stitching Batch.
     */
    public static function updateStitchingBatch(int $id, array $batchData, array $items): bool {
        Database::beginTransaction();
        try {
            $existing = StitchingBatch::find($id);
            if (!$existing) {
                throw new Exception("Stitching batch not found: $id");
            }

            StitchingBatch::update($id, $batchData);

            StitchingItem::deleteByBatch($id);
            foreach ($items as $item) {
                $itemStitchedQty = (int)($item['stitched_qty'] ?? 0);
                $itemWeight = (float)($item['weight'] ?? 0.00);
                $itemUnitWeight = isset($item['weight_per_piece']) ? (float)$item['weight_per_piece'] : ($itemStitchedQty > 0 ? round($itemWeight / $itemStitchedQty, 3) : 0.000);

                StitchingItem::create([
                    'stitching_batch_id' => $id,
                    'variant_id'         => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                    'category_id'        => !empty($item['category_id']) ? (int)$item['category_id'] : null,
                    'subcategory_id'     => !empty($item['subcategory_id']) ? (int)$item['subcategory_id'] : null,
                    'sub_subcategory_id' => !empty($item['sub_subcategory_id']) ? (int)$item['sub_subcategory_id'] : null,
                    'catalog_hierarchy'  => $item['catalog_hierarchy'] ?? null,
                    'size_name'          => $item['size_name'] ?? null,
                    'color_name'         => $item['color_name'] ?? null,
                    'received_qty'       => (int)($item['received_qty'] ?? 0),
                    'stitched_qty'       => $itemStitchedQty,
                    'weight_per_piece'   => $itemUnitWeight,
                    'weight'             => $itemWeight,
                    'base_price'         => (float)($item['base_price'] ?? 0.00),
                    'discount_price'     => (float)($item['discount_price'] ?? 0.00),
                    'rejected_qty'       => (int)($item['rejected_qty'] ?? 0),
                    'rework_qty'         => (int)($item['rework_qty'] ?? 0),
                ]);
            }

            StitchingBatch::recalculateTotals($id);
            AuditService::log('production', 'edit', 'stitching_batch', $id, $existing, $batchData);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Create Label Batch.
     */
    public static function createLabelBatch(array $batchData, array $items, int $userId): int {
        Database::beginTransaction();
        try {
            if (!empty($batchData['stitching_batch_id'])) {
                $existingLabel = Database::fetchOne(
                    "SELECT id, batch_number FROM label_batches WHERE stitching_batch_id = ?",
                    [(int)$batchData['stitching_batch_id']]
                );
                if ($existingLabel) {
                    throw new Exception("Validation Error: This Stitching Batch has already been assigned to Label Batch {$existingLabel['batch_number']}. Duplicate label generation is not allowed.");
                }
            }
            if (!empty($batchData['program_id'])) {
                $existingLabelProg = Database::fetchOne(
                    "SELECT id, batch_number FROM label_batches WHERE program_id = ?",
                    [(int)$batchData['program_id']]
                );
                if ($existingLabelProg) {
                    throw new Exception("Validation Error: This Production Program has already been assigned to Label Batch {$existingLabelProg['batch_number']}. Duplicate label generation is not allowed.");
                }
            }

            if (empty($batchData['batch_number'])) {
                $batchData['batch_number'] = self::generateSequenceNumber('label_batches', 'batch_number', 'LBL');
            }

            $batchId = LabelBatch::create($batchData);

            $catalogRunningSeq = [];
            foreach ($items as $item) {
                $catId = !empty($item['category_id']) ? (int)$item['category_id'] : null;
                $subcatId = !empty($item['subcategory_id']) ? (int)$item['subcategory_id'] : null;
                $subSubcatId = !empty($item['sub_subcategory_id']) ? (int)$item['sub_subcategory_id'] : null;
                $catHierarchy = $item['catalog_hierarchy'] ?? null;
                $qty = (int)($item['required_qty'] ?? ($item['printed_qty'] ?? 1));

                $catKey = ($catId ?? 0) . '_' . ($subcatId ?? 0) . '_' . ($subSubcatId ?? 0);
                if (!isset($catalogRunningSeq[$catKey])) {
                    $lastDbSeq = LabelItem::getLastSequenceForCatalog($catId, $subcatId, $subSubcatId, $catHierarchy);
                    if (!empty($item['start_seq']) && (int)$item['start_seq'] > $lastDbSeq) {
                        $catalogRunningSeq[$catKey] = (int)$item['start_seq'];
                    } else {
                        $catalogRunningSeq[$catKey] = $lastDbSeq + 1;
                    }
                }

                $startSeq = (!empty($item['start_seq']) && (int)$item['start_seq'] >= $catalogRunningSeq[$catKey]) 
                    ? (int)$item['start_seq'] 
                    : $catalogRunningSeq[$catKey];
                $endSeq = (!empty($item['end_seq']) && (int)$item['end_seq'] >= $startSeq) 
                    ? (int)$item['end_seq'] 
                    : ($startSeq + max($qty, 1) - 1);
                $catalogRunningSeq[$catKey] = $endSeq + 1;

                $startCode = LabelItem::generateLabelCode($startSeq, $catHierarchy, $item);
                $endCode = LabelItem::generateLabelCode($endSeq, $catHierarchy, $item);

                LabelItem::create([
                    'label_batch_id'     => $batchId,
                    'variant_id'         => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                    'category_id'        => $catId,
                    'subcategory_id'     => $subcatId,
                    'sub_subcategory_id' => $subSubcatId,
                    'catalog_hierarchy'  => $catHierarchy,
                    'size_name'          => $item['size_name'] ?? null,
                    'color_name'         => $item['color_name'] ?? null,
                    'base_price'         => (float)($item['base_price'] ?? 0.00),
                    'discount_price'     => (float)($item['discount_price'] ?? 0.00),
                    'required_qty'       => (int)($item['required_qty'] ?? 0),
                    'printed_qty'        => (int)($item['printed_qty'] ?? 0),
                    'used_qty'           => (int)($item['used_qty'] ?? 0),
                    'damaged_qty'        => (int)($item['damaged_qty'] ?? 0),
                    'start_seq'          => $startSeq,
                    'end_seq'            => $endSeq,
                    'start_code'         => $startCode,
                    'end_code'           => $endCode,
                ]);
            }

            LabelBatch::recalculateTotals($batchId);
            AuditService::log('production', 'create', 'label_batch', $batchId, null, $batchData);
            Database::commit();
            return $batchId;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Update Label Batch.
     */
    public static function updateLabelBatch(int $id, array $batchData, array $items): bool {
        Database::beginTransaction();
        try {
            $existing = LabelBatch::find($id);
            if (!$existing) {
                throw new Exception("Label batch not found: $id");
            }

            LabelBatch::update($id, $batchData);

            LabelItem::deleteByBatch($id);
            $catalogRunningSeq = [];
            foreach ($items as $item) {
                $catId = !empty($item['category_id']) ? (int)$item['category_id'] : null;
                $subcatId = !empty($item['subcategory_id']) ? (int)$item['subcategory_id'] : null;
                $subSubcatId = !empty($item['sub_subcategory_id']) ? (int)$item['sub_subcategory_id'] : null;
                $catHierarchy = $item['catalog_hierarchy'] ?? null;
                $qty = (int)($item['required_qty'] ?? ($item['printed_qty'] ?? 1));

                $catKey = ($catId ?? 0) . '_' . ($subcatId ?? 0) . '_' . ($subSubcatId ?? 0);
                if (!isset($catalogRunningSeq[$catKey])) {
                    $lastDbSeq = LabelItem::getLastSequenceForCatalog($catId, $subcatId, $subSubcatId, $catHierarchy, $id);
                    if (!empty($item['start_seq']) && (int)$item['start_seq'] > $lastDbSeq) {
                        $catalogRunningSeq[$catKey] = (int)$item['start_seq'];
                    } else {
                        $catalogRunningSeq[$catKey] = $lastDbSeq + 1;
                    }
                }

                $startSeq = (!empty($item['start_seq']) && (int)$item['start_seq'] >= $catalogRunningSeq[$catKey]) 
                    ? (int)$item['start_seq'] 
                    : $catalogRunningSeq[$catKey];
                $endSeq = (!empty($item['end_seq']) && (int)$item['end_seq'] >= $startSeq) 
                    ? (int)$item['end_seq'] 
                    : ($startSeq + max($qty, 1) - 1);
                $catalogRunningSeq[$catKey] = $endSeq + 1;

                $startCode = LabelItem::generateLabelCode($startSeq, $catHierarchy, $item);
                $endCode = LabelItem::generateLabelCode($endSeq, $catHierarchy, $item);

                LabelItem::create([
                    'label_batch_id'     => $id,
                    'variant_id'         => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                    'category_id'        => $catId,
                    'subcategory_id'     => $subcatId,
                    'sub_subcategory_id' => $subSubcatId,
                    'catalog_hierarchy'  => $catHierarchy,
                    'size_name'          => $item['size_name'] ?? null,
                    'color_name'         => $item['color_name'] ?? null,
                    'base_price'         => (float)($item['base_price'] ?? 0.00),
                    'discount_price'     => (float)($item['discount_price'] ?? 0.00),
                    'required_qty'       => (int)($item['required_qty'] ?? 0),
                    'printed_qty'        => (int)($item['printed_qty'] ?? 0),
                    'used_qty'           => (int)($item['used_qty'] ?? 0),
                    'damaged_qty'        => (int)($item['damaged_qty'] ?? 0),
                    'start_seq'          => $startSeq,
                    'end_seq'            => $endSeq,
                    'start_code'         => $startCode,
                    'end_code'           => $endCode,
                ]);
            }

            LabelBatch::recalculateTotals($id);
            AuditService::log('production', 'edit', 'label_batch', $id, $existing, $batchData);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Delete Label Batch.
     */
    public static function deleteLabelBatch(int $id): bool {
        Database::beginTransaction();
        try {
            $existing = LabelBatch::find($id);
            if (!$existing) {
                throw new Exception("Label batch not found: $id");
            }

            LabelBatch::delete($id);
            AuditService::log('production', 'delete', 'label_batch', $id, $existing, null);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Create QC Batch.
     */
    public static function createQCBatch(array $batchData, array $items, int $userId): int {
        Database::beginTransaction();
        try {
            if (!empty($batchData['stitching_batch_id'])) {
                $existingQC = Database::fetchOne(
                    "SELECT id FROM qc_batches WHERE stitching_batch_id = ?",
                    [(int)$batchData['stitching_batch_id']]
                );
                if ($existingQC) {
                    throw new Exception("Validation Error: This Stitching Batch has already undergone QC Inspection (QC #{$existingQC['id']}). Duplicate QC inspection is not allowed.");
                }
            }
            if (!empty($batchData['label_batch_id'])) {
                $existingQC = Database::fetchOne(
                    "SELECT id FROM qc_batches WHERE label_batch_id = ?",
                    [(int)$batchData['label_batch_id']]
                );
                if ($existingQC) {
                    throw new Exception("Validation Error: This Label Batch has already undergone QC Inspection (QC #{$existingQC['id']}). Duplicate QC inspection is not allowed.");
                }
            }
            if (!empty($batchData['program_id'])) {
                $existingQCProg = Database::fetchOne(
                    "SELECT id FROM qc_batches WHERE program_id = ?",
                    [(int)$batchData['program_id']]
                );
                if ($existingQCProg) {
                    throw new Exception("Validation Error: This Production Program has already undergone QC Inspection (QC #{$existingQCProg['id']}). Duplicate QC inspection is not allowed.");
                }
            }

            $batchData['inspector_id'] = $batchData['inspector_id'] ?? $userId;
            $batchId = QCBatch::create($batchData);

            foreach ($items as $item) {
                QCItem::create([
                    'qc_batch_id'   => $batchId,
                    'variant_id'    => (int)$item['variant_id'],
                    'inspected_qty' => (int)($item['inspected_qty'] ?? 0),
                    'passed_qty'    => (int)($item['passed_qty'] ?? 0),
                    'rejected_qty'  => (int)($item['rejected_qty'] ?? 0),
                    'rework_qty'    => (int)($item['rework_qty'] ?? 0),
                    'defect_notes'  => $item['defect_notes'] ?? null,
                ]);
            }

            QCBatch::recalculateTotals($batchId);
            AuditService::log('production', 'create', 'qc_batch', $batchId, null, $batchData);
            Database::commit();
            return $batchId;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Inspect & Update QC Batch.
     */
    public static function updateQC(int $id, array $batchData, array $items): bool {
        Database::beginTransaction();
        try {
            $existing = QCBatch::find($id);
            if (!$existing) {
                throw new Exception("QC batch not found: $id");
            }

            QCBatch::update($id, $batchData);

            QCItem::deleteByBatch($id);
            foreach ($items as $item) {
                QCItem::create([
                    'qc_batch_id'   => $id,
                    'variant_id'    => (int)$item['variant_id'],
                    'inspected_qty' => (int)($item['inspected_qty'] ?? 0),
                    'passed_qty'    => (int)($item['passed_qty'] ?? 0),
                    'rejected_qty'  => (int)($item['rejected_qty'] ?? 0),
                    'rework_qty'    => (int)($item['rework_qty'] ?? 0),
                    'defect_notes'  => $item['defect_notes'] ?? null,
                ]);
            }

            QCBatch::recalculateTotals($id);
            AuditService::log('production', 'edit', 'qc_batch', $id, $existing, $batchData);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Resolve approved QR tags by QC item, including legacy label sequence tags.
     */
    private static function getApprovedQCPieceTags(int $qcBatchId): array {
        if ($qcBatchId <= 0) {
            return [];
        }

        $qcItems = QCBatch::getItems($qcBatchId);
        $batch = Database::fetchOne("SELECT pieces_data FROM qc_batches WHERE id = ?", [$qcBatchId]);
        $rawPieces = (string)($batch['pieces_data'] ?? '');
        $piecesData = json_decode($rawPieces, true);
        if (!is_array($piecesData)) {
            $piecesData = json_decode(html_entity_decode($rawPieces, ENT_QUOTES, 'UTF-8'), true);
        }
        if (!is_array($piecesData)) {
            $piecesData = json_decode(stripslashes($rawPieces), true);
        }

        $savedApprovedByItem = [];
        if (is_array($piecesData)) {
            foreach ($piecesData as $piece) {
                if (!is_array($piece) || strtoupper((string)($piece['status'] ?? '')) !== 'APPROVED') {
                    continue;
                }
                $itemId = (int)($piece['item_id'] ?? 0);
                $tagCode = trim((string)($piece['tag_code'] ?? ''));
                if ($itemId > 0 && $tagCode !== '') {
                    $savedApprovedByItem[$itemId][] = $tagCode;
                }
            }
        }

        $approvedByItem = [];
        foreach ($qcItems as $item) {
            $itemId = (int)$item['id'];
            $tags = array_values(array_unique($savedApprovedByItem[$itemId] ?? []));
            if (empty($tags)) {
                $startCode = trim((string)($item['label_start_code'] ?? $item['tag_code'] ?? ''));
                $startSeq = (int)($item['label_start_seq'] ?? 0);
                $passedQty = max(0, (int)($item['passed_qty'] ?? 0));
                if ($startSeq > 0 && $passedQty > 0 && preg_match('/^(.+?)(\d+)$/', $startCode, $match)) {
                    $endSeq = (int)($item['label_end_seq'] ?? ($startSeq + $passedQty - 1));
                    $endSeq = min($endSeq, $startSeq + $passedQty - 1);
                    for ($seq = $startSeq; $seq <= $endSeq; $seq++) {
                        $tags[] = $match[1] . str_pad((string)$seq, strlen($match[2]), '0', STR_PAD_LEFT);
                    }
                }
            }
            $approvedByItem[$itemId] = [
                'variant_id' => (int)($item['variant_id'] ?? 0),
                'tags' => $tags,
            ];
        }

        return $approvedByItem;
    }

    public static function getQCPieceTagPositions(int $qcBatchId): array {
        $approvedByItem = self::getApprovedQCPieceTags($qcBatchId);
        $items = QCBatch::getItems($qcBatchId);
        $positions = [];
        $pieceNo = 0;
        foreach ($items as $item) {
            $itemId = (int)$item['id'];
            $passedQty = max(0, (int)($item['passed_qty'] ?? 0));
            $tags = $approvedByItem[$itemId]['tags'] ?? [];
            for ($pieceIndex = 0; $pieceIndex < $passedQty; $pieceIndex++) {
                $pieceNo++;
                if (!empty($tags[$pieceIndex])) {
                    $positions[strtoupper(trim((string)$tags[$pieceIndex]))] = $pieceNo;
                }
            }
        }

        return $positions;
    }

    public static function getAvailableQCPieceTags(int $qcBatchId): array {
        if ($qcBatchId <= 0) {
            return [];
        }

        $approvedByItem = self::getApprovedQCPieceTags($qcBatchId);
        $legacyPackedByVariant = [];
        $assignedTagsByVariant = [];
        $previousRows = Database::fetchAll(
            "SELECT pbi.variant_id, pbi.quantity, pbi.piece_tags
             FROM production_batch_items pbi
             JOIN production_batches pb ON pb.id = pbi.production_batch_id
             WHERE pb.qc_batch_id = ? AND pb.status != 'CANCELLED'
             ORDER BY pb.id ASC, pbi.id ASC",
            [$qcBatchId]
        );
        foreach ($previousRows as $row) {
            $variantId = (int)$row['variant_id'];
            $tags = json_decode((string)($row['piece_tags'] ?? ''), true);
            if (is_array($tags) && !empty($tags)) {
                foreach ($tags as $tag) {
                    $tag = trim((string)$tag);
                    if ($tag !== '') {
                        $assignedTagsByVariant[$variantId][strtoupper($tag)] = true;
                    }
                }
            } else {
                $legacyPackedByVariant[$variantId] = ($legacyPackedByVariant[$variantId] ?? 0) + (int)$row['quantity'];
            }
        }

        $deletedRows = Database::fetchAll("SELECT code FROM production_deleted_label_codes");
        $deletedTags = array_fill_keys(array_map(
            static fn(array $row): string => strtoupper(trim((string)$row['code'])),
            $deletedRows
        ), true);
        $unavailableTagsByItem = self::getUnavailableQCPieceTags($qcBatchId);
        try {
            $unavailableDamageTags = ProductionInventoryDamage::getUnavailableTags($qcBatchId);
            if (method_exists('ProductionInventoryDamage', 'getUnavailableConcernTags')) {
                $concernDamageRow = Database::fetchOne(
                    "SELECT pu.concern_id
                     FROM qc_batches qb
                     JOIN production_programs pp ON pp.id = qb.program_id
                     JOIN production_units pu ON pu.id = pp.production_unit_id
                     WHERE qb.id = ?",
                    [$qcBatchId]
                );
                if (!empty($concernDamageRow['concern_id'])) {
                    $unavailableDamageTags += ProductionInventoryDamage::getUnavailableConcernTags((int)$concernDamageRow['concern_id']);
                }
            }
        } catch (\Throwable $e) {
            error_log('Unable to load Production inventory damage tags: ' . $e->getMessage());
            $unavailableDamageTags = [];
        }

        $availableByItem = [];
        foreach ($approvedByItem as $itemId => $itemData) {
            $variantId = $itemData['variant_id'];
            $legacySkip = $legacyPackedByVariant[$variantId] ?? 0;
            $available = [];
            foreach ($itemData['tags'] as $tag) {
                if ($legacySkip > 0) {
                    $legacySkip--;
                    continue;
                }
                $normalizedTag = strtoupper($tag);
                if (empty($assignedTagsByVariant[$variantId][$normalizedTag])
                    && empty($deletedTags[$normalizedTag])
                    && empty($unavailableTagsByItem[$itemId][$normalizedTag])
                    && empty($unavailableDamageTags[$normalizedTag])) {
                    $available[] = $tag;
                }
            }
            $legacyPackedByVariant[$variantId] = $legacySkip;
            $availableByItem[$itemId] = $available;
        }

        $batchConcernRow = Database::fetchOne(
            "SELECT pu.concern_id AS concern_id
             FROM qc_batches qb
             JOIN production_programs pp ON pp.id = qb.program_id
             JOIN production_units pu ON pu.id = pp.production_unit_id
             WHERE qb.id = ?",
            [$qcBatchId]
        );
        $batchConcernId = (int)($batchConcernRow['concern_id'] ?? 0);
        $returnedRows = $batchConcernId > 0
            ? Database::fetchAll(
                "SELECT ri.qc_item_id, ri.variant_id, ri.new_tag_code
                 FROM production_return_inventory ri
                 JOIN production_bill_returns pbr ON pbr.id = ri.return_id
                 WHERE ri.qc_batch_id = ? AND pbr.concern_id = ? AND ri.status = 'AVAILABLE'",
                [$qcBatchId, $batchConcernId]
            )
            : [];
        foreach ($returnedRows as $row) {
            $itemId = (int)$row['qc_item_id'];
            $variantId = (int)($row['variant_id'] ?? 0);
            $tag = trim((string)$row['new_tag_code']);
            $normalizedTag = strtoupper($tag);
            if ($tag !== ''
                && empty($assignedTagsByVariant[$variantId][$normalizedTag])
                && empty($deletedTags[$normalizedTag])
                && empty($unavailableDamageTags[$normalizedTag])
                && empty($unavailableTagsByItem[$itemId][$normalizedTag])) {
                $availableByItem[$itemId][] = $tag;
            }
        }

        return $availableByItem;
    }

    public static function getUnavailableQCPieceTags(int $qcBatchId): array {
        if ($qcBatchId <= 0) {
            return [];
        }

        $batchConcernRow = Database::fetchOne(
            "SELECT pu.concern_id AS concern_id
             FROM qc_batches qb
             JOIN production_programs pp ON pp.id = qb.program_id
             JOIN production_units pu ON pu.id = pp.production_unit_id
             WHERE qb.id = ?",
            [$qcBatchId]
        );
        $batchConcernId = (int)($batchConcernRow['concern_id'] ?? 0);
        if ($batchConcernId <= 0) {
            return [];
        }

        $rows = Database::fetchAll(
            "SELECT t.qc_item_id, t.tag_code
             FROM production_bill_item_tags t
             JOIN production_bills pb ON pb.id = t.bill_id
             JOIN qc_items qi ON qi.id = t.qc_item_id
             WHERE qi.qc_batch_id = ? AND pb.concern_id = ? AND t.status IN ('BILLED', 'DAMAGED', 'RETURNED')",
            [$qcBatchId, $batchConcernId]
        );
        $tagsByItem = [];
        foreach ($rows as $row) {
            $tagsByItem[(int)$row['qc_item_id']][strtoupper(trim((string)$row['tag_code']))] = true;
        }
        $returnedRows = Database::fetchAll(
            "SELECT ri.qc_item_id, ri.new_tag_code
             FROM production_return_inventory ri
             JOIN production_bill_returns pbr ON pbr.id = ri.return_id
             WHERE ri.qc_batch_id = ? AND pbr.concern_id = ? AND ri.status IN ('BILLED', 'DAMAGED', 'RETIRED')",
            [$qcBatchId, $batchConcernId]
        );
        foreach ($returnedRows as $row) {
            $tagsByItem[(int)$row['qc_item_id']][strtoupper(trim((string)$row['new_tag_code']))] = true;
        }
        return $tagsByItem;
    }

    public static function getUnavailableQCPieceCounts(int $qcBatchId, array $excludedCodes = []): array {
        if ($qcBatchId <= 0) {
            return [];
        }

        $approvedByItem = self::getApprovedQCPieceTags($qcBatchId);
        $unavailableTags = array_fill_keys(array_map(
            static fn($code): string => strtoupper(trim((string)$code)),
            $excludedCodes
        ), true);
        $billedTagsByItem = self::getUnavailableQCPieceTags($qcBatchId);
        $deletedCounts = [];

        foreach (QCBatch::getItems($qcBatchId) as $item) {
            $itemId = (int)$item['id'];
            $tags = $approvedByItem[$itemId]['tags'] ?? [];

            $deletedCounts[$itemId] = count(array_filter(
                $tags,
                static fn(string $tag): bool => isset($unavailableTags[strtoupper($tag)])
                    || isset($billedTagsByItem[$itemId][strtoupper(trim($tag))])
            ));
        }

        return $deletedCounts;
    }

    public static function getDeletedQCPieceCounts(int $qcBatchId, array $deletedCodes = []): array {
        return self::getUnavailableQCPieceCounts($qcBatchId, $deletedCodes);
    }

    /**
     * Load available inwarded Concern / Production Unit inventory grouped by source location for packing.
     */
    public static function getAvailableInwardLocationsForPacking(?int $concernId = null, ?int $scopedUnitId = null): array {
        $concernId = $concernId ?? (function_exists('currentConcernId') ? currentConcernId() : null);
        if (!$concernId || $scopedUnitId === 0) {
            return [];
        }
        if (!class_exists('ProductionBillingService')) {
            require_once BASE_PATH . '/app/Services/ProductionBillingService.php';
        }

        $locations = [];
        foreach (ProductionBillingService::getAvailableInventory(0) as $entry) {
            if (($entry['source_kind'] ?? '') !== 'INWARD') {
                continue;
            }
            $locType = strtoupper(trim((string)($entry['stock_location_type'] ?? '')));
            $locId = (int)($entry['stock_location_id'] ?? 0);
            $variantId = (int)($entry['variant_id'] ?? 0);
            $tags = array_values($entry['available_tags'] ?? []);
            $stockQty = (int)($entry['stock_qty'] ?? 0);
            if (!in_array($locType, ['CONCERN', 'PRODUCTION_UNIT'], true) || $locId <= 0 || $variantId <= 0 || $stockQty <= 0 || !$tags) {
                continue;
            }
            if ($scopedUnitId !== null && $locType === 'PRODUCTION_UNIT' && $locId !== (int)$scopedUnitId) {
                continue;
            }

            $locKey = $locType . ':' . $locId;
            if (!isset($locations[$locKey])) {
                $locations[$locKey] = [
                    'location_key' => $locKey,
                    'location_type' => $locType,
                    'location_id' => $locId,
                    'location_label' => (string)($entry['location_label'] ?? ($locType === 'CONCERN' ? 'Concern Inventory' : 'Production Unit #' . $locId)),
                    'total_qty' => 0,
                    'items' => [],
                ];
            }

            $packableQty = min($stockQty, count($tags));
            $locations[$locKey]['total_qty'] += $packableQty;
            $locations[$locKey]['items'][] = [
                'variant_id' => $variantId,
                'product_name' => (string)($entry['product_name'] ?? 'Finished Product'),
                'sku' => (string)($entry['sku'] ?? ''),
                'size_name' => (string)($entry['size_name'] ?? 'Std'),
                'color_name' => (string)($entry['color_name'] ?? 'Std'),
                'grn_label' => (string)($entry['program_number'] ?? ''),
                'stock_qty' => $stockQty,
                'packable_qty' => $packableQty,
                'available_piece_tags' => $tags,
            ];
        }

        return array_values($locations);
    }

    /**
     * Create Production Batch (Finished Goods Packing).
     */
    public static function createProductionBatch(array $batchData, array $items, int $userId): int {
        Database::beginTransaction();
        try {
            $sourceType = strtoupper(trim((string)($batchData['source_type'] ?? (!empty($batchData['qc_batch_id']) ? 'QC' : 'INWARD'))));
            $concernId = (int)($batchData['concern_id'] ?? (function_exists('currentConcernId') ? currentConcernId() : 0));
            $reservedFromLocation = null;

            if ($sourceType === 'INWARD') {
                $locType = strtoupper(trim((string)($batchData['source_location_type'] ?? '')));
                $locId = (int)($batchData['source_location_id'] ?? 0);
                if (!in_array($locType, ['CONCERN', 'PRODUCTION_UNIT'], true) || $locId <= 0) {
                    throw new Exception('Choose a valid inward inventory location to pack.');
                }
                if (!class_exists('StockService')) {
                    require_once BASE_PATH . '/app/Services/StockService.php';
                }
                StockService::assertLocationBelongsToActiveConcern($locType, $locId);

                $locKey = $locType . ':' . $locId;
                $locBucket = null;
                foreach (self::getAvailableInwardLocationsForPacking($concernId) as $loc) {
                    if ($loc['location_key'] === $locKey) {
                        $locBucket = $loc;
                        break;
                    }
                }
                if (!$locBucket || empty($locBucket['items'])) {
                    throw new Exception('No available inward inventory remains at the selected source location.');
                }

                $availableByVariant = [];
                foreach ($locBucket['items'] as $locItem) {
                    $availableByVariant[(int)$locItem['variant_id']] = [
                        'stock_qty' => (int)$locItem['stock_qty'],
                        'tags' => array_fill_keys(array_map('strtoupper', $locItem['available_piece_tags']), true),
                    ];
                }

                $requestedTags = [];
                $qtyByVariant = [];
                foreach ($items as &$item) {
                    $variantId = (int)($item['variant_id'] ?? 0);
                    $qty = (int)($item['quantity'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }
                    $tags = array_values(array_unique(array_filter(array_map(
                        static fn($tag) => strtoupper(trim((string)$tag)),
                        is_array($item['piece_tags'] ?? null) ? $item['piece_tags'] : []
                    ), static fn($tag) => $tag !== '')));
                    $item['piece_tags'] = $tags;

                    if ($variantId <= 0 || empty($availableByVariant[$variantId])) {
                        throw new Exception('A packed product variant is not available at the selected inward location.');
                    }
                    if (count($tags) !== $qty) {
                        throw new Exception("Select exactly {$qty} available QR code(s) for each packed variant.");
                    }
                    foreach ($tags as $tag) {
                        if (empty($availableByVariant[$variantId]['tags'][$tag]) || isset($requestedTags[$tag])) {
                            throw new Exception("QR code {$tag} is no longer available in inward inventory.");
                        }
                        $requestedTags[$tag] = true;
                    }
                    $qtyByVariant[$variantId] = ($qtyByVariant[$variantId] ?? 0) + $qty;
                    if ($qtyByVariant[$variantId] > $availableByVariant[$variantId]['stock_qty']) {
                        throw new Exception('Packed quantity exceeds available inward stock balance for a variant.');
                    }
                }
                unset($item);

                if (empty($qtyByVariant)) {
                    throw new Exception('Select at least one available inward QR piece to pack.');
                }

                $batchData['program_id'] = null;
                $batchData['qc_batch_id'] = null;
                $batchData['source_type'] = 'INWARD';
                $batchData['source_location_type'] = $locType;
                $batchData['source_location_id'] = $locId;
                $reservedFromLocation = ['type' => $locType, 'id' => $locId, 'qty_by_variant' => $qtyByVariant];
            } elseif (!empty($batchData['qc_batch_id'])) {
                $batchData['source_type'] = 'QC';
                $qcId = (int)$batchData['qc_batch_id'];
                $availableTagsByItem = self::getAvailableQCPieceTags($qcId);
                $qcItemsById = [];
                foreach (QCBatch::getItems($qcId) as $qcItem) {
                    $qcItemsById[(int)$qcItem['id']] = $qcItem;
                }
                $requestedTags = [];
                foreach ($items as &$item) {
                    $qty = (int)($item['quantity'] ?? 0);
                    $itemId = (int)($item['qc_item_id'] ?? 0);
                    $tags = array_values(array_unique(array_filter(array_map(
                        static fn($tag) => trim((string)$tag),
                        is_array($item['piece_tags'] ?? null) ? $item['piece_tags'] : []
                    ), static fn($tag) => $tag !== '')));
                    $item['piece_tags'] = $tags;

                    if ($qty <= 0 || empty($availableTagsByItem[$itemId])) {
                        if (!empty($tags)) {
                            throw new Exception('Selected QR codes are not available for this QC item.');
                        }
                        continue;
                    }
                    if (count($tags) !== $qty) {
                        throw new Exception("Select exactly {$qty} approved QR code(s) for each packed variant.");
                    }

                    $qcItem = $qcItemsById[$itemId] ?? null;
                    if (!$qcItem || (int)($qcItem['variant_id'] ?? 0) !== (int)$item['variant_id']) {
                        throw new Exception('The selected QC item does not match the packed variant.');
                    }
                    $availableSet = array_fill_keys($availableTagsByItem[$itemId], true);
                    foreach ($tags as $tag) {
                        if (empty($availableSet[$tag]) || isset($requestedTags[$tag])) {
                            throw new Exception("QR code {$tag} is no longer available for packing. Refresh the page and select available codes.");
                        }
                        $requestedTags[$tag] = true;
                    }
                }
                unset($item);

                $totalQCPassed = (int)(Database::fetchOne(
                    "SELECT COALESCE(SUM(passed_qty), 0) as total FROM qc_items WHERE qc_batch_id = ?",
                    [$qcId]
                )['total'] ?? 0);
                if ($totalQCPassed <= 0) {
                    $qcRow = QCBatch::find($qcId);
                    $totalQCPassed = (int)($qcRow['passed_qty'] ?? 0);
                }

                $alreadyPacked = (int)(Database::fetchOne(
                    "SELECT COALESCE(SUM(pbi.quantity), 0) as total
                     FROM production_batch_items pbi
                     JOIN production_batches pb ON pbi.production_batch_id = pb.id
                     WHERE pb.qc_batch_id = ? AND pb.status != 'CANCELLED'",
                    [$qcId]
                )['total'] ?? 0);

                $remainingQty = max(0, $totalQCPassed - $alreadyPacked);

                $requestPackingQty = 0;
                foreach ($items as $item) {
                    $requestPackingQty += (int)($item['quantity'] ?? 0);
                }

                if ($remainingQty <= 0) {
                    throw new Exception("Validation Error: All QC passed garments in QC Batch #{$qcId} ({$totalQCPassed} pcs) have already been packed into finished batches.");
                }

                if ($requestPackingQty > $remainingQty) {
                    throw new Exception("Validation Error: Cannot pack {$requestPackingQty} pcs. Only {$remainingQty} pcs remain available to pack for QC Batch #{$qcId} ({$totalQCPassed} total passed, {$alreadyPacked} already packed).");
                }
            }

            if (empty($batchData['batch_number'])) {
                $batchData['batch_number'] = self::generateSequenceNumber('production_batches', 'batch_number', 'BAT');
            }
            $batchData['prepared_by'] = $userId;

            $totalQty = 0;
            foreach ($items as $item) {
                $totalQty += (int)($item['quantity'] ?? 0);
            }
            $batchData['total_qty'] = $totalQty;

            $batchId = ProductionBatch::create($batchData);

            foreach ($items as $item) {
                if ((int)($item['quantity'] ?? 0) > 0) {
                    ProductionBatchItem::create([
                        'production_batch_id' => $batchId,
                        'variant_id'          => (int)$item['variant_id'],
                        'quantity'            => (int)$item['quantity'],
                        'piece_tags'          => $item['piece_tags'] ?? [],
                    ]);
                }
            }

            if ($reservedFromLocation !== null) {
                foreach ($reservedFromLocation['qty_by_variant'] as $variantId => $qty) {
                    StockService::reserve(
                        (int)$variantId,
                        $reservedFromLocation['type'],
                        (int)$reservedFromLocation['id'],
                        (float)$qty,
                        'production_batches',
                        $batchId,
                        $userId ?: null,
                        "Reserved inward stock for batch {$batchData['batch_number']}"
                    );
                }
            }

            AuditService::log('production', 'create', 'production_batch', $batchId, null, $batchData);
            Database::commit();
            return $batchId;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Approve Production Batch.
     */
    public static function approveProductionBatch(int $id, int $userId): bool {
        $batch = ProductionBatch::find($id);
        if (!$batch) {
            throw new Exception("Batch not found: $id");
        }
        if ($batch['status'] === 'DISPATCHED' || $batch['status'] === 'RECEIVED') {
            throw new Exception("Batch {$batch['batch_number']} has already been dispatched and cannot be approved again.");
        }
        if ($batch['status'] === 'APPROVED') {
            throw new Exception("Batch {$batch['batch_number']} is already approved.");
        }

        $result = ProductionBatch::approve($id, $userId);
        if ($result) {
            // Update program completed quantity for manufacturing-origin batches
            if (!empty($batch['program_id'])) {
                ProductionProgram::recalculateCompletedQty((int)$batch['program_id']);
            }
            AuditService::log('production', 'approve', 'production_batch', $id, ['status' => $batch['status']], ['status' => 'APPROVED']);
        }
        return $result;
    }

    /**
     * Create Outward Dispatch for Finished Batch.
     */
    public static function createOutward(array $outwardData, int $userId): int {
        Database::beginTransaction();
        try {
            $batchId = (int)($outwardData['production_batch_id'] ?? 0);
            $batch = ProductionBatch::find($batchId);
            if (!$batch) {
                throw new Exception("Production batch not found.");
            }
            $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
            if ($scopedUnitId === 0) {
                throw new Exception('You cannot dispatch a batch without an active unit in this concern.');
            }
            $isInwardBatch = strtoupper((string)($batch['source_type'] ?? 'QC')) === 'INWARD' || empty($batch['program_id']);
            if ($scopedUnitId !== null) {
                if ($isInwardBatch) {
                    if (strtoupper((string)($batch['source_location_type'] ?? '')) === 'PRODUCTION_UNIT'
                        && (int)($batch['source_location_id'] ?? 0) !== (int)$scopedUnitId) {
                        throw new Exception('You cannot dispatch a batch packed from another production unit.');
                    }
                } else {
                    $batchUnit = Database::fetchOne(
                        "SELECT production_unit_id FROM production_programs WHERE id = ?",
                        [(int)$batch['program_id']]
                    );
                    if ((int)($batchUnit['production_unit_id'] ?? 0) !== (int)$scopedUnitId) {
                        throw new Exception('You cannot dispatch a batch assigned to another production unit.');
                    }
                }
            }
            if ($batch['status'] === 'DISPATCHED' || $batch['status'] === 'RECEIVED') {
                throw new Exception("Batch {$batch['batch_number']} has already been dispatched and cannot be dispatched again.");
            }
            if ($batch['status'] !== 'APPROVED') {
                throw new Exception("Only APPROVED batches can be dispatched. Current batch status is: {$batch['status']}.");
            }

            // Check if outward record already exists for this batch
            $existingOutward = Database::fetchOne("SELECT id, outward_number FROM production_outwards WHERE production_batch_id = ?", [$batchId]);
            if ($existingOutward) {
                throw new Exception("Batch {$batch['batch_number']} has already been dispatched under Outward #{$existingOutward['outward_number']}.");
            }

            if (empty($outwardData['outward_number'])) {
                $outwardData['outward_number'] = self::generateSequenceNumber('production_outwards', 'outward_number', 'OUT');
            }
            $outwardData['created_by'] = $userId;
            $outwardData['dispatched_by'] = $userId;

            $outwardId = ProductionOutward::create($outwardData);

            // Update batch status to DISPATCHED
            ProductionBatch::updateStatus($batch['id'], 'DISPATCHED');

            // Deduct sender balance for inward-packed batches
            if ($isInwardBatch
                && !empty($batch['source_location_type'])
                && !empty($batch['source_location_id'])) {
                if (!class_exists('StockService')) {
                    require_once BASE_PATH . '/app/Services/StockService.php';
                }
                $srcType = strtoupper((string)$batch['source_location_type']);
                $srcId = (int)$batch['source_location_id'];
                $batchItems = Database::fetchAll(
                    "SELECT variant_id, quantity FROM production_batch_items WHERE production_batch_id = ? AND quantity > 0",
                    [$batchId]
                );
                foreach ($batchItems as $bItem) {
                    $qty = (float)$bItem['quantity'];
                    $variantId = (int)$bItem['variant_id'];
                    if ($qty <= 0 || $variantId <= 0) {
                        continue;
                    }
                    $reservedBal = StockService::getBalance($variantId, $srcType, $srcId, 'RESERVED');
                    $deductState = $reservedBal >= $qty ? 'RESERVED' : 'AVAILABLE';
                    StockService::decrease(
                        $variantId,
                        $srcType,
                        $srcId,
                        $deductState,
                        $qty,
                        'PRODUCTION_OUTWARD',
                        'production_outwards',
                        $outwardId,
                        $userId ?: null,
                        "Dispatched inward batch {$batch['batch_number']} via {$outwardData['outward_number']}"
                    );
                }
            }

            AuditService::log('production', 'create', 'production_outward', $outwardId, null, $outwardData);
            Database::commit();
            return $outwardId;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Dispatch an Outward.
     */
    public static function dispatchOutward(int $id, int $userId): bool {
        $outward = ProductionOutward::find($id);
        if (!$outward) {
            throw new Exception("Outward record not found: $id");
        }

        ProductionBatch::updateStatus((int)$outward['production_batch_id'], 'DISPATCHED');
        AuditService::log('production', 'dispatch', 'production_outward', $id, null, ['status' => 'DISPATCHED']);
        return true;
    }

    /**
     * Get Production Dashboard KPIs.
     */
    public static function getDashboardStats(?int $unitId = null, ?int $concernId = null): array {
        $concernValid = $concernId !== null && $concernId > 0 && $unitId !== 0;
        $safeConcernId = $concernValid ? (int)$concernId : 0;
        $safeUnitId = $unitId !== null ? (int)$unitId : null;

        $unitClauseProg = '';
        $unitClausePP = '';
        $batchScopeClause = '';
        if ($concernValid) {
            if ($safeUnitId !== null) {
                $unitClauseProg = " AND production_unit_id = {$safeUnitId} AND production_unit_id IN (SELECT id FROM production_units WHERE concern_id = {$safeConcernId})";
                $unitClausePP = " AND pp.production_unit_id = {$safeUnitId} AND pp.production_unit_id IN (SELECT id FROM production_units WHERE concern_id = {$safeConcernId})";
                $batchScopeClause = $unitClausePP;
            } else {
                $unitClauseProg = " AND production_unit_id IN (SELECT id FROM production_units WHERE concern_id = {$safeConcernId})";
                $unitClausePP = " AND pp.production_unit_id IN (SELECT id FROM production_units WHERE concern_id = {$safeConcernId})";
                $batchScopeClause = " AND ((pb.concern_id = {$safeConcernId} AND (pp.id IS NULL OR pp.production_unit_id IN (SELECT id FROM production_units WHERE concern_id = {$safeConcernId})))"
                    . " OR (pb.concern_id IS NULL AND pp.production_unit_id IN (SELECT id FROM production_units WHERE concern_id = {$safeConcernId})))";
            }
        } else {
            $unitClauseProg = ' AND 1 = 0';
            $unitClausePP = ' AND 1 = 0';
            $batchScopeClause = ' AND 1 = 0';
        }

        // Active Programs
        $activePrograms = Database::fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(planned_quantity), 0) as total_planned, COALESCE(SUM(completed_quantity), 0) as total_completed
             FROM production_programs
             WHERE status IN ('APPROVED', 'SCHEDULED', 'IN_PROGRESS')
               AND (is_active IS NULL OR is_active = 1)
               AND (COALESCE(planned_quantity, 0) <= 0 OR COALESCE(completed_quantity, 0) < COALESCE(planned_quantity, 0))$unitClauseProg"
        ) ?: ['count' => 0, 'total_planned' => 0, 'total_completed' => 0];

        // Pending Cutting
        $pendingCutting = Database::fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(cb.planned_qty), 0) as total_qty
             FROM cutting_batches cb
             LEFT JOIN production_programs pp ON cb.program_id = pp.id
             WHERE cb.status IN ('PENDING', 'IN_PROGRESS')$unitClausePP"
        ) ?: ['count' => 0, 'total_qty' => 0];

        // Pending Stitching
        $pendingStitching = Database::fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(sb.received_qty), 0) as total_qty
             FROM stitching_batches sb
             LEFT JOIN production_programs pp ON sb.program_id = pp.id
             WHERE sb.status IN ('PENDING', 'IN_PROGRESS')$unitClausePP"
        ) ?: ['count' => 0, 'total_qty' => 0];

        // Pending QC
        $pendingQC = Database::fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(qb.total_qty), 0) as total_qty
             FROM qc_batches qb
             LEFT JOIN production_programs pp ON qb.program_id = pp.id
             WHERE qb.status IN ('PENDING', 'PARTIAL', 'REWORK')$unitClausePP"
        ) ?: ['count' => 0, 'total_qty' => 0];

        // Ready for Dispatch
        $readyDispatch = Database::fetchOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(pb.total_qty), 0) as total_qty
             FROM production_batches pb
             LEFT JOIN production_programs pp ON pb.program_id = pp.id
             WHERE pb.status = 'APPROVED'$batchScopeClause"
        ) ?: ['count' => 0, 'total_qty' => 0];

        // This month outward
        $monthOutward = Database::fetchOne(
            "SELECT COUNT(po.id) as count, COALESCE(SUM(pb.total_qty), 0) as total_qty
             FROM production_outwards po
             JOIN production_batches pb ON po.production_batch_id = pb.id
             LEFT JOIN production_programs pp ON pb.program_id = pp.id
             WHERE MONTH(po.created_at) = MONTH(CURRENT_DATE()) AND YEAR(po.created_at) = YEAR(CURRENT_DATE())$batchScopeClause"
        ) ?: ['count' => 0, 'total_qty' => 0];

        // Active programs not yet completed against their planned quantity
        $recentPrograms = Database::fetchAll(
            "SELECT pp.*, COALESCE(p.name, pp.name, 'General Apparel') as product_name, p.code as product_code, pu.name as unit_name
             FROM production_programs pp
             LEFT JOIN products p ON pp.product_id = p.id
             JOIN production_units pu ON pp.production_unit_id = pu.id
             WHERE pp.status IN ('APPROVED', 'SCHEDULED', 'IN_PROGRESS')
               AND (pp.is_active IS NULL OR pp.is_active = 1)
               AND (COALESCE(pp.planned_quantity, 0) <= 0 OR COALESCE(pp.completed_quantity, 0) < COALESCE(pp.planned_quantity, 0))$unitClausePP
             ORDER BY pp.created_at DESC LIMIT 6"
        );

        // Recent QC inspections
        $recentQC = Database::fetchAll(
            "SELECT qb.*, pp.program_number, COALESCE(p.name, pp.name, 'General Apparel') as product_name, u.full_name as inspector_name
             FROM qc_batches qb
             JOIN production_programs pp ON qb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN users u ON qb.inspector_id = u.id
             WHERE 1=1$unitClausePP
             ORDER BY qb.created_at DESC LIMIT 5"
        );

        // Recent Outward dispatches
        $recentOutwards = Database::fetchAll(
            "SELECT po.*, pb.batch_number, pb.total_qty, w.name as warehouse_name, dc.name as destination_concern_name
             FROM production_outwards po
             JOIN production_batches pb ON po.production_batch_id = pb.id
             LEFT JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN concerns dc ON pb.destination_concern_id = dc.id
             WHERE 1=1$batchScopeClause
             ORDER BY po.created_at DESC LIMIT 5"
        );

        // Concern / Unit Inward Stock, Pending Inwards, Recent GRNs & Billing Summary
        $inwardStock = ['variant_count' => 0, 'available_qty' => 0, 'damaged_qty' => 0];
        $pendingInwards = ['count' => 0, 'total_qty' => 0];
        $inwardReceipts = ['month_count' => 0, 'month_qty' => 0, 'total_count' => 0];
        $recentInwards = [];
        $billingSummary = ['month_count' => 0, 'month_total' => 0.0, 'total_count' => 0, 'total_amount' => 0.0, 'pending_count' => 0];
        $recentBills = [];

        if ($concernValid) {
            $stockSql = "SELECT COUNT(DISTINCT CASE WHEN sb.stock_state = 'AVAILABLE' AND sb.quantity > 0 THEN sb.variant_id END) AS variant_count,
                                COALESCE(SUM(CASE WHEN sb.stock_state = 'AVAILABLE' THEN sb.quantity ELSE 0 END), 0) AS available_qty,
                                COALESCE(SUM(CASE WHEN sb.stock_state = 'DAMAGED' THEN sb.quantity ELSE 0 END), 0) AS damaged_qty
                         FROM stock_balances sb
                         LEFT JOIN production_units pu ON sb.location_type = 'PRODUCTION_UNIT'
                            AND pu.id = sb.location_id AND pu.concern_id = ?
                         WHERE sb.concern_id = ?";
            $stockParams = [$safeConcernId, $safeConcernId];
            if ($safeUnitId !== null) {
                $stockSql .= " AND ((sb.location_type = 'PRODUCTION_UNIT' AND pu.id IS NOT NULL AND sb.location_id = ?)
                                OR (sb.location_type = 'CONCERN' AND sb.location_id = ?))";
                $stockParams[] = $safeUnitId;
                $stockParams[] = $safeConcernId;
            } else {
                $stockSql .= " AND ((sb.location_type = 'PRODUCTION_UNIT' AND pu.id IS NOT NULL)
                                OR (sb.location_type = 'CONCERN' AND sb.location_id = ?))";
                $stockParams[] = $safeConcernId;
            }
            $inwardStock = Database::fetchOne($stockSql, $stockParams) ?: $inwardStock;

            $pendingInwards = Database::fetchOne(
                "SELECT COUNT(*) AS count, COALESCE(SUM(pb.total_qty), 0) AS total_qty
                 FROM production_batches pb
                 WHERE pb.destination_concern_id = ?
                   AND pb.status = 'DISPATCHED'
                   AND NOT EXISTS (
                       SELECT 1 FROM production_inwards pi
                       WHERE pi.source_type = 'PRODUCTION_BATCH'
                         AND pi.source_id = pb.id
                         AND pi.status = 'RECEIVED'
                   )",
                [$safeConcernId]
            ) ?: $pendingInwards;

            $inwardScopeSql = 'WHERE pi.concern_id = ? AND pi.status = ?';
            $inwardParams = [$safeConcernId, 'RECEIVED'];
            if ($safeUnitId !== null) {
                $inwardScopeSql .= " AND ((pi.destination_type = 'PRODUCTION_UNIT' AND pi.destination_unit_id = ?) OR pi.destination_type = 'CONCERN')";
                $inwardParams[] = $safeUnitId;
            }

            $inwardReceipts = Database::fetchOne(
                "SELECT COUNT(DISTINCT pi.id) AS total_count,
                        COUNT(DISTINCT CASE WHEN MONTH(pi.received_at) = MONTH(CURRENT_DATE()) AND YEAR(pi.received_at) = YEAR(CURRENT_DATE()) THEN pi.id END) AS month_count,
                        COALESCE(SUM(CASE WHEN MONTH(pi.received_at) = MONTH(CURRENT_DATE()) AND YEAR(pi.received_at) = YEAR(CURRENT_DATE()) THEN pii.quantity ELSE 0 END), 0) AS month_qty
                 FROM production_inwards pi
                 LEFT JOIN production_inward_items pii ON pii.inward_id = pi.id
                 {$inwardScopeSql}",
                $inwardParams
            ) ?: $inwardReceipts;

            $recentInwards = Database::fetchAll(
                "SELECT pi.id, pi.grn_number, pi.source_type, pi.source_batch_number, pi.destination_type, pi.received_at,
                        COALESCE(dest.name, dest_concern.name, 'Concern Inventory') AS destination_name,
                        COALESCE(SUM(pii.quantity), 0) AS available_qty,
                        COALESCE(SUM(pii.damaged_qty), 0) AS damaged_qty
                 FROM production_inwards pi
                 LEFT JOIN production_units dest ON pi.destination_type = 'PRODUCTION_UNIT'
                    AND dest.id = pi.destination_unit_id AND dest.concern_id = ?
                 LEFT JOIN concerns dest_concern ON pi.destination_type = 'CONCERN'
                    AND dest_concern.id = pi.destination_concern_id AND dest_concern.id = pi.concern_id
                 LEFT JOIN production_inward_items pii ON pii.inward_id = pi.id
                 {$inwardScopeSql}
                 GROUP BY pi.id
                 ORDER BY pi.received_at DESC, pi.id DESC
                 LIMIT 5",
                array_merge([$safeConcernId], $inwardParams)
            );

            $billWhere = 'WHERE pb.concern_id = ?';
            $billParams = [$safeConcernId];
            if ($safeUnitId !== null) {
                $billWhere .= ' AND pb.production_unit_id = ?';
                $billParams[] = $safeUnitId;
            }
            $billingSummary = Database::fetchOne(
                "SELECT COUNT(*) AS total_count,
                        COALESCE(SUM(pb.total_amount), 0) AS total_amount,
                        COALESCE(SUM(CASE WHEN MONTH(pb.bill_date) = MONTH(CURRENT_DATE()) AND YEAR(pb.bill_date) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END), 0) AS month_count,
                        COALESCE(SUM(CASE WHEN MONTH(pb.bill_date) = MONTH(CURRENT_DATE()) AND YEAR(pb.bill_date) = YEAR(CURRENT_DATE()) THEN pb.total_amount ELSE 0 END), 0) AS month_total,
                        COALESCE(SUM(CASE WHEN pb.payment_status = 'PENDING' THEN 1 ELSE 0 END), 0) AS pending_count
                 FROM production_bills pb
                 {$billWhere}",
                $billParams
            ) ?: $billingSummary;

            $recentBills = Database::fetchAll(
                "SELECT pb.id, pb.bill_number, pb.bill_date, pb.buyer_type, pb.company_name, pb.customer_name,
                        pb.total_amount, pb.payment_status
                 FROM production_bills pb
                 {$billWhere}
                 ORDER BY pb.bill_date DESC, pb.id DESC
                 LIMIT 5",
                $billParams
            );
        }

        return [
            'activePrograms'   => $activePrograms,
            'pendingCutting'   => $pendingCutting,
            'pendingStitching' => $pendingStitching,
            'pendingQC'        => $pendingQC,
            'readyDispatch'    => $readyDispatch,
            'monthOutward'     => $monthOutward,
            'recentPrograms'   => $recentPrograms,
            'recentQC'         => $recentQC,
            'recentOutwards'   => $recentOutwards,
            'inwardStock'      => $inwardStock,
            'pendingInwards'   => $pendingInwards,
            'inwardReceipts'   => $inwardReceipts,
            'recentInwards'    => $recentInwards,
            'billingSummary'   => $billingSummary,
            'recentBills'      => $recentBills,
        ];
    }

    /**
     * Get Complete Timeline & Stage Information for a Program.
     */
    public static function getProgramTimeline(int $programId): array {
        $program = ProductionProgram::find($programId);
        $items = ProductionProgram::getItems($programId);
        $cutting = Database::fetchAll("SELECT * FROM cutting_batches WHERE program_id = ? ORDER BY id DESC", [$programId]);
        $stitching = Database::fetchAll("SELECT * FROM stitching_batches WHERE program_id = ? ORDER BY id DESC", [$programId]);
        $labels = Database::fetchAll("SELECT * FROM label_batches WHERE program_id = ? ORDER BY id DESC", [$programId]);
        $qc = Database::fetchAll("SELECT * FROM qc_batches WHERE program_id = ? ORDER BY id DESC", [$programId]);
        $batches = Database::fetchAll("SELECT * FROM production_batches WHERE program_id = ? ORDER BY id DESC", [$programId]);

        return [
            'program'   => $program,
            'items'     => $items,
            'cutting'   => $cutting,
            'stitching' => $stitching,
            'labels'    => $labels,
            'qc'        => $qc,
            'batches'   => $batches,
        ];
    }

    /**
     * Generate the next unused GRM-STY-XXXX catalog code scoped to a concern.
     */
    public static function generateNextCatalogCode(?int $concernId = null): string {
        $concernId = $concernId ?? (function_exists('currentConcernId') ? currentConcernId() : null);
        $rows = $concernId
            ? Database::fetchAll(
                "SELECT code FROM products WHERE concern_id = ? AND code LIKE 'GRM-STY-%'",
                [$concernId]
            )
            : Database::fetchAll("SELECT code FROM products WHERE code LIKE 'GRM-STY-%'");

        $maxNum = 0;
        foreach ($rows as $row) {
            if (preg_match('/^GRM-STY-(\d+)/i', (string)($row['code'] ?? ''), $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        do {
            $candidate = 'GRM-STY-' . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
            $exists = $concernId
                ? Database::fetchOne(
                    "SELECT id FROM products WHERE concern_id = ? AND code = ? LIMIT 1",
                    [$concernId, $candidate]
                )
                : Database::fetchOne("SELECT id FROM products WHERE code = ? LIMIT 1", [$candidate]);
            $nextNum++;
        } while ($exists);

        return $candidate;
    }

    /**
     * Find or create a product for a production program.
     */
    public static function findOrCreateProductForProgram(int $programId): int {
        if ($programId <= 0) {
            return 0;
        }

        $prog = Database::fetchOne("SELECT * FROM production_programs WHERE id = ?", [$programId]);
        if (!$prog) {
            return 0;
        }

        $concernId = null;
        if (!empty($prog['production_unit_id'])) {
            $unitRow = Database::fetchOne(
                "SELECT concern_id FROM production_units WHERE id = ?",
                [(int)$prog['production_unit_id']]
            );
            if (!empty($unitRow['concern_id'])) {
                $concernId = (int)$unitRow['concern_id'];
            }
        }
        if (!$concernId && function_exists('currentConcernId')) {
            $concernId = currentConcernId();
        }
        if (!$concernId) {
            throw new RuntimeException('Unable to determine concern ownership for production program product.');
        }

        if (!empty($prog['product_id'])) {
            $existing = Database::fetchOne(
                "SELECT id FROM products WHERE id = ? AND concern_id = ?",
                [(int)$prog['product_id'], $concernId]
            );
            if ($existing) {
                return (int)$existing['id'];
            }
        }

        // Try matching an existing product by category hierarchy or name
        $prod = null;
        if (!empty($prog['sub_subcategory_id'])) {
            $prod = Database::fetchOne(
                "SELECT id FROM products WHERE concern_id = ? AND sub_subcategory_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1",
                [$concernId, (int)$prog['sub_subcategory_id']]
            );
        }
        if (!$prod && !empty($prog['subcategory_id'])) {
            $prod = Database::fetchOne(
                "SELECT id FROM products WHERE concern_id = ? AND subcategory_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1",
                [$concernId, (int)$prog['subcategory_id']]
            );
        }
        if (!$prod && !empty($prog['name'])) {
            $prod = Database::fetchOne(
                "SELECT id FROM products WHERE concern_id = ? AND name = ? AND is_active = 1 ORDER BY id DESC LIMIT 1",
                [$concernId, $prog['name']]
            );
        }

        if ($prod) {
            $productId = (int)$prod['id'];
            Database::execute("UPDATE production_programs SET product_id = ? WHERE id = ?", [$productId, $programId]);
            return $productId;
        }

        // Auto-create product for this program
        $catNames = [];
        $catCode = null;
        $subCode = null;
        $sscCode = null;

        if (!empty($prog['sub_subcategory_id'])) {
            $ssc = Database::fetchOne("SELECT name, code FROM sub_subcategories WHERE id = ?", [(int)$prog['sub_subcategory_id']]);
            if ($ssc) {
                $catNames[] = $ssc['name'];
                $sscCode = $ssc['code'];
            }
        }
        if (!empty($prog['subcategory_id'])) {
            $sc = Database::fetchOne("SELECT name, code FROM subcategories WHERE id = ?", [(int)$prog['subcategory_id']]);
            if ($sc) {
                $catNames[] = $sc['name'];
                $subCode = $sc['code'];
            }
        }
        if (!empty($prog['category_id'])) {
            $c = Database::fetchOne("SELECT name, code FROM categories WHERE id = ?", [(int)$prog['category_id']]);
            if ($c) {
                $catNames[] = $c['name'];
                $catCode = $c['code'];
            }
        }

        $name = !empty($catNames) ? implode(' - ', array_reverse($catNames)) : (!empty($prog['name']) ? $prog['name'] : 'PRODUCT STYLE');
        $code = self::generateNextCatalogCode($concernId);

        $qrPrefix = '';
        if ($catCode) $qrPrefix .= substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $catCode)), 0, 1);
        if ($subCode) $qrPrefix .= substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $subCode)), 0, 1);
        if ($sscCode) $qrPrefix .= substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sscCode)), 0, 1);

        Database::execute(
            "INSERT INTO products (concern_id, name, code, category_id, subcategory_id, sub_subcategory_id, category_code, subcategory_code, sub_subcategory_code, qr_code_prefix, source_type, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'OWN_PRODUCTION', 1, NOW(), NOW())",
            [
                $concernId,
                $name,
                $code,
                $prog['category_id'] ?? null,
                $prog['subcategory_id'] ?? null,
                $prog['sub_subcategory_id'] ?? null,
                $catCode,
                $subCode,
                $sscCode,
                $qrPrefix ?: null,
            ]
        );

        $productId = (int)Database::lastInsertId();
        Database::execute("UPDATE production_programs SET product_id = ? WHERE id = ?", [$productId, $programId]);

        return $productId;
    }

    /**
     * Find or create a product variant for a product with given size and color names.
     */
    public static function findOrCreateVariant(int $productId, ?string $sizeName = null, ?string $colorName = null, ?string $sku = null): int {
        $sizeName = trim($sizeName ?? '');
        $colorName = trim($colorName ?? '');
        $product = Database::fetchOne("SELECT concern_id, code, name FROM products WHERE id = ?", [$productId]);
        $concernId = !empty($product['concern_id'])
            ? (int)$product['concern_id']
            : (function_exists('currentConcernId') ? currentConcernId() : null);

        $sizeId = null;
        if ($sizeName !== '' && $sizeName !== 'Standard' && $sizeName !== 'All') {
            $size = Database::fetchOne("SELECT id FROM sizes WHERE name = ? OR code = ?", [$sizeName, $sizeName]);
            if ($size) {
                $sizeId = (int)$size['id'];
            } else {
                Database::execute("INSERT INTO sizes (name, code, is_active) VALUES (?, ?, 1)", [$sizeName, $sizeName]);
                $sizeId = (int)Database::lastInsertId();
            }
        }

        $colorId = null;
        if ($colorName !== '' && $colorName !== 'Assorted' && $colorName !== 'Standard' && $colorName !== 'All') {
            $color = Database::fetchOne("SELECT id FROM colors WHERE name = ?", [$colorName]);
            if ($color) {
                $colorId = (int)$color['id'];
            } else {
                Database::execute("INSERT INTO colors (name, is_active) VALUES (?, 1)", [$colorName]);
                $colorId = (int)Database::lastInsertId();
            }
        }

        // Check if variant already exists with size_id & color_id
        if ($sizeId && $colorId) {
            $v = Database::fetchOne(
                "SELECT id FROM product_variants WHERE product_id = ? AND size_id = ? AND color_id = ? AND is_active = 1",
                [$productId, $sizeId, $colorId]
            );
            if ($v) return (int)$v['id'];
        } elseif ($sizeId) {
            $v = Database::fetchOne(
                "SELECT id FROM product_variants WHERE product_id = ? AND size_id = ? AND (color_id IS NULL OR color_id = 0) AND is_active = 1",
                [$productId, $sizeId]
            );
            if ($v) return (int)$v['id'];
        } elseif ($colorId) {
            $v = Database::fetchOne(
                "SELECT id FROM product_variants WHERE product_id = ? AND (size_id IS NULL OR size_id = 0) AND color_id = ? AND is_active = 1",
                [$productId, $colorId]
            );
            if ($v) return (int)$v['id'];
        }

        // Check if matching by SKU if provided
        if (!empty($sku)) {
            $v = Database::fetchOne("SELECT id FROM product_variants WHERE product_id = ? AND sku = ? AND is_active = 1", [$productId, $sku]);
            if ($v) return (int)$v['id'];
        }

        // Generate SKU if needed
        $prodCode = $product['code'] ?? ('PRD-' . $productId);

        $skuParts = [$prodCode];
        if ($sizeName !== '' && $sizeName !== 'Standard') $skuParts[] = $sizeName;
        if ($colorName !== '' && $colorName !== 'Assorted') $skuParts[] = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $colorName)), 0, 4);
        $generatedSku = implode('-', $skuParts);

        // Check if this generated SKU exists within the same concern
        $v = $concernId
            ? Database::fetchOne(
                "SELECT id FROM product_variants WHERE concern_id = ? AND sku = ?",
                [$concernId, $generatedSku]
            )
            : Database::fetchOne("SELECT id FROM product_variants WHERE sku = ?", [$generatedSku]);
        if ($v) return (int)$v['id'];

        // Insert new variant
        Database::execute(
            "INSERT INTO product_variants (concern_id, product_id, size_id, color_id, sku, mrp, purchase_price, production_cost, selling_price, is_active)
             VALUES (?, ?, ?, ?, ?, 0.00, 0.00, 0.00, 0.00, 1)",
            [$concernId, $productId, $sizeId, $colorId, $generatedSku]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Generate a non-colliding sequential document code (e.g. PRG-20261111-0001).
     */
    public static function generateNextSequentialCode(string $table, string $column, string $prefix, int $padLength = 4): array {
        $allowedColumnsByTable = [
            'production_programs' => 'program_number',
            'cutting_batches' => 'batch_number',
            'stitching_batches' => 'batch_number',
            'label_batches' => 'batch_number',
            'wastage_sales' => 'sale_number',
            'stitching_wastage_sales' => 'sale_number',
            'production_expenses' => 'expense_number',
            'production_warehouse_reworks' => 'rework_number',
        ];
        if (!isset($allowedColumnsByTable[$table]) || $allowedColumnsByTable[$table] !== $column) {
            throw new InvalidArgumentException("Unsupported sequential code target: {$table}.{$column}");
        }

        $rows = Database::fetchAll(
            "SELECT `{$column}` AS doc_code FROM `{$table}` WHERE `{$column}` LIKE ?",
            [$prefix . '%']
        );
        $maxNum = 0;
        $quotedPrefix = preg_quote($prefix, '/');
        foreach ($rows as $row) {
            $val = (string)($row['doc_code'] ?? '');
            if (preg_match('/^' . $quotedPrefix . '(\d+)$/i', $val, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        do {
            $candidate = $prefix . str_pad((string)$nextNum, $padLength, '0', STR_PAD_LEFT);
            $exists = Database::fetchOne(
                "SELECT id FROM `{$table}` WHERE `{$column}` = ? LIMIT 1",
                [$candidate]
            );
            $nextNum++;
        } while ($exists);

        return [
            'seq' => $nextNum - 1,
            'code' => $candidate,
        ];
    }

    /**
     * Fetch deleted production QR label codes scoped to the active concern.
     */
    public static function getDeletedLabelCodesForConcern(?int $concernId = null): array {
        $concernId = $concernId ?? (function_exists('currentConcernId') ? currentConcernId() : null);
        if (!$concernId) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT DISTINCT pdlc.code
             FROM production_deleted_label_codes pdlc
             LEFT JOIN users u ON u.id = pdlc.created_by
             LEFT JOIN production_units pu_user ON pu_user.id = u.production_unit_id
             WHERE pu_user.concern_id = ?
                OR EXISTS (
                    SELECT 1 FROM user_concerns uc
                    WHERE uc.user_id = pdlc.created_by AND uc.concern_id = ?
                )
                OR EXISTS (
                    SELECT 1
                    FROM label_items li
                    JOIN label_batches lb ON lb.id = li.label_batch_id
                    JOIN production_programs pp ON pp.id = lb.program_id
                    JOIN production_units pu ON pu.id = pp.production_unit_id
                    WHERE pu.concern_id = ?
                      AND li.start_code IS NOT NULL
                      AND pdlc.code LIKE CONCAT(TRIM(TRAILING '0123456789' FROM li.start_code), '%')
                )",
            [(int)$concernId, (int)$concernId, (int)$concernId]
        );
        return array_values(array_unique(array_filter(array_column($rows, 'code'))));
    }
}
