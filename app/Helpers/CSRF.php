<?php
/**
 * CSRF Protection Helper
 * Generates and validates CSRF tokens for form submissions.
 */

class CSRF {
    /**
     * Generate a new CSRF token and store in session.
     */
    public static function generateToken(): string {
        $token = bin2hex(random_bytes(32));
        Session::set('_csrf_token', $token);
        return $token;
    }

    /**
     * Get the current CSRF token (generate if not exists).
     */
    public static function token(): string {
        if (!Session::has('_csrf_token')) {
            return self::generateToken();
        }
        return Session::get('_csrf_token');
    }

    /**
     * Validate a submitted CSRF token.
     * If no token passed, validates from request POST/header.
     */
    public static function validate(?string $token = null): bool {
        if ($token === null) {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }
        if (!$token) return false;
        $sessionToken = Session::get('_csrf_token');
        if (!$sessionToken) return false;
        return hash_equals($sessionToken, $token);
    }

    /**
     * Validate from POST request and return error if invalid.
     */
    public static function validateRequest(): bool {
        return self::validate();
    }

    /**
     * Validate token alias.
     */
    public static function validateToken(?string $token = null): bool {
        return self::validate($token);
    }

    /**
     * Verify submitted CSRF token or redirect back with error if invalid.
     */
    public static function verify(): bool {
        if (!self::validate()) {
            Session::flash('error', 'Invalid security token or session expired. Please try submitting again.');
            if (!empty($_SERVER['HTTP_REFERER'])) {
                Response::redirect($_SERVER['HTTP_REFERER']);
            } else {
                Response::redirect(url(''));
            }
            exit;
        }
        return true;
    }

    /**
     * Return hidden input HTML for forms.
     */
    public static function tokenField(): string {
        $token = self::token();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Return hidden input HTML for forms (alias for tokenField).
     */
    public static function field(): string {
        return self::tokenField();
    }

    /**
     * Return hidden input HTML for forms (alias for tokenField).
     */
    public static function input(): string {
        return self::tokenField();
    }

    /**
     * Return meta tag for AJAX usage.
     */
    public static function metaTag(): string {
        $token = self::token();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}
