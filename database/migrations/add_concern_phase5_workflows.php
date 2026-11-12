<?php
/**
 * Phase 5 migration (additive & idempotent):
 * 1. Allow inward-packed batches in production_batches (nullable program_id + source location metadata).
 * 2. Allow unit-less concern cash registers in production_cash_* tables and track bill sales/refunds.
 * 3. Add 'INWARD' source stage option to production_inventory_damages.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $tableExists = static function (string $table): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
    };
    $columnExists = static function (string $table, string $column): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
    };
    $indexExists = static function (string $table, string $indexName): bool {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $indexName]
        );
    };

    if ($tableExists('production_batches')) {
        Database::execute("ALTER TABLE production_batches MODIFY program_id INT UNSIGNED NULL DEFAULT NULL");

        if (!$columnExists('production_batches', 'source_type')) {
            Database::execute(
                "ALTER TABLE production_batches ADD COLUMN source_type ENUM('QC','INWARD') NOT NULL DEFAULT 'QC' AFTER qc_batch_id"
            );
        }
        if (!$columnExists('production_batches', 'source_location_type')) {
            Database::execute(
                "ALTER TABLE production_batches ADD COLUMN source_location_type ENUM('CONCERN','PRODUCTION_UNIT') NULL DEFAULT NULL AFTER source_type"
            );
        }
        if (!$columnExists('production_batches', 'source_location_id')) {
            Database::execute(
                "ALTER TABLE production_batches ADD COLUMN source_location_id INT UNSIGNED NULL DEFAULT NULL AFTER source_location_type"
            );
        }
        if (!$indexExists('production_batches', 'idx_production_batches_source')) {
            Database::execute(
                "ALTER TABLE production_batches ADD INDEX idx_production_batches_source (source_type, source_location_type, source_location_id)"
            );
        }
    }

    if ($tableExists('production_cash_registers')) {
        Database::execute("ALTER TABLE production_cash_registers MODIFY production_unit_id INT UNSIGNED NULL DEFAULT NULL");
        if (!$columnExists('production_cash_registers', 'cash_sales_amount')) {
            Database::execute(
                "ALTER TABLE production_cash_registers ADD COLUMN cash_sales_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER opening_discrepancy_reason"
            );
        }
        if (!$columnExists('production_cash_registers', 'upi_sales_amount')) {
            Database::execute(
                "ALTER TABLE production_cash_registers ADD COLUMN upi_sales_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER cash_sales_amount"
            );
        }
        if (!$columnExists('production_cash_registers', 'card_sales_amount')) {
            Database::execute(
                "ALTER TABLE production_cash_registers ADD COLUMN card_sales_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER upi_sales_amount"
            );
        }
        if (!$columnExists('production_cash_registers', 'cash_refund_amount')) {
            Database::execute(
                "ALTER TABLE production_cash_registers ADD COLUMN cash_refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER cash_out_amount"
            );
        }
    }

    if ($tableExists('production_cash_transactions')) {
        Database::execute("ALTER TABLE production_cash_transactions MODIFY production_unit_id INT UNSIGNED NULL DEFAULT NULL");
        Database::execute(
            "ALTER TABLE production_cash_transactions
             MODIFY transaction_type ENUM('OPENING_FLOAT','SALE_CASH','SALE_UPI','SALE_CARD','SALE_OTHER','CASH_IN','EXPENSE','REFUND_CASH','REFUND_UPI','BANK_DEPOSIT','CLOSING_ADJUSTMENT') NOT NULL"
        );
        if (!$indexExists('production_cash_transactions', 'idx_production_cash_tx_source')) {
            Database::execute(
                "ALTER TABLE production_cash_transactions ADD INDEX idx_production_cash_tx_source (source_table, source_id)"
            );
        }
    }

    if ($tableExists('production_cash_expenses')) {
        Database::execute("ALTER TABLE production_cash_expenses MODIFY production_unit_id INT UNSIGNED NULL DEFAULT NULL");
    }

    if ($tableExists('production_cash_deposits')) {
        Database::execute("ALTER TABLE production_cash_deposits MODIFY production_unit_id INT UNSIGNED NULL DEFAULT NULL");
    }

    if ($tableExists('production_inventory_damages')) {
        Database::execute(
            "ALTER TABLE production_inventory_damages
             MODIFY source_stage ENUM('QC','LABELING','PACKING','INWARD','STORAGE_HANDLING','OTHER') NOT NULL DEFAULT 'OTHER'"
        );
    }

    echo "Phase 5 production downstream, cash register, and damage schema updates applied successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Phase 5 migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
