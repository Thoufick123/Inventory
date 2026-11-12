<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

final class TestSysStop extends RuntimeException
{
    public function __construct(public readonly int $codeNum, string $message)
    {
        parent::__construct($message);
    }
}

final class Session
{
    private static array $data = [];
    private static array $flash = [];

    public static function reset(): void
    {
        self::$data = [];
        self::$flash = [];
    }

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

    public static function setFlash(string $type, string $msg): void
    {
        self::$flash[$type][] = $msg;
    }

    public static function hasFlash(string $type): bool
    {
        return !empty(self::$flash[$type]);
    }

    public static function getFlash(string $type): array
    {
        $m = self::$flash[$type] ?? [];
        unset(self::$flash[$type]);
        return $m;
    }
}

final class CSRF
{
    public static bool $valid = true;

    public static function validateRequest(): bool
    {
        return self::$valid;
    }

    public static function field(): string
    {
        return '';
    }
}

final class Response
{
    public static ?array $lastView = null;
    public static ?array $lastJson = null;
    public static int $lastStatus = 200;
    public static ?string $lastRedirect = null;

    public static function reset(): void
    {
        self::$lastView = null;
        self::$lastJson = null;
        self::$lastStatus = 200;
        self::$lastRedirect = null;
    }

    public static function view(string $view, array $data = []): void
    {
        self::$lastView = ['view' => $view, 'data' => $data];
    }

    public static function json(array $data, int $status = 200): void
    {
        self::$lastJson = $data;
        self::$lastStatus = $status;
    }

    public static function redirect(string $url, ?string $flashKey = null, ?string $flashMsg = null): void
    {
        if ($flashKey !== null && $flashMsg !== null) {
            Session::setFlash($flashKey, $flashMsg);
        }
        self::$lastRedirect = $url;
    }

    public static function error(int $code, string $msg = ''): void
    {
        throw new TestSysStop($code, $msg);
    }
}

final class Database
{
    public static array $queries = [];
    public static array $notifications = [
        1 => ['id' => 1, 'user_id' => 9, 'type' => 'ALERT', 'title' => 'Sulur Low Stock', 'message' => 'msg1', 'is_read' => 0, 'link' => '', 'created_at' => '2026-11-12 09:00:00'],
        2 => ['id' => 2, 'user_id' => 5, 'type' => 'ALERT', 'title' => 'Factory Low Stock', 'message' => 'msg2', 'is_read' => 0, 'link' => '', 'created_at' => '2026-11-12 09:01:00'],
        3 => ['id' => 3, 'user_id' => null, 'type' => 'SYSTEM', 'title' => 'Global System', 'message' => 'msg3', 'is_read' => 0, 'link' => '', 'created_at' => '2026-11-12 09:02:00'],
    ];

    public static function reset(): void
    {
        self::$queries = [];
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        self::$queries[] = ['sql' => $sql, 'params' => $params];
        if (str_contains($sql, 'FROM notifications') && str_contains($sql, 'COUNT(*) AS cnt')) {
            return ['cnt' => count(self::fetchAll($sql, $params))];
        }
        if (str_contains($sql, 'FROM products')) {
            return ['cnt' => 3];
        }
        if (str_contains($sql, 'FROM production_bills')) {
            return ['cnt' => 4];
        }
        if (str_contains($sql, 'FROM audit_logs')) {
            return ['c' => 2, 'cnt' => 2];
        }
        return ['cnt' => 1, 'total' => '5000.00'];
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        self::$queries[] = ['sql' => $sql, 'params' => $params];
        if (str_contains($sql, 'FROM notifications n')) {
            $wantUnread = str_contains($sql, 'n.is_read = 0');
            if (str_contains($sql, 'n.user_id = ? AND EXISTS')) {
                $uid = (int)$params[0];
                return array_values(array_filter(
                    self::$notifications,
                    static fn(array $n): bool => (int)($n['user_id'] ?? 0) === $uid && (!$wantUnread || empty($n['is_read']))
                ));
            }
            if (str_contains($sql, 'n.user_id IS NULL')) {
                $uid = (int)$params[0];
                return array_values(array_filter(
                    self::$notifications,
                    static fn(array $n): bool => ($n['user_id'] === null || (int)$n['user_id'] === $uid) && (!$wantUnread || empty($n['is_read']))
                ));
            }
            return array_values(array_filter(self::$notifications, static fn(array $n): bool => !$wantUnread || empty($n['is_read'])));
        }
        return [];
    }

