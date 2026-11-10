<?php
/**
 * Concern-to-concern stock transfer workflow.
 */

require_once BASE_PATH . '/app/Models/TransferItem.php';
require_once BASE_PATH . '/app/Models/Concern.php';
require_once BASE_PATH . '/app/Services/StockService.php';

class WarehouseTransfer {

    public static function find(int $id): ?array {
        $concernId = self::activeConcernId();
        $transfer = Database::fetchOne(
            "SELECT wt.*, source_concern.name AS source_concern_name,
                    destination_concern.name AS destination_concern_name,
                    u_req.full_name AS requested_by_name,
                    u_appr.full_name AS approved_by_name,
                    u_dispatch.full_name AS dispatched_by_name,
                    u_receive.full_name AS received_by_name
             FROM warehouse_transfers wt
             JOIN concerns source_concern ON source_concern.id = wt.source_concern_id
             JOIN concerns destination_concern ON destination_concern.id = wt.destination_concern_id
             LEFT JOIN users u_req ON wt.requested_by = u_req.id
             LEFT JOIN users u_appr ON wt.approved_by = u_appr.id
             LEFT JOIN users u_dispatch ON wt.dispatched_by = u_dispatch.id
             LEFT JOIN users u_receive ON wt.received_by = u_receive.id
             WHERE wt.id = ?
               AND (wt.source_concern_id = ? OR wt.destination_concern_id = ?)",
            [$id, $concernId, $concernId]
        );
        if (!$transfer || !self::canAccessActiveSide($transfer, $concernId)) {
            return null;
        }

        $transfer['source_name'] = self::getLocationName(
            $transfer['source_type'],
            (int)$transfer['source_id'],
            (int)$transfer['source_concern_id']
        );
        $transfer['destination_name'] = self::getLocationName(
            $transfer['destination_type'],
            (int)$transfer['destination_id'],
            (int)$transfer['destination_concern_id']
        );
        $transfer['items'] = TransferItem::getByTransferId($id);
        $transfer['item_count'] = count($transfer['items']);
        $transfer['total_qty'] = array_sum(array_map(
            static fn(array $item): int => (int)($item['transferred_qty'] ?: $item['quantity']),
            $transfer['items']
        ));
        self::parseLogistics($transfer);
        return $transfer;
    }

    public static function getAll(array $filters = []): array {
        $concernId = self::activeConcernId();
        $sql = "SELECT wt.id
                FROM warehouse_transfers wt
                WHERE (wt.source_concern_id = ? OR wt.destination_concern_id = ?)";
        $params = [$concernId, $concernId];
        if (!empty($filters['status'])) {
            $sql .= " AND wt.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (wt.transfer_number LIKE ? OR wt.notes LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            array_push($params, $term, $term);
        }
        $sql .= " ORDER BY wt.created_at DESC";
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . max(1, (int)$filters['limit']);
        }

