<?php
/**
 * Migration: Create Cash Flow & Register Tables
 * - cash_registers (Shifts & Till Management)
 * - cash_transactions (Unified Cash & Payment Flow Ledger)
 * - cash_expenses (Petty Cash & Shop Expense Vouchers)
 * - bank_deposits (Bank Cash Drops & Handover Slips)
 */

define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();

    // 1. cash_registers (Shift & Till Management)
    $db->exec("
        CREATE TABLE IF NOT EXISTS cash_registers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id INT UNSIGNED NOT NULL,
            register_number VARCHAR(50) NOT NULL UNIQUE,
            cashier_id INT UNSIGNED NOT NULL,
            cashier_name VARCHAR(150) NULL,
            closed_by INT UNSIGNED NULL,
            opening_time DATETIME NOT NULL,
            closing_time DATETIME NULL,
            opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            opening_expected_float DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            opening_difference DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            opening_denominations TEXT NULL,
            opening_notes TEXT NULL,
            opening_discrepancy_reason TEXT NULL,
            cash_sales_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            upi_sales_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            card_sales_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            cash_in_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            cash_out_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            cash_refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            bank_deposit_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            expected_cash DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            closing_cash_counted DECIMAL(12,2) NULL,
            closing_denominations TEXT NULL,
            cash_difference DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            discrepancy_reason TEXT NULL,
            status ENUM('OPEN', 'CLOSED', 'AUDITED') NOT NULL DEFAULT 'OPEN',
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            INDEX idx_shop_status (shop_id, status),
            INDEX idx_cashier (cashier_id),
            INDEX idx_opening_time (opening_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `cash_registers` ready.\n";

    // 2. cash_transactions (Unified Flow Ledger)
    $db->exec("
        CREATE TABLE IF NOT EXISTS cash_transactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id INT UNSIGNED NOT NULL,
            register_id INT UNSIGNED NULL,
            transaction_number VARCHAR(50) NOT NULL UNIQUE,
            transaction_type ENUM(
                'OPENING_FLOAT',
                'SALE_CASH',
                'SALE_UPI',
                'SALE_CARD',
                'CASH_IN',
                'CASH_OUT',
                'EXPENSE',
                'REFUND_CASH',
                'BANK_DEPOSIT',
                'SAFE_DROP',
                'CLOSING_ADJUSTMENT'
            ) NOT NULL,
            flow_direction ENUM('IN', 'OUT') NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            payment_mode ENUM('CASH', 'UPI', 'CARD', 'BANK_TRANSFER', 'OTHER') NOT NULL DEFAULT 'CASH',
            category VARCHAR(50) NOT NULL DEFAULT 'GENERAL',
            reference_id VARCHAR(100) NULL,
            source_table VARCHAR(50) NULL,
            source_id INT UNSIGNED NULL,
            handled_by INT UNSIGNED NOT NULL,
            description TEXT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_shop_flow (shop_id, flow_direction),
            INDEX idx_register (register_id),
            INDEX idx_type (transaction_type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `cash_transactions` ready.\n";

    // 3. cash_expenses (Petty Cash & Expense Vouchers)
    $db->exec("
        CREATE TABLE IF NOT EXISTS cash_expenses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id INT UNSIGNED NOT NULL,
            register_id INT UNSIGNED NULL,
            voucher_number VARCHAR(50) NOT NULL UNIQUE,
            expense_category ENUM(
                'TEA_SNACKS',
                'CLEANING_SUPPLIES',
                'STORE_MAINTENANCE',
                'COURIER_SHIPPING',
                'PACKAGING_BAGS',
                'STAFF_EXPENSE',
                'UTILITIES',
                'TRANSPORT',
                'PRINTING_STATIONERY',
                'MISC'
            ) NOT NULL DEFAULT 'MISC',
            amount DECIMAL(10,2) NOT NULL,
            paid_to VARCHAR(150) NOT NULL,
            payment_mode ENUM('CASH', 'UPI', 'CARD', 'ONLINE') NOT NULL DEFAULT 'CASH',
            bill_number VARCHAR(100) NULL,
            approved_by INT UNSIGNED NULL,
            created_by INT UNSIGNED NOT NULL,
            notes TEXT NULL,
            receipt_image VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_shop_cat (shop_id, expense_category),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `cash_expenses` ready.\n";

    // 4. bank_deposits (Bank Cash Drops & Handover Slips)
    $db->exec("
        CREATE TABLE IF NOT EXISTS bank_deposits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id INT UNSIGNED NOT NULL,
            register_id INT UNSIGNED NULL,
            deposit_number VARCHAR(50) NOT NULL UNIQUE,
            bank_name VARCHAR(100) NOT NULL,
            account_number VARCHAR(50) NULL,
            amount DECIMAL(12,2) NOT NULL,
            slip_number VARCHAR(100) NULL,
            deposit_date DATE NOT NULL,
            deposited_by INT UNSIGNED NOT NULL,
            verified_by INT UNSIGNED NULL,
            status ENUM('PENDING', 'VERIFIED', 'REJECTED') NOT NULL DEFAULT 'VERIFIED',
            notes TEXT NULL,
            slip_image VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_shop_status (shop_id, status),
            INDEX idx_deposit_date (deposit_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `bank_deposits` ready.\n";

    echo "\nAll Cash Management migration tables created successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
