<?php
/**
 * Response Helper
 * Handles view rendering, JSON responses, and redirects.
 */

class Response {
    /**
     * Render a view within the main layout.
     */
    public static function view(string $template, array $data = [], ?string $layout = 'main'): void {
        // Make data available as variables
        extract($data);
        
        // Capture the content
        ob_start();
        $viewPath = __DIR__ . '/../../views/' . str_replace('.', '/', $template) . '.php';
        if (!file_exists($viewPath)) {
            ob_end_clean();
            self::error(404, "View not found: $template");
            return;
        }
        require $viewPath;
        $content = ob_get_clean();
        
        // Render within layout
        if ($layout) {
            $layoutPath = __DIR__ . '/../../views/layouts/' . $layout . '.php';
            if (file_exists($layoutPath)) {
                require $layoutPath;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }

    /**
     * Render a view without layout (for POS, print pages, etc.)
     */
    public static function viewOnly(string $template, array $data = []): void {
        self::view($template, $data, null);
    }

    /**
     * Alias for viewOnly.
     */
    public static function rawView(string $template, array $data = []): void {
        self::view($template, $data, null);
    }

    /**
     * Send a JSON response.
     */
    public static function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * JSON success response.
     */
    public static function jsonSuccess(string $message, array $data = [], int $statusCode = 200): void {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * JSON error response.
     */
    public static function jsonError(string $message, array $errors = [], int $statusCode = 400): void {
        self::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $statusCode);
    }

    /**
     * Redirect to a URL.
     */
    public static function redirect(string $url, ?string $flashType = null, ?string $flashMessage = null): void {
        if ($flashType && $flashMessage) {
            Session::flash($flashType, $flashMessage);
        }
        $targetUrl = (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) ? $url : url($url);
        header("Location: $targetUrl");
        exit;
    }

    /**
     * Redirect back to the previous page.
     */
    public static function back(?string $flashType = null, ?string $flashMessage = null): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        self::redirect($referer, $flashType, $flashMessage);
    }

    /**
     * Show an error page.
     */
    public static function error(int $code, string $message = ''): void {
        error_log("Response::error called with code $code and message: $message");
        http_response_code($code);
        $data = ['errorCode' => $code, 'errorMessage' => $message];
        $errorView = __DIR__ . '/../../views/errors/' . $code . '.php';
        if (file_exists($errorView)) {
            extract($data);
            require $errorView;
        } else {
            echo "<h1>Error $code</h1><p>" . htmlspecialchars($message) . "</p>";
        }
        exit;
    }
}

/**
 * Generate application URL.
 */
function url(string $path = ''): string {
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    $configuredUrl = env('APP_URL', '');
    if (!empty($_SERVER['HTTP_HOST'])) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
        $protocol = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Dynamically detect the base path from the current request
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if (str_ends_with($scriptDir, '/public')) {
            $scriptDir = substr($scriptDir, 0, -strlen('/public'));
        }

        if (!empty($scriptDir) && $scriptDir !== '/') {
            $pathPrefix = $scriptDir;
        } else {
            $pathPrefix = parse_url($configuredUrl, PHP_URL_PATH);
            if (empty($pathPrefix) || $pathPrefix === '/') {
                $pathPrefix = '';
            }
        }
        $baseUrl = $protocol . '://' . $host . rtrim($pathPrefix, '/');
    } else {
        $baseUrl = !empty($configuredUrl) ? rtrim($configuredUrl, '/') : 'http://localhost';
    }
    $cleanPath = ltrim($path, '/');
    if (str_starts_with($cleanPath, 'uploads/')) {
        $cleanPath = 'public/' . $cleanPath;
    }
    return rtrim($baseUrl, '/') . ($cleanPath !== '' ? '/' . $cleanPath : '');
}

/**
 * CSRF field global helper.
 */
function csrf_field(): string {
    require_once BASE_PATH . '/app/Helpers/CSRF.php';
    return CSRF::field();
}

/**
 * Generate uploaded file URL.
 */
function uploadUrl(?string $path = ''): string {
    if (empty($path)) return '';
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    $cleanPath = ltrim($path, '/');
    if (!str_starts_with($cleanPath, 'public/')) {
        $cleanPath = 'public/' . $cleanPath;
    }
    return url($cleanPath);
}

