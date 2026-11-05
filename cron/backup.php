<?php
/**
 * Cron Execution Runner — Automated Database Backup Task
 * Executed via CLI (Windows Task Scheduler or Linux Crontab).
 * 
 * Usage: php cron/backup.php
 */

define('BASE_PATH', dirname(__DIR__));

// Load configuration and env() helper function
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Services/SettingsService.php';
require_once BASE_PATH . '/app/Services/BackupService.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting automated cron backup runner...\n";

try {
    $enabled = SettingsService::get('auto_backup_enabled', 'true');
    if ($enabled === 'false' || $enabled === false) {
        echo "[" . date('Y-m-d H:i:s') . "] Automated cron backups are currently disabled in Settings Hub. Exiting.\n";
        exit(0);
    }

    $targetEmail = env('BACKUP_EMAIL', 'abiprasath6@gmail.com');
    echo "[" . date('Y-m-d H:i:s') . "] Generating backup and emailing snapshot to {$targetEmail} (Zero Local Storage)...\n";
    
    $result = BackupService::createAndEmailBackup($targetEmail, true, 'Automated Cron Remote Backup', false);
    echo "[" . date('Y-m-d H:i:s') . "] Success! Snapshot ({$result['filename']}, {$result['size']}) dispatched to {$result['email']}.\n";
    echo "[" . date('Y-m-d H:i:s') . "] Local temporary backup file purged successfully. No local storage consumed.\n";

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Error during cron backup: " . $e->getMessage() . "\n";
    exit(1);
}