        $rows = Database::fetchAll($sql, $params);
        $transfers = [];
        foreach ($rows as $row) {
            $transfer = self::find((int)$row['id']);
            if ($transfer) {
                $transfers[] = $transfer;
            }
        }
        return $transfers;
    }

    public static function create(array $data): int {
        $sourceConcernId = self::activeConcernId();
        $destinationConcernId = (int)($data['destination_concern_id'] ?? 0);
        $sourceType = strtoupper((string)($data['source_type'] ?? ''));
        $destinationType = strtoupper((string)($data['destination_type'] ?? ''));
        $sourceId = (int)($data['source_id'] ?? 0);
        $destinationId = (int)($data['destination_id'] ?? 0);

        if ($destinationConcernId <= 0 || $destinationConcernId === $sourceConcernId) {
            throw new InvalidArgumentException('Choose a destination facility in a different concern.');
        }
        if (!Database::fetchOne(
            'SELECT id FROM concerns WHERE id = ? AND is_active = 1',
            [$destinationConcernId]
        )) {
            throw new InvalidArgumentException('The destination concern is unavailable.');
        }
        if (!Concern::hasModule($destinationConcernId, 'warehouse')) {
            throw new InvalidArgumentException('The destination concern must have stock transfers enabled.');
        }
        self::assertFacility($sourceType, $sourceId, $sourceConcernId, true);
        self::assertFacility($destinationType, $destinationId, $destinationConcernId, false);

        $requestedItems = $data['items'] ?? [];
        if (!is_array($requestedItems)) {
            throw new InvalidArgumentException('Transfer items must be submitted as a list.');
        }
        $items = [];
        foreach ($requestedItems as $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('A transfer item is invalid.');
            }
            $rawVariantId = $item['variant_id'] ?? null;
            $rawQuantity = $item['quantity'] ?? null;
            if ($rawVariantId === null || $rawQuantity === null || $rawQuantity === '') {
                continue;
            }
            $variantId = filter_var($rawVariantId, FILTER_VALIDATE_INT);
            $quantity = filter_var($rawQuantity, FILTER_VALIDATE_INT);
            if ($variantId === false || $variantId <= 0 || $quantity === false || $quantity <= 0) {
                throw new InvalidArgumentException('Transfer item IDs and quantities must be positive whole numbers.');
            }
            if (!Database::fetchOne('SELECT id FROM product_variants WHERE id = ? AND is_active = 1', [$variantId])) {
                throw new InvalidArgumentException('A transfer item is invalid or inactive.');
            }
            $items[$variantId] = ($items[$variantId] ?? 0) + $quantity;
        }
        if (!$items) {
            throw new InvalidArgumentException('Add at least one item with a positive quantity.');
        }

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO warehouse_transfers
                 (transfer_number, source_type, source_id, destination_type, destination_id,
                  concern_id, source_concern_id, destination_concern_id, requested_by, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'REQUESTED', ?)",
                [
                    $data['transfer_number'],
                    $sourceType,
                    $sourceId,
                    $destinationType,
                    $destinationId,
                    $sourceConcernId,
                    $sourceConcernId,
                    $destinationConcernId,
                    $data['requested_by'] ?? null,
                    $data['notes'] ?? null,
                ]
            );
            $transferId = (int)Database::lastInsertId();
            foreach ($items as $variantId => $quantity) {
                TransferItem::create([
                    'transfer_id' => $transferId,
                    'concern_id' => $sourceConcernId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'requested_qty' => $quantity,
                ]);
            }
            Database::commit();
            return $transferId;
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function dispatch(int $id, int $userId): void {
        $concernId = self::activeConcernId();
        Database::beginTransaction();
        try {
            $transfer = Database::fetchOne(
                "SELECT * FROM warehouse_transfers
                 WHERE id = ? AND source_concern_id = ? FOR UPDATE",
                [$id, $concernId]
            );
            if (!$transfer) {
                throw new InvalidArgumentException('Transfer not found in the active source concern.');
            }
            self::assertFacility(
                (string)$transfer['source_type'],
                (int)$transfer['source_id'],
                $concernId,
                true
            );
            if ($transfer['status'] !== 'REQUESTED') {
                throw new InvalidArgumentException('Only a requested transfer can be dispatched.');
            }

            $items = Database::fetchAll(
                'SELECT * FROM transfer_items WHERE transfer_id = ? ORDER BY id FOR UPDATE',
                [$id]
            );
            if (!$items) {
                throw new InvalidArgumentException('The transfer has no items to dispatch.');
            }
            foreach ($items as $item) {
                $quantity = (int)$item['quantity'];
                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Transfer quantities must be positive.');
                }
                StockService::decrease(
                    (int)$item['variant_id'],
                    (string)$transfer['source_type'],
                    (int)$transfer['source_id'],
                    'AVAILABLE',
                    $quantity,
                    'TRANSFER_OUT',
                    'warehouse_transfers',
                    $id,
                    $userId,
                    'Concern transfer ' . $transfer['transfer_number']
                );
                Database::execute(
                    'UPDATE transfer_items SET transferred_qty = ? WHERE id = ? AND transfer_id = ?',
                    [$quantity, (int)$item['id'], $id]
                );
            }
            Database::execute(
                "UPDATE warehouse_transfers
                 SET status = 'IN_TRANSIT', approved_by = ?, dispatched_by = ?, approved_at = NOW(),
                     dispatched_at = NOW(), updated_at = NOW()
                 WHERE id = ? AND source_concern_id = ? AND status = 'REQUESTED'",
                [$userId, $userId, $id, $concernId]
            );
            Database::commit();
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function receive(int $id, array $receipt, int $userId): void {
        $concernId = self::activeConcernId();
        Database::beginTransaction();
        try {
            $transfer = Database::fetchOne(
                "SELECT * FROM warehouse_transfers
                 WHERE id = ? AND destination_concern_id = ? FOR UPDATE",
                [$id, $concernId]
            );
            if (!$transfer) {
                throw new InvalidArgumentException('Transfer not found in the active destination concern.');
            }
            self::assertFacility(
                (string)$transfer['destination_type'],
                (int)$transfer['destination_id'],
                $concernId,
                true
            );
            if ($transfer['status'] !== 'IN_TRANSIT') {
                throw new InvalidArgumentException('Only an in-transit transfer can be received.');
            }

            $items = Database::fetchAll(
                'SELECT * FROM transfer_items WHERE transfer_id = ? ORDER BY id FOR UPDATE',
                [$id]
            );
            if (!$items) {
                throw new InvalidArgumentException('The transfer has no dispatched items.');
            }
            foreach ($items as $item) {
                $itemId = (int)$item['id'];
                if (!array_key_exists($itemId, $receipt)) {
                    throw new InvalidArgumentException('Enter the received quantity for every transfer item.');
                }
                if (!is_array($receipt[$itemId])) {
                    throw new InvalidArgumentException('A received item entry is invalid.');
                }
                $receivedQty = filter_var($receipt[$itemId]['received_qty'] ?? null, FILTER_VALIDATE_INT);
                $damagedQty = filter_var($receipt[$itemId]['damaged_qty'] ?? 0, FILTER_VALIDATE_INT);
                $transferredQty = (int)$item['transferred_qty'];
                if ($receivedQty === false || $damagedQty === false
                    || $receivedQty < 0 || $damagedQty < 0
                    || $damagedQty > $receivedQty || $receivedQty > $transferredQty) {
                    throw new InvalidArgumentException('Received and damaged quantities must be valid and cannot exceed the dispatched quantity.');
                }
                $availableQty = $receivedQty - $damagedQty;
                if ($availableQty > 0) {
                    StockService::increase(
                        (int)$item['variant_id'],
                        (string)$transfer['destination_type'],
                        (int)$transfer['destination_id'],
                        'AVAILABLE',
                        $availableQty,
                        'TRANSFER_IN',
                        'warehouse_transfers',
                        $id,
                        $userId,
                        'Concern transfer ' . $transfer['transfer_number']
                    );
                }
                if ($damagedQty > 0) {
                    StockService::increase(
                        (int)$item['variant_id'],
                        (string)$transfer['destination_type'],
                        (int)$transfer['destination_id'],
                        'DAMAGED',
                        $damagedQty,
                        'TRANSFER_IN_DAMAGED',
                        'warehouse_transfers',
                        $id,
                        $userId,
                        'Damaged on receipt of concern transfer ' . $transfer['transfer_number']
                    );
                }
                Database::execute(
                    'UPDATE transfer_items
                     SET received_qty = ?, damaged_qty = ?, discrepancy_qty = ?
                     WHERE id = ? AND transfer_id = ?',
                    [$receivedQty, $damagedQty, $transferredQty - $receivedQty, $itemId, $id]
                );
            }
            Database::execute(
                "UPDATE warehouse_transfers
                 SET status = 'RECEIVED', received_by = ?, received_at = NOW(), updated_at = NOW()
                 WHERE id = ? AND destination_concern_id = ? AND status = 'IN_TRANSIT'",
                [$userId, $id, $concernId]
            );
            Database::commit();
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function generateNumber(): string {
        $prefix = 'TRF-' . date('Ymd') . '-';
        $last = Database::fetchOne(
            'SELECT transfer_number FROM warehouse_transfers WHERE transfer_number LIKE ? ORDER BY id DESC LIMIT 1',
            [$prefix . '%']
        );
        $num = $last ? (int)substr($last['transfer_number'], -4) + 1 : 1;
        return $prefix . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
    }

    private static function activeConcernId(): int {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$concernId) {
            throw new RuntimeException('Select an active concern before accessing transfers.');
        }
        return (int)$concernId;
    }

    private static function assertFacility(string $type, int $id, int $concernId, bool $requireAssignment): void {
        $type = strtoupper($type);
        $table = match ($type) {
            'WAREHOUSE' => 'warehouses',
            'SHOP' => 'shops',
            default => throw new InvalidArgumentException('Only warehouse and shop facilities can participate in stock transfers.'),
        };
        $facility = Database::fetchOne(
            "SELECT id FROM {$table} WHERE id = ? AND concern_id = ? AND is_active = 1",
            [$id, $concernId]
        );
        if (!$facility) {
            throw new InvalidArgumentException('The selected transfer facility is inactive or belongs to another concern.');
        }
        $module = $type === 'WAREHOUSE' ? 'WAREHOUSE' : 'SHOP';
        if (!Concern::hasFacilityModules($concernId, $module)) {
            throw new InvalidArgumentException('The concern does not have the module required by this transfer facility.');
        }
        if ($requireAssignment) {
            self::assertAssignedFacility($type, $id);
            StockService::assertLocationBelongsToActiveConcern($type, $id);
        }
    }

    private static function canAccessActiveSide(array $transfer, int $concernId): bool {
        $types = [];
        if ((int)$transfer['source_concern_id'] === $concernId) {
            $types[] = [(string)$transfer['source_type'], (int)$transfer['source_id']];
        }
        if ((int)$transfer['destination_concern_id'] === $concernId) {
            $types[] = [(string)$transfer['destination_type'], (int)$transfer['destination_id']];
        }
        foreach ($types as [$type, $facilityId]) {
            try {
                self::assertAssignedFacility($type, $facilityId);
            } catch (InvalidArgumentException $e) {
                continue;
            }
            return true;
        }
        return false;
    }

    private static function assertAssignedFacility(string $type, int $facilityId): void {
        if (function_exists('isSuperAdmin') && isSuperAdmin()) {
            return;
        }
        $user = function_exists('currentUser') ? (currentUser() ?? []) : [];
        $assignedWarehouseId = (int)($user['warehouse_id'] ?? 0);
        $assignedShopId = (int)($user['shop_id'] ?? 0);
        if ($assignedWarehouseId === 0 && $assignedShopId === 0) {
            return;
        }

        $type = strtoupper($type);
        $assignedId = $type === 'WAREHOUSE' ? $assignedWarehouseId
            : ($type === 'SHOP' ? $assignedShopId : 0);
        $scopedId = $type === 'WAREHOUSE' && function_exists('currentWarehouseId')
            ? currentWarehouseId()
            : ($type === 'SHOP' && function_exists('currentShopId') ? currentShopId() : null);
        if ($assignedId <= 0 || $assignedId !== $facilityId || $scopedId !== $facilityId) {
            throw new InvalidArgumentException('This transfer facility is outside the staff member’s assigned facility scope.');
        }
    }

    private static function getLocationName(string $type, int $id, int $concernId): string {
        $table = strtoupper($type) === 'WAREHOUSE' ? 'warehouses' : 'shops';
        $location = Database::fetchOne(
            "SELECT name, code FROM {$table} WHERE id = ? AND concern_id = ?",
            [$id, $concernId]
        );
        if (!$location) {
            return strtoupper($type) . ' #' . $id;
        }
        $concern = Database::fetchOne('SELECT name FROM concerns WHERE id = ?', [$concernId]);
        return $location['name'] . (!empty($location['code']) ? ' (' . $location['code'] . ')' : '')
            . ($concern ? ' — ' . $concern['name'] : '');
    }

    private static function parseLogistics(array &$transfer): void {
        $logisticsKeys = [
            'carrier_name',
            'vehicle_no',
            'driver_phone',
            'dispatch_at',
            'expected_arrival',
        ];
        foreach ($logisticsKeys as $key) {
            $transfer[$key] = '';
        }
        if (preg_match('/\[LOGISTICS\](.*?)\[\/LOGISTICS\]/s', (string)($transfer['notes'] ?? ''), $matches)) {
            $logistics = json_decode($matches[1], true);
            if (is_array($logistics)) {
                foreach ($logisticsKeys as $key) {
                    if (array_key_exists($key, $logistics)) {
                        $transfer[$key] = $logistics[$key];
                    }
                }
            }
        }
    }
}
