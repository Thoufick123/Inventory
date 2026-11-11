<?php

define('BASE_PATH', dirname(__DIR__));

final class Session
{
    private static array $data = [];

    public static function user(): ?array
    {
        return self::$data['user'] ?? null;
    }

    public static function userId(): ?int
    {
        return isset(self::$data['user']['id']) ? (int)self::$data['user']['id'] : null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$data[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$data[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset(self::$data[$key]);
    }
}

final class Database
{
    public static array $queries = [];
    public static array $validUnitsByConcern = [];

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        self::$queries[] = ['type' => 'one', 'sql' => $sql, 'params' => $params];
        if (str_contains($sql, 'FROM production_units') && count($params) >= 2) {
            $unitId = (int)$params[0];
            $concernId = (int)$params[1];
            if (!empty(self::$validUnitsByConcern[$concernId][$unitId])) {
                return ['id' => $unitId];
            }
        }
        return null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        self::$queries[] = ['type' => 'all', 'sql' => $sql, 'params' => $params];
        return [];
    }
}

final class User
{
    public static array $permissionsByUserId = [];

    public static function findById(int $id): ?array
    {
        $sessionUser = Session::user();
        if ($sessionUser && (int)($sessionUser['id'] ?? 0) === $id) {
            return $sessionUser;
        }
        return null;
    }

    public static function getPermissions(int $userId): array
    {
        return self::$permissionsByUserId[$userId] ?? [];
    }
}

final class Concern
{
    public static array $concerns = [
        1 => ['id' => 1, 'name' => 'SUPER ADMIN', 'module_keys' => ['production', 'qc', 'inventory']],
        2 => ['id' => 2, 'name' => 'SULUR SHOP', 'module_keys' => ['inventory', 'production_inward']],
        3 => ['id' => 3, 'name' => 'QC ONLY SHOP', 'module_keys' => ['qc']],
    ];

    public static function getActiveForCurrentUser(): ?array
    {
        $id = (int)Session::get('active_concern_id', 0);
        return self::$concerns[$id] ?? null;
    }

    public static function hasModule(int $concernId, string $moduleKey): bool
    {
        return in_array($moduleKey, self::$concerns[$concernId]['module_keys'] ?? [], true);
    }

