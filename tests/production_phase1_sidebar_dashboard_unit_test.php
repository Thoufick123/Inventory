<?php

define('BASE_PATH', dirname(__DIR__));

final class TestPhase1RedirectException extends RuntimeException
{
    public function __construct(public readonly int $statusCode, string $message)
    {
        parent::__construct($message);
    }
}

final class Session
{
    private static array $data = [];
    private static array $flash = [];

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

    public static function flash(string $type, string $message): void
    {
        self::$flash[$type][] = $message;
    }

    public static function getFlash(string $type): array
    {
        $msgs = self::$flash[$type] ?? [];
        unset(self::$flash[$type]);
        return $msgs;
    }
}

final class CSRF
{
    public static function validateRequest(): bool
    {
        return true;
    }

    public static function metaTag(): string
    {
        return '<meta name="csrf-token" content="test">';
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="test">';
    }
}

final class Concern
{
    public static array $concerns = [
        1 => [
            'id' => 1,
            'name' => 'SUPER ADMIN CONCERN',
            'module_keys' => [
                'production', 'sellers', 'damages', 'catalog', 'programs', 'cutting',
                'wastage', 'stitching', 'stitching_wastage', 'production_expenses',
                'production_cash', 'labels', 'qc', 'inventory', 'production_inward',
                'batches', 'outward', 'production_billing', 'production_billing_returns',
                'warehouse_rework',
            ],
        ],
        2 => [
            'id' => 2,
            'name' => 'SULUR SHOP',
            'module_keys' => [
                'batches', 'inventory', 'outward', 'production',
                'production_billing', 'production_billing_returns',
                'production_cash', 'production_inward',
            ],
        ],
        3 => [
            'id' => 3,
            'name' => 'FACTORY CONCERN',
            'module_keys' => [
                'production', 'programs', 'cutting', 'stitching', 'labels', 'qc',
                'batches', 'outward', 'sellers', 'wastage', 'stitching_wastage',
                'production_expenses',
            ],
        ],
        4 => [
            'id' => 4,
            'name' => 'MULTI UNIT REWORK',
            'module_keys' => ['production', 'warehouse_rework'],
        ],
    ];

    public static function getActiveForCurrentUser(): ?array
    {
        $id = (int)Session::get('active_concern_id', 0);
        return self::$concerns[$id] ?? null;
    }

    public static function getAccessibleToUser(int $userId, bool $isSuperAdmin): array
    {
        return array_values(self::$concerns);
    }

    public static function hasFacilityModules(int $concernId, string $facilityType): bool
    {
        if (strtoupper($facilityType) !== 'PRODUCTION') {
            return false;
        }
        return in_array($concernId, [1, 3], true);
    }
}

final class User
{
    public static array $permissionsByUserId = [];

    public static function getPermissions(int $userId): array
    {
        return self::$permissionsByUserId[$userId] ?? [];
    }
}

final class TestDbState
{
    public static array $unitsByConcern = [
        1 => [
            1 => ['id' => 1, 'concern_id' => 1, 'name' => 'Main Unit', 'code' => 'PU-MAIN', 'is_active' => 1],
        ],
        2 => [],
        3 => [
            10 => ['id' => 10, 'concern_id' => 3, 'name' => 'Factory Unit 1', 'code' => 'PU-F1', 'is_active' => 1],
            11 => ['id' => 11, 'concern_id' => 3, 'name' => 'Factory Unit 2', 'code' => 'PU-F2', 'is_active' => 1],
        ],
    ];

