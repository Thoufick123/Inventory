<?php

/**
 * Permission gate for concern-to-concern transfers, whose model enforces both sides.
 */
class ConcernTransferPermissionMiddleware {
    public function handle(string $action): void {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$concernId) {
            Response::redirect(url('admin/concern/select'), 'error', 'Select an active concern before opening stock transfers.');
        }
        if (!Concern::hasModule((int)$concernId, 'warehouse')) {
            Response::error(403, 'Stock transfers are not enabled for the active concern.');
        }
        if (!hasRolePermission('warehouse', $action)) {
            Response::error(403, 'Your assigned role does not have permission to ' . strtolower($action) . ' stock transfers.');
        }
    }
}
