<?php

class LegacyConcernDataService
{
    private const TABLES = [
        'production_units' => 'Production units',
        'warehouses' => 'Warehouses',
        'shops' => 'Shops',
        'sales_channels' => 'Website and WhatsApp channels',
        'stock_balances' => 'Stock balances',
        'stock_transactions' => 'Stock transactions',
        'stock_adjustments' => 'Stock adjustments',
        'production_batches' => 'Production batches',
        'production_batch_items' => 'Production batch items',
        'production_outwards' => 'Production outwards',
        'production_inwards' => 'Production inwards',
        'production_inward_items' => 'Production inward items',
        'warehouse_transfers' => 'Warehouse transfers (legacy)',
        'warehouse_allocations' => 'Warehouse allocations',
        'allocation_items' => 'Allocation items',
        'allocation_outwards' => 'Allocation outwards',
        'transfer_items' => 'Warehouse transfer items (legacy)',
        'customers' => 'Customers',
        'sales' => 'Sales',
        'payments' => 'Payments',
        'purchase_orders' => 'Purchase orders',
        'purchase_inwards' => 'Purchase inwards',
        'returns' => 'Returns',
        'exchanges' => 'Exchanges',
        'warehouse_returns' => 'Warehouse returns',
        'production_bills' => 'Production bills',
        'production_bill_returns' => 'Production bill returns',
        'production_purchases' => 'Production purchases',
        'production_expenses' => 'Production expenses',
        'wastage_sales' => 'Wastage sales',
        'stitching_wastage_sales' => 'Stitching wastage sales',
        'expenses' => 'Expenses',
        'cash_registers' => 'Cash registers',
        'cash_transactions' => 'Cash transactions',
        'cash_expenses' => 'Cash expenses',
        'bank_deposits' => 'Bank deposits',
        'production_cash_registers' => 'Production cash registers',
        'production_cash_transactions' => 'Production cash transactions',
        'production_cash_expenses' => 'Production cash expenses',
        'production_cash_deposits' => 'Production cash deposits',
    ];

    private const DISPLAY_COLUMNS = [
        'name',
        'invoice_number',
        'bill_number',
        'po_number',
        'grn_number',
        'adjustment_number',
        'reference_number',
        'code',
        'number',
        'date',
    ];

    private static array $columnCache = [];

    public static function getTables(): array
    {
        return self::TABLES;
    }

    public static function isAssignable(string $table): bool
    {
        return array_key_exists($table, self::TABLES)
            && !in_array($table, ['warehouse_transfers', 'transfer_items'], true);
    }

    public static function getSummary(): array
    {
        $summary = [];
        foreach (self::TABLES as $table => $label) {
            $columns = self::getColumns($table);
            if (!isset($columns['id'], $columns['concern_id'])) {
                $summary[] = [
                    'table' => $table,
                    'label' => $label,
                    'available' => false,
                    'unassigned' => 0,
                    'total' => 0,
                    'concerns' => [],
                ];
                continue;
            }

            $buckets = Database::fetchAll(
                "SELECT t.concern_id, c.name AS concern_name, COUNT(*) AS record_count
                 FROM `{$table}` t
                 LEFT JOIN concerns c ON c.id = t.concern_id
                 GROUP BY t.concern_id, c.name"
            );
            $unassigned = 0;
            $total = 0;
            $concerns = [];
            foreach ($buckets as $bucket) {
                $count = (int)$bucket['record_count'];
                $total += $count;
                if ($bucket['concern_id'] === null) {
                    $unassigned = $count;
                } else {
                    $concerns[] = [
                        'id' => (int)$bucket['concern_id'],
                        'name' => $bucket['concern_name'] ?: 'Concern #' . (int)$bucket['concern_id'],
                        'count' => $count,
                    ];
                }
            }

            $summary[] = [
                'table' => $table,
                'label' => $label,
                'available' => true,
                'unassigned' => $unassigned,
                'total' => $total,
                'concerns' => $concerns,
            ];
        }

        return $summary;
    }

    public static function getUnassignedRecords(string $table, int $limit = 50, int $offset = 0): array
    {
        self::assertKnownTable($table);
        $columns = self::getColumns($table);
        if (!isset($columns['id'], $columns['concern_id'])) {
            throw new RuntimeException("The ownership schema for '{$table}' is not installed.");
        }

        $displayColumn = null;
        foreach (self::DISPLAY_COLUMNS as $candidate) {
            if (isset($columns[$candidate])) {
                $displayColumn = $candidate;
                break;
            }
        }

        $displaySql = $displayColumn
            ? ", CAST(`{$displayColumn}` AS CHAR) AS display_value"
            : '';
        $records = Database::fetchAll(
            "SELECT id{$displaySql}
             FROM `{$table}`
             WHERE concern_id IS NULL
             ORDER BY id
             LIMIT " . max(1, min(50, $limit)) . " OFFSET " . max(0, $offset)
        );
        $count = Database::fetchOne(
            "SELECT COUNT(*) AS record_count FROM `{$table}` WHERE concern_id IS NULL"
        );

        return [
            'records' => $records,
            'total' => (int)($count['record_count'] ?? 0),
        ];
    }

    public static function assignUnassignedRecords(string $table, array $recordIds, int $concernId): int
    {
        self::assertAssignableTable($table);
        if ($concernId < 1) {
            throw new InvalidArgumentException('Choose a valid destination concern.');
        }

        $recordIds = array_values(array_unique(array_filter(
            array_map('intval', $recordIds),
            static fn(int $id): bool => $id > 0
        )));
        if (!$recordIds || count($recordIds) > 50) {
            throw new InvalidArgumentException('Select between 1 and 50 records to assign.');
        }

        $columns = self::getColumns($table);
        if (!isset($columns['id'], $columns['concern_id'])) {
            throw new RuntimeException("The ownership schema for '{$table}' is not installed.");
        }

        $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
        Database::beginTransaction();
        try {
            $rows = Database::fetchAll(
                "SELECT id FROM `{$table}`
                 WHERE concern_id IS NULL AND id IN ({$placeholders})
                 FOR UPDATE",
                $recordIds
            );
            $foundIds = array_map('intval', array_column($rows, 'id'));
            sort($foundIds);
            $expectedIds = $recordIds;
            sort($expectedIds);
            if ($foundIds !== $expectedIds) {
                throw new RuntimeException('One or more selected records are no longer unassigned. Refresh the list and try again.');
            }

            $updated = Database::execute(
                "UPDATE `{$table}` SET concern_id = ?
                 WHERE concern_id IS NULL AND id IN ({$placeholders})",
                array_merge([$concernId], $recordIds)
            );
            if ($updated !== count($recordIds)) {
                throw new RuntimeException('The assignment could not be completed for every selected record.');
            }

            Database::commit();
            return $updated;
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    private static function assertAssignableTable(string $table): void
    {
        if (!self::isAssignable($table)) {
            throw new InvalidArgumentException('This data table is not available for legacy assignment.');
        }
    }

    private static function assertKnownTable(string $table): void
    {
        if (!array_key_exists($table, self::TABLES)) {
            throw new InvalidArgumentException('This data table is not available for legacy review.');
        }
    }

    private static function getColumns(string $table): array
    {
        if (!array_key_exists($table, self::$columnCache)) {
            $rows = Database::fetchAll(
                "SELECT COLUMN_NAME
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$table]
            );
            self::$columnCache[$table] = array_fill_keys(
                array_column($rows, 'COLUMN_NAME'),
                true
            );
        }

        return self::$columnCache[$table];
    }
}