    public static array $purchases = [
        101 => ['id' => 101, 'concern_id' => 3, 'production_unit_id' => 10, 'purchase_number' => 'PP-2026-000101', 'seller_id' => 1, 'seller_name' => 'Supplier A', 'company' => 'Comp A', 'seller_company' => 'Comp A', 'seller_phone' => '9999999999', 'seller_email' => '', 'seller_gst_number' => '', 'seller_city' => '', 'seller_state' => '', 'seller_address' => '', 'seller_payment_terms' => '', 'purchase_date' => '2026-11-01', 'invoice_number' => 'INV-10', 'document_url' => null, 'notes' => null, 'total_amount' => 1000],
        102 => ['id' => 102, 'concern_id' => 3, 'production_unit_id' => 11, 'purchase_number' => 'PP-2026-000102', 'seller_id' => 1, 'seller_name' => 'Supplier A', 'company' => 'Comp A', 'seller_company' => 'Comp A', 'seller_phone' => '9999999999', 'seller_email' => '', 'seller_gst_number' => '', 'seller_city' => '', 'seller_state' => '', 'seller_address' => '', 'seller_payment_terms' => '', 'purchase_date' => '2026-11-02', 'invoice_number' => 'INV-11', 'document_url' => null, 'notes' => null, 'total_amount' => 2500],
        201 => ['id' => 201, 'concern_id' => 2, 'production_unit_id' => null, 'purchase_number' => 'PP-2026-000201', 'seller_id' => 2, 'seller_name' => 'Supplier B', 'company' => 'Comp B', 'seller_company' => 'Comp B', 'seller_phone' => '8888888888', 'seller_email' => '', 'seller_gst_number' => '', 'seller_city' => '', 'seller_state' => '', 'seller_address' => '', 'seller_payment_terms' => '', 'purchase_date' => '2026-11-03', 'invoice_number' => 'INV-20', 'document_url' => null, 'notes' => null, 'total_amount' => 500],
    ];

    public static array $expenses = [
        501 => ['id' => 501, 'concern_id' => 3, 'production_unit_id' => 10, 'production_unit' => 'PU-F1', 'expense_number' => 'EXP-TEST-UNIT1', 'item_name' => 'Needles', 'quantity' => 5, 'unit_cost' => 100, 'total_cost' => 500, 'paid_amount' => 500, 'payment_status' => 'PAID', 'payment_method' => 'CASH', 'vendor_name' => 'Direct', 'expense_date' => '2026-11-01', 'category' => 'GENERAL', 'unit_type' => 'PCS', 'photo_url' => null],
        502 => ['id' => 502, 'concern_id' => 3, 'production_unit_id' => 11, 'production_unit' => 'PU-F2', 'expense_number' => 'EXP-TEST-UNIT2', 'item_name' => 'Oil', 'quantity' => 2, 'unit_cost' => 300, 'total_cost' => 600, 'paid_amount' => 600, 'payment_status' => 'PAID', 'payment_method' => 'CASH', 'vendor_name' => 'Direct', 'expense_date' => '2026-11-02', 'category' => 'GENERAL', 'unit_type' => 'PCS', 'photo_url' => null],
        601 => ['id' => 601, 'concern_id' => 2, 'production_unit_id' => null, 'production_unit' => 'GENERAL', 'expense_number' => 'EXP-TEST-SHOP2', 'item_name' => 'Shop Packing Tape', 'quantity' => 4, 'unit_cost' => 50, 'total_cost' => 200, 'paid_amount' => 200, 'payment_status' => 'PAID', 'payment_method' => 'CASH', 'vendor_name' => 'Direct', 'expense_date' => '2026-11-03', 'category' => 'GENERAL', 'unit_type' => 'PCS', 'photo_url' => null],
    ];
}

final class Database
{
    public static array $queries = [];
    public static array $executions = [];
    private static int $nextInsertId = 900;

    public static function beginTransaction(): bool { return true; }
    public static function commit(): bool { return true; }
    public static function rollBack(): bool { return true; }
    public static function inTransaction(): bool { return false; }
    public static function lastInsertId(): string { return (string)(++self::$nextInsertId); }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        self::$queries[] = ['one', $sql, $params];

