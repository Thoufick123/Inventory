<?php
/**
 * Migration: Production-unit cash registers, cash ledger, expenses, and deposits.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_cash_registers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            production_unit_id INT UNSIGNED NOT NULL,
            register_number VARCHAR(50) NOT NULL,
            cashier_id INT UNSIGNED NOT NULL,
            cashier_name VARCHAR(150) DEFAULT NULL,
            closed_by INT UNSIGNED DEFAULT NULL,
            opening_time DATETIME NOT NULL,
            closing_time DATETIME DEFAULT NULL,
            opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            opening_expected_float DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            opening_difference DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            opening_denominations LONGTEXT DEFAULT NULL,
            opening_notes TEXT DEFAULT NULL,
            opening_discrepancy_reason TEXT DEFAULT NULL,
            cash_in_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            cash_out_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            bank_deposit_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            expected_cash DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            closing_cash_counted DECIMAL(12,2) DEFAULT NULL,
            closing_denominations LONGTEXT DEFAULT NULL,
            cash_difference DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            discrepancy_reason TEXT DEFAULT NULL,
            status ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_cash_register_number (register_number),
            KEY idx_production_cash_unit_status (production_unit_id, status),
            KEY idx_production_cash_opening_time (opening_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_cash_transactions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            production_unit_id INT UNSIGNED NOT NULL,
            register_id INT UNSIGNED DEFAULT NULL,
            transaction_number VARCHAR(50) NOT NULL,
            transaction_type ENUM('OPENING_FLOAT','CASH_IN','EXPENSE','BANK_DEPOSIT','CLOSING_ADJUSTMENT') NOT NULL,
            flow_direction ENUM('IN','OUT') NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            payment_mode ENUM('CASH','UPI','BANK_TRANSFER','CARD','OTHER') NOT NULL DEFAULT 'CASH',
            category VARCHAR(100) NOT NULL DEFAULT 'GENERAL',
            reference_id VARCHAR(100) DEFAULT NULL,
            source_table VARCHAR(50) DEFAULT NULL,
            source_id INT UNSIGNED DEFAULT NULL,
            handled_by INT UNSIGNED NOT NULL,
            description TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_cash_transaction_number (transaction_number),
            KEY idx_production_cash_tx_unit_flow (production_unit_id, flow_direction),
            KEY idx_production_cash_tx_register (register_id),
            KEY idx_production_cash_tx_type (transaction_type),
            KEY idx_production_cash_tx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_cash_expenses (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            production_unit_id INT UNSIGNED NOT NULL,
            register_id INT UNSIGNED DEFAULT NULL,
            voucher_number VARCHAR(50) NOT NULL,
            expense_category VARCHAR(150) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            paid_to VARCHAR(200) NOT NULL,
            payment_mode ENUM('CASH','UPI','BANK_TRANSFER','CARD','OTHER') NOT NULL DEFAULT 'CASH',
            bill_number VARCHAR(100) DEFAULT NULL,
            approved_by INT UNSIGNED DEFAULT NULL,
            created_by INT UNSIGNED NOT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_cash_voucher (voucher_number),
            KEY idx_production_cash_exp_unit_category (production_unit_id, expense_category),
            KEY idx_production_cash_exp_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    Database::execute(
        "CREATE TABLE IF NOT EXISTS production_cash_deposits (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            production_unit_id INT UNSIGNED NOT NULL,
            register_id INT UNSIGNED DEFAULT NULL,
            deposit_number VARCHAR(50) NOT NULL,
            bank_name VARCHAR(150) NOT NULL,
            account_number VARCHAR(80) DEFAULT NULL,
            amount DECIMAL(12,2) NOT NULL,
            slip_number VARCHAR(100) DEFAULT NULL,
            deposit_date DATE NOT NULL,
            deposited_by INT UNSIGNED NOT NULL,
            verified_by INT UNSIGNED DEFAULT NULL,
            status ENUM('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'VERIFIED',
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_production_cash_deposit_number (deposit_number),
            KEY idx_production_cash_dep_unit_status (production_unit_id, status),
            KEY idx_production_cash_dep_date (deposit_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $permissionDefinitions = [
        ['view', 'View production-unit cash flow and shift records'],
        ['create', 'Open shifts and record production cash movements'],
        ['close_shift', 'Close and reconcile a production cash shift'],
        ['voucher', 'View and print production cash vouchers'],
    ];
    foreach ($permissionDefinitions as [$action, $description]) {
        Database::execute(
            "INSERT INTO permissions (module, action, description, slug, created_at)
             VALUES ('production_cash', ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE description = VALUES(description), slug = VALUES(slug)",
            [$action, $description, 'production_cash.' . $action]
        );
    }

    $permissionMappings = [
        'view' => ['view'],
        'create' => ['create'],
        'close_shift' => ['edit'],
        'voucher' => ['view', 'print'],
    ];
    foreach ($permissionMappings as $cashAction => $expenseActions) {
        $actionPlaceholders = implode(', ', array_fill(0, count($expenseActions), '?'));
        Database::execute(
            "INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
             SELECT DISTINCT source_rp.role_id, cash_permission.id, NOW()
             FROM role_permissions source_rp
             JOIN permissions source_permission ON source_permission.id = source_rp.permission_id
             JOIN permissions cash_permission ON cash_permission.module = 'production_cash' AND cash_permission.action = ?
             WHERE source_permission.module = 'production_expenses'
               AND source_permission.action IN ({$actionPlaceholders})",
            array_merge([$cashAction], $expenseActions)
        );
    }

    echo "Production cash flow tables and permissions are ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Production cash flow migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}