<?php
/**
 * Authentication Middleware
 * Ensures user is logged in before accessing protected routes.
 */

class AuthMiddleware {
    /**
     * Handle the middleware check.
     */
    public function handle(): void {
        if (!Session::isLoggedIn()) {
            // For AJAX requests, return JSON error
            if ($this->isAjax()) {
                Response::jsonError('Authentication required. Please login.', ['redirect' => url('login')], 401);
            }
            
            // Store intended URL for redirect after login
            Session::set('_intended_url', $_SERVER['REQUEST_URI'] ?? '');
            Session::flash('error', 'Please login to continue.');
            Response::redirect(url('login'));
        }

        $userId = Session::userId();
        $dbUser = null;
        if ($userId) {
            if (!class_exists('User')) {
                require_once BASE_PATH . '/app/Models/User.php';
            }
            $dbUser = User::findById($userId);
        }

        // Real-time check: Verify if account is still active in database
        if (!$dbUser || empty($dbUser['is_active'])) {
            Session::destroy();
            Session::start();
            if ($this->isAjax()) {
                Response::jsonError('Your account has been deactivated by the administrator. Contact administrator.', ['redirect' => url('login')], 401);
            }
            Session::flash('error', 'Your account has been deactivated by the administrator. Contact administrator.');
            Response::redirect(url('login'));
        }
    }

    /**
     * Check if request is AJAX.
     */
    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
