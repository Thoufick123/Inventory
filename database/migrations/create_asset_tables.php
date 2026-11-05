<?php
/**
 * Migration: Create Enterprise Asset Management Tables
 * - asset_categories (Category masters with depreciation rates & icons)
 * - assets (Master inventory of equipment, machinery, IT hardware, and fixtures across all units)
 * - asset_transfers (Inter-unit asset movement audit logs & delivery notes)
 * - asset_maintenance_logs (Breakdown repairs, preventive servicing, AMC, and calibrations)
 * - asset_audits (Physical asset verification & stock-taking cycles)
 * - asset_audit_items (Individual asset scan checks during audits)
 */

define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();

    // 1. asset_categories
    $db->exec("
        CREATE TABLE IF NOT EXISTS asset_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            code VARCHAR(50) NOT NULL UNIQUE,
            icon VARCHAR(100) NOT NULL DEFAULT 'bi-box-seam',
            description TEXT NULL,
            depreciation_rate_annual DECIMAL(5,2) NOT NULL DEFAULT 15.00,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            INDEX idx_code (code),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `asset_categories` ready.\n";

    // 2. assets
    $db->exec("
        CREATE TABLE IF NOT EXISTS assets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            asset_code VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(200) NOT NULL,
            category_id INT UNSIGNED NOT NULL,
            location_type ENUM('PRODUCTION', 'WAREHOUSE', 'SHOP', 'OFFICE') NOT NULL DEFAULT 'PRODUCTION',
            location_id INT UNSIGNED NOT NULL DEFAULT 1,
            floor_area VARCHAR(150) NULL,
            brand VARCHAR(100) NULL,
            model_number VARCHAR(100) NULL,
            serial_number VARCHAR(150) NULL,
            barcode_qr VARCHAR(100) NOT NULL UNIQUE,
            specifications TEXT NULL,
            purchase_date DATE NULL,
            purchase_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            vendor_name VARCHAR(150) NULL,
            vendor_contact VARCHAR(100) NULL,
            invoice_number VARCHAR(100) NULL,
            warranty_expiry DATE NULL,
            useful_life_years INT NOT NULL DEFAULT 5,
            salvage_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            current_book_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            last_depreciation_date DATE NULL,
            status ENUM('IN_USE', 'IDLE', 'IN_MAINTENANCE', 'DAMAGED', 'DISPOSED', 'WRITTEN_OFF') NOT NULL DEFAULT 'IN_USE',
            condition_state ENUM('EXCELLENT', 'GOOD', 'FAIR', 'POOR', 'CRITICAL') NOT NULL DEFAULT 'GOOD',
            custodian_id INT UNSIGNED NULL,
            custodian_name VARCHAR(150) NULL,
            photo_url VARCHAR(255) NULL,
            invoice_file_url VARCHAR(255) NULL,
            notes TEXT NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            INDEX idx_category (category_id),
            INDEX idx_location (location_type, location_id),
            INDEX idx_status (status),
            INDEX idx_condition (condition_state),
            INDEX idx_qr (barcode_qr),
            INDEX idx_code (asset_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `assets` ready.\n";

    // 3. asset_transfers
    $db->exec("
        CREATE TABLE IF NOT EXISTS asset_transfers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            transfer_number VARCHAR(50) NOT NULL UNIQUE,
            asset_id INT UNSIGNED NOT NULL,
            from_location_type ENUM('PRODUCTION', 'WAREHOUSE', 'SHOP', 'OFFICE') NOT NULL,
            from_location_id INT UNSIGNED NOT NULL,
            from_floor_area VARCHAR(150) NULL,
            to_location_type ENUM('PRODUCTION', 'WAREHOUSE', 'SHOP', 'OFFICE') NOT NULL,
            to_location_id INT UNSIGNED NOT NULL,
            to_floor_area VARCHAR(150) NULL,
            transfer_date DATETIME NOT NULL,
            status ENUM('INITIATED', 'IN_TRANSIT', 'COMPLETED', 'REJECTED') NOT NULL DEFAULT 'COMPLETED',
            transferred_by INT UNSIGNED NULL,
            received_by INT UNSIGNED NULL,
            reason VARCHAR(255) NULL,
            challan_notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            INDEX idx_asset (asset_id),
            INDEX idx_from (from_location_type, from_location_id),
            INDEX idx_to (to_location_type, to_location_id),
            INDEX idx_status (status),
            INDEX idx_date (transfer_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `asset_transfers` ready.\n";

    // 4. asset_maintenance_logs
    $db->exec("
        CREATE TABLE IF NOT EXISTS asset_maintenance_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            log_number VARCHAR(50) NOT NULL UNIQUE,
            asset_id INT UNSIGNED NOT NULL,
            service_type ENUM('ROUTINE_PREVENTIVE', 'BREAKDOWN_REPAIR', 'CALIBRATION', 'PART_REPLACEMENT', 'ANNUAL_AMC') NOT NULL DEFAULT 'ROUTINE_PREVENTIVE',
            issue_description TEXT NULL,
            action_taken TEXT NULL,
            serviced_by_vendor VARCHAR(150) NULL,
            technician_name VARCHAR(100) NULL,
            technician_contact VARCHAR(50) NULL,
            service_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            parts_replaced TEXT NULL,
            service_date DATE NOT NULL,
            next_due_date DATE NULL,
            downtime_hours DECIMAL(5,1) NOT NULL DEFAULT 0.0,
            status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED') NOT NULL DEFAULT 'COMPLETED',
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            INDEX idx_asset (asset_id),
            INDEX idx_service_type (service_type),
            INDEX idx_service_date (service_date),
            INDEX idx_next_due (next_due_date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `asset_maintenance_logs` ready.\n";

    // 5. asset_audits & audit items
    $db->exec("
        CREATE TABLE IF NOT EXISTS asset_audits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            audit_number VARCHAR(50) NOT NULL UNIQUE,
            location_type ENUM('PRODUCTION', 'WAREHOUSE', 'SHOP', 'OFFICE') NOT NULL,
            location_id INT UNSIGNED NOT NULL,
            title VARCHAR(200) NOT NULL,
            audit_date DATE NOT NULL,
            status ENUM('PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'COMPLETED',
            total_assets_expected INT NOT NULL DEFAULT 0,
            total_assets_verified INT NOT NULL DEFAULT 0,
            total_assets_missing INT NOT NULL DEFAULT 0,
            total_assets_damaged INT NOT NULL DEFAULT 0,
            auditor_id INT UNSIGNED NULL,
            auditor_name VARCHAR(150) NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            INDEX idx_loc (location_type, location_id),
            INDEX idx_status (status),
            INDEX idx_date (audit_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `asset_audits` ready.\n";

    $db->exec("
        CREATE TABLE IF NOT EXISTS asset_audit_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            audit_id INT UNSIGNED NOT NULL,
            asset_id INT UNSIGNED NOT NULL,
            status ENUM('VERIFIED_OK', 'DAMAGED', 'MISSING', 'WRONG_LOCATION') NOT NULL DEFAULT 'VERIFIED_OK',
            scanned_at DATETIME NULL,
            observed_condition ENUM('EXCELLENT', 'GOOD', 'FAIR', 'POOR', 'CRITICAL') NULL,
            notes TEXT NULL,
            INDEX idx_audit (audit_id),
            INDEX idx_asset (asset_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table `asset_audit_items` ready.\n";

    echo "\n=== All Asset Tables Successfully Created! ===\n";

} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
    exit(1);
}
