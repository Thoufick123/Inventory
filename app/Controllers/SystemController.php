<?php
/**
 * System Controller — Handles Notifications, Audit Logs, Settings Hub, and Data Backups.
 */

require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Services/SettingsService.php';
require_once BASE_PATH . '/app/Services/BackupService.php';

class SystemController {

    // =========================================================================
    // AUDIT LOGS
    // =========================================================================

    private function resolveAuditScopeFilters(array $filters): array {
        $isSuper = function_exists('isSuperAdmin') && isSuperAdmin();
        $concernId = function_exists('currentConcernId') ? (int)(currentConcernId() ?? 0) : 0;
        if (!$isSuper) {
            $filters['concern_id'] = $concernId > 0 ? $concernId : -1;
        } elseif ($concernId > 0) {
            $filters['concern_id'] = $concernId;
        }
        return $filters;
    }

    public function auditLogs(): void {
        $filters = $this->resolveAuditScopeFilters([
            'search'    => $_GET['search'] ?? '',
            'module'    => $_GET['module'] ?? '',
            'action'    => $_GET['action'] ?? '',
            'user_id'   => $_GET['user_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to'] ?? '',
        ]);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $logs = AuditService::getLogs($filters, $limit, $offset);
        $totalLogs = AuditService::count($filters);
        $stats = AuditService::getStats($filters);

        $isSuper = function_exists('isSuperAdmin') && isSuperAdmin();
        $scopeConcernId = isset($filters['concern_id']) ? (int)$filters['concern_id'] : 0;
        $userWhereParts = ['1=1'];
        $userParams = [];
        if (!$isSuper) {
            if ($scopeConcernId > 0) {
                $userWhereParts[] = 'EXISTS (SELECT 1 FROM user_concerns uc WHERE uc.user_id = u.id AND uc.concern_id = ?)';
                $userParams[] = $scopeConcernId;
            } else {
                $userWhereParts[] = '1 = 0';
            }
            $userWhereParts[] = "(u.role_id != 1 AND LOWER(COALESCE(r.slug, '')) != 'super_admin')";
        } elseif ($scopeConcernId > 0) {
            $userWhereParts[] = 'EXISTS (SELECT 1 FROM user_concerns uc WHERE uc.user_id = u.id AND uc.concern_id = ?)';
            $userParams[] = $scopeConcernId;
        }
        $userWhere = implode(' AND ', $userWhereParts);

        $users = Database::fetchAll(
            "SELECT u.id, u.full_name, u.username
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE {$userWhere}
             ORDER BY u.full_name ASC",
            $userParams
        );

        $puJoinCond = $scopeConcernId > 0 ? 'pu.id = u.production_unit_id AND pu.concern_id = ?' : 'pu.id = u.production_unit_id';
        $profileParams = $scopeConcernId > 0 ? array_merge([$scopeConcernId], $userParams) : $userParams;
        $staffProfiles = Database::fetchAll(
            "SELECT u.id, u.username, u.emp_id, u.full_name, u.email, u.phone, u.department, u.is_active, u.last_login,
                    r.name as role_name, r.slug as role_slug,
                    pu.name as production_unit_name, NULL as warehouse_name, NULL as shop_name,
                    COUNT(al.id) as log_count,
                    SUM(CASE WHEN al.action = 'CREATE' THEN 1 ELSE 0 END) as create_count,
                    SUM(CASE WHEN al.action = 'UPDATE' THEN 1 ELSE 0 END) as update_count,
                    SUM(CASE WHEN al.action = 'DELETE' THEN 1 ELSE 0 END) as delete_count,
                    MAX(al.created_at) as last_activity_at,
                    (SELECT al2.action FROM audit_logs al2 WHERE al2.user_id = u.id ORDER BY al2.id DESC LIMIT 1) as last_action,
                    (SELECT al2.module FROM audit_logs al2 WHERE al2.user_id = u.id ORDER BY al2.id DESC LIMIT 1) as last_module,
                    (SELECT al2.reference_type FROM audit_logs al2 WHERE al2.user_id = u.id ORDER BY al2.id DESC LIMIT 1) as last_ref_type
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN production_units pu ON {$puJoinCond}
             LEFT JOIN audit_logs al ON al.user_id = u.id
             WHERE {$userWhere}
             GROUP BY u.id, u.username, u.emp_id, u.full_name, u.email, u.phone, u.department, u.is_active, u.last_login, r.name, r.slug, pu.name
             ORDER BY log_count DESC, u.full_name ASC",
            $profileParams
        );

        $selectedStaff = null;
        if (!empty($filters['user_id'])) {
            foreach ($staffProfiles as $sp) {
                if ((int)$sp['id'] === (int)$filters['user_id']) {
                    $selectedStaff = $sp;
                    break;
                }
            }
        }

        Response::view('system.audit', [
            'pageTitle'     => 'Master Audit Logs & Activity Tracker',
            'activeModule'  => 'system',
            'activeMenu'    => 'system-audit',
            'breadcrumb'    => [
                ['label' => 'System', 'url' => url('system/audit')],
                ['label' => 'Audit Logs'],
            ],
            'logs'          => $logs,
            'totalLogs'     => $totalLogs,
            'stats'         => $stats,
            'users'         => $users,
            'staffProfiles' => $staffProfiles,
            'selectedStaff' => $selectedStaff,
            'filters'       => $filters,
            'page'          => $page,
            'totalPages'    => ceil($totalLogs / $limit),
        ]);
    }

