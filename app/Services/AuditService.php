<?php
/**
 * Audit Service — Enterprise Activity & Security Tracker
 * Records, queries, exports, and cleans all system audit logs.
 */

class AuditService {
    /**
     * Log an audit entry.
     */
    public static function log(
        string $module,
        string $action,
        ?string $referenceType = null,
        ?int $referenceId = null,
        $previousValue = null,
        $newValue = null,
        ?int $userId = null
    ): void {
        try {
            $userId = $userId ?? (class_exists('Session') ? Session::userId() : null);
            $module = strtolower(trim($module));
            $action = strtoupper(trim($action));

            Database::execute(
                "INSERT INTO audit_logs (user_id, module, action, reference_type, reference_id, 
                    previous_value, new_value, ip_address, user_agent, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $userId,
                    $module,
                    $action,
                    $referenceType,
                    $referenceId,
                    $previousValue !== null ? (is_string($previousValue) ? $previousValue : json_encode($previousValue, JSON_UNESCAPED_UNICODE)) : null,
                    $newValue !== null ? (is_string($newValue) ? $newValue : json_encode($newValue, JSON_UNESCAPED_UNICODE)) : null,
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System', 0, 500),
                ]
            );
        } catch (Exception $e) {
            error_log("AuditService::log failed: " . $e->getMessage());
        }
    }

    /**
     * Get audit logs with filters and pagination.
     */
    public static function getLogs(array $filters = [], int $limit = 50, int $offset = 0): array {
        $sql = "SELECT al.*, u.full_name as user_name, u.username, u.department, u.email as user_email, u.phone as user_phone,
                       r.name as role_name,
                       pu.name as production_unit_name, w.name as warehouse_name, s.name as shop_name
                FROM audit_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN production_units pu ON u.production_unit_id = pu.id
                LEFT JOIN warehouses w ON u.warehouse_id = w.id
                LEFT JOIN shops s ON u.shop_id = s.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (al.module LIKE ? OR al.action LIKE ? OR al.reference_type LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }
        if (!empty($filters['module'])) {
            $sql .= " AND LOWER(al.module) = LOWER(?)";
            $params[] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND UPPER(al.action) = UPPER(?)";
            $params[] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND al.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND al.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get audit logs for a specific entity reference.
     */
    public static function getLogsForReference(string $referenceType, int $referenceId, int $limit = 50): array {
        return Database::fetchAll(
            "SELECT al.*, u.full_name as user_name, u.username, r.name as role_name 
             FROM audit_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE al.reference_type = ? AND al.reference_id = ?
             ORDER BY al.created_at DESC LIMIT " . (int)$limit,
            [$referenceType, $referenceId]
        );
    }

    /**
     * Count audit logs matching filters.
     */
    public static function count(array $filters = []): int {
        $sql = "SELECT COUNT(*) as cnt 
                FROM audit_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (al.module LIKE ? OR al.action LIKE ? OR al.reference_type LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }
        if (!empty($filters['module'])) {
            $sql .= " AND LOWER(al.module) = LOWER(?)";
            $params[] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND UPPER(al.action) = UPPER(?)";
            $params[] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND al.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND al.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $result = Database::fetchOne($sql, $params);
        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Get key metrics summary statistics.
     */
    public static function getStats(): array {
        $total = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM audit_logs")['c'] ?? 0);
        $updates = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM audit_logs WHERE action IN ('UPDATE', 'EDIT', 'MODIFY')")['c'] ?? 0);
        $deletions = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM audit_logs WHERE action IN ('DELETE', 'REMOVE', 'PURGE')")['c'] ?? 0);
        $logins = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM audit_logs WHERE action IN ('LOGIN', 'LOGOUT', 'PASSWORD_CHANGE')")['c'] ?? 0);
        $modules = Database::fetchAll("SELECT DISTINCT LOWER(module) as module FROM audit_logs WHERE module IS NOT NULL AND module != '' ORDER BY module ASC");

        return [
            'total'     => $total,
            'updates'   => $updates,
            'deletions' => $deletions,
            'logins'    => $logins,
            'modules'   => array_column($modules, 'module'),
        ];
    }

    /**
     * Format snapshot payload for clean, human-readable CSV/Excel export.
     */
    public static function formatSnapshotForExport($val): string {
        if ($val === null || $val === '') return '';
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $val = $decoded;
            } else {
                return $val;
            }
        }
        if (is_array($val)) {
            $ignoredKeys = ['_csrf_token', 'csrf_token', '_token', 'confirm_password', 'password_hash'];
            $lines = [];
            foreach ($val as $k => $v) {
                if (in_array(strtolower($k), $ignoredKeys, true)) continue;
                if (is_array($v)) {
                    $vStr = json_encode($v, JSON_UNESCAPED_UNICODE);
                } elseif (is_bool($v)) {
                    $vStr = $v ? 'Yes' : 'No';
                } else {
                    $vStr = (string)$v;
                }
                $lines[] = "$k: $vStr";
            }
            return implode(" | ", $lines);
        }
        return (string)$val;
    }

    /**
     * Compute field-level differences for change tracking.
     */
    public static function computeDiffSummary($prevVal, $newVal): string {
        $prev = is_string($prevVal) ? json_decode($prevVal, true) : $prevVal;
        $new = is_string($newVal) ? json_decode($newVal, true) : $newVal;
        
        if (!is_array($prev) || !is_array($new)) {
            return '';
        }
        
        $ignoredKeys = ['_csrf_token', 'csrf_token', '_token', 'confirm_password', 'password_hash'];
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($prev), array_keys($new)));
        foreach ($allKeys as $k) {
            if (in_array(strtolower($k), $ignoredKeys, true)) continue;
            $p = $prev[$k] ?? 'None';
            $n = $new[$k] ?? 'None';
            if ($p !== $n) {
                $pStr = is_array($p) ? json_encode($p, JSON_UNESCAPED_UNICODE) : (is_bool($p) ? ($p ? 'Yes':'No') : (string)$p);
                $nStr = is_array($n) ? json_encode($n, JSON_UNESCAPED_UNICODE) : (is_bool($n) ? ($n ? 'Yes':'No') : (string)$n);
                $changes[] = "$k changed from '$pStr' to '$nStr'";
            }
        }
        return !empty($changes) ? implode("; ", $changes) : '';
    }

    /**
     * Export logs as downloadable, fully-detailed CSV file.
     */
    public static function exportCsv(array $filters = []): void {
        $logs = self::getLogs($filters, 10000, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_logs_report_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        // Write UTF-8 BOM so Microsoft Excel opens special symbols & Rupee properly
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Log ID',
            'Date & Time',
            'Staff Full Name',
            'Username',
            'Role',
            'Department',
            'Assigned Facility / Unit',
            'Module',
            'Action',
            'Reference Type',
            'Reference ID',
            'Activity Summary',
            'New Record Snapshot / Current Values',
            'Previous Record Snapshot (Before Change)',
            'Field Differences (Changes Made)',
            'IP Address',
            'User Agent'
        ]);

        foreach ($logs as $row) {
            $facility = $row['production_unit_name'] ?: ($row['warehouse_name'] ?: ($row['shop_name'] ?: 'All Facilities'));
            $newFmt = self::formatSnapshotForExport($row['new_value']);
            $prevFmt = self::formatSnapshotForExport($row['previous_value']);
            $diffFmt = self::computeDiffSummary($row['previous_value'], $row['new_value']);
            
            $userName = $row['user_name'] ?? 'System / Automated';
            $actionUpper = strtoupper($row['action']);
            $moduleName = ucfirst($row['module']);
            $summaryText = "$userName performed " . strtolower($row['action']) . " in $moduleName";
            if ($row['reference_type']) {
                $summaryText .= " (" . $row['reference_type'] . " #" . ($row['reference_id'] ?? '-') . ")";
            }

            fputcsv($output, [
                $row['id'],
                date('Y-m-d H:i:s', strtotime($row['created_at'])),
                $userName,
                $row['username'] ?? 'system',
                $row['role_name'] ?? 'Super Admin',
                strtoupper($row['department'] ?? 'GENERAL'),
                $facility,
                $moduleName,
                $actionUpper,
                $row['reference_type'] ?? '-',
                $row['reference_id'] ?? '-',
                $summaryText,
                $newFmt,
                $prevFmt,
                $diffFmt,
                $row['ip_address'] ?? '127.0.0.1',
                $row['user_agent'] ?? 'N/A',
            ]);
        }
        fclose($output);
        exit;
    }

    /**
     * Purge logs older than given number of days.
     */
    public static function purgeOldLogs(int $daysToKeep = 90): int {
        if ($daysToKeep < 7) {
            $daysToKeep = 7; // Safety minimum limit
        }
        return Database::execute(
            "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$daysToKeep]
        );
    }
}
