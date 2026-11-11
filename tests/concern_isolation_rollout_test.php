<?php

define('BASE_PATH', dirname(__DIR__));

final class RouterResponseException extends RuntimeException
{
    public function __construct(public readonly int $statusCode, string $message)
    {
        parent::__construct($message);
    }
}

final class Response
{
    public static function error(int $code, string $message = ''): void
    {
        throw new RouterResponseException($code, $message);
    }

    public static function redirect(string $url, ?string $flashType = null, ?string $flashMessage = null): void
    {
        throw new RouterResponseException(302, $flashMessage ?? $url);
    }

    public static function json(array $data, int $statusCode = 200): void
    {
        throw new RouterResponseException($statusCode, (string)($data['error'] ?? 'JSON response'));
    }
}

final class AuthMiddleware
{
    public function handle(): void {}
}

final class Concern
{
    public static function hasModule(int $concernId, string $module): bool
    {
        return $concernId === (int)(currentConcern()['id'] ?? 0)
            && concernHasModule($module);
    }
}

$testConcern = ['id' => 1, 'module_keys' => []];
$testRoleAllowed = false;
$testSuperAdmin = false;

final class RolloutTestController
{
    public function dashboard(): void {}
    public function qcList(): void {}
}

function isSuperAdmin(): bool
{
    global $testSuperAdmin;
    return (bool)$testSuperAdmin;
}

function isGlobalConcernModule(string $module): bool
{
    return in_array($module, ['staff', 'roles', 'dashboard', 'settings', 'audit', 'backup', 'notifications'], true);
}

function currentConcern(): ?array
{
    global $testConcern;
    return $testConcern;
}

function currentConcernId(): ?int
{
    return (int)(currentConcern()['id'] ?? 0) ?: null;
}

function concernHasModule(string $module): bool
{
    $keys = currentConcern()['module_keys'] ?? [];
    return in_array('*', $keys, true) || in_array($module, $keys, true);
}

function hasRolePermission(string $module, ?string $action = null): bool
{
    global $testRoleAllowed;
    return $testRoleAllowed;
}

function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

require BASE_PATH . '/app/Middleware/PermissionMiddleware.php';
require BASE_PATH . '/app/Middleware/ConcernTransferPermissionMiddleware.php';
require BASE_PATH . '/routes/web.php';

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function dispatchAndCapture(Router $router, string $path, string $method = 'GET'): int
{
    try {
        $router->dispatch($path, $method);
    } catch (RouterResponseException $e) {
        return $e->statusCode;
    }

    throw new RuntimeException("Request reached a controller instead of being gated: {$method} {$path}");
}

function dispatchAllowedAndCapture(Router $router, string $path, string $method = 'GET'): int
{
    try {
        $router->dispatch($path, $method);
    } catch (RouterResponseException $e) {
        return $e->statusCode;
    }

    return 200;
}

function routePathForTest(string $path): string
{
    return preg_replace('/\{[a-zA-Z_]+\}/', '987654321', $path);
}

function isGlobalAdminPathForTest(string $path): bool
{
    foreach ([
        'admin/staff',
        'admin/legacy-data',
        'admin/roles',
        'admin/dashboard',
        'admin/concern/select',
        'concern/activate',
        'admin/change-password',
        'logout',
        'admin/audit',
        'admin/audit-logs',
        'system/',
        'api/notifications/',
    ] as $prefix) {
        if ($path === rtrim($prefix, '/') || str_starts_with($path, $prefix)) {
            return true;
        }
    }
    return false;
}

