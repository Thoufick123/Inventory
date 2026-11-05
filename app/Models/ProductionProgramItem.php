<?php
/**
 * Production Program Item Model
 * Manages variant-wise planned and completed quantities within a program.
 */

class ProductionProgramItem {

    /**
     * Get all items for a program with variant details.
     */
    public static function getByProgram(int $programId): array {
        return Database::fetchAll(
            "SELECT ppi.*, 
                    pv.sku, pv.qr_code, pv.selling_price, pv.production_cost,
                    s.name as size_name, s.code as size_code,
                    c.name as color_name, c.hex_code as color_hex
             FROM production_program_items ppi
             JOIN product_variants pv ON ppi.variant_id = pv.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE ppi.program_id = ?
             ORDER BY s.sort_order ASC, c.name ASC",
            [$programId]
        );
    }

    /**
     * Create a program item record.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO production_program_items 
             (program_id, variant_id, planned_quantity, completed_quantity)
             VALUES (?, ?, ?, ?)",
            [
                $data['program_id'],
                $data['variant_id'],
                $data['planned_quantity'],
                $data['completed_quantity'] ?? 0,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update completed quantity for a specific program variant item.
     */
    public static function updateCompletedQty(int $programId, int $variantId, int $qty): bool {
        return Database::execute(
            "UPDATE production_program_items 
             SET completed_quantity = completed_quantity + ? 
             WHERE program_id = ? AND variant_id = ?",
            [$qty, $programId, $variantId]
        ) > 0;
    }

    /**
     * Delete all items for a program (used when updating/recreating items).
     */
    public static function deleteByProgram(int $programId): int {
        return Database::execute(
            "DELETE FROM production_program_items WHERE program_id = ?",
            [$programId]
        );
    }
}
