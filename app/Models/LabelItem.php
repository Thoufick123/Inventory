<?php
/**
 * Label Item Model
 * Manages variant-level required, printed, used, and damaged label counts.
 */

class LabelItem {

    /**
     * Get items for a label batch.
     */
    public static function getByBatch(int $batchId): array {
        return Database::fetchAll(
            "SELECT li.*, 
                    pv.sku, pv.qr_code, 
                    COALESCE(li.base_price, pv.mrp, 0.00) as base_price, 
                    COALESCE(li.discount_price, pv.selling_price, 0.00) as discount_price,
                    COALESCE(li.size_name, s.name, s.code) as display_size,
                    COALESCE(li.color_name, c.name) as display_color,
                    s.name as size_name, s.code as size_code,
                    c.name as color_name, c.hex_code as color_hex,
                    cat.name as category_name,
                    subcat.name as subcategory_name,
                    subsubcat.name as sub_subcategory_name
             FROM label_items li
             LEFT JOIN product_variants pv ON li.variant_id = pv.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN categories cat ON li.category_id = cat.id
             LEFT JOIN subcategories subcat ON li.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON li.sub_subcategory_id = subsubcat.id
             WHERE li.label_batch_id = ?
             ORDER BY li.id ASC",
            [$batchId]
        );
    }

    /**
     * Create label item record.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO label_items 
             (label_batch_id, variant_id, category_id, subcategory_id, sub_subcategory_id, catalog_hierarchy, size_name, color_name, base_price, discount_price, required_qty, printed_qty, used_qty, damaged_qty, start_seq, end_seq, start_code, end_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['label_batch_id'],
                !empty($data['variant_id']) ? $data['variant_id'] : null,
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null,
                !empty($data['sub_subcategory_id']) ? (int)$data['sub_subcategory_id'] : null,
                $data['catalog_hierarchy'] ?? null,
                $data['size_name'] ?? null,
                $data['color_name'] ?? null,
                $data['base_price'] ?? 0.00,
                $data['discount_price'] ?? 0.00,
                $data['required_qty'] ?? 0,
                $data['printed_qty'] ?? 0,
                $data['used_qty'] ?? 0,
                $data['damaged_qty'] ?? 0,
                !empty($data['start_seq']) ? (int)$data['start_seq'] : 1,
                !empty($data['end_seq']) ? (int)$data['end_seq'] : 1,
                $data['start_code'] ?? null,
                $data['end_code'] ?? null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update label item quantities and sequences.
     */
    public static function update(int $id, array $data): bool {
        return Database::execute(
            "UPDATE label_items 
             SET required_qty = ?, printed_qty = ?, used_qty = ?, damaged_qty = ?,
                 start_seq = COALESCE(?, start_seq), end_seq = COALESCE(?, end_seq),
                 start_code = COALESCE(?, start_code), end_code = COALESCE(?, end_code)
             WHERE id = ?",
            [
                $data['required_qty'] ?? 0,
                $data['printed_qty'] ?? 0,
                $data['used_qty'] ?? 0,
                $data['damaged_qty'] ?? 0,
                $data['start_seq'] ?? null,
                $data['end_seq'] ?? null,
                $data['start_code'] ?? null,
                $data['end_code'] ?? null,
                $id
            ]
        ) >= 0;
    }

    /**
     * Delete all items for a label batch.
     */
    public static function deleteByBatch(int $batchId): int {
        return Database::execute(
            "DELETE FROM label_items WHERE label_batch_id = ?",
            [$batchId]
        );
    }