    public static function execute(string $sql, array $params = []): int
    {
        self::$queries[] = ['sql' => $sql, 'params' => $params];
        if (str_contains($sql, 'UPDATE notifications SET is_read = 1 WHERE id = ?')) {
            $id = (int)$params[0];
            $uid = (int)($params[1] ?? 0);
            if (isset(self::$notifications[$id]) && (int)(self::$notifications[$id]['user_id'] ?? 0) === $uid) {
                self::$notifications[$id]['is_read'] = 1;
                return 1;
            }
            return 0;
        }
        if (str_contains($sql, 'UPDATE notifications SET is_read = 1 WHERE is_read = 0')) {
            $uid = (int)($params[0] ?? 0);
            $changed = 0;
            foreach (self::$notifications as &$n) {
                if (empty($n['is_read']) && (int)($n['user_id'] ?? 0) === $uid) {
                    $n['is_read'] = 1;
                    $changed++;
                }
            }
            return $changed;
        }
        return 1;
    }
}

function isSuperAdmin(): bool { return !empty((Session::user() ?? [])['is_super_admin']); }
function currentConcernId(): ?int { $c = Session::get('active_concern_id'); return $c ? (int)$c : null; }
function currentUser(): ?array { return Session::user(); }
function hasPermission(string $m, string $a): bool { return true; }
function url(string $p = ''): string { return '/' . ltrim($p, '/'); }
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

require_once BASE_PATH . '/app/Controllers/SystemController.php';
require_once BASE_PATH . '/app/Controllers/AdminController.php';

function assertSameSys(mixed $exp, mixed $act, string $msg): void
{
    if ($exp !== $act) {
        fwrite(STDERR, "FAIL: {$msg}\nExpected: " . var_export($exp, true) . "\nActual:   " . var_export($act, true) . "\n");
        exit(1);
    }
}

function assertTrueSys(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

// 1. Notifications scoping + markRead + markAllRead
Session::reset();
Response::reset();
Database::reset();
Session::set('user', ['id' => 9, 'is_super_admin' => 0]);
Session::set('active_concern_id', 2);

$sys = new SystemController();
$_SERVER['HTTP_ACCEPT'] = 'application/json';

$sys->notifications();
$list = Response::$lastView['data']['notifications'] ?? [];
assertSameSys(1, count($list), 'Concern staff sees only own user_id=9 notifications');
assertSameSys(1, (int)$list[0]['id'], 'Notification id=1 returned for user 9');

Response::reset();
$sys->unreadNotifications();
assertSameSys(1, (int)(Response::$lastJson['count'] ?? -1), 'unreadNotifications returns real unread count instead of stub 0');

// Cross-user markRead rejected
Response::reset();
$sys->markRead('2');
assertSameSys(false, Response::$lastJson['success'] ?? true, 'User 9 cannot mark User 5 notification read');
assertSameSys(0, Database::$notifications[2]['is_read'], 'User 5 notification remains unread');

// Own notification markRead succeeds
Response::reset();
$sys->markRead('1');
assertSameSys(true, Response::$lastJson['success'] ?? false, 'User 9 can mark own notification read');
assertSameSys(1, Database::$notifications[1]['is_read'], 'Notification id=1 updated to is_read=1');

// 2. Audit logs concern scoping for non-super-admin
Database::reset();
$_GET = [];
$sys->auditLogs();
$foundAuditScope = false;
foreach (Database::$queries as $q) {
    if (str_contains($q['sql'], 'FROM audit_logs al') && str_contains($q['sql'], 'user_concerns uc')) {
        $foundAuditScope = true;
    }
}
assertTrueSys($foundAuditScope, 'AuditService scopes queries by user_concerns for non-Super-Admin');

// 3. AdminController::dashboard uses concern-scoped products, production_bills, production_inwards, production_bill_returns
Database::reset();
$adm = new AdminController();
$adm->dashboard();
$prodScoped = false;
$usesProdBills = false;
$usesLegacySales = false;
$usesLegacyAlloc = false;
foreach (Database::$queries as $q) {
    if (str_contains($q['sql'], 'FROM products WHERE concern_id = ?')) {
        $prodScoped = true;
    }
    if (str_contains($q['sql'], 'FROM production_bills')) {
        $usesProdBills = true;
    }
    if (str_contains($q['sql'], 'FROM sales')) {
        $usesLegacySales = true;
    }
    if (str_contains($q['sql'], 'FROM warehouse_allocations')) {
        $usesLegacyAlloc = true;
    }
}
assertTrueSys($prodScoped, 'AdminController::dashboard scopes products count by concern_id');
assertTrueSys($usesProdBills, 'AdminController::dashboard queries active production_bills');
assertTrueSys(!$usesLegacySales, 'AdminController::dashboard no longer queries retired sales table');
assertTrueSys(!$usesLegacyAlloc, 'AdminController::dashboard no longer queries retired warehouse_allocations table');

echo "PASS: System notifications, concern-scoped audit logs, and Admin dashboard checks passed.\n";