    public function exportAuditLogs(): void {
        $filters = $this->resolveAuditScopeFilters([
            'search'    => $_GET['search'] ?? '',
            'module'    => $_GET['module'] ?? '',
            'action'    => $_GET['action'] ?? '',
            'user_id'   => $_GET['user_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to'] ?? '',
        ]);
        AuditService::exportCsv($filters);
    }

    // =========================================================================
    // SYSTEM SETTINGS HUB
    // =========================================================================

    public function settings(): void {
        $groupedSettings = SettingsService::getAllGrouped();

        Response::view('system.settings', [
            'pageTitle'    => 'Unified System Settings Hub',
            'activeModule' => 'system',
            'activeMenu'   => 'system-settings',
            'breadcrumb'   => [
                ['label' => 'System', 'url' => url('system/settings')],
                ['label' => 'Settings'],
            ],
            'settings'     => $groupedSettings,
        ]);
    }

    public function updateSettings(): void {
        $group = $_POST['group'] ?? 'general';
        $settingsData = $_POST['settings'] ?? [];

        try {
            SettingsService::updateGroup($group, $settingsData);
            Session::setFlash('success', 'Settings section updated successfully.');
        } catch (Exception $e) {
            Session::setFlash('error', 'Failed to save settings: ' . $e->getMessage());
        }

        Response::redirect(url('system/settings?tab=' . $group));
    }

    // =========================================================================
    // DATA BACKUP & DISASTER RECOVERY
    // =========================================================================

    public function backupList(): void {
        $backups = BackupService::listBackups();

        Response::view('system.backup', [
            'pageTitle'    => 'Data Backup & Disaster Recovery',
            'activeModule' => 'system',
            'activeMenu'   => 'system-backup',
            'breadcrumb'   => [
                ['label' => 'System', 'url' => url('system/backup')],
                ['label' => 'Backups'],
            ],
            'backups'      => $backups,
        ]);
    }

    public function createBackup(): void {
        CSRF::verify();
        $compress = isset($_POST['compress']) && $_POST['compress'] == '1';
        $notes = $_POST['notes'] ?? 'Manual backup from Admin Dashboard';
        $recipient = trim((string)env('BACKUP_EMAIL', ''));
        if ($recipient === '') {
            $recipient = 'abiprasath6@gmail.com';
        }

        try {
            $filename = BackupService::createBackup(null, $compress, $notes);
            $delivery = BackupService::sendBackupByEmail($recipient, $filename, false);
            if ($delivery['success']) {
                Session::setFlash('success', "Database snapshot created and emailed to {$delivery['email']}: {$filename}");
            } else {
                $mailError = MailService::getLastError() ?: 'Email delivery failed. Check SMTP configuration.';
                Session::setFlash('warning', "Database snapshot {$filename} was created and saved, but email to {$recipient} failed: {$mailError}");
            }
        } catch (Exception $e) {
            Session::setFlash('error', 'Backup creation failed: ' . $e->getMessage());
        }

        Response::redirect(url('system/backup'));
    }

    public function importBackup(): void {
        $submittedToken = $_POST['_csrf_token'] ?? $_POST['csrf_token'] ?? null;
        if (!CSRF::validate($submittedToken)) {
            Session::setFlash('error', 'Invalid security token or session expired. Please try again.');
            Response::redirect(url('system/backup'));
            return;
        }

        $notes = $_POST['notes'] ?? 'Imported external backup file';
        $fileInfo = $_FILES['backup_file'] ?? [];
        $restoreNow = isset($_POST['restore_now']) && $_POST['restore_now'] == '1';
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        try {
            $filename = BackupService::importBackup($fileInfo, $notes);

            if ($restoreNow) {
                BackupService::restoreBackup($filename, $password, $confirmation);
                Session::setFlash('success', "External backup $filename uploaded and database restored successfully! All data is now active.");
            } else {
                Session::setFlash('success', "External backup snapshot imported successfully: $filename. You can download or click Restore to apply it.");
            }
        } catch (Exception $e) {
            Session::setFlash('error', 'Backup import failed: ' . $e->getMessage());
        }

        Response::redirect(url('system/backup'));
    }

