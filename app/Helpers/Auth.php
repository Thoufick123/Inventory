<?php
/**
 * Auth Helper
 * Provides convenient authentication accessors backed by Session.
 */

class Auth {
    /**
     * Get the currently authenticated user's ID.
     */
    public static function id(): ?int {
        return Session::userId() ?? (int)Session::get('user_id') ?: null;
    }

    /**
     * Get the currently authenticated user array.
     */
    public static function user(): ?array {
        return Session::user();
    }

    /**
     * Check if a user is authenticated.
     */
    public static function check(): bool {
        return Session::isLoggedIn();
    }

    /**
     * Check if a user is a guest (not authenticated).
     */
    public static function guest(): bool {
        return !self::check();
    }
}
