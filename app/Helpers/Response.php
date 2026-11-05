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
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
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
 * Check if current user has a specific permission.
 * Supports both hasPermission('module.action') and hasPermission('module', 'action')
 */
function hasPermission(string $module, ?string $action = null): bool {
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
            $fresh = Database::fetchOne(
                "SELECT u.*, r.name as role_name, r.slug as role_slug,
                        pu.name as production_unit_name, w.name as warehouse_name, s.name as shop_name
                 FROM users u 
                 LEFT JOIN roles r ON u.role_id = r.id 
                 LEFT JOIN production_units pu ON u.production_unit_id = pu.id
                 LEFT JOIN warehouses w ON u.warehouse_id = w.id
                 LEFT JOIN shops s ON u.shop_id = s.id
                 WHERE u.id = ?",
                [$uid]
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

/**
 * Get the restricted production unit ID for the current user.
 * Returns null if user is Super Admin or has unrestricted access.
 */
function currentProductionUnitId(): ?int {
    if (isSuperAdmin()) {
        return null;
    }
    $u = currentUser();
    return !empty($u['production_unit_id']) ? (int)$u['production_unit_id'] : null;
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
    return !empty($u['warehouse_id']) ? (int)$u['warehouse_id'] : null;
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
    return !empty($u['shop_id']) ? (int)$u['shop_id'] : null;
}

/**
 * Get production units scoped to the current user's assignment.
 */
function getScopedProductionUnits(bool $activeOnly = true): array {
    $scopedId = currentProductionUnitId();
    if ($scopedId !== null) {
        $pu = Database::fetchOne("SELECT * FROM production_units WHERE id = ?", [$scopedId]);
        return $pu ? [$pu] : [];
    }
    $sql = "SELECT * FROM production_units" . ($activeOnly ? " WHERE is_active = 1" : "") . " ORDER BY name ASC";
    return Database::fetchAll($sql);
}

/**
 * Get warehouses scoped to the current user's assignment.
 */
function getScopedWarehouses(bool $activeOnly = true): array {
    $scopedId = currentWarehouseId();
    if ($scopedId !== null) {
        $wh = Database::fetchOne("SELECT * FROM warehouses WHERE id = ?", [$scopedId]);
        return $wh ? [$wh] : [];
    }
    $sql = "SELECT * FROM warehouses" . ($activeOnly ? " WHERE is_active = 1" : "") . " ORDER BY name ASC";
    return Database::fetchAll($sql);
}

/**
 * Get shops scoped to the current user's assignment.
 */
function getScopedShops(bool $activeOnly = true): array {
    $scopedId = currentShopId();
    if ($scopedId !== null) {
        $sh = Database::fetchOne("SELECT * FROM shops WHERE id = ?", [$scopedId]);
        return $sh ? [$sh] : [];
    }
    $sql = "SELECT * FROM shops" . ($activeOnly ? " WHERE is_active = 1" : "") . " ORDER BY name ASC";
    return Database::fetchAll($sql);
}
