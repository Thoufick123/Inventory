<?php

require_once BASE_PATH . '/app/Models/ProductionInventoryDamage.php';

class ProductionInventoryDamageController
{
    private const SOURCE_STAGES = ['QC', 'LABELING', 'PACKING', 'STORAGE_HANDLING', 'OTHER'];
    private const STATUSES = ['LOGGED', 'INSPECTED', 'REWORK', 'REPAIRED', 'RESTOCKED', 'SALVAGED', 'WRITTEN_OFF'];

    public function index(): void
    {
        $unitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $filters = [
            'search' => trim((string)($_GET['search'] ?? '')),
            'status' => trim((string)($_GET['status'] ?? '')),
            'source_stage' => trim((string)($_GET['source_stage'] ?? '')),
            'production_unit_id' => $unitId ?? (!empty($_GET['production_unit_id']) ? (int)$_GET['production_unit_id'] : null),
        ];
        if (!in_array($filters['status'], self::STATUSES, true)) {
            $filters['status'] = '';
        }
        if (!in_array($filters['source_stage'], self::SOURCE_STAGES, true)) {
            $filters['source_stage'] = '';
        }

        Response::view('production.inventory_damages.index', [
            'pageTitle' => 'Finished Goods Damage & Rework',
            'activeModule' => 'production',
            'activeMenu' => 'prod-inventory-damages',
            'damages' => ProductionInventoryDamage::getAll($filters),
            'stats' => ProductionInventoryDamage::getSummaryStats($unitId),
            'filters' => $filters,
            'sourceStages' => self::SOURCE_STAGES,
            'statuses' => self::STATUSES,
            'breadcrumb' => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Finished Goods Damage & Rework'],
            ],
        ]);
    }

    public function create(): void
    {
        Response::view('production.inventory_damages.create', [
            'pageTitle' => 'Report Finished Goods Damage',
            'activeModule' => 'production',
            'activeMenu' => 'prod-inventory-damages',
            'sourceStages' => self::SOURCE_STAGES,
            'breadcrumb' => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Finished Goods Damage', 'url' => url('production/inventory-damages')],
                ['label' => 'Report Damage'],
            ],
        ]);
    }

    public function lookup(): void
    {
        $tag = strtoupper(trim((string)($_GET['code'] ?? '')));
        if ($tag === '') {
            Response::json(['success' => false, 'message' => 'Scan or enter a production QR tag.'], 400);
            return;
        }

        $piece = $this->findAvailablePiece($tag);
        if (!$piece) {
            Response::json(['success' => false, 'message' => 'This QR tag is not available in Production inventory. It may be damaged, deleted, packed, or already dispatched.'], 404);
            return;
        }

        Response::json(['success' => true, 'piece' => $piece]);
    }

    public function store(): void
    {
        if (!CSRF::validateRequest()) {
            Response::json(['success' => false, 'message' => 'Invalid security token or session expired.'], 403);
            return;
        }

        $rawItems = $_POST['items'] ?? [];
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true);
        }
        if (!is_array($rawItems)) {
            $rawItems = [];
        }

        $tags = [];
        foreach ($rawItems as $item) {
            $tag = strtoupper(trim((string)($item['tag_code'] ?? '')));
            if ($tag !== '' && !in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }
        $reason = trim((string)($_POST['damage_reason'] ?? ''));
        $sourceStage = strtoupper(trim((string)($_POST['source_stage'] ?? 'OTHER')));
        $notes = trim((string)($_POST['defect_notes'] ?? ''));

        if (!$tags || $reason === '' || mb_strlen($reason) > 255 || !in_array($sourceStage, self::SOURCE_STAGES, true)) {
            Response::json(['success' => false, 'message' => 'Add at least one valid QR tag, a damage reason, and a valid production stage.'], 422);
            return;
        }

        $photoUrl = null;
        if (!empty($_FILES['photo']['name']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                Response::json(['success' => false, 'message' => 'Use a JPG, PNG, or WebP evidence photo.'], 422);
                return;
            }
            $photoUrl = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/production/damages', 'image');
            if (!$photoUrl) {
                Response::json(['success' => false, 'message' => 'The damage photo could not be uploaded.'], 422);
                return;
            }
        }

        $userId = (int)(Session::userId() ?? 0);
        $created = [];
        try {
            Database::beginTransaction();
            foreach ($tags as $tag) {
                $piece = $this->findAvailablePiece($tag);
                if (!$piece) {
                    throw new RuntimeException("QR tag {$tag} is no longer available in Production inventory.");
                }

                $number = 'DMG-PRD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                $damageId = ProductionInventoryDamage::create([
                    'damage_number' => $number,
                    'qc_batch_id' => (int)$piece['qc_batch_id'],
                    'qc_item_id' => (int)$piece['qc_item_id'],
                    'program_id' => (int)$piece['program_id'],
                    'production_unit_id' => $piece['production_unit_id'] ?? null,
                    'tag_code' => $tag,
                    'source_stage' => $sourceStage,
                    'damage_reason' => $reason,
                    'defect_notes' => $notes,
                    'photo_url' => $photoUrl,
                    'unit_value' => (float)($piece['unit_value'] ?? 0),
                    'reported_by' => $userId,
                ]);
                AuditService::log('production', 'create', 'production_inventory_damages', $damageId, null, [
                    'damage_number' => $number,
                    'tag_code' => $tag,
                    'qc_batch_id' => (int)$piece['qc_batch_id'],
                    'source_stage' => $sourceStage,
                    'damage_reason' => $reason,
                ], $userId);
                $created[] = $number;
            }
            Database::commit();
            Response::json(['success' => true, 'message' => count($created) . ' production piece(s) quarantined.', 'damage_numbers' => $created]);
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            error_log('Production inventory damage creation failed: ' . $e->getMessage());
            Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function view(string $id): void
    {
        $damage = ProductionInventoryDamage::find((int)$id);
        if (!$damage || !$this->isInCurrentUnit($damage)) {
            Response::redirect(url('production/inventory-damages'), 'error', 'Damage case not found.');
            return;
        }

        Response::view('production.inventory_damages.view', [
            'pageTitle' => 'Damage Case ' . $damage['damage_number'],
            'activeModule' => 'production',
            'activeMenu' => 'prod-inventory-damages',
            'damage' => $damage,
            'statuses' => self::STATUSES,
            'auditLogs' => AuditService::getLogsForReference('production_inventory_damages', (int)$damage['id']),
            'breadcrumb' => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Finished Goods Damage', 'url' => url('production/inventory-damages')],
                ['label' => $damage['damage_number']],
            ],
        ]);
    }

    public function updateStatus(string $id): void
    {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $damageId = (int)$id;
        $damage = ProductionInventoryDamage::find($damageId);
        $status = strtoupper(trim((string)($_POST['status'] ?? '')));
        if (!$damage || !$this->isInCurrentUnit($damage) || !in_array($status, self::STATUSES, true)) {
            Session::flash('error', 'Invalid damage case or disposition status.');
            Response::back();
            return;
        }

        $userId = (int)(Session::userId() ?? 0);
        $notes = trim((string)($_POST['defect_notes'] ?? ''));
        try {
            Database::beginTransaction();
            ProductionInventoryDamage::update($damageId, [
                'status' => $status,
                'defect_notes' => $notes,
                'resolved_by' => in_array($status, ['REPAIRED', 'RESTOCKED', 'WRITTEN_OFF'], true) ? $userId : null,
                'resolved_at' => in_array($status, ['REPAIRED', 'RESTOCKED', 'WRITTEN_OFF'], true) ? date('Y-m-d H:i:s') : null,
            ]);
            AuditService::log('production', 'update', 'production_inventory_damages', $damageId, [
                'status' => $damage['status'],
            ], [
                'status' => $status,
                'tag_code' => $damage['tag_code'],
                'defect_notes' => $notes,
            ], $userId);
            Database::commit();
            Session::flash('success', 'Damage case updated. Only repaired or restocked pieces return to available Production inventory.');
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            Session::flash('error', 'Could not update damage case: ' . $e->getMessage());
        }
        Response::redirect(url('production/inventory-damages/view/' . $damageId));
    }

    private function findAvailablePiece(string $tag): ?array
    {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return null;
        }
        $unitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($unitId === 0) {
            return null;
        }
        $sql = "SELECT DISTINCT qb.id
                FROM qc_batches qb
                JOIN production_programs pp ON pp.id = qb.program_id
                JOIN production_units pu ON pu.id = pp.production_unit_id
                JOIN qc_items qi ON qi.qc_batch_id = qb.id AND qi.passed_qty > 0
                WHERE pu.concern_id = ?";
        $params = [$concernId];
        if ($unitId !== null) {
            $sql .= ' AND pp.production_unit_id = ?';
            $params[] = $unitId;
        }
        $sql .= ' ORDER BY qb.id DESC';

        foreach (Database::fetchAll($sql, $params) as $batch) {
            $batchId = (int)$batch['id'];
            foreach (ProductionService::getAvailableQCPieceTags($batchId) as $qcItemId => $tags) {
                foreach ($tags as $availableTag) {
                    if (strtoupper(trim((string)$availableTag)) !== $tag) {
                        continue;
                    }
                    $piece = Database::fetchOne(
                        "SELECT qi.id AS qc_item_id, qi.qc_batch_id, qi.base_price, qi.discount_price,
                                qb.program_id, pp.production_unit_id,
                                COALESCE(NULLIF(qi.discount_price, 0), qi.base_price, 0) AS unit_value,
                                COALESCE(p.name, pp.name, 'Finished Product') AS product_name,
                                pp.program_number, qb.batch_number AS qc_batch_number,
                                qi.size_name, qi.color_name
                         FROM qc_items qi
                         JOIN qc_batches qb ON qb.id = qi.qc_batch_id
                         JOIN production_programs pp ON pp.id = qb.program_id
                         JOIN production_units pu ON pu.id = pp.production_unit_id
                         LEFT JOIN products p ON p.id = pp.product_id
                         WHERE qi.id = ? AND qb.id = ? AND pu.concern_id = ?",
                        [(int)$qcItemId, $batchId, $concernId]
                    );
                    if ($piece) {
                        $piece['tag_code'] = $tag;
                        return $piece;
                    }
                }
            }
        }
        return null;
    }

    private function isInCurrentUnit(array $damage): bool
    {
        $unitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($unitId === 0) {
            return false;
        }
        return $unitId === null || (int)($damage['production_unit_id'] ?? 0) === (int)$unitId;
    }
}