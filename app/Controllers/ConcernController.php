<?php

require_once BASE_PATH . '/app/Models/Concern.php';

class ConcernController
{
    public function select(): void
    {
        $user = currentUser();
        $isAdmin = isSuperAdmin();
        $concerns = Concern::getAccessibleToUser((int)$user['id'], $isAdmin);
        $activeConcern = Concern::getActiveForCurrentUser();

        Response::view('admin.concern_select', [
            'pageTitle' => 'Select Concern',
            'activeModule' => 'concern',
            'activeMenu' => 'concern-select',
            'breadcrumb' => [['label' => 'Select Concern']],
            'concerns' => $concerns,
            'activeConcern' => $activeConcern,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function activate(): void
    {
        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
            return;
        }

        $user = currentUser();
        if (!$user || !Concern::activateForUser(
            (int)$user['id'],
            (int)($_POST['concern_id'] ?? 0),
            isSuperAdmin()
        )) {
            Response::back('error', 'You are not assigned to that concern.');
            return;
        }

        Response::redirect(url('admin/concern/select'), 'success', 'Active concern updated.');
    }
}