        if (str_contains($sql, 'FROM users u')) {
            return null;
        }
        if (str_contains($sql, 'FROM production_units') && count($params) >= 2 && str_contains($sql, 'id = ? AND concern_id = ?')) {
            $unitId = (int)$params[0];
            $concernId = (int)$params[1];
            return TestDbState::$unitsByConcern[$concernId][$unitId] ?? null;
        }
        if (str_contains($sql, 'FROM production_purchases') && (str_contains($sql, 'WHERE id = ?') || str_contains($sql, 'WHERE pp.id = ?')) && !str_contains($sql, 'SUM(')) {
            $id = (int)$params[0];
            $concernId = (int)$params[1];
            $unitFilter = count($params) >= 3 ? (int)$params[2] : null;
            $row = TestDbState::$purchases[$id] ?? null;
            if (!$row || (int)$row['concern_id'] !== $concernId) {
                return null;
            }
            if ($unitFilter !== null && (int)($row['production_unit_id'] ?? 0) !== $unitFilter) {
                return null;
            }
            return $row;
        }
        if (str_contains($sql, 'FROM production_expenses') && str_contains($sql, 'WHERE') && str_contains($sql, 'id = ?')) {
            $id = (int)$params[0];
            $concernId = (int)$params[1];
            $unitFilter = count($params) >= 3 ? (int)$params[2] : null;
            $row = TestDbState::$expenses[$id] ?? null;
            if (!$row || (int)$row['concern_id'] !== $concernId) {
                return null;
            }
            if ($unitFilter !== null && (int)($row['production_unit_id'] ?? 0) !== $unitFilter) {
                return null;
            }
            return $row;
        }
        if (str_contains($sql, 'SELECT COUNT(*) as cnt FROM production_expenses')) {
            return ['cnt' => count(TestDbState::$expenses)];
        }
        if (str_contains($sql, 'SELECT COALESCE(SUM(total_amount)')) {
            return ['total' => 3500];
        }
        return null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        self::$queries[] = ['all', $sql, $params];

        if (str_contains($sql, 'FROM production_units WHERE concern_id = ?')) {
            $concernId = (int)$params[0];
            $units = TestDbState::$unitsByConcern[$concernId] ?? [];
            if (count($params) >= 2 && str_contains($sql, 'AND id = ?')) {
                $uid = (int)$params[1];
                return isset($units[$uid]) ? [$units[$uid]] : [];
            }
            return array_values($units);
        }
        if (str_contains($sql, 'FROM production_purchases pp')) {
            $concernId = (int)$params[0];
            $unitFilter = count($params) >= 2 ? (int)$params[1] : null;
            return array_values(array_filter(TestDbState::$purchases, static function (array $row) use ($concernId, $unitFilter): bool {
                if ((int)$row['concern_id'] !== $concernId) {
                    return false;
                }
                return $unitFilter === null || (int)($row['production_unit_id'] ?? 0) === $unitFilter;
            }));
        }
        if (str_contains($sql, 'FROM production_expenses pe')) {
            $concernId = (int)$params[0];
            $unitFilter = count($params) >= 2 && str_contains($sql, 'pe.production_unit_id = ?') ? (int)$params[1] : null;
            return array_values(array_filter(TestDbState::$expenses, static function (array $row) use ($concernId, $unitFilter): bool {
                if ((int)$row['concern_id'] !== $concernId) {
                    return false;
                }
                return $unitFilter === null || (int)($row['production_unit_id'] ?? 0) === $unitFilter;
            }));
        }
        return [];
    }

    public static function execute(string $sql, array $params = []): bool
    {
        self::$executions[] = [$sql, $params];
        $placeholderCount = substr_count($sql, '?');
        if ($placeholderCount !== count($params)) {
            throw new RuntimeException("SQL placeholder mismatch ({$placeholderCount} '?' vs " . count($params) . " params) in: {$sql}");
        }
        return true;
    }
}

function env(string $key, mixed $default = null): mixed
{
    return $default;
}

function testAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

// Intercept redirects/views cleanly by overriding Response only after we copy view logic, or simply test with real Response.php
// Wait: Response::view() renders views directly and prints output, while Response::redirect() sets headers and calls exit;
// So for controller tests invoking redirect/json/error, run pure assertions via Reflection + clean view captures!
require BASE_PATH . '/app/Helpers/Response.php';
require BASE_PATH . '/app/Services/ProductionService.php';
require BASE_PATH . '/app/Controllers/ProductionController.php';