    public function downloadBackup(string $filename): void {
        $backupDir = BASE_PATH . '/storage/backups/';
        $filePath = realpath($backupDir . basename($filename));

        if ($filePath && file_exists($filePath) && strpos($filePath, realpath($backupDir)) === 0) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            Session::setFlash('error', 'Backup file not found.');
            Response::redirect(url('system/backup'));
        }
    }

    public function restoreBackup(): void {
        $filename = $_POST['filename'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        try {
            BackupService::restoreBackup($filename, $password, $confirmation);
            Session::setFlash('success', "System database successfully restored from snapshot $filename. Pre-restore rollback snapshot created.");
        } catch (Exception $e) {
            Session::setFlash('error', 'Restore failed: ' . $e->getMessage());
        }

        Response::redirect(url('system/backup'));
    }

    public function exportSqlDirect(): void {
        try {
            BackupService::streamInstantSqlExport();
        } catch (Exception $e) {
            Session::setFlash('error', 'Direct SQL Export failed: ' . $e->getMessage());
            Response::redirect(url('system/backup'));
        }
    }

    public function exportZipDirect(): void {
        try {
            BackupService::streamInstantZipExport();
        } catch (Exception $e) {
            Session::setFlash('error', 'Direct ZIP Export failed: ' . $e->getMessage());
            Response::redirect(url('system/backup'));
        }
    }

    public function wipeTestData(): void {
        $submittedToken = $_POST['_csrf_token'] ?? $_POST['csrf_token'] ?? null;
        if (!CSRF::validate($submittedToken)) {
            Session::setFlash('error', 'Invalid security token or session expired. Please try again.');
            Response::redirect(url('system/backup'));
            return;
        }

        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        try {
            $result = BackupService::wipeTestData($password, $confirmation);
            $msg = sprintf(
                "Test data successfully deleted! Saved auto-safety snapshot: %s. Cleared %d records across %d operational tables. Core Super Admin accounts, master categories, and facilities remain safely preserved.",
                $result['safety_snapshot'],
                $result['total_records_deleted'],
                $result['tables_count']
            );
            Session::setFlash('success', $msg);
        } catch (Exception $e) {
            Session::setFlash('error', 'Delete test data failed: ' . $e->getMessage());
        }

        Response::redirect(url('system/backup'));
    }

    public function deleteBackup(string $filename): void {
        try {
            if (BackupService::deleteBackup($filename)) {
                Session::setFlash('success', "Backup snapshot deleted: $filename");
            } else {
                Session::setFlash('error', "Failed to delete backup snapshot.");
            }
        } catch (Exception $e) {
            Session::setFlash('error', 'Error: ' . $e->getMessage());
        }

        Response::redirect(url('system/backup'));
    }

    public function emailLatestBackup(): void {
        $submittedToken = $_POST['_csrf_token'] ?? $_POST['csrf_token'] ?? null;
        if (!CSRF::validate($submittedToken)) {
            Session::setFlash('error', 'Invalid security token or session expired. Please try again.');
            Response::redirect(url('system/backup'));
            return;
        }

        $email = trim($_POST['email'] ?? 'abiprasath6@gmail.com');
        $deleteLocal = isset($_POST['delete_local']) && $_POST['delete_local'] == '1';
        $fresh = isset($_POST['fresh']) && $_POST['fresh'] == '1';

        try {
            if ($fresh) {
                $result = BackupService::createAndEmailBackup($email, true, 'Manual On-Demand Cloud Backup', !$deleteLocal);
            } else {
                $result = BackupService::sendBackupByEmail($email, null, $deleteLocal);
            }

            if ($result['success']) {
                $delNote = $result['deleted_local'] ? ' (Local temporary files automatically removed — Zero Disk Storage)' : '';
                Session::setFlash('success', "✓ Database backup archive ({$result['filename']}) successfully sent to {$result['email']}!{$delNote}");
            } else {
                $errMsg = MailService::getLastError() ?: "Mail delivery returned false. Please verify SMTP configuration in .env.";
                Session::setFlash('warning', "Backup generated ({$result['filename']}) but mail delivery failed: {$errMsg}");
            }
        } catch (Exception $e) {
            Session::setFlash('error', 'Email delivery failed: ' . $e->getMessage());
        }

        Response::redirect(url('system/backup'));
    }

    public function emailSpecificBackup(string $filename): void {
        $email = trim($_GET['email'] ?? $_POST['email'] ?? 'abiprasath6@gmail.com');
        
        try {
            $result = BackupService::sendBackupByEmail($email, $filename, false);
            if ($result['success']) {
                Session::setFlash('success', "✓ Backup archive {$filename} successfully sent to {$email}!");
            } else {
                $errMsg = MailService::getLastError() ?: "Please verify SMTP configuration in .env.";
                Session::setFlash('warning', "Failed to deliver {$filename} to {$email}: {$errMsg}");
            }
        } catch (Exception $e) {
            Session::setFlash('error', 'Failed to send backup email: ' . $e->getMessage());
        }

        Response::redirect(url('system/backup'));
    }

    // =========================================================================
    // SYSTEM NOTIFICATIONS
    // =========================================================================

    private function notificationScopeSql(string $alias = 'n'): array {
        $userId = (int)(Session::userId() ?? 0);
        $isSuper = function_exists('isSuperAdmin') && isSuperAdmin();
        $concernId = function_exists('currentConcernId') ? (int)(currentConcernId() ?? 0) : 0;

        if ($isSuper) {
            if ($concernId > 0) {
                return [
                    "({$alias}.user_id IS NULL OR {$alias}.user_id = ? OR EXISTS (SELECT 1 FROM user_concerns uc WHERE uc.user_id = {$alias}.user_id AND uc.concern_id = ?))",
                    [$userId, $concernId],
                ];
            }
            return ['1 = 1', []];
        }

        if ($userId <= 0 || $concernId <= 0) {
            return ['1 = 0', []];
        }

        return [
            "({$alias}.user_id = ? AND EXISTS (SELECT 1 FROM user_concerns uc WHERE uc.user_id = {$alias}.user_id AND uc.concern_id = ?))",
            [$userId, $concernId],
        ];
    }

    public function notifications(): void {
        [$scopeSql, $scopeParams] = $this->notificationScopeSql('n');
        $notifications = Database::fetchAll(
            "SELECT n.* FROM notifications n WHERE {$scopeSql} ORDER BY n.created_at DESC, n.id DESC LIMIT 100",
            $scopeParams
        );

        Response::view('system.notifications', [
            'pageTitle'     => 'System Notifications',
            'activeModule'  => 'system',
            'activeMenu'    => 'system-notifications',
            'breadcrumb'    => [
                ['label' => 'System', 'url' => url('system/notifications')],
                ['label' => 'Notifications'],
            ],
            'notifications' => $notifications,
        ]);
    }

    public function markRead(string $id): void {
        $isJson = str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
        if (!CSRF::validateRequest()) {
            if ($isJson) {
                Response::json(['success' => false, 'message' => 'Invalid security token.'], 403);
                return;
            }
            Session::setFlash('error', 'Invalid security token. Please try again.');
            Response::redirect(url('system/notifications'));
            return;
        }

        $notifId = (int)$id;
        [$scopeSql, $scopeParams] = $this->notificationScopeSql('notifications');
        $affected = 0;
        if ($notifId > 0) {
            $affected = Database::execute(
                "UPDATE notifications SET is_read = 1 WHERE id = ? AND {$scopeSql}",
                array_merge([$notifId], $scopeParams)
            );
        }

        if ($isJson) {
            Response::json(['success' => $affected > 0]);
            return;
        }
        Session::setFlash($affected > 0 ? 'success' : 'error', $affected > 0 ? 'Notification marked as read.' : 'Notification not found.');
        Response::redirect(url('system/notifications'));
    }

    public function markAllRead(): void {
        $isJson = str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
        if (!CSRF::validateRequest()) {
            if ($isJson) {
                Response::json(['success' => false, 'message' => 'Invalid security token.'], 403);
                return;
            }
            Session::setFlash('error', 'Invalid security token. Please try again.');
            Response::redirect(url('system/notifications'));
            return;
        }

        [$scopeSql, $scopeParams] = $this->notificationScopeSql('notifications');
        $affected = Database::execute(
            "UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND {$scopeSql}",
            $scopeParams
        );

        if ($isJson) {
            Response::json(['success' => true, 'updated' => $affected]);
            return;
        }
        Session::setFlash('success', $affected > 0 ? 'All notifications marked as read.' : 'No unread notifications to update.');
        Response::redirect(url('system/notifications'));
    }

    public function unreadNotifications(): void {
        [$scopeSql, $scopeParams] = $this->notificationScopeSql('n');
        $countRow = Database::fetchOne(
            "SELECT COUNT(*) AS cnt FROM notifications n WHERE n.is_read = 0 AND {$scopeSql}",
            $scopeParams
        );
        $items = Database::fetchAll(
            "SELECT n.id, n.type, n.title, n.message, n.link, n.created_at
             FROM notifications n
             WHERE n.is_read = 0 AND {$scopeSql}
             ORDER BY n.created_at DESC, n.id DESC LIMIT 10",
            $scopeParams
        );
        Response::json([
            'count'         => (int)($countRow['cnt'] ?? 0),
            'notifications' => $items,
        ]);
    }
}
