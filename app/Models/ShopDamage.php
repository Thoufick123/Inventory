<?php
/**
 * Shop Damage Model
 * Handles damaged goods logging in retail shops (both during inward transit inspection
 * and shop bin / floor damage identification), defect categorization, photo attachments,
 * salvage/write-off/repair/return-to-warehouse lifecycle, and financial loss calculations.
 */

class ShopDamage {

    /**
     * Return only the piece tags explicitly recorded for this damage case.
     */
    public static function resolvePieceTags(array $d): array {
        $rawTags = trim((string)($d['tag_code'] ?? ''));
        if ($rawTags === '') {
            return [];
        }

        $decoded = json_decode($rawTags, true);
        $parts = is_array($decoded) ? $decoded : explode(',', $rawTags);
        $tags = [];
        foreach ($parts as $part) {
            $tag = is_array($part)
                ? trim((string)($part['tag_code'] ?? $part['qr_code'] ?? ''))
                : trim((string)$part);
            if ($tag !== '') {
                $tags[$tag] = $tag;
            }
        }

        return array_values($tags);
    }

    /**
     * Find shop damage record by ID.
     */
    public static function find(int $id): ?array {
        $record = Database::fetchOne(
            "SELECT sd.*, 
                    sh.name as shop_name, sh.code as shop_code,
                    pv.sku, pv.qr_code, pv.selling_price, pv.mrp, pv.purchase_price, pv.production_cost,
                    p.id as product_id, p.name as product_name, p.code as product_code, p.image_url as product_image,
                    s.name as size_name, c.name as color_name,
                    cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                    u.full_name as reported_by_name,
                    u_insp.full_name as inspected_by_name,
                    wa.allocation_number, wa.id as allocation_id, wa.status as allocation_status,
                    ao.outward_number, ao.dispatched_at as outward_dispatch_date, ao.vehicle_number, ao.driver_name
             FROM shop_damages sd
             JOIN shops sh ON sd.shop_id = sh.id
             JOIN product_variants pv ON sd.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
             LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
             LEFT JOIN users u ON sd.reported_by = u.id
             LEFT JOIN users u_insp ON sd.inspected_by = u_insp.id
             LEFT JOIN warehouse_allocations wa ON (sd.source_type = 'INWARD_TRANSIT' AND (sd.allocation_id = wa.id OR sd.reference_id = wa.id))
             LEFT JOIN allocation_outwards ao ON ao.allocation_id = wa.id
             WHERE sd.id = ?",
            [$id]
        );

        if ($record) {
            $record['piece_tags'] = self::resolvePieceTags($record);
            $record['tag_code'] = implode(', ', $record['piece_tags']);
        }

        return $record;
    }