// -----------------------------------------------------------------------------
// 1. SIDEBAR GATING (views/layouts/main.php)
// -----------------------------------------------------------------------------
function renderMainSidebarFor(array $user, array $perms, int $concernId): string
{
    User::$permissionsByUserId[(int)$user['id']] = $perms;
    Session::set('user', $user);
    Session::set('permissions', $perms);
    Session::set('active_concern_id', $concernId);

    $pageTitle = 'Test Dashboard';
    $activeModule = 'production';
    $activeMenu = 'prod-dashboard';
    $content = '<div>Body</div>';

    ob_start();
    require BASE_PATH . '/views/layouts/main.php';
    return (string)ob_get_clean();
}

$sulurSidebar = renderMainSidebarFor(
    ['id' => 601, 'role_id' => 3, 'role_slug' => 'sulur-shop-manager', 'full_name' => 'Jasmine', 'is_active' => 1],
    ['production.view', 'production_billing.view', 'production_billing.create', 'production_billing_returns.view', 'production_cash.view', 'inventory.view', 'production_inward.view', 'batches.view', 'outward.view'],
    2
);
testAssert(
    !str_contains($sulurSidebar, 'Rework from Warehouse'),
    'Sidebar must NOT show Rework from Warehouse when warehouse_rework is disabled in SULUR SHOP even though production.view is enabled.'
);
testAssert(
    str_contains($sulurSidebar, 'Billing Returns &amp; Exchanges') && str_contains($sulurSidebar, 'Inward / GRN'),
    'Sidebar must show permitted SULUR SHOP modules.'
);

$reworkSidebar = renderMainSidebarFor(
    ['id' => 602, 'role_id' => 3, 'role_slug' => 'rework-staff', 'full_name' => 'Rework User', 'is_active' => 1],
    ['production.view', 'warehouse_rework.view'],
    4
);
testAssert(
    str_contains($reworkSidebar, 'Rework from Warehouse'),
    'Sidebar MUST show Rework from Warehouse when both concern module and role permission allow it.'
);

// -----------------------------------------------------------------------------
// 2. PRODUCTION DASHBOARD STATS & VIEW (ProductionService::getDashboardStats + dashboard.php)
// -----------------------------------------------------------------------------
Database::$queries = [];
$failClosedStats = ProductionService::getDashboardStats(0, 2);
testAssert(
    (int)($failClosedStats['inwardStock']['available_qty'] ?? -1) === 0
    && (int)($failClosedStats['pendingInwards']['count'] ?? -1) === 0
    && (int)($failClosedStats['billingSummary']['total_count'] ?? -1) === 0
    && $failClosedStats['recentInwards'] === []
    && $failClosedStats['recentBills'] === [],
    'getDashboardStats(0, 2) must fail closed with empty inward and billing KPIs.'
);

Database::$queries = [];
$sulurStats = ProductionService::getDashboardStats(null, 2);
$dashboardSqlList = implode("\n", array_column(Database::$queries, 1));
testAssert(
    str_contains($dashboardSqlList, 'FROM stock_balances sb')
    && str_contains($dashboardSqlList, "sb.location_type = 'CONCERN'")
    && str_contains($dashboardSqlList, 'FROM production_inwards pi')
    && str_contains($dashboardSqlList, 'FROM production_bills pb'),
    'getDashboardStats must query concern inward stock_balances, production_inwards, and production_bills.'
);

// Render dashboard view as non-Super-Admin SULUR SHOP user
User::$permissionsByUserId[603] = [
    'production.view', 'inventory.view', 'production_inward.view',
    'production_billing.view', 'production_billing.create', 'batches.view', 'outward.view',
];
Session::set('user', ['id' => 603, 'role_id' => 3, 'role_slug' => 'sulur-shop-manager', 'full_name' => 'Jasmine', 'is_active' => 1]);
Session::set('permissions', User::$permissionsByUserId[603]);
Session::set('active_concern_id', 2);