try {
    $router = new Router();
    $routesProperty = new ReflectionProperty(Router::class, 'routes');
    $routesProperty->setAccessible(true);
    $routes = $routesProperty->getValue($router);
    foreach ($routes as &$route) {
        if (in_array($route['path'], ['production/dashboard', 'production/qc'], true) && $route['method'] === 'GET') {
            $route['controller'] = 'RolloutTestController';
        }
    }
    unset($route);
    $routesProperty->setValue($router, $routes);
    $registeredPaths = array_column($routes, 'path');
    foreach ([
        'admin/production-units/store',
        'admin/warehouses/store',
        'admin/shops/store',
        'admin/channels/store',
        'api/admin/production-units/quick-add',
    ] as $facilityManagementPath) {
        assertTrue(
            !in_array($facilityManagementPath, $registeredPaths, true),
            "Removed Infrastructure Management route must not be registered: {$facilityManagementPath}"
        );
    }
    foreach (['reports/overview', 'reports/sales', 'asset/dashboard'] as $retainedFeaturePath) {
        assertTrue(
            in_array($retainedFeaturePath, $registeredPaths, true),
            "Retained Assets/Reports route must remain registered: {$retainedFeaturePath}"
        );
    }

    $operationalRouteCount = 0;
    $unauthenticatedPaths = [];
    $coverage = [
        'get' => 0,
        'post' => 0,
        'api' => 0,
        'reports' => 0,
        'search' => 0,
        'export_print' => 0,
    ];

    foreach ($routes as $route) {
        if (!in_array('AuthMiddleware', $route['middleware'], true)) {
            $unauthenticatedPaths[] = $route['path'];
            continue;
        }

        $path = routePathForTest($route['path']);
        if (isGlobalAdminPathForTest($path)) {
            continue;
        }

        $permissionMiddleware = null;
        foreach ($route['middleware'] as $middleware) {
            if (is_array($middleware) && ($middleware[0] ?? null) === 'PermissionMiddleware') {
                $permissionMiddleware = $middleware;
                break;
            }
        }
        if ($permissionMiddleware && isGlobalConcernModule((string)($permissionMiddleware[1] ?? ''))) {
            continue;
        }

        $status = dispatchAndCapture($router, $path, $route['method']);
        assertTrue(
            in_array($status, [403, 503], true),
            "Operational route must fail closed, got HTTP {$status}: {$route['method']} {$path}"
        );
        $operationalRouteCount++;
        $coverage[strtolower($route['method'])]++;
        $lowerPath = strtolower($path);
        if (str_starts_with($lowerPath, 'api/')) {
            $coverage['api']++;
        }
        if (str_starts_with($lowerPath, 'reports/')) {
            $coverage['reports']++;
        }
        if (preg_match('#search|lookup|scan#', $lowerPath)) {
            $coverage['search']++;
        }
        if (preg_match('#export|pdf|print|invoice|label|receipt#', $lowerPath . '/' . strtolower($route['action']))) {
            $coverage['export_print']++;
        }
    }

    assertTrue($operationalRouteCount > 100, 'Expected broad coverage of authenticated operational routes.');
    assertTrue(
        count(array_diff($unauthenticatedPaths, ['', 'login', 'logout', 'api/auth/check-status'])) === 0,
        'An unexpected route is missing authentication middleware.'
    );
    foreach (['get', 'post', 'api', 'reports', 'search', 'export_print'] as $coverageKey) {
        assertTrue($coverage[$coverageKey] > 0, "Expected route regression coverage for {$coverageKey} workflows.");
    }

    $testConcern = null;
    assertTrue(
        dispatchAndCapture($router, 'production/dashboard') === 302,
        'A staff member without an active concern must be sent to concern selection.'
    );

    $testConcern = ['id' => 10, 'module_keys' => ['warehouse']];
    assertTrue(
        dispatchAndCapture($router, 'production/dashboard') === 403,
        'A concern without the production module must reject a direct URL.'
    );

    $testConcern = ['id' => 11, 'module_keys' => ['production']];
    $testRoleAllowed = false;
    assertTrue(
        dispatchAndCapture($router, 'production/dashboard') === 403,
        'A concern module selection must not bypass the staff role permission.'
    );
    $testRoleAllowed = true;
    assertTrue(
        dispatchAllowedAndCapture($router, 'production/dashboard') === 200,
        'A staff member with the role permission and active concern module must be allowed.'
    );
    assertTrue(
        dispatchAllowedAndCapture($router, 'production/dashboard') === 200,
        'Refreshing an authorized operational page must remain allowed.'
    );

    $testConcern = ['id' => 14, 'module_keys' => []];
    assertTrue(
        dispatchAndCapture($router, 'production/dashboard') === 403,
        'An empty concern must not inherit access to operational modules.'
    );

    $testConcern = ['id' => 15, 'module_keys' => ['stock']];
    assertTrue(
        dispatchAndCapture($router, 'production/dashboard') === 403,
        'Switching to a concern without Production must deny the Production dashboard.'
    );

    // Super Admin still requires selecting an active concern for operational routes,
    // retains access to non-retired modules even when the active concern only enables a subset,
    // and remains blocked from retired modules.
    $testSuperAdmin = true;
    $testConcern = null;
    assertTrue(
        dispatchAndCapture($router, 'production/dashboard') === 302,
        'Super Admin without an active concern must be sent to concern selection.'
    );
    $testConcern = ['id' => 16, 'module_keys' => ['inventory', 'production_inward']];
    assertTrue(
        dispatchAllowedAndCapture($router, 'production/dashboard') === 200,
        'Super Admin must retain non-retired Production modules even when the active concern omits them.'
    );
    assertTrue(
        dispatchAllowedAndCapture($router, 'production/qc') === 200,
        'Super Admin must retain existing QC module routes on a concern with only Inward enabled.'
    );
    assertTrue(
        dispatchAndCapture($router, 'warehouse/stock') === 404,
        'Super Admin must remain blocked from retired Warehouse routes.'
    );
    $testSuperAdmin = false;

    $testConcern = ['id' => 12, 'module_keys' => ['warehouse', 'stock']];
    assertTrue(
        dispatchAndCapture($router, 'warehouse/transfers/receive/987654321', 'POST') === 404,
        'The retired Warehouse module must not register transfer routes.'
    );

    $testConcern = ['id' => 13, 'module_keys' => ['stock']];
    $testRoleAllowed = false;
    assertTrue(
        dispatchAndCapture($router, 'warehouse/stock') === 404,
        'The retired Warehouse module must not register stock routes.'
    );
    $testRoleAllowed = true;

    foreach ([
        ['warehouse/dashboard', 'GET'],
        ['warehouse/whatsapp-orders', 'GET'],
        ['warehouse/website-orders', 'GET'],
        ['shop/pos', 'GET'],
        ['sales/whatsapp/create', 'GET'],
        ['sales/website/create', 'GET'],
        ['reports/shop', 'GET'],
        ['reports/print/warehouse', 'GET'],
        ['api/shop/inward/scan', 'POST'],
        ['api/whatsapp/customers/lookup', 'GET'],
        ['api/warehouse-categories', 'GET'],
        ['admin/production-units/store', 'POST'],
        ['admin/warehouses/store', 'POST'],
        ['admin/shops/store', 'POST'],
        ['admin/channels/store', 'POST'],
        ['api/admin/production-units/quick-add', 'POST'],
    ] as [$path, $method]) {
        assertTrue(
            dispatchAndCapture($router, $path, $method) === 404,
            "A removed module or management route must not be registered: {$method} {$path}"
        );
    }

    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    assertTrue(
        dispatchAndCapture($router, 'shop/pos') === 404,
        'AJAX requests must not reach removed module routes.'
    );
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);

    echo "PASS: {$operationalRouteCount} authenticated operational routes fail closed.\n";
    echo 'PASS: GET=' . $coverage['get'] . ', POST=' . $coverage['post']
        . ', API=' . $coverage['api'] . ', reports=' . $coverage['reports']
        . ', search/lookup=' . $coverage['search'] . ', export/print=' . $coverage['export_print'] . ".\n";
    echo "PASS: retired module and Infrastructure Management routes return 404; Assets and Reports remain registered.\n";
    echo "PASS: concern selection, role permissions, direct IDs, and AJAX operational routes remain gated.\n";
    echo "PASS: staff access follows role permissions and the active concern module selection.\n";
    echo "NOTE: Database-backed row ownership and business workflow isolation require a separate test database.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