    /**
     * Get all shop damage records with filters.
     */
    public static function getAll(array $filters = []): array {
        $sql = "SELECT sd.*, 
                       sh.name as shop_name, sh.code as shop_code,
                       pv.sku, pv.mrp, pv.selling_price,
                       p.name as product_name, p.code as product_code, p.image_url as product_image,
                       s.name as size_name, c.name as color_name,
                       cat.name as category_name, subcat.name as subcategory_name, subsubcat.name as sub_subcategory_name,
                       u.full_name as reported_by_name,
                       wa.allocation_number, wa.id as allocation_id, wa.status as allocation_status,
                       ao.outward_number
                FROM shop_damages sd
                JOIN shops sh ON sd.shop_id = sh.id
                JOIN product_variants pv ON sd.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN sizes s ON pv.size_id = s.id
                LEFT JOIN colors c ON pv.color_id = c.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN subcategories subcat ON p.subcategory_id = subcat.id
                LEFT JOIN sub_subcategories subsubcat ON p.sub_subcategory_id = subsubcat.id
                LEFT JOIN users u ON sd.reported_by = u.id
                LEFT JOIN warehouse_allocations wa ON (sd.source_type = 'INWARD_TRANSIT' AND (sd.allocation_id = wa.id OR sd.reference_id = wa.id))
                LEFT JOIN allocation_outwards ao ON ao.allocation_id = wa.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['shop_id'])) {
            $sql .= " AND sd.shop_id = ?";
            $params[] = (int)$filters['shop_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND sd.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['source_type'])) {
            $sql .= " AND sd.source_type = ?";
            $params[] = $filters['source_type'];
        }

        if (!empty($filters['reference_id'])) {
            $sql .= " AND (sd.reference_id = ? OR sd.allocation_id = ?)";
            $params[] = (int)$filters['reference_id'];
            $params[] = (int)$filters['reference_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (sd.damage_number LIKE ? OR pv.sku LIKE ? OR p.name LIKE ? OR sd.damage_reason LIKE ? OR sd.tag_code LIKE ? OR wa.allocation_number LIKE ? OR ao.outward_number LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY sd.id DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $records = Database::fetchAll($sql, $params);
        foreach ($records as &$r) {
            $r['piece_tags'] = self::resolvePieceTags($r);
            $r['tag_code'] = implode(', ', $r['piece_tags']);
        }

        return $records;
    }

    /**
     * Get all damage cases for a specific allocation.
     */
    public static function getByAllocationId(int $allocationId): array {
        return self::getAll(['reference_id' => $allocationId]);
    }

    /**
     * Generate unique damage case number for shop (e.g. SH-DMG-20261026-0001).
     */
    public static function generateDamageNumber(string $prefix = 'SH-DMG'): string {
        $today = date('Ymd');
        $fullPrefix = $prefix . '-' . $today . '-';
        $last = Database::fetchOne(
            "SELECT damage_number FROM shop_damages WHERE damage_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$fullPrefix . '%']
        );
        $seq = 1;
        if ($last && !empty($last['damage_number'])) {
            $parts = explode('-', $last['damage_number']);
            $seq = (int)end($parts) + 1;
        }

        do {
            $num = sprintf('%s%04d', $fullPrefix, $seq);
            $exists = Database::fetchOne("SELECT id FROM shop_damages WHERE damage_number = ? LIMIT 1", [$num]);
            if (!$exists) {
                return $num;
            }
            $seq++;
        } while ($seq < 9999);

        return sprintf('%s%04d', $fullPrefix, $seq);
    }

    /**
     * Create shop damage entry record.
     */
    public static function create(array $data): int {
        if (empty($data['damage_number'])) {
            $data['damage_number'] = self::generateDamageNumber();
        }

        Database::execute(
            "INSERT INTO shop_damages 
             (damage_number, shop_id, variant_id, tag_code, source_type, reference_id, allocation_id, defect_type, quantity, cost_per_unit, estimated_cost, total_loss, damage_reason, defect_notes, photo_url, status, reported_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['damage_number'],
                (int)$data['shop_id'],
                (int)$data['variant_id'],
                !empty($data['tag_code']) ? trim($data['tag_code']) : null,
                $data['source_type'] ?? 'SHOP_BIN_HANDLING',
                !empty($data['reference_id']) ? (int)$data['reference_id'] : null,
                !empty($data['allocation_id']) ? (int)$data['allocation_id'] : (!empty($data['reference_id']) && ($data['source_type'] ?? '') === 'INWARD_TRANSIT' ? (int)$data['reference_id'] : null),
                $data['defect_type'] ?? 'OTHER',
                max(1, (int)($data['quantity'] ?? 1)),
                (float)($data['cost_per_unit'] ?? 0.00),
                (float)($data['estimated_cost'] ?? 0.00),
                (float)($data['total_loss'] ?? 0.00),
                $data['damage_reason'] ?? 'Damaged / Defect',
                $data['defect_notes'] ?? null,
                $data['photo_url'] ?? null,
                $data['status'] ?? 'LOGGED',
                !empty($data['reported_by']) ? (int)$data['reported_by'] : null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Update damage record.
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = [];

        $allowed = ['status', 'tag_code', 'defect_notes', 'damage_reason', 'quantity', 'cost_per_unit', 'estimated_cost', 'total_loss', 'photo_url', 'disposition_notes', 'inspected_by', 'defect_type'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`$field` = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE shop_damages SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params) >= 0;
    }

    /**
     * Get KPI Summary Statistics for Shop Damage Module.
     */
    public static function getSummaryStats(?int $shopId = null): array {
        $sql = "SELECT 
                COUNT(*) as total_records,
                COALESCE(SUM(quantity), 0) as total_units,
                COALESCE(SUM(total_loss), 0.00) as total_valuation_loss,
                SUM(CASE WHEN status = 'LOGGED' THEN quantity ELSE 0 END) as pending_inspection_units,
                SUM(CASE WHEN status = 'INSPECTED' THEN quantity ELSE 0 END) as inspected_units,
                SUM(CASE WHEN status = 'SALVAGED' THEN quantity ELSE 0 END) as salvaged_units,
                SUM(CASE WHEN status = 'WRITTEN_OFF' THEN quantity ELSE 0 END) as written_off_units,
                SUM(CASE WHEN status = 'REPAIRED' THEN quantity ELSE 0 END) as repaired_units,
                SUM(CASE WHEN status = 'RETURN_TO_WH' THEN quantity ELSE 0 END) as return_to_wh_units,
                SUM(CASE WHEN status = 'REWORK' THEN quantity ELSE 0 END) as rework_units,
                SUM(CASE WHEN source_type = 'INWARD_TRANSIT' THEN quantity ELSE 0 END) as inward_damage_units,
                SUM(CASE WHEN source_type = 'SHOP_BIN_HANDLING' THEN quantity ELSE 0 END) as bin_damage_units
             FROM shop_damages";
        
        $params = [];
        if ($shopId) {
            $sql .= " WHERE shop_id = ?";
            $params[] = $shopId;
        }

        $stats = Database::fetchOne($sql, $params);

        return [
            'total_records'          => (int)($stats['total_records'] ?? 0),
            'total_units'            => (int)($stats['total_units'] ?? 0),
            'total_valuation_loss'   => (float)($stats['total_valuation_loss'] ?? 0.00),
            'pending_inspection'     => (int)($stats['pending_inspection_units'] ?? 0),
            'inspected'              => (int)($stats['inspected_units'] ?? 0),
            'salvaged'               => (int)($stats['salvaged_units'] ?? 0),
            'written_off'            => (int)($stats['written_off_units'] ?? 0),
            'repaired'               => (int)($stats['repaired_units'] ?? 0),
            'return_to_wh'           => (int)($stats['return_to_wh_units'] ?? 0),
            'rework_units'           => (int)($stats['rework_units'] ?? 0),
            'inward_damage_units'    => (int)($stats['inward_damage_units'] ?? 0),
            'bin_damage_units'       => (int)($stats['bin_damage_units'] ?? 0),
        ];
    }
}