$statsViewData = array_merge($sulurStats, [
    'inwardStock' => ['variant_count' => 3, 'available_qty' => 26, 'damaged_qty' => 1],
    'pendingInwards' => ['count' => 2, 'total_qty' => 15],
    'inwardReceipts' => ['month_count' => 1, 'month_qty' => 26, 'total_count' => 1],
    'recentInwards' => [['grn_number' => 'PGRN-0001', 'source_batch_number' => 'BAT-01', 'destination_name' => 'SULUR SHOP', 'available_qty' => 26, 'damaged_qty' => 1, 'received_at' => '2026-11-11 08:00:00']],
    'billingSummary' => ['month_count' => 2, 'month_total' => 1950.0, 'total_count' => 2, 'total_amount' => 1950.0, 'pending_count' => 0],
    'recentBills' => [['id' => 12, 'bill_number' => 'PB-0012', 'bill_date' => '2026-11-11', 'buyer_type' => 'CUSTOMER', 'customer_name' => 'Walk-in Buyer', 'company_name' => null, 'total_amount' => 1200.0, 'payment_status' => 'PAID']],
    'scopedUnitId' => null,
    'activeConcern' => Concern::$concerns[2],
]);
extract($statsViewData);
ob_start();
require BASE_PATH . '/views/production/dashboard.php';
$sulurDashHtml = (string)ob_get_clean();

testAssert(
    !str_contains($sulurDashHtml, 'Other production workflows remain unavailable'),
    'Dashboard must NOT display the stale data isolation warning banner.'
);
testAssert(
    !str_contains($sulurDashHtml, 'No manufacturing production units are assigned')
    && !str_contains($sulurDashHtml, 'Active Programs')
    && !str_contains($sulurDashHtml, 'Pending QC')
    && !str_contains($sulurDashHtml, 'Production Lifecycle Stages'),
    'SULUR SHOP dashboard must not show manufacturing unit warnings or unconfigured Programs/QC/Lifecycle cards.'
);
testAssert(
    str_contains($sulurDashHtml, 'Inward Stock (Pcs)')
    && str_contains($sulurDashHtml, '26')
    && str_contains($sulurDashHtml, 'Pending Inwards')
    && str_contains($sulurDashHtml, 'Month Billing')
    && str_contains($sulurDashHtml, 'PGRN-0001')
    && str_contains($sulurDashHtml, 'PB-0012')
    && str_contains($sulurDashHtml, 'production/billing/create')
    && str_contains($sulurDashHtml, 'production/outward'),
    'SULUR SHOP dashboard must render inward stock, pending inwards, billing KPIs, recent GRNs/bills, and clickable quick actions for permitted modules.'
);

// Render dashboard view as non-Super-Admin Factory Manager WITH manufacturing permissions
User::$permissionsByUserId[604] = [
    'production.view', 'programs.view', 'programs.create', 'cutting.view', 'qc.view', 'batches.view', 'outward.view',
];
Session::set('user', ['id' => 604, 'role_id' => 4, 'role_slug' => 'factory-manager', 'full_name' => 'Factory Lead', 'is_active' => 1]);
Session::set('permissions', User::$permissionsByUserId[604]);
Session::set('active_concern_id', 3);

$factoryDashData = array_merge(ProductionService::getDashboardStats(null, 3), [
    'recentPrograms' => [[
        'id' => 77,
        'program_number' => 'PRG-20261111-0001',
        'product_name' => 'Test Tee',
        'product_code' => 'TT-01',
        'unit_name' => 'Factory Unit 1',
        'planned_quantity' => 100,
        'completed_quantity' => 40,
        'priority' => 'HIGH',
        'status' => 'IN_PROGRESS',
        'created_at' => '2026-11-10 10:00:00',
    ]],
    'scopedUnitId' => null,
    'activeConcern' => Concern::$concerns[3],
]);
extract($factoryDashData);
ob_start();
require BASE_PATH . '/views/production/dashboard.php';
$factoryDashHtml = (string)ob_get_clean();
testAssert(
    str_contains($factoryDashHtml, 'production/programs/create')
    && str_contains($factoryDashHtml, 'production/programs/view/77')
    && str_contains($factoryDashHtml, 'production/qc')
    && str_contains($factoryDashHtml, 'production/outward'),
    'Permitted non-Super-Admin staff must see New Program, Program view links, All QC, and All Outwards links (previously hidden behind isSuperAdmin).'
);