/**
 * Generate force-download URL for an uploaded file or external resource.
 * If Cloudinary, injects fl_attachment transformation with custom filename to trigger direct browser download.
 */
function downloadUrl(?string $path = '', string $filename = ''): string {
    if (empty($path)) return '';
    if (str_contains($path, 'res.cloudinary.com')) {
        $param = 'fl_attachment';
        if (!empty($filename)) {
            $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
            if ($safeName) {
                $param .= ':' . $safeName;
            }
        }
        if (str_contains($path, '/upload/')) {
            return str_replace('/upload/', '/upload/' . $param . '/', $path);
        }
    }
    return uploadUrl($path);
}

/**
 * Generate asset URL.
 */
function asset(string $path): string {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Escape HTML output to prevent XSS.
 */
function e(mixed $value): string {
    if (is_array($value)) {
        return htmlspecialchars(implode(', ', array_map(fn($v) => is_array($v) ? json_encode($v) : (string)$v, $value)), ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency in INR.
 */
function formatCurrency(float $amount): string {
    return '₹' . number_format($amount, 2, '.', ',');
}

/**
 * Format date for display.
 */
function formatDate(?string $date, string $format = 'd M Y'): string {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display.
 */
function formatDateTime(?string $datetime, string $format = 'd M Y h:i A'): string {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Format a UTC datetime using the configured application timezone.
 */
function formatUtcDateTime(?string $datetime, string $format = 'd M Y h:i A'): string {
    if (!$datetime) return '-';

    $applicationTimezone = function_exists('env')
        ? (string)env('APP_TIMEZONE', 'Asia/Kolkata')
        : date_default_timezone_get();
    $utcDateTime = new DateTimeImmutable($datetime, new DateTimeZone('UTC'));
    return $utcDateTime
        ->setTimezone(new DateTimeZone($applicationTimezone))
        ->format($format);
}

/**
 * Check whether the current user's role grants an action, without concern checks.
 */
function hasRolePermission(string $module, ?string $action = null): bool {
    if ($action === null) {
        if (strpos($module, '.') !== false) {
            [$module, $action] = explode('.', $module, 2);
        } else {
            $action = 'view';
        }
    }

    $user = Session::user();
    if (empty($user)) {
        return false;
    }

    $roleSlug = strtolower($user['role_slug'] ?? '');
    $roleId = (int)($user['role_id'] ?? 0);
    if ($roleId === 1 || $roleSlug === 'super_admin') {
        return true;
    }

    // Cache permissions for the current request to ensure instant updates from DB
    static $cachedUserId = null;
    static $userPermissions = null;

    $currentUserId = (int)($user['id'] ?? 0);
    if ($cachedUserId !== $currentUserId || $userPermissions === null) {
        $cachedUserId = $currentUserId;
        if (!class_exists('User')) {
            $userModelPath = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/app/Models/User.php';
            if (file_exists($userModelPath)) {
                require_once $userModelPath;
            }
        }
        $userPermissions = class_exists('User') ? User::getPermissions($currentUserId) : (Session::get('permissions') ?? []);
        Session::set('permissions', $userPermissions);
    }

    if (empty($userPermissions)) {
        return false;
    }

    if (in_array('*', $userPermissions, true)) {
        return true;
    }

    $key = $module . '.' . $action;
    if (in_array($key, $userPermissions, true)) {
        return true;
    }
    if ($module === 'allocation' && in_array('allocations.' . $action, $userPermissions, true)) {
        return true;
    }

    // Secondary fallback alias checks for returns
    if ($module === 'whatsapp_return_to_wh') {
        if (in_array('whatsapp_returns.' . $action, $userPermissions, true) || in_array('whatsapp_return_to_wh.' . $action, $userPermissions, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Check the role permission and, for operational modules, the active concern boundary.
 * Supports both hasPermission('module.action') and hasPermission('module', 'action').
 */
function hasPermission(string $module, ?string $action = null): bool {
    if ($action === null && strpos($module, '.') !== false) {
        [$module, $action] = explode('.', $module, 2);
    }
    $action = $action ?? 'view';

    if (isRetiredConcernModule($module)) {
        return false;
    }

    if (!hasRolePermission($module, $action)) {
        return false;
    }

    $user = Session::user() ?? [];
    $isSuperAdminUser = (int)($user['role_id'] ?? 0) === 1
        || strtolower($user['role_slug'] ?? '') === 'super_admin';
    if ($module === 'dashboard' && !$isSuperAdminUser) {
        return false;
    }

    return $isSuperAdminUser || isGlobalConcernModule($module) || concernHasModule($module);
}

function isRetiredConcernModule(string $module): bool {
    static $retiredModules = [
        'warehouse', 'inward', 'stock', 'wh_sellers', 'wh_catalog', 'wh_labels', 'purchases',
        'purchases_returns', 'allocation', 'wh_batches', 'allocation_outward', 'damage', 'returns',
        'shop_requests', 'warehouse_expenses',
        'whatsapp_dashboard', 'whatsapp_orders', 'whatsapp_inward', 'whatsapp_inventory',
        'whatsapp_damage', 'whatsapp_returns', 'whatsapp_return_to_wh', 'whatsapp_customers',
        'whatsapp_expenses',
        'website_dashboard', 'website_orders', 'website_inward', 'website_inventory',
        'website_damage', 'website_returns', 'website_return_to_wh', 'website_customers',
        'website_expenses',
        'shop', 'shop_cash', 'shop_inward', 'shop_stock', 'shop_damage', 'pos', 'sales',
        'customers', 'shop_returns', 'return_to_warehouse',
        'reports_online', 'reports_website', 'reports_whatsapp', 'reports_warehouse',
        'reports_purchase', 'reports_shop', 'reports_inventory',
    ];

    return in_array($module, $retiredModules, true);
}

function isGlobalConcernModule(string $module): bool {
    return in_array($module, ['staff', 'roles', 'dashboard', 'settings', 'audit', 'backup', 'notifications'], true);
}

function concernHasModule(string $module): bool {
    if (isGlobalConcernModule($module)) {
        return true;
    }

    $concern = currentConcern();
    return $concern !== null
        && in_array($module, $concern['module_keys'] ?? [], true);
}

/**
 * Get old form input (for repopulating forms after validation errors).
 */
function old(string $field, $default = '') {
    return Session::get('_old_input')[$field] ?? $default;
}

/**
 * Get current authenticated user array with live unit assignment from DB.
 */
function currentUser(): ?array {
    $user = Session::user();
    if (empty($user)) {
        $uid = Session::get('user_id');
        if (!empty($uid)) {
            $user = ['id' => (int)$uid];
        } else {
            return null;
        }
    }

    static $cachedUser = null;
    static $cachedId = null;

    $uid = (int)($user['id'] ?? Session::get('user_id') ?? 0);
    if ($cachedId !== $uid || $cachedUser === null) {
        $cachedId = $uid;
        if (class_exists('Database')) {
            $activeConcernId = (int)Session::get('active_concern_id', 0);
            $fresh = Database::fetchOne(
                "SELECT u.*, r.name as role_name, r.slug as role_slug,
                        pu.name as production_unit_name, w.name as warehouse_name, s.name as shop_name
                 FROM users u 
                 LEFT JOIN roles r ON u.role_id = r.id 
                 LEFT JOIN production_units pu ON u.production_unit_id = pu.id AND pu.concern_id = ?
                 LEFT JOIN warehouses w ON u.warehouse_id = w.id AND w.concern_id = ?
                 LEFT JOIN shops s ON u.shop_id = s.id AND s.concern_id = ?
                 WHERE u.id = ?",
                [$activeConcernId, $activeConcernId, $activeConcernId, $uid]
            );
            if ($fresh) {
                $user = array_merge($user, [
                    'id'                   => (int)$fresh['id'],
                    'username'             => $fresh['username'],
                    'full_name'            => $fresh['full_name'],
                    'email'                => $fresh['email'],
                    'role_id'              => (int)$fresh['role_id'],
                    'role_name'            => $fresh['role_name'] ?? ($user['role_name'] ?? ''),
                    'role_slug'            => $fresh['role_slug'] ?? ($user['role_slug'] ?? ''),
                    'department'           => $fresh['department'] ?? null,
                    'production_unit_id'   => !empty($fresh['production_unit_id']) ? (int)$fresh['production_unit_id'] : null,
                    'warehouse_id'         => !empty($fresh['warehouse_id']) ? (int)$fresh['warehouse_id'] : null,
                    'shop_id'              => !empty($fresh['shop_id']) ? (int)$fresh['shop_id'] : null,
                    'production_unit_name' => $fresh['production_unit_name'] ?? null,
                    'warehouse_name'       => $fresh['warehouse_name'] ?? null,
                    'shop_name'            => $fresh['shop_name'] ?? null,
                    'is_active'            => (int)$fresh['is_active'],
                ]);
                Session::set('user', $user);
            }
        }
        $cachedUser = $user;
    }
    return $cachedUser;
}

/**
 * Check if the current user is a super admin with unrestricted access.
 */
function isSuperAdmin(): bool {
    $u = currentUser();
    if (!$u) return false;
    $roleId = (int)($u['role_id'] ?? 0);
    $roleSlug = strtolower($u['role_slug'] ?? '');
    return ($roleId === 1 || $roleSlug === 'super_admin');
}

function currentConcern(): ?array {
    static $resolved = false;
    static $cachedUserId = null;
    static $cachedConcernId = null;
    static $activeConcern = null;

    $user = currentUser();
    $userId = (int)($user['id'] ?? 0);
    $sessionConcernId = (int)Session::get('active_concern_id', 0);

    if ($resolved && $cachedUserId === $userId && $cachedConcernId === $sessionConcernId) {
        return $activeConcern;
    }

    if (!class_exists('Concern')) {
        require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/app/Models/Concern.php';
    }
    $activeConcern = Concern::getActiveForCurrentUser();
    $cachedUserId = $userId;
    $cachedConcernId = (int)Session::get('active_concern_id', 0);
    $resolved = true;
    return $activeConcern;
}

function currentConcernId(): ?int {
    $concern = currentConcern();
    return $concern ? (int)$concern['id'] : null;
}

/**
 * Get the restricted production unit ID for the current user.
 * Returns null if user is Super Admin or has unrestricted access.
 */
function currentProductionUnitId(): ?int {
    if (isSuperAdmin()) {
        return null;
    }
    $u = currentUser();
    $unitId = !empty($u['production_unit_id']) ? (int)$u['production_unit_id'] : null;
    if ($unitId === null) {
        return null;
    }

    $concernId = currentConcernId();
    if ($concernId === null) {
        return 0;
    }
    $unit = Database::fetchOne(
        "SELECT id FROM production_units WHERE id = ? AND concern_id = ?",
        [$unitId, $concernId]
    );

    return $unit ? $unitId : 0;
}

/**
 * Get the restricted warehouse ID for the current user.
 * Returns null if user is Super Admin or has unrestricted access.
 */
function currentWarehouseId(): ?int {
    if (isSuperAdmin()) {
        return null;
    }
    $u = currentUser();
    $warehouseId = !empty($u['warehouse_id']) ? (int)$u['warehouse_id'] : null;
    if ($warehouseId === null) {
        return null;
    }

    $concernId = currentConcernId();
    if ($concernId === null || !Database::fetchOne(
        "SELECT id FROM warehouses WHERE id = ? AND concern_id = ?",
        [$warehouseId, $concernId]
    )) {
        return 0;
    }

    return $warehouseId;
}

/**
 * Get the restricted shop ID for the current user.
 * Returns null if user is Super Admin or has unrestricted access.
 */
function currentShopId(): ?int {
    if (isSuperAdmin()) {
        return null;
    }
    $u = currentUser();
    $shopId = !empty($u['shop_id']) ? (int)$u['shop_id'] : null;
    if ($shopId === null) {
        return null;
    }

    $concernId = currentConcernId();
    if ($concernId === null || !Database::fetchOne(
        "SELECT id FROM shops WHERE id = ? AND concern_id = ?",
        [$shopId, $concernId]
    )) {
        return 0;
    }

    return $shopId;
}

/**
 * Get production units scoped to the current user's assignment.
 */
function getScopedProductionUnits(bool $activeOnly = true): array {
    $concernId = currentConcernId();
    if ($concernId === null || !Concern::hasFacilityModules($concernId, 'PRODUCTION')) {
        return [];
    }

    $scopedId = currentProductionUnitId();
    $sql = "SELECT * FROM production_units WHERE concern_id = ?";
    $params = [$concernId];
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    if ($scopedId !== null && $scopedId > 0) {
        $sql .= " AND id = ?";
        $params[] = $scopedId;
    } elseif ($scopedId === 0) {
        return [];
    }
    $sql .= " ORDER BY name ASC";
    return Database::fetchAll($sql, $params);
}

/**
 * Get warehouses scoped to the current user's assignment.
 */
function getScopedWarehouses(bool $activeOnly = true): array {
    $concernId = currentConcernId();
    if ($concernId === null || !Concern::hasFacilityModules($concernId, 'WAREHOUSE')) {
        return [];
    }

    $scopedId = currentWarehouseId();
    if ($scopedId === 0) {
        return [];
    }
    $sql = "SELECT * FROM warehouses WHERE concern_id = ?";
    $params = [$concernId];
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    if ($scopedId !== null) {
        $sql .= " AND id = ?";
        $params[] = $scopedId;
    }
    $sql .= " ORDER BY name ASC";
    return Database::fetchAll($sql, $params);
}

/**
 * Get shops scoped to the current user's assignment.
 */
function getScopedShops(bool $activeOnly = true): array {
    $concernId = currentConcernId();
    if ($concernId === null || !Concern::hasFacilityModules($concernId, 'SHOP')) {
        return [];
    }

    $scopedId = currentShopId();
    if ($scopedId === 0) {
        return [];
    }
    $sql = "SELECT * FROM shops WHERE concern_id = ?";
    $params = [$concernId];
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    if ($scopedId !== null) {
        $sql .= " AND id = ?";
        $params[] = $scopedId;
    }
    $sql .= " ORDER BY name ASC";
    return Database::fetchAll($sql, $params);
}

function currentChannelId(): ?int {
    if (isSuperAdmin()) {
        return null;
    }
    $user = currentUser();
    $channelId = !empty($user['channel_id']) ? (int)$user['channel_id'] : null;
    if ($channelId === null) {
        return null;
    }
    $concernId = currentConcernId();
    if ($concernId === null || !Database::fetchOne(
        "SELECT id FROM sales_channels WHERE id = ? AND concern_id = ?",
        [$channelId, $concernId]
    )) {
        return 0;
    }
    return $channelId;
}

function getScopedSalesChannels(?string $channelType = null, bool $activeOnly = true): array {
    $channelType = $channelType !== null ? strtoupper($channelType) : null;
    $concernId = currentConcernId();
    if ($concernId === null) {
        return [];
    }
    if ($channelType !== null && !in_array($channelType, ['WHATSAPP', 'WEBSITE'], true)) {
        return [];
    }
    $typeFilters = [];
    if (Concern::hasFacilityModules($concernId, 'WHATSAPP')) {
        $typeFilters['WHATSAPP'] = "((channel_type = 'WHATSAPP') OR ((channel_type IS NULL OR channel_type = '') AND (LOWER(name) LIKE '%whatsapp%' OR LOWER(code) LIKE 'wa%')))";
    }
    if (Concern::hasFacilityModules($concernId, 'WEBSITE')) {
        $typeFilters['WEBSITE'] = "((channel_type = 'WEBSITE') OR ((channel_type IS NULL OR channel_type = '') AND (LOWER(name) LIKE '%website%' OR LOWER(name) LIKE '%web%' OR LOWER(code) LIKE 'web%' OR LOWER(code) LIKE 'we%')))";
    }

    if ($channelType === 'WHATSAPP' || $channelType === 'WEBSITE') {
        if (!isset($typeFilters[$channelType])) {
            return [];
        }
        $typeFilters = [$typeFilters[$channelType]];
    }
    $allowedTypes = array_values($typeFilters);
    if (!$allowedTypes) {
        return [];
    }

    $sql = "SELECT * FROM sales_channels WHERE concern_id = ?";
    $params = [$concernId];
    $sql .= ' AND (' . implode(' OR ', $allowedTypes) . ')';
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }

    $scopedId = currentChannelId();
    if ($scopedId === 0) {
        return [];
    }
    if ($scopedId !== null) {
        $sql .= " AND id = ?";
        $params[] = $scopedId;
    }
    $sql .= " ORDER BY name ASC";
    return Database::fetchAll($sql, $params);
}