    public static function hasFacilityModules(int $concernId, string $facilityType): bool
    {
        return false;
    }
}

final class StockService
{
    public static function assertLocationBelongsToActiveConcern(string $type, int $id): int
    {
        return $id;
    }
}

function testAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

require BASE_PATH . '/app/Helpers/Response.php';
require BASE_PATH . '/app/Services/ProductionInwardService.php';
require BASE_PATH . '/app/Models/ProductionProgram.php';
require BASE_PATH . '/app/Models/CuttingBatch.php';
require BASE_PATH . '/app/Models/StitchingBatch.php';
require BASE_PATH . '/app/Models/LabelBatch.php';
require BASE_PATH . '/app/Models/QCBatch.php';
require BASE_PATH . '/app/Models/ProductionInventoryDamage.php';
require BASE_PATH . '/app/Models/ProductionOutward.php';
require BASE_PATH . '/app/Models/ProductionBatch.php';
require BASE_PATH . '/app/Models/ProductionWarehouseRework.php';

// 1. Fail-closed checks when no concern is selected.
Session::set('user', ['id' => 1, 'role_id' => 1, 'role_slug' => 'sulur-shop-manager', 'is_active' => 1]);
Session::remove('active_concern_id');
testAssert(currentConcernId() === null, 'Active concern should start unset when active_concern_id is not chosen.');

Database::$queries = [];
testAssert(ProductionProgram::find(1) === null, 'ProductionProgram::find must fail closed without active concern.');
testAssert(ProductionProgram::findByNumber('PRG-1') === null, 'ProductionProgram::findByNumber must fail closed without active concern.');
testAssert(ProductionProgram::getAll() === [], 'ProductionProgram::getAll must fail closed without active concern.');
testAssert(CuttingBatch::find(1) === null, 'CuttingBatch::find must fail closed without active concern.');
testAssert(CuttingBatch::findByNumber('CUT-1') === null, 'CuttingBatch::findByNumber must fail closed without active concern.');
testAssert(CuttingBatch::getAll() === [], 'CuttingBatch::getAll must fail closed without active concern.');
testAssert(StitchingBatch::find(1) === null, 'StitchingBatch::find must fail closed without active concern.');
testAssert(StitchingBatch::getAll() === [], 'StitchingBatch::getAll must fail closed without active concern.');
testAssert(LabelBatch::find(1) === null, 'LabelBatch::find must fail closed without active concern.');
testAssert(LabelBatch::getAll() === [], 'LabelBatch::getAll must fail closed without active concern.');
testAssert(QCBatch::find(1) === null, 'QCBatch::find must fail closed without active concern.');
testAssert(QCBatch::getAll() === [], 'QCBatch::getAll must fail closed without active concern.');
testAssert(ProductionInventoryDamage::find(1) === null, 'ProductionInventoryDamage::find must fail closed without active concern.');
testAssert(ProductionInventoryDamage::getAll() === [], 'ProductionInventoryDamage::getAll must fail closed without active concern.');
testAssert((ProductionInventoryDamage::getSummaryStats()['total_cases'] ?? -1) === 0, 'ProductionInventoryDamage::getSummaryStats must return zeroes without active concern.');
testAssert(ProductionOutward::find(1) === null, 'ProductionOutward::find must fail closed without active concern.');
testAssert(ProductionOutward::findByBatchId(1) === null, 'ProductionOutward::findByBatchId must fail closed without active concern.');
testAssert(ProductionOutward::findByOutwardNumber('OUT-1') === null, 'ProductionOutward::findByOutwardNumber must fail closed without active concern.');
testAssert(ProductionOutward::getAll() === [], 'ProductionOutward::getAll must fail closed without active concern.');
testAssert(ProductionBatch::find(1) === null, 'ProductionBatch::find must fail closed without active concern.');
testAssert(ProductionBatch::findByNumber('BAT-1') === null, 'ProductionBatch::findByNumber must fail closed without active concern.');
testAssert(ProductionBatch::getAll() === [], 'ProductionBatch::getAll must fail closed without active concern.');
testAssert(ProductionBatch::approve(1, 1) === false, 'ProductionBatch::approve must fail closed without active concern.');
testAssert(ProductionBatch::updateStatus(1, 'APPROVED') === false, 'ProductionBatch::updateStatus must fail closed without active concern.');
testAssert(ProductionWarehouseRework::find(1) === null, 'ProductionWarehouseRework::find must fail closed without active concern.');
testAssert(ProductionWarehouseRework::getAll() === [], 'ProductionWarehouseRework::getAll must fail closed without active concern.');
testAssert((ProductionWarehouseRework::getStats()['total_records'] ?? -1) === 0, 'ProductionWarehouseRework::getStats must return zeroes without active concern.');
testAssert(ProductionInwardService::getConcernStock() === [], 'getConcernStock must fail closed without active concern.');
testAssert(ProductionInwardService::getStockAcrossUnits() === [], 'getStockAcrossUnits must fail closed without active concern.');
testAssert(Database::$queries === [], 'No SQL query may execute on fail-closed concern reads.');

// 2. Super Admin permission retention + concern switch freshness.
Session::set('active_concern_id', 2);
testAssert((currentConcern()['id'] ?? 0) === 2, 'currentConcern() must update when active_concern_id changes.');
testAssert(hasPermission('production', 'view') === true, 'Super Admin must keep Production module even on Inward-only concern.');
testAssert(hasPermission('qc', 'approve') === true, 'Super Admin must keep QC module even on Inward-only concern.');
testAssert(hasPermission('assets', 'view') === true, 'Super Admin must keep Assets module.');
testAssert(hasPermission('shop', 'view') === false, 'Super Admin must remain blocked from retired Shop module.');
testAssert(hasPermission('warehouse', 'view') === false, 'Super Admin must remain blocked from retired Warehouse module.');
testAssert(hasPermission('website_orders', 'view') === false, 'Super Admin must remain blocked from retired Website Orders module.');

// 3. Regular staff strict concern + role permission intersection & shared CONCERN inward stock access.
Session::set('user', [
    'id' => 25,
    'role_id' => 2,
    'role_slug' => 'sulur_inward_staff',
    'production_unit_id' => 5,
    'is_active' => 1,
]);
User::$permissionsByUserId[25] = ['inventory.view', 'production_inward.view', 'production_inward.receive', 'qc.view', 'dashboard.view'];
Session::set('permissions', User::$permissionsByUserId[25]);

// Stub Database::fetchOne for production_units check inside currentProductionUnitId().
$unitConcernValidated = false;
testAssert(hasPermission('inventory', 'view') === true, 'Staff must see Inventory when both role and concern include it.');
testAssert(hasPermission('production_inward', 'receive') === true, 'Staff must see Inward receive permission when both role and concern include it.');
testAssert(hasPermission('qc', 'view') === false, 'Staff must NOT see QC when active concern omits QC.');
testAssert(hasPermission('inventory', 'create') === false, 'Staff must NOT get create action omitted from their role.');
testAssert(hasPermission('dashboard', 'view') === false, 'Non-Super Admin staff must NOT access executive dashboard.');

Session::set('active_concern_id', 3);
testAssert((currentConcern()['id'] ?? 0) === 3, 'Switching active concern must refresh currentConcern() for staff too.');
testAssert(hasPermission('qc', 'view') === true, 'Staff with qc.view role permission gains QC on a concern that enables QC.');
testAssert(hasPermission('inventory', 'view') === false, 'Staff loses Inventory on a concern that omits Inventory.');

// 4. Shared CONCERN inward stock remains visible to unit-assigned staff while foreign units are blocked.
Session::set('active_concern_id', 2);
Database::$validUnitsByConcern = [2 => [5 => true]];
Database::$queries = [];
ProductionInwardService::getStockAcrossUnits(null);
$stockQueries = array_values(array_filter(
    Database::$queries,
    static fn(array $q): bool => str_contains($q['sql'], 'FROM stock_balances sb')
));
testAssert(count($stockQueries) === 1, 'Expected getStockAcrossUnits(null) to run the stock_balances query.');
testAssert(
    str_contains($stockQueries[0]['sql'], "sb.location_type = 'CONCERN' AND sb.location_id = ?"),
    'Unit-assigned staff must see shared CONCERN inward stock alongside their own unit.'
);
testAssert(
    $stockQueries[0]['params'] === [2, 2, 2, 5, 2],
    'Expected concern + assigned unit params [2, 2, 2, 5, 2].'
);
Database::$queries = [];
testAssert(
    ProductionInwardService::getStockAcrossUnits(6) === [],
    'Unit-assigned staff must be blocked from viewing another production unit stock.'
);

echo "PASS: Super Admin retains non-retired modules across concern switches while all production models fail closed and staff permissions follow role+concern boundaries.\n";