// -----------------------------------------------------------------------------
// 3. MULTI-UNIT & UNIT-LESS SCOPE HELPERS (ProductionController.php)
// -----------------------------------------------------------------------------
$controller = new ProductionController();
$refScoped = new ReflectionMethod(ProductionController::class, 'scopedProductionUnitId');
$refScoped->setAccessible(true);
$refWriteUnit = new ReflectionMethod(ProductionController::class, 'resolveWriteProductionUnitId');
$refWriteUnit->setAccessible(true);
$refGetPurchase = new ReflectionMethod(ProductionController::class, 'getProductionPurchase');
$refGetPurchase->setAccessible(true);
$refRecordAccess = new ReflectionMethod(ProductionController::class, 'assertRecordProductionUnitAccess');
$refRecordAccess->setAccessible(true);

// 3a. Multi-unit concern (Concern #3 has units 10 & 11) with staff on -- All Production Units --
Session::set('user', ['id' => 605, 'role_id' => 4, 'role_slug' => 'factory-manager', 'production_unit_id' => null, 'is_active' => 1]);
Session::set('active_concern_id', 3);

testAssert($refScoped->invoke($controller) === null, 'Multi-unit manager with no pinned unit must resolve scopedProductionUnitId() = null.');
testAssert($refGetPurchase->invoke($controller, 101) !== null, 'Multi-unit manager must load Unit 10 purchase.');
testAssert($refGetPurchase->invoke($controller, 102) !== null, 'Multi-unit manager must ALSO load Unit 11 purchase (not narrowed to $units[0]).');
testAssert($refGetPurchase->invoke($controller, 201) === null, 'Multi-unit manager must NOT load Concern #2 purchase.');
testAssert($refWriteUnit->invoke($controller, 11) === 11, 'Multi-unit manager can explicitly write to unit 11 in same concern.');

$foreignUnitRejected = false;
try {
    $refWriteUnit->invoke($controller, 1); // Unit 1 belongs to Concern 1, not Concern 3
} catch (InvalidArgumentException $e) {
    $foreignUnitRejected = true;
}
testAssert($foreignUnitRejected, 'Writing to a foreign concern production unit must be rejected.');

// Check productionPurchaseList / expensesList / wastageList / stitchingWastageList across multi-unit concern #3
ob_start();
$controller->productionPurchaseList();
$multiPurchaseHtml = (string)ob_get_clean();
testAssert(
    str_contains($multiPurchaseHtml, 'PP-2026-000101')
    && str_contains($multiPurchaseHtml, 'PP-2026-000102')
    && !str_contains($multiPurchaseHtml, 'PP-2026-000201'),
    'Multi-unit productionPurchaseList() must display BOTH Unit 10 and Unit 11 purchases in Concern #3 and exclude Concern #2.'
);

ob_start();
$controller->expensesList();
$multiExpenseHtml = (string)ob_get_clean();
testAssert(
    str_contains($multiExpenseHtml, 'EXP-TEST-UNIT1')
    && str_contains($multiExpenseHtml, 'EXP-TEST-UNIT2')
    && !str_contains($multiExpenseHtml, 'EXP-TEST-SHOP2')
    && str_contains($multiExpenseHtml, 'id="newExpenseUnitId"'),
    'Multi-unit expensesList() must display BOTH Unit 10 and Unit 11 expenses, exclude Concern #2, and render unit selector when >1 unit exists.'
);

// 3b. Pinned-unit staff (pinned to Unit 11 in Concern #3)
Session::set('user', ['id' => 606, 'role_id' => 4, 'role_slug' => 'unit2-staff', 'production_unit_id' => 11, 'is_active' => 1]);
Session::set('active_concern_id', 3);

testAssert($refScoped->invoke($controller) === 11, 'Unit-11-pinned staff must resolve scopedProductionUnitId() = 11.');
testAssert($refGetPurchase->invoke($controller, 102) !== null, 'Unit-11-pinned staff can load Unit 11 purchase.');
testAssert($refGetPurchase->invoke($controller, 101) === null, 'Unit-11-pinned staff must be blocked from Unit 10 purchase.');

$crossUnitWriteRejected = false;
try {
    $refWriteUnit->invoke($controller, 10);
} catch (InvalidArgumentException $e) {
    $crossUnitWriteRejected = true;
}
testAssert($crossUnitWriteRejected, 'Unit-11-pinned staff cannot submit production_unit_id=10.');

