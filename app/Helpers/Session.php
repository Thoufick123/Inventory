<?php
/**
 * Session Helper
 * Manages PHP sessions, flash messages, and session security.
 */

class Session {
    private static bool $started = false;

    /**
     * Start session with secure settings.
     */
    public static function start(): void {
        if (self::$started) return;
        
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = (int)(env('SESSION_LIFETIME', 120)) * 60;
            
            if (!headers_sent()) {
                ini_set('session.cookie_httponly', '1');
                ini_set('session.use_strict_mode', '1');
                ini_set('session.cookie_samesite', 'Lax');
                ini_set('session.gc_maxlifetime', (string)$lifetime);
                
                session_set_cookie_params([
                    'lifetime' => $lifetime,
                    'path'     => '/',
                    'httponly'  => true,
                    'samesite'  => 'Lax',
                ]);
                
                session_start();
            } elseif (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
        }
        
        self::$started = true;
    }

    /**
     * Set a session value.
     */
    public static function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value.
     */
    public static function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session key exists.
     */
    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session key.
     */
    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the session entirely.
     */
    public static function destroy(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::$started = false;
    }

    /**
     * Regenerate session ID (call after login).
     */
    public static function regenerate(): void {
        session_regenerate_id(true);
    }

    /**
     * Set a flash message (available only on next request).
     */
    public static function flash(string $type, string $message): void {
        $_SESSION['_flash'][$type][] = $message;
    }

    /**
     * Alias for flash().
     */
    public static function setFlash(string $type, string $message): void {
        self::flash($type, $message);
    }

    /**
     * Get and clear flash messages.
     */
    public static function getFlash(string $type = null): array {
        if ($type) {
            $messages = $_SESSION['_flash'][$type] ?? [];
            unset($_SESSION['_flash'][$type]);
            return $messages;
        }
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    /**
     * Check if there are flash messages.
     */
    public static function hasFlash(string $type = null): bool {
        if ($type) {
            return !empty($_SESSION['_flash'][$type]);
        }
        return !empty($_SESSION['_flash']);
    }

    /**
     * Get currently logged-in user data.
     */
    public static function user(): ?array {
        return self::get('user');
    }

    /**
     * Get current user ID.
     */
    public static function userId(): ?int {
        $user = self::user();
        if (!empty($user['id'])) {
            return (int)$user['id'];
        }
        return null;
    }

    /**
     * Check if user is logged in.
     */
    public static function isLoggedIn(): bool {
        return self::has('user') && self::get('user') !== null;
    }
}