    /**
     * Get the highest sequence number used for a catalog (category, subcategory, sub-subcategory).
     */
    public static function getLastSequenceForCatalog(?int $categoryId, ?int $subcategoryId, ?int $subSubcategoryId, ?string $catalogHierarchy = null, ?int $excludeBatchId = null): int {
        $where = [];
        $params = [];

        if ($excludeBatchId) {
            $where[] = "li.label_batch_id != ?";
            $params[] = (int)$excludeBatchId;
        }

        if ($categoryId || $subcategoryId || $subSubcategoryId) {
            $catClause = "(li.category_id <=> ? AND li.subcategory_id <=> ? AND li.sub_subcategory_id <=> ?)";
            $params[] = $categoryId ?: null;
            $params[] = $subcategoryId ?: null;
            $params[] = $subSubcategoryId ?: null;

            if (!empty($catalogHierarchy)) {
                $catClause = "($catClause OR li.catalog_hierarchy = ?)";
                $params[] = trim($catalogHierarchy);
            }
            $where[] = $catClause;
        } elseif (!empty($catalogHierarchy)) {
            $where[] = "li.catalog_hierarchy = ?";
            $params[] = trim($catalogHierarchy);
        } else {
            $where[] = "(li.category_id IS NULL AND li.subcategory_id IS NULL AND li.sub_subcategory_id IS NULL)";
        }

        $sql = "SELECT GREATEST(
                    COALESCE(MAX(li.end_seq), 0),
                    COALESCE(SUM(li.printed_qty), 0),
                    COALESCE(SUM(li.required_qty), 0)
                ) as last_seq
                FROM label_items li";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $row = Database::fetchOne($sql, $params);
        return (int)($row['last_seq'] ?? 0);
    }

    /**
     * Generate 2D QR label code string (e.g. WMF102631).
     */
    public static function generateLabelCode(int $seqIndex, ?string $catalogHierarchy = null, array $item = []): string {
        $abbr = 'RMT';
        if (!empty($item['qr_code_prefix'])) {
            $rawPrefix = trim($item['qr_code_prefix']);
            if (strpos($rawPrefix, '-') !== false) {
                $parts = preg_split('/[^A-Za-z0-9]+/', $rawPrefix);
                $extracted = '';
                foreach ($parts as $p) {
                    if (strlen($p) > 0) $extracted .= $p[0];
                }
                $abbr = strtoupper(substr($extracted, 0, 3));
            } elseif (strlen($rawPrefix) <= 4) {
                $abbr = strtoupper($rawPrefix);
            } else {
                $abbr = strtoupper(substr($rawPrefix, 0, 3));
            }
        } elseif (!empty($item['category_code']) || !empty($item['subcategory_code']) || !empty($item['sub_subcategory_code'])) {
            $c1 = !empty($item['category_code']) ? substr(trim($item['category_code']), 0, 1) : '';
            $c2 = !empty($item['subcategory_code']) ? substr(trim($item['subcategory_code']), 0, 1) : '';
            $c3 = !empty($item['sub_subcategory_code']) ? substr(trim($item['sub_subcategory_code']), 0, 1) : '';
            $abbr = strtoupper($c1 . $c2 . $c3);
            if (strlen($abbr) < 3) {
                $full = trim(($item['category_code'] ?? '') . ($item['subcategory_code'] ?? '') . ($item['sub_subcategory_code'] ?? ''));
                $abbr = strtoupper(substr($full, 0, 3));
            }
        } elseif (!empty($catalogHierarchy) && $catalogHierarchy !== 'General Apparel' && $catalogHierarchy !== 'Apparel Catalog') {
            $parts = preg_split('/[›>|\-]/', $catalogHierarchy);
            $extracted = '';
            foreach ($parts as $part) {
                $words = preg_split('/\s+/', trim($part));
                if (!empty($words[0])) {
                    $extracted .= $words[0][0];
                }
            }
            $abbr = strtoupper(substr($extracted, 0, 3));
        }

        if (empty($abbr)) $abbr = 'RMT';

        $mfgDate = (!empty($item['variant_mfg_date']) || !empty($item['mfg_date'])) 
            ? strtotime($item['variant_mfg_date'] ?? $item['mfg_date']) 
            : time();
        $month = date('m', $mfgDate ?: time());
        $year = date('y', $mfgDate ?: time());
        $seq = str_pad((string)$seqIndex, 2, '0', STR_PAD_LEFT);

        return strtoupper($abbr . $month . $year . $seq);
    }
}
