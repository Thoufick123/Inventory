<?php
/**
 * Permission Middleware
 * Checks if user has required module + action permission.
 */

class PermissionMiddleware {
    /**
     * Handle the permission check.
     * 
     * @param string $module Module name (e.g., 'production', 'warehouse')
     * @param string $action Action name (e.g., 'view', 'create', 'edit', 'delete', 'approve', 'print', 'export')
     */
    public function handle(string $module, string $action): void {
        if (!isGlobalConcernModule($module)) {
            $concern = function_exists('currentConcern') ? currentConcern() : null;
            if (!$concern) {
                Response::redirect(url('admin/concern/select'), 'error', 'Select an active concern before opening operational modules.');
            }
            if (!concernHasModule($module)) {
                Response::error(403, 'This module is not assigned to the active concern.');
            }
        }

        if (!hasRolePermission($module, $action)) {
            $readableModule = ucwords(str_replace('_', ' ', $module));
            $readableAction = strtolower($action);

            if ($this->isAjax()) {
                Response::json([
                    'success' => false,
                    'error'   => "Access restricted: You do not have permission to {$readableAction} in {$readableModule}."
                ], 403);
            }

            Response::error(403, "Your assigned role does not have permission to {$readableAction} in {$readableModule}.");
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