ob_start();
$controller->expensesList();
$unit11ExpenseHtml = (string)ob_get_clean();
testAssert(
    str_contains($unit11ExpenseHtml, 'EXP-TEST-UNIT2')
    && !str_contains($unit11ExpenseHtml, 'EXP-TEST-UNIT1')
    && !str_contains($unit11ExpenseHtml, 'id="newExpenseUnitId"'),
    'Unit-11-pinned staff expensesList() must show ONLY Unit 11 expenses and not show multi-unit dropdown.'
);

// 3c. Unit-less concern (Concern #2 SULUR SHOP has zero production_units)
Session::set('user', ['id' => 607, 'role_id' => 3, 'role_slug' => 'sulur-shop-manager', 'production_unit_id' => null, 'is_active' => 1]);
Session::set('active_concern_id', 2);

testAssert($refScoped->invoke($controller) === null, 'Unit-less concern staff must resolve scopedProductionUnitId() = null.');
testAssert($refWriteUnit->invoke($controller, null) === null, 'Unit-less concern write must resolve production_unit_id = null without throwing.');
testAssert($refGetPurchase->invoke($controller, 201) !== null, 'Unit-less concern must still access its own concern purchase (production_unit_id = null).');
ob_start();
$controller->wastageList();
ob_end_clean();
ob_start();
$controller->stitchingWastageList();
ob_end_clean();
ob_start();
$controller->expensesList();
$unitlessExpenseHtml = (string)ob_get_clean();
testAssert(
    str_contains($unitlessExpenseHtml, 'EXP-TEST-SHOP2') && !str_contains($unitlessExpenseHtml, 'EXP-TEST-UNIT1'),
    'Unit-less concern list pages (wastage/stitching-wastage/expenses) must not throw and must show Concern #2 data only.'
);

// 3d. Staff pinned to a production unit outside the active concern (fails closed cleanly)
Session::set('user', ['id' => 608, 'role_id' => 4, 'role_slug' => 'foreign-unit-staff', 'production_unit_id' => 1, 'is_active' => 1]);
Session::set('active_concern_id', 3);
testAssert($refScoped->invoke($controller) === 0, 'Staff assigned to a foreign concern unit must resolve scopedProductionUnitId() = 0.');
testAssert($refGetPurchase->invoke($controller, 101) === null, 'Foreign-unit staff must see null on getProductionPurchase().');

$foreignWriteThrew = false;
try {
    $refWriteUnit->invoke($controller, null);
} catch (RuntimeException $e) {
    $foreignWriteThrew = true;
}
testAssert($foreignWriteThrew, 'Foreign-unit staff must be blocked from writing.');

ob_start();
$controller->expensesList();
$foreignExpenseHtml = (string)ob_get_clean();
testAssert(
    !str_contains($foreignExpenseHtml, 'EXP-TEST-UNIT1') && !str_contains($foreignExpenseHtml, 'EXP-TEST-UNIT2'),
    'Foreign-unit staff must render empty lists with zero leaked records.'
);

// 3e. Verify no activeConcernProductionUnitId references remain in ProductionController and storeExpense INSERT placeholders match params
$prodCtrlSource = file_get_contents(BASE_PATH . '/app/Controllers/ProductionController.php');
testAssert(
    !str_contains($prodCtrlSource, 'activeConcernProductionUnitId'),
    'Single-unit activeConcernProductionUnitId() must be completely removed from ProductionController.'
);

// Verify storeExpense INSERT SQL placeholder count via regex against source
preg_match('/INSERT INTO production_expenses\s*\(([^)]+)\)\s*VALUES\s*\(([^;]+)\)"/s', $prodCtrlSource, $m);
$cols = array_filter(array_map('trim', explode(',', $m[1] ?? '')));
$qMarks = substr_count($m[2] ?? '', '?');
testAssert(
    count($cols) === 19 && $qMarks === 17,
    "storeExpense() INSERT columns/placeholders check failed: cols=" . count($cols) . ", ?={$qMarks}."
);

echo "PASS: Phase 1 sidebar gating, dashboard KPIs/links, multi-unit & unit-less scope helpers, and storeExpense placeholders all passed.\n";
