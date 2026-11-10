<?php

class ConcernTransactionService
{
    private const OWNED_TABLES = [
        'sales',
        'payments',
        'purchase_orders',
        'purchase_inwards',
        'returns',
        'exchanges',
        'warehouse_returns',
        'production_bills',
        'production_bill_returns',
        'production_purchases',
        'expenses',
        'cash_registers',
        'cash_transactions',
        'cash_expenses',
        'bank_deposits',
        'production_cash_registers',
        'production_cash_transactions',
        'production_cash_expenses',
        'production_cash_deposits',
        'customers',
    ];

    public static function activeConcernId(): int
    {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$concernId) {
            throw new RuntimeException('Select an active concern before accessing business transactions.');
        }
        return (int)$concernId;
    }

    public static function assertFacility(string $type, int $facilityId): void
    {
        $type = strtoupper($type);
        $tables = [
            'PRODUCTION' => 'production_units',
            'WAREHOUSE' => 'warehouses',
            'SHOP' => 'shops',
            'CHANNEL' => 'sales_channels',
            'WHATSAPP' => 'sales_channels',
            'WEBSITE' => 'sales_channels',
        ];
        if (!isset($tables[$type]) || $facilityId <= 0) {
            throw new InvalidArgumentException('Choose a valid facility for this transaction.');
        }

        $concernId = self::activeConcernId();
        $facility = Database::fetchOne(
            "SELECT id FROM {$tables[$type]} WHERE id = ? AND concern_id = ?",
            [$facilityId, $concernId]
        );
        if (!$facility) {
            throw new InvalidArgumentException('The selected facility does not belong to the active concern.');
        }

        $scopedFacilities = match ($type) {
            'PRODUCTION' => function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : [],
            'WAREHOUSE' => function_exists('getScopedWarehouses') ? getScopedWarehouses() : [],
            'SHOP' => function_exists('getScopedShops') ? getScopedShops() : [],
            'CHANNEL', 'WHATSAPP', 'WEBSITE' => function_exists('getScopedSalesChannels')
                ? getScopedSalesChannels(in_array($type, ['WHATSAPP', 'WEBSITE'], true) ? $type : null)
                : [],
        };
        $scopedIds = array_map('intval', array_column($scopedFacilities, 'id'));
        if (!in_array($facilityId, $scopedIds, true)) {
            throw new InvalidArgumentException('The selected facility is not assigned to the active user.');
        }
    }

    public static function findOwned(string $table, int $id): ?array
    {
        if (!in_array($table, self::OWNED_TABLES, true)) {
            throw new InvalidArgumentException('Unsupported transaction table.');
        }
        if ($id <= 0) {
            return null;
        }

        return Database::fetchOne(
            "SELECT * FROM `{$table}` WHERE id = ? AND concern_id = ?",
            [$id, self::activeConcernId()]
        );
    }

    public static function assertOwned(string $table, int $id): array
    {
        $record = self::findOwned($table, $id);
        if (!$record) {
            throw new InvalidArgumentException('The requested record was not found in the active concern.');
        }
        return $record;
    }

    public static function assertCustomer(int $customerId): void
    {
        if ($customerId <= 0) {
            return;
        }
        $customer = Database::fetchOne(
            'SELECT id FROM customers WHERE id = ? AND concern_id = ?',
            [$customerId, self::activeConcernId()]
        );
        if (!$customer) {
            throw new InvalidArgumentException('The selected customer does not belong to the active concern.');
        }
    }
}
