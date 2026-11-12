<?php

define('BASE_PATH', dirname(__DIR__));

final class TestPhase3StopException extends RuntimeException
{
    public function __construct(public readonly int $statusCode, string $message)
    {
        parent::__construct($message);
    }
}

final class Session
{
    private static array $data = [];
    private static array $flash = [];

    public static function reset(): void
    {
        self::$data = [];
        self::$flash = [];
    }

    public static function user(): ?array
    {
        return self::$data['user'] ?? null;
    }

    public static function userId(): ?int
    {
        return isset(self::$data['user']['id']) ? (int)self::$data['user']['id'] : null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$data[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$data[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$data);
    }

    public static function remove(string $key): void
    {
        unset(self::$data[$key]);
    }

    public static function flash(string $type, string $message): void
    {
        self::$flash[$type][] = $message;
    }

    public static function getFlash(string $type): array
    {
        $msgs = self::$flash[$type] ?? [];
        unset(self::$flash[$type]);
        return $msgs;
    }
}

final class CSRF
{
    public static bool $valid = true;

    public static function validateRequest(): bool
    {
        return self::$valid;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="test">';
    }
}

final class Concern
{
    public static array $concerns = [
        1 => ['id' => 1, 'name' => 'MAIN FACTORY', 'module_keys' => ['production', 'sellers', 'damages', 'catalog', 'programs']],
        2 => ['id' => 2, 'name' => 'CONCERN TWO', 'module_keys' => ['production', 'sellers', 'damages', 'catalog', 'programs']],
    ];

    public static function getActiveForCurrentUser(): ?array
    {
        $id = (int)Session::get('active_concern_id', 0);
        return self::$concerns[$id] ?? null;
    }

    public static function getAccessibleToUser(int $userId, bool $isSuperAdmin): array
    {
        return array_values(self::$concerns);
    }

    public static function hasFacilityModules(int $concernId, string $facilityType): bool
    {
        return strtoupper($facilityType) === 'PRODUCTION';
    }
}

final class User
{
    public static array $permissionsByUserId = [];

    public static function getPermissions(int $userId): array
    {
        return self::$permissionsByUserId[$userId] ?? [];
    }
}

final class Validator
{
    public function __construct(private array $errors = []) {}

    public static function make(array $data, array $rules): self
    {
        foreach ($rules as $field => $ruleStr) {
            if (str_contains($ruleStr, 'required') && trim((string)($data[$field] ?? '')) === '') {
                return new self(["{$field} is required."]);
            }
        }
        return new self([]);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function firstError(): string
    {
        return $this->errors[0] ?? 'Validation failed.';
    }
}

final class Response
{
    public static ?array $lastView = null;
    public static ?array $lastJson = null;
    public static int $lastJsonStatus = 200;
    public static ?string $lastRedirect = null;
    public static ?array $lastError = null;

    public static function reset(): void
    {
        self::$lastView = null;
        self::$lastJson = null;
        self::$lastJsonStatus = 200;
        self::$lastRedirect = null;
        self::$lastError = null;
    }

    public static function view(string $view, array $data = []): void
    {
        self::$lastView = ['view' => $view, 'data' => $data];
    }

    public static function viewOnly(string $view, array $data = []): void
    {
        self::$lastView = ['view' => $view, 'data' => $data];
    }

    public static function json(mixed $data, int $status = 200): void
    {
        self::$lastJson = is_array($data) ? $data : ['value' => $data];
        self::$lastJsonStatus = $status;
    }

    public static function redirect(string $url): void
    {
        self::$lastRedirect = $url;
    }

    public static function back(): void
    {
        self::$lastRedirect = 'BACK';
    }

    public static function error(int $code, string $message = ''): void
    {
        self::$lastError = ['code' => $code, 'message' => $message];
        throw new TestPhase3StopException($code, $message);
    }
}

final class Phase3Db
{
    public static int $nextId = 1000;

    public static array $units = [
        10 => ['id' => 10, 'concern_id' => 1, 'name' => 'Unit 1', 'code' => 'U1', 'is_active' => 1],
        20 => ['id' => 20, 'concern_id' => 2, 'name' => 'Unit 2', 'code' => 'U2', 'is_active' => 1],
    ];

    public static array $sellers = [
        11 => ['id' => 11, 'concern_id' => 1, 'name' => 'MILL ONE', 'company' => 'MILL ONE TEXTILES', 'phone' => '9000000001', 'email' => null, 'gst_number' => null, 'city' => 'ERODE', 'state' => 'TAMIL NADU', 'address' => 'ADDR 1', 'payment_terms' => 'NET 30', 'seller_type' => 'MATERIAL_SUPPLIER', 'roll_count' => 1, 'roll_weight' => 30.0, 'roll_price' => 3000.0, 'is_active' => 1, 'created_at' => '2026-11-01 10:00:00'],
        21 => ['id' => 21, 'concern_id' => 2, 'name' => 'MILL TWO', 'company' => 'MILL TWO FABRICS', 'phone' => '9000000002', 'email' => null, 'gst_number' => null, 'city' => 'SALEM', 'state' => 'TAMIL NADU', 'address' => 'ADDR 2', 'payment_terms' => 'CASH', 'seller_type' => 'MATERIAL_SUPPLIER', 'roll_count' => 1, 'roll_weight' => 20.0, 'roll_price' => 2000.0, 'is_active' => 1, 'created_at' => '2026-11-01 11:00:00'],
    ];

    public static array $rolls = [
        101 => ['id' => 101, 'concern_id' => 1, 'seller_id' => 11, 'roll_number' => 'R-101', 'color' => 'RED', 'design_name' => 'FLORAL', 'design_count' => 1, 'quantity' => 3, 'weight' => 30.0, 'price' => 3000.0, 'material_type' => 'RAYON', 'photo_url' => null, 'start_seq' => 1, 'end_seq' => 3, 'start_code' => 'FB112601', 'end_code' => 'FB112603', 'qr_code' => 'FB112601-FB112603', 'created_at' => '2026-11-01 10:00:00'],
        201 => ['id' => 201, 'concern_id' => 2, 'seller_id' => 21, 'roll_number' => 'R-201', 'color' => 'BLUE', 'design_name' => 'PLAIN', 'design_count' => 1, 'quantity' => 2, 'weight' => 20.0, 'price' => 2000.0, 'material_type' => 'COTTON', 'photo_url' => null, 'start_seq' => 4, 'end_seq' => 5, 'start_code' => 'FB112604', 'end_code' => 'FB112605', 'qr_code' => 'FB112604-FB112605', 'created_at' => '2026-11-01 11:00:00'],
    ];

    public static array $rollDamages = [
        301 => ['id' => 301, 'concern_id' => 1, 'damage_number' => 'DMG-ROL-2026-000301-ID301', 'seller_id' => 11, 'seller_roll_id' => 101, 'qr_code' => 'FB112601', 'damaged_quantity' => 1, 'damaged_weight' => 10.0, 'loss_amount' => 1000.0, 'damage_reason' => 'Stain', 'defect_notes' => 'Oil spot', 'photo_url' => 'https://example.com/d1.jpg', 'status' => 'LOGGED', 'resolution_type' => 'NONE', 'refund_amount' => 0.0, 'debit_note_number' => null, 'replacement_roll_number' => null, 'replacement_roll_id' => null, 'reported_by' => 1, 'resolved_by' => null, 'created_at' => '2026-11-02 10:00:00'],
        302 => ['id' => 302, 'concern_id' => 2, 'damage_number' => 'DMG-ROL-2026-000302-ID302', 'seller_id' => 21, 'seller_roll_id' => 201, 'qr_code' => 'FB112604', 'damaged_quantity' => 1, 'damaged_weight' => 10.0, 'loss_amount' => 1000.0, 'damage_reason' => 'Tear', 'defect_notes' => 'Edge cut', 'photo_url' => 'https://example.com/d2.jpg', 'status' => 'LOGGED', 'resolution_type' => 'NONE', 'refund_amount' => 0.0, 'debit_note_number' => null, 'replacement_roll_number' => null, 'replacement_roll_id' => null, 'reported_by' => 2, 'resolved_by' => null, 'created_at' => '2026-11-02 11:00:00'],
    ];

    public static array $purchases = [];
    public static array $purchaseItems = [];

    public static array $products = [
        401 => ['id' => 401, 'concern_id' => 1, 'name' => 'NIGHTY - COTTON - A-LINE', 'code' => 'GRM-STY-0001', 'product_code' => null, 'category_id' => 1, 'subcategory_id' => 10, 'sub_subcategory_id' => 100, 'category_code' => 'N', 'subcategory_code' => 'C', 'sub_subcategory_code' => 'A', 'qr_code_prefix' => 'NCA', 'brand_id' => null, 'source_type' => 'OWN_PRODUCTION', 'material' => 'COTTON', 'description' => '', 'hsn_code' => '6208', 'gst_percentage' => 5.0, 'min_stock' => 10, 'max_stock' => 1000, 'image_url' => null, 'cloudinary_public_id' => null, 'is_active' => 1, 'created_at' => '2026-11-01 09:00:00'],
        402 => ['id' => 402, 'concern_id' => 1, 'name' => 'OLD REMOVED STYLE', 'code' => 'GRM-STY-0002_DEL_1700000000', 'product_code' => null, 'category_id' => 1, 'subcategory_id' => 10, 'sub_subcategory_id' => null, 'category_code' => 'N', 'subcategory_code' => 'C', 'sub_subcategory_code' => '', 'qr_code_prefix' => 'NC', 'brand_id' => null, 'source_type' => 'OWN_PRODUCTION', 'material' => 'COTTON', 'description' => '', 'hsn_code' => '6208', 'gst_percentage' => 5.0, 'min_stock' => 10, 'max_stock' => 1000, 'image_url' => null, 'cloudinary_public_id' => null, 'is_active' => 0, 'created_at' => '2026-11-01 09:30:00'],
    ];

    public static array $variants = [
        501 => ['id' => 501, 'concern_id' => 1, 'product_id' => 401, 'size_id' => 1, 'color_id' => 1, 'sku' => 'GRM-STY-0001-L-RED', 'qr_code' => null, 'mrp' => 599.0, 'purchase_price' => 250.0, 'production_cost' => 250.0, 'selling_price' => 499.0, 'is_active' => 1],
    ];

    public static array $programs = [
        601 => ['id' => 601, 'concern_id' => 1, 'production_unit_id' => 10, 'product_id' => 401, 'program_number' => 'PRG-1', 'name' => 'C1 Program', 'seller_id' => 11, 'seller_roll_id' => 101, 'status' => 'CUTTING', 'is_active' => 1, 'category_id' => 1, 'subcategory_id' => 10, 'sub_subcategory_id' => 100, 'created_at' => '2026-11-03 10:00:00'],
    ];
    public static array $programRolls = [];
    public static array $categories = [
        1 => ['id' => 1, 'name' => 'NIGHTY', 'slug' => 'nighty', 'code' => 'N', 'is_active' => 1, 'sort_order' => 1],
        2 => ['id' => 2, 'name' => 'KURTI', 'slug' => 'kurti', 'code' => 'K', 'is_active' => 1, 'sort_order' => 2],
    ];
    public static array $subcategories = [
        10 => ['id' => 10, 'category_id' => 1, 'name' => 'COTTON', 'slug' => 'cotton', 'code' => 'C', 'is_active' => 1, 'sort_order' => 1],
        20 => ['id' => 20, 'category_id' => 2, 'name' => 'RAYON', 'slug' => 'rayon', 'code' => 'R', 'is_active' => 1, 'sort_order' => 1],
    ];
    public static array $subSubcategories = [
        100 => ['id' => 100, 'subcategory_id' => 10, 'name' => 'A-LINE', 'slug' => 'a-line', 'code' => 'A', 'is_active' => 1],
        200 => ['id' => 200, 'subcategory_id' => 20, 'name' => 'STRAIGHT', 'slug' => 'straight', 'code' => 'S', 'is_active' => 1],
    ];
    public static array $sizes = [
        1 => ['id' => 1, 'name' => 'L', 'code' => 'L', 'is_active' => 1, 'sort_order' => 1],
        2 => ['id' => 2, 'name' => 'XL', 'code' => 'XL', 'is_active' => 1, 'sort_order' => 2],
    ];
    public static array $colors = [
        1 => ['id' => 1, 'name' => 'RED', 'hex_code' => '#ff0000', 'is_active' => 1],
        2 => ['id' => 2, 'name' => 'BLUE', 'hex_code' => '#0000ff', 'is_active' => 1],
    ];
}

final class Database
{
    private static int $lastInsertId = 0;

    public static function beginTransaction(): bool { return true; }
    public static function commit(): bool { return true; }
    public static function rollBack(): bool { return true; }
    public static function inTransaction(): bool { return false; }
    public static function lastInsertId(): string { return (string)self::$lastInsertId; }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        // Production units lookup
        if (str_contains($sql, 'SELECT concern_id FROM production_units WHERE id = ?')) {
            $u = Phase3Db::$units[(int)$params[0]] ?? null;
            return $u ? ['concern_id' => $u['concern_id']] : null;
        }
        if (str_contains($sql, 'FROM production_units WHERE id = ? AND concern_id = ?')) {
            $u = Phase3Db::$units[(int)$params[0]] ?? null;
            return ($u && (int)$u['concern_id'] === (int)$params[1]) ? $u : null;
        }

        // Sellers lookup
        if (str_contains($sql, 'FROM sellers WHERE id = ?') && !str_contains($sql, 'seller_rolls')) {
            $id = (int)$params[0];
            $row = Phase3Db::$sellers[$id] ?? null;
            if (!$row) {
                return null;
            }
            if (str_contains($sql, "seller_type = 'MATERIAL_SUPPLIER'") && $row['seller_type'] !== 'MATERIAL_SUPPLIER') {
                return null;
            }
            if (str_contains($sql, 'is_active = 1') && (int)$row['is_active'] !== 1) {
                return null;
            }
            if (str_contains($sql, 'concern_id = ?')) {
                $concernParam = str_contains($sql, 'is_active = 1') ? (int)$params[1] : (int)$params[1];
                if ((int)$row['concern_id'] !== $concernParam) {
                    return null;
                }
            }
            return $row;
        }

        // Purchase dependency check for seller / roll deletion
        if (str_contains($sql, 'FROM production_purchase_items ppi') && str_contains($sql, 'ppi.seller_roll_id = ? AND pp.concern_id = ?')) {
            return null;
        }
        if (str_contains($sql, 'COUNT(*) AS purchase_count FROM production_purchases WHERE seller_id = ? AND concern_id = ?')) {
            return ['purchase_count' => 0];
        }

        // Seller rolls single lookup
        if (str_contains($sql, 'FROM seller_rolls sr') && str_contains($sql, 'sr.id = ? AND sr.concern_id = ?') && str_contains($sql, 'dmg.damaged_quantity')) {
            $concernId = (int)$params[0];
            $rollId = (int)$params[1];
            $roll = Phase3Db::$rolls[$rollId] ?? null;
            if (!$roll || (int)$roll['concern_id'] !== $concernId) {
                return null;
            }
            $dmgQty = 0;
            foreach (Phase3Db::$rollDamages as $d) {
                if ((int)$d['concern_id'] === $concernId && (int)$d['seller_roll_id'] === $rollId && !in_array($d['status'], ['CLOSED', 'CANCELLED'], true)) {
                    $dmgQty += (int)$d['damaged_quantity'];
                }
            }
            return ['roll_number' => $roll['roll_number'], 'available_quantity' => max(0, (int)$roll['quantity'] - $dmgQty)];
        }
        if (str_contains($sql, 'FROM seller_rolls sr') && str_contains($sql, 'WHERE sr.id = ? AND sr.seller_id = ? AND sr.concern_id = ?')) {
            $roll = Phase3Db::$rolls[(int)$params[0]] ?? null;
            if (!$roll || (int)$roll['seller_id'] !== (int)$params[1] || (int)$roll['concern_id'] !== (int)$params[2]) {
                return null;
            }
            $seller = Phase3Db::$sellers[(int)$roll['seller_id']] ?? null;
            if (!$seller || (int)$seller['concern_id'] !== (int)$params[3]) {
                return null;
            }
            return $roll;
        }
        if (str_contains($sql, 'FROM seller_rolls WHERE id = ?')) {
            $roll = Phase3Db::$rolls[(int)$params[0]] ?? null;
            if (!$roll) {
                return null;
            }
            if (str_contains($sql, 'AND concern_id = ?') && (int)$roll['concern_id'] !== (int)$params[1]) {
                return null;
            }
            return $roll;
        }

        if (str_contains($sql, 'FROM seller_roll_deleted_labels')) {
            return null;
        }

        // Roll damages single lookups
        if (str_contains($sql, 'FROM roll_damages WHERE qr_code = ? AND concern_id = ?')) {
            $qr = strtoupper(trim((string)$params[0]));
            $cid = (int)$params[1];
            foreach (Phase3Db::$rollDamages as $d) {
                if ((int)$d['concern_id'] === $cid && strtoupper((string)$d['qr_code']) === $qr) {
                    return $d;
                }
            }
            return null;
        }
        if (str_contains($sql, 'FROM roll_damages WHERE seller_id = ? AND concern_id = ?')) {
            foreach (Phase3Db::$rollDamages as $d) {
                if ((int)$d['seller_id'] === (int)$params[0] && (int)$d['concern_id'] === (int)$params[1]) {
                    return $d;
                }
            }
            return null;
        }
        if (str_contains($sql, 'FROM roll_damages WHERE seller_roll_id = ? AND concern_id = ?') && str_contains($sql, 'dmg_qty')) {
            $rollId = (int)$params[0];
            $cid = (int)$params[1];
            $excl = (int)$params[2];
            $qty = 0;
            $wt = 0.0;
            foreach (Phase3Db::$rollDamages as $d) {
                if ((int)$d['seller_roll_id'] === $rollId && (int)$d['concern_id'] === $cid && (int)$d['id'] !== $excl && !in_array($d['status'], ['CLOSED', 'CANCELLED'], true)) {
                    $qty += (int)$d['damaged_quantity'];
                    $wt += (float)$d['damaged_weight'];
                }
            }
            return ['dmg_qty' => $qty, 'dmg_weight' => $wt];
        }
        if (str_contains($sql, 'FROM roll_damages rd') && str_contains($sql, 'WHERE rd.id = ? AND rd.concern_id = ?')) {
            $d = Phase3Db::$rollDamages[(int)$params[0]] ?? null;
            if (!$d || (int)$d['concern_id'] !== (int)$params[1]) {
                return null;
            }
            $s = Phase3Db::$sellers[(int)$d['seller_id']] ?? null;
            $r = Phase3Db::$rolls[(int)$d['seller_roll_id']] ?? null;
            if (!$s || (int)$s['concern_id'] !== (int)$d['concern_id'] || !$r || (int)$r['concern_id'] !== (int)$d['concern_id']) {
                return null;
            }
            $d['seller_name'] = $s['name'];
            $d['seller_company'] = $s['company'];
            $d['roll_number'] = $r['roll_number'];
            $d['roll_color'] = $r['color'];
            $d['roll_design'] = $r['design_name'];
            return $d;
        }
        if (str_contains($sql, 'FROM roll_damages WHERE id = ? AND concern_id = ?')) {
            $d = Phase3Db::$rollDamages[(int)$params[0]] ?? null;
            return ($d && (int)$d['concern_id'] === (int)$params[1]) ? $d : null;
        }

        // Next fabric roll sequence
        if (str_contains($sql, 'SELECT MAX(end_seq) as max_seq FROM seller_rolls')) {
            $max = 0;
            foreach (Phase3Db::$rolls as $r) {
                if ((int)$r['end_seq'] > $max) {
                    $max = (int)$r['end_seq'];
                }
            }
            return ['max_seq' => $max];
        }

        // Catalog lookups
        if (str_contains($sql, 'FROM products WHERE concern_id = ? AND code = ?')) {
            $cid = (int)$params[0];
            $code = strtoupper(trim((string)$params[1]));
            $excl = $params[2] ?? null;
            foreach (Phase3Db::$products as $p) {
                if ((int)$p['concern_id'] === $cid && strtoupper((string)$p['code']) === $code && ($excl === null || (int)$p['id'] !== (int)$excl)) {
                    return $p;
                }
            }
            return null;
        }
        if (str_contains($sql, 'FROM products') && str_contains($sql, 'qr_code_prefix = ? AND ') && str_contains($sql, 'concern_id = ?')) {
            $qr = strtoupper(trim((string)$params[0]));
            $cid = (int)$params[1];
            $hasExcl = str_contains($sql, 'id != ?');
            $excl = $hasExcl ? (int)$params[2] : null;
            foreach (Phase3Db::$products as $p) {
                if ((int)$p['concern_id'] === $cid && strtoupper((string)$p['qr_code_prefix']) === $qr && (int)$p['is_active'] === 1 && ($excl === null || (int)$p['id'] !== $excl)) {
                    $p['category_name'] = Phase3Db::$categories[(int)$p['category_id']]['name'] ?? '';
                    $p['subcategory_name'] = Phase3Db::$subcategories[(int)($p['subcategory_id'] ?? 0)]['name'] ?? '';
                    $p['sub_subcategory_name'] = Phase3Db::$subSubcategories[(int)($p['sub_subcategory_id'] ?? 0)]['name'] ?? '';
                    return $p;
                }
            }
            return null;
        }
        if (str_contains($sql, 'FROM products p') && str_contains($sql, 'WHERE p.id = ? AND p.concern_id = ?')) {
            $p = Phase3Db::$products[(int)$params[0]] ?? null;
            if (!$p || (int)$p['concern_id'] !== (int)$params[1]) {
                return null;
            }
            $p['category_name'] = Phase3Db::$categories[(int)$p['category_id']]['name'] ?? '';
            $p['subcategory_name'] = Phase3Db::$subcategories[(int)($p['subcategory_id'] ?? 0)]['name'] ?? '';
            $p['sub_subcategory_name'] = Phase3Db::$subSubcategories[(int)($p['sub_subcategory_id'] ?? 0)]['name'] ?? '';
            $p['brand_name'] = null;
            return $p;
        }
        if (str_contains($sql, 'SELECT id, name FROM products WHERE id = ? AND concern_id = ? AND is_active = 1')) {
            $p = Phase3Db::$products[(int)$params[0]] ?? null;
            return ($p && (int)$p['concern_id'] === (int)$params[1] && (int)$p['is_active'] === 1) ? ['id' => $p['id'], 'name' => $p['name']] : null;
        }
        if (str_contains($sql, 'FROM products WHERE id = ?')) {
            $p = Phase3Db::$products[(int)$params[0]] ?? null;
            if (!$p) {
                return null;
            }
            if (str_contains($sql, 'AND concern_id = ?') && (int)$p['concern_id'] !== (int)$params[1]) {
                return null;
            }
            return $p;
        }
        if (str_contains($sql, 'FROM products WHERE concern_id = ?') && str_contains($sql, 'ORDER BY id DESC LIMIT 1')) {
            $cid = (int)$params[0];
            $val = $params[1];
            foreach (array_reverse(Phase3Db::$products, true) as $p) {
                if ((int)$p['concern_id'] !== $cid || (int)$p['is_active'] !== 1) {
                    continue;
                }
                if (str_contains($sql, 'sub_subcategory_id = ?') && (int)$p['sub_subcategory_id'] === (int)$val) return $p;
                if (str_contains($sql, 'subcategory_id = ?') && (int)$p['subcategory_id'] === (int)$val) return $p;
                if (str_contains($sql, 'category_id = ?') && (int)$p['category_id'] === (int)$val) return $p;
                if (str_contains($sql, 'name = ?') && $p['name'] === $val) return $p;
            }
            return null;
        }

        // Taxonomy & attributes lookups
        if (str_contains($sql, 'FROM categories WHERE id = ?')) return Phase3Db::$categories[(int)$params[0]] ?? null;
        if (str_contains($sql, 'FROM subcategories WHERE id = ?')) return Phase3Db::$subcategories[(int)$params[0]] ?? null;
        if (str_contains($sql, 'FROM sub_subcategories WHERE id = ?')) return Phase3Db::$subSubcategories[(int)$params[0]] ?? null;
        if (str_contains($sql, 'FROM sizes WHERE id = ?')) return Phase3Db::$sizes[(int)$params[0]] ?? null;
        if (str_contains($sql, 'FROM sizes WHERE name = ? OR code = ?')) {
            foreach (Phase3Db::$sizes as $s) {
                if ($s['name'] === $params[0] || $s['code'] === $params[1]) return $s;
            }
            return null;
        }
        if (str_contains($sql, 'FROM colors WHERE id = ?')) return Phase3Db::$colors[(int)$params[0]] ?? null;
        if (str_contains($sql, 'FROM colors WHERE name = ?')) {
            foreach (Phase3Db::$colors as $c) {
                if (strcasecmp($c['name'], (string)$params[0]) === 0) return $c;
            }
            return null;
        }
        if (str_contains($sql, 'FROM production_programs WHERE id = ?')) {
            return Phase3Db::$programs[(int)$params[0]] ?? null;
        }
        if (str_contains($sql, 'FROM product_variants WHERE concern_id = ? AND sku = ?')) {
            foreach (Phase3Db::$variants as $v) {
                if ((int)$v['concern_id'] === (int)$params[0] && $v['sku'] === $params[1]) return $v;
            }
            return null;
        }
        if (str_contains($sql, 'FROM product_variants WHERE product_id = ?')) {
            foreach (Phase3Db::$variants as $v) {
                if ((int)$v['product_id'] === (int)$params[0] && (int)$v['is_active'] === 1) {
                    return $v;
                }
            }
            return null;
        }
        if (str_contains($sql, 'SELECT COUNT(*) as c FROM production_programs')) {
            return ['c' => count(Phase3Db::$programs)];
        }
        return null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        // Sellers list & dropdowns
        if (str_contains($sql, 'FROM sellers') && str_contains($sql, 'concern_id = ?')) {
            $cid = (int)$params[0];
            $rows = array_values(array_filter(Phase3Db::$sellers, static fn(array $s): bool => (int)$s['concern_id'] === $cid && $s['seller_type'] === 'MATERIAL_SUPPLIER' && (!str_contains($sql, 'is_active = 1') || (int)$s['is_active'] === 1)));
            if (str_contains($sql, 'SELECT DISTINCT city')) {
                $cities = array_values(array_unique(array_filter(array_column($rows, 'city'))));
                return array_map(static fn(string $c): array => ['city' => $c], $cities);
            }
            return $rows;
        }

        // Seller rolls lookup for damage scan
        if (str_contains($sql, 'FROM seller_rolls sr') && str_contains($sql, 'sr.start_code LIKE ? AND sr.end_code LIKE ?')) {
            $cid = (int)$params[0];
            $sellerCid = (int)$params[1];
            $prefix = rtrim((string)$params[2], '%');
            $out = [];
            foreach (Phase3Db::$rolls as $r) {
                if ((int)$r['concern_id'] !== $cid) continue;
                $s = Phase3Db::$sellers[(int)$r['seller_id']] ?? null;
                if (!$s || (int)$s['concern_id'] !== $sellerCid) continue;
                if (!str_starts_with((string)$r['start_code'], $prefix)) continue;
                $r['seller_name'] = $s['name'];
                $r['seller_company'] = $s['company'];
                $out[] = $r;
            }
            return $out;
        }

        // AJAX seller rolls
        if (str_contains($sql, 'FROM seller_rolls sr') && str_contains($sql, 'WHERE sr.seller_id = ? AND sr.concern_id = ?')) {
            $progCid = (int)$params[0];
            $dmgCid = (int)$params[1];
            $exclDmg = (int)$params[2];
            $sellerId = (int)$params[4];
            $rollCid = (int)$params[5];
            $out = [];
            foreach (Phase3Db::$rolls as $r) {
                if ((int)$r['seller_id'] !== $sellerId || (int)$r['concern_id'] !== $rollCid) continue;
                $dmgQty = 0;
                foreach (Phase3Db::$rollDamages as $d) {
                    if ((int)$d['concern_id'] === $dmgCid && (int)$d['seller_roll_id'] === (int)$r['id'] && (int)$d['id'] !== $exclDmg && !in_array($d['status'], ['CLOSED', 'CANCELLED'], true)) {
                        $dmgQty += (int)$d['damaged_quantity'];
                    }
                }
                $r['used_quantity'] = 0;
                $r['used_weight'] = 0.0;
                $r['damaged_quantity'] = $dmgQty;
                $r['damaged_weight'] = 0.0;
                $r['available_quantity'] = max(0, (int)$r['quantity'] - $dmgQty);
                $r['available_weight'] = (float)$r['weight'];
                $out[] = $r;
            }
            return $out;
        }

        // Seller rolls list / totals
        if (str_contains($sql, 'FROM seller_rolls') && str_contains($sql, 'seller_id = ? AND concern_id = ?')) {
            $sellerId = (int)$params[0];
            $cid = (int)$params[1];
            return array_values(array_filter(Phase3Db::$rolls, static fn(array $r): bool => (int)$r['seller_id'] === $sellerId && (int)$r['concern_id'] === $cid));
        }

        // Roll damages list & stats & reason options
        if (str_contains($sql, 'FROM roll_damage_reasons')) {
            $cid = (int)$params[0];
            $out = [];
            foreach (Phase3Db::$rollDamages as $d) {
                if ((int)$d['concern_id'] === $cid && !empty($d['damage_reason'])) {
                    $out[] = ['damage_reason' => $d['damage_reason']];
                }
            }
            return $out;
        }
        if (str_contains($sql, 'FROM roll_damages rd') && str_contains($sql, 'rd.concern_id = ?')) {
            $cid = (int)$params[0];
            $out = [];
            foreach (Phase3Db::$rollDamages as $d) {
                if ((int)$d['concern_id'] !== $cid) continue;
                $s = Phase3Db::$sellers[(int)$d['seller_id']] ?? null;
                $r = Phase3Db::$rolls[(int)$d['seller_roll_id']] ?? null;
                if (str_contains($sql, 'JOIN sellers s') && (!$s || (int)$s['concern_id'] !== $cid || !$r || (int)$r['concern_id'] !== $cid)) continue;
                $d['seller_name'] = $s['name'] ?? '';
                $d['seller_company'] = $s['company'] ?? '';
                $d['roll_number'] = $r['roll_number'] ?? '';
                $d['roll_color'] = $r['color'] ?? '';
                $d['roll_design'] = $r['design_name'] ?? '';
                $out[] = $d;
            }
            return $out;
        }
        if (str_contains($sql, 'SELECT qr_code FROM roll_damages')) {
            $rollId = (int)$params[0];
            $cid = (int)$params[1];
            $out = [];
            foreach (Phase3Db::$rollDamages as $d) {
                if ((int)$d['seller_roll_id'] === $rollId && (int)$d['concern_id'] === $cid && !empty($d['qr_code'])) {
                    $out[] = ['qr_code' => $d['qr_code']];
                }
            }
            return $out;
        }

        // Catalog code generator rows & catalog list
        if (str_contains($sql, "SELECT code FROM products WHERE concern_id = ? AND code LIKE 'GRM-STY-%'")) {
            $cid = (int)$params[0];
            $out = [];
            foreach (Phase3Db::$products as $p) {
                if ((int)$p['concern_id'] === $cid && str_starts_with(strtoupper((string)$p['code']), 'GRM-STY-')) {
                    $out[] = ['code' => $p['code']];
                }
            }
            return $out;
        }
        if (str_contains($sql, 'FROM products p') && str_contains($sql, 'p.concern_id = ?')) {
            if (str_contains($sql, 'pv_summary')) {
                $pvCid = (int)$params[0];
                $pCid = (int)$params[1];
                $out = [];
                foreach (Phase3Db::$products as $p) {
                    if ((int)$p['concern_id'] !== $pCid || (int)$p['is_active'] !== 1) continue;
                    $cnt = 0;
                    foreach (Phase3Db::$variants as $v) {
                        if ((int)$v['product_id'] === (int)$p['id'] && (int)$v['concern_id'] === $pvCid && (int)$v['is_active'] === 1) {
                            $cnt++;
                        }
                    }
                    $p['variant_count'] = $cnt;
                    $p['category_name'] = Phase3Db::$categories[(int)$p['category_id']]['name'] ?? '';
                    $p['subcategory_name'] = Phase3Db::$subcategories[(int)($p['subcategory_id'] ?? 0)]['name'] ?? '';
                    $p['sub_subcategory_name'] = Phase3Db::$subSubcategories[(int)($p['sub_subcategory_id'] ?? 0)]['name'] ?? '';
                    $out[] = $p;
                }
                return $out;
            }
            // searchProducts()
            $cid = (int)$params[0];
            $term = trim((string)($params[1] ?? ''), '%');
            $out = [];
            foreach (Phase3Db::$products as $p) {
                if ((int)$p['concern_id'] !== $cid || (int)$p['is_active'] !== 1) continue;
                if ($term === '' || stripos($p['name'], $term) !== false || stripos($p['code'], $term) !== false) {
                    $out[] = $p;
                }
            }
            return $out;
        }

        // searchVariants() & product variants queries
        if (str_contains($sql, 'FROM product_variants pv') && str_contains($sql, 'pv.concern_id = ?')) {
            if (str_contains($sql, 'p.concern_id = ? AND pv.concern_id = ?')) {
                $pCid = (int)$params[0];
                $pvCid = (int)$params[1];
                $term = trim((string)($params[2] ?? ''), '%');
                $out = [];
                foreach (Phase3Db::$variants as $v) {
                    $p = Phase3Db::$products[(int)$v['product_id']] ?? null;
                    if (!$p || (int)$p['concern_id'] !== $pCid || (int)$v['concern_id'] !== $pvCid || (int)$v['is_active'] !== 1) continue;
                    if ($term === '' || stripos($v['sku'], $term) !== false || stripos($p['name'], $term) !== false) {
                        $v['product_name'] = $p['name'];
                        $out[] = $v;
                    }
                }
                return $out;
            }
            $prodId = (int)$params[0];
            $cid = (int)$params[1];
            $out = [];
            foreach (Phase3Db::$variants as $v) {
                if ((int)$v['product_id'] === $prodId && (int)$v['concern_id'] === $cid && (int)$v['is_active'] === 1) {
                    $v['size_name'] = Phase3Db::$sizes[(int)($v['size_id'] ?? 0)]['name'] ?? '';
                    $v['size_code'] = Phase3Db::$sizes[(int)($v['size_id'] ?? 0)]['code'] ?? '';
                    $v['color_name'] = Phase3Db::$colors[(int)($v['color_id'] ?? 0)]['name'] ?? '';
                    $out[] = $v;
                }
            }
            return $out;
        }

        // Catalog taxonomy filters (?catalog_only=1)
        if (str_contains($sql, 'FROM categories c')) {
            if (str_contains($sql, 'p.concern_id = ?')) {
                $cid = (int)$params[0];
                $catIds = [];
                foreach (Phase3Db::$products as $p) {
                    if ((int)$p['concern_id'] === $cid && (int)$p['is_active'] === 1) {
                        $catIds[(int)$p['category_id']] = true;
                    }
                }
                return array_values(array_filter(Phase3Db::$categories, static fn(array $c): bool => isset($catIds[(int)$c['id']])));
            }
            return array_values(Phase3Db::$categories);
        }
        if (str_contains($sql, 'FROM subcategories sc')) {
            $catId = (int)$params[0];
            $subcatIds = null;
            if (str_contains($sql, 'p.concern_id = ?')) {
                $cid = (int)$params[1];
                $subcatIds = [];
                foreach (Phase3Db::$products as $p) {
                    if ((int)$p['concern_id'] === $cid && (int)$p['is_active'] === 1 && !empty($p['subcategory_id'])) {
                        $subcatIds[(int)$p['subcategory_id']] = true;
                    }
                }
            }
            return array_values(array_filter(Phase3Db::$subcategories, static fn(array $sc): bool => (int)$sc['category_id'] === $catId && ($subcatIds === null || isset($subcatIds[(int)$sc['id']]))));
        }
        if (str_contains($sql, 'FROM sub_subcategories ssc')) {
            $subcatId = (int)$params[0];
            $sscIds = null;
            if (str_contains($sql, 'p.concern_id = ?')) {
                $cid = (int)$params[1];
                $sscIds = [];
                foreach (Phase3Db::$products as $p) {
                    if ((int)$p['concern_id'] === $cid && (int)$p['is_active'] === 1 && !empty($p['sub_subcategory_id'])) {
                        $sscIds[(int)$p['sub_subcategory_id']] = true;
                    }
                }
            }
            return array_values(array_filter(Phase3Db::$subSubcategories, static fn(array $ssc): bool => (int)$ssc['subcategory_id'] === $subcatId && ($sscIds === null || isset($sscIds[(int)$ssc['id']]))));
        }

        if (str_contains($sql, 'FROM production_programs pp') && str_contains($sql, 'u.concern_id = ?')) {
            $prodId = (int)$params[0];
            $cid = (int)$params[1];
            $out = [];
            foreach (Phase3Db::$programs as $prog) {
                $u = Phase3Db::$units[(int)$prog['production_unit_id']] ?? null;
                if ((int)$prog['product_id'] === $prodId && $u && (int)$u['concern_id'] === $cid) {
                    $prog['unit_name'] = $u['name'];
                    $out[] = $prog;
                }
            }
            return $out;
        }
        if (str_contains($sql, 'FROM sizes')) return array_values(Phase3Db::$sizes);
        if (str_contains($sql, 'FROM colors')) return array_values(Phase3Db::$colors);
        return [];
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        if (str_starts_with($sql, 'INSERT INTO sellers ')) {
            $id = ++Phase3Db::$nextId;
            self::$lastInsertId = $id;
            Phase3Db::$sellers[$id] = [
                'id' => $id,
                'concern_id' => (int)$params[0],
                'name' => $params[1],
                'company' => $params[2],
                'phone' => $params[3],
                'email' => $params[4],
                'gst_number' => $params[5],
                'city' => $params[6],
                'state' => $params[7],
                'address' => $params[8],
                'payment_terms' => $params[9],
                'seller_type' => 'MATERIAL_SUPPLIER',
                'roll_count' => 0,
                'roll_weight' => 0.0,
                'roll_price' => 0.0,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            return true;
        }
        if (str_starts_with(trim($sql), 'UPDATE sellers SET')) {
            if (str_contains($sql, 'roll_count = ?')) {
                $sellerId = (int)$params[7];
                $cid = (int)$params[8];
                if (isset(Phase3Db::$sellers[$sellerId]) && (int)Phase3Db::$sellers[$sellerId]['concern_id'] === $cid) {
                    Phase3Db::$sellers[$sellerId]['roll_count'] = (int)$params[0];
                    Phase3Db::$sellers[$sellerId]['roll_weight'] = (float)$params[4];
                    Phase3Db::$sellers[$sellerId]['roll_price'] = (float)$params[5];
                }
                return true;
            }
            $sellerId = (int)$params[10];
            $cid = (int)$params[11];
            if (isset(Phase3Db::$sellers[$sellerId]) && (int)Phase3Db::$sellers[$sellerId]['concern_id'] === $cid) {
                Phase3Db::$sellers[$sellerId]['name'] = $params[0];
                Phase3Db::$sellers[$sellerId]['company'] = $params[1];
                Phase3Db::$sellers[$sellerId]['is_active'] = (int)$params[9];
            }
            return true;
        }
        if (str_starts_with(trim($sql), 'DELETE FROM seller_rolls WHERE seller_id = ? AND concern_id = ?')) {
            foreach (Phase3Db::$rolls as $rid => $r) {
                if ((int)$r['seller_id'] === (int)$params[0] && (int)$r['concern_id'] === (int)$params[1]) {
                    unset(Phase3Db::$rolls[$rid]);
                }
            }
            return true;
        }
        if (str_starts_with(trim($sql), 'DELETE FROM seller_rolls WHERE id = ? AND concern_id = ?')) {
            $rid = (int)$params[0];
            if (isset(Phase3Db::$rolls[$rid]) && (int)Phase3Db::$rolls[$rid]['concern_id'] === (int)$params[1]) {
                unset(Phase3Db::$rolls[$rid]);
            }
            return true;
        }
        if (str_starts_with(trim($sql), 'DELETE FROM sellers WHERE id = ? AND concern_id = ?')) {
            $sid = (int)$params[0];
            if (isset(Phase3Db::$sellers[$sid]) && (int)Phase3Db::$sellers[$sid]['concern_id'] === (int)$params[1]) {
                unset(Phase3Db::$sellers[$sid]);
            }
            return true;
        }

        if (str_starts_with(trim($sql), 'INSERT INTO production_purchases')) {
            $id = ++Phase3Db::$nextId;
            self::$lastInsertId = $id;
            Phase3Db::$purchases[$id] = [
                'id' => $id,
                'purchase_number' => $params[0],
                'concern_id' => (int)$params[1],
                'production_unit_id' => $params[2],
                'seller_id' => (int)$params[3],
                'purchase_date' => $params[4],
                'total_amount' => (float)$params[8],
            ];
            return true;
        }
        if (str_starts_with(trim($sql), 'UPDATE production_purchases SET purchase_number = ?')) {
            $pid = (int)$params[1];
            if (isset(Phase3Db::$purchases[$pid]) && (int)Phase3Db::$purchases[$pid]['concern_id'] === (int)$params[2]) {
                Phase3Db::$purchases[$pid]['purchase_number'] = $params[0];
            }
            return true;
        }
        if (str_starts_with(trim($sql), 'INSERT INTO production_purchase_items')) {
            self::$lastInsertId = ++Phase3Db::$nextId;
            Phase3Db::$purchaseItems[self::$lastInsertId] = ['id' => self::$lastInsertId, 'purchase_id' => $params[0], 'seller_roll_id' => $params[20]];
            return true;
        }

        if (str_contains($sql, 'INSERT INTO seller_rolls')) {
            $id = ++Phase3Db::$nextId;
            self::$lastInsertId = $id;
            if (str_contains($sql, 'start_seq, end_seq')) {
                Phase3Db::$rolls[$id] = [
                    'id' => $id,
                    'concern_id' => (int)$params[0],
                    'seller_id' => (int)$params[1],
                    'roll_number' => $params[2],
                    'color' => $params[3],
                    'design_name' => $params[4],
                    'design_count' => (int)$params[5],
                    'quantity' => (int)$params[6],
                    'weight' => (float)$params[7],
                    'price' => (float)$params[8],
                    'material_type' => $params[9],
                    'photo_url' => $params[10],
                    'start_seq' => (int)$params[11],
                    'end_seq' => (int)$params[12],
                    'start_code' => $params[13],
                    'end_code' => $params[14],
                    'qr_code' => $params[15],
                ];
            } else {
                Phase3Db::$rolls[$id] = [
                    'id' => $id,
                    'concern_id' => (int)$params[0],
                    'seller_id' => (int)$params[1],
                    'roll_number' => $params[2],
                    'color' => $params[3],
                    'design_name' => $params[4],
                    'design_count' => (int)$params[5],
                    'quantity' => (int)$params[6],
                    'weight' => (float)$params[7],
                    'price' => (float)$params[8],
                    'start_seq' => 0,
                    'end_seq' => 0,
                    'start_code' => '',
                    'end_code' => '',
                    'qr_code' => '',
                ];
            }
            return true;
        }
        if (str_contains($sql, 'UPDATE seller_rolls SET') && str_contains($sql, 'end_seq = ?')) {
            $rid = (int)$params[4];
            $cid = (int)$params[5];
            if (isset(Phase3Db::$rolls[$rid]) && (int)Phase3Db::$rolls[$rid]['concern_id'] === $cid) {
                Phase3Db::$rolls[$rid]['quantity'] = (int)$params[0];
                Phase3Db::$rolls[$rid]['end_seq'] = (int)$params[1];
                Phase3Db::$rolls[$rid]['end_code'] = $params[2];
                Phase3Db::$rolls[$rid]['qr_code'] = $params[3];
            }
            return true;
        }

        if (str_contains($sql, 'INSERT INTO roll_damages')) {
            $id = ++Phase3Db::$nextId;
            self::$lastInsertId = $id;
            Phase3Db::$rollDamages[$id] = [
                'id' => $id,
                'concern_id' => (int)$params[0],
                'damage_number' => $params[1],
                'seller_id' => (int)$params[2],
                'seller_roll_id' => (int)$params[3],
                'qr_code' => $params[4],
                'damaged_quantity' => 1,
                'damaged_weight' => (float)$params[5],
                'loss_amount' => (float)$params[6],
                'damage_reason' => $params[7],
                'defect_notes' => $params[8],
                'photo_url' => $params[9],
                'status' => 'LOGGED',
                'resolution_type' => 'NONE',
                'refund_amount' => 0.0,
                'debit_note_number' => null,
                'replacement_roll_number' => null,
                'replacement_roll_id' => null,
                'created_at' => $params[11],
            ];
            return true;
        }
        if (str_contains($sql, 'UPDATE roll_damages SET damage_number = ? WHERE id = ? AND concern_id = ?')) {
            $did = (int)$params[1];
            if (isset(Phase3Db::$rollDamages[$did]) && (int)Phase3Db::$rollDamages[$did]['concern_id'] === (int)$params[2]) {
                Phase3Db::$rollDamages[$did]['damage_number'] = $params[0];
            }
            return true;
        }
        if (str_contains($sql, 'UPDATE roll_damages SET') && str_contains($sql, 'resolution_type = ?')) {
            $did = (int)$params[8];
            $cid = (int)$params[9];
            if (isset(Phase3Db::$rollDamages[$did]) && (int)Phase3Db::$rollDamages[$did]['concern_id'] === $cid) {
                Phase3Db::$rollDamages[$did]['status'] = $params[0];
                Phase3Db::$rollDamages[$did]['resolution_type'] = $params[1];
                Phase3Db::$rollDamages[$did]['refund_amount'] = (float)$params[2];
                Phase3Db::$rollDamages[$did]['replacement_roll_id'] = $params[5];
            }
            return true;
        }
        if (str_contains($sql, 'UPDATE roll_damages SET') && str_contains($sql, 'damage_reason = ?')) {
            $did = (int)$params[8];
            $cid = (int)$params[9];
            if (isset(Phase3Db::$rollDamages[$did]) && (int)Phase3Db::$rollDamages[$did]['concern_id'] === $cid) {
                Phase3Db::$rollDamages[$did]['damage_reason'] = $params[5];
                Phase3Db::$rollDamages[$did]['defect_notes'] = $params[6];
            }
            return true;
        }
        if (str_contains($sql, 'DELETE FROM roll_damages WHERE id = ? AND concern_id = ?')) {
            $did = (int)$params[0];
            if (isset(Phase3Db::$rollDamages[$did]) && (int)Phase3Db::$rollDamages[$did]['concern_id'] === (int)$params[1]) {
                unset(Phase3Db::$rollDamages[$did]);
            }
            return true;
        }

        // Products & variants
        if (str_contains($sql, 'INSERT INTO products')) {
            $id = ++Phase3Db::$nextId;
            self::$lastInsertId = $id;
            if (count($params) >= 18) {
                Phase3Db::$products[$id] = [
                    'id' => $id,
                    'concern_id' => (int)$params[0],
                    'name' => $params[1],
                    'code' => $params[2],
                    'category_id' => $params[3],
                    'subcategory_id' => $params[4],
                    'sub_subcategory_id' => $params[5],
                    'category_code' => $params[6],
                    'subcategory_code' => $params[7],
                    'sub_subcategory_code' => $params[8],
                    'qr_code_prefix' => $params[9],
                    'brand_id' => $params[10],
                    'source_type' => 'OWN_PRODUCTION',
                    'material' => $params[11],
                    'description' => $params[12],
                    'hsn_code' => $params[13],
                    'gst_percentage' => $params[14],
                    'min_stock' => $params[15],
                    'max_stock' => $params[16],
                    'image_url' => $params[17],
                    'cloudinary_public_id' => $params[18],
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            } else {
                Phase3Db::$products[$id] = [
                    'id' => $id,
                    'concern_id' => (int)$params[0],
                    'name' => $params[1],
                    'code' => $params[2],
                    'category_id' => $params[3],
                    'subcategory_id' => $params[4],
                    'sub_subcategory_id' => $params[5],
                    'category_code' => $params[6],
                    'subcategory_code' => $params[7],
                    'sub_subcategory_code' => $params[8],
                    'qr_code_prefix' => $params[9],
                    'source_type' => 'OWN_PRODUCTION',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
            }
            return true;
        }
        if (str_contains($sql, 'UPDATE products SET is_active = 0') && str_contains($sql, 'WHERE id = ? AND concern_id = ?')) {
            $pid = (int)$params[1];
            $cid = (int)$params[2];
            if (isset(Phase3Db::$products[$pid]) && (int)Phase3Db::$products[$pid]['concern_id'] === $cid) {
                Phase3Db::$products[$pid]['is_active'] = 0;
                Phase3Db::$products[$pid]['code'] .= '_DEL_' . $params[0];
            }
            return true;
        }
        if (str_contains($sql, 'UPDATE products SET name = ?') && str_contains($sql, 'WHERE id = ? AND concern_id = ?')) {
            $pid = (int)$params[18];
            $cid = (int)$params[19];
            if (isset(Phase3Db::$products[$pid]) && (int)Phase3Db::$products[$pid]['concern_id'] === $cid) {
                Phase3Db::$products[$pid]['name'] = $params[0];
                Phase3Db::$products[$pid]['code'] = $params[1];
                Phase3Db::$products[$pid]['category_id'] = $params[2];
                Phase3Db::$products[$pid]['qr_code_prefix'] = $params[8];
            }
            return true;
        }
        if (str_contains($sql, 'DELETE FROM product_variants WHERE product_id = ? AND concern_id = ?')) {
            foreach (Phase3Db::$variants as $vid => $v) {
                if ((int)$v['product_id'] === (int)$params[0] && (int)$v['concern_id'] === (int)$params[1]) {
                    unset(Phase3Db::$variants[$vid]);
                }
            }
            return true;
        }
        if (str_contains($sql, 'UPDATE product_variants') && str_contains($sql, 'WHERE product_id = ? AND concern_id = ?')) {
            $pid = (int)$params[2];
            $cid = (int)$params[3];
            foreach (Phase3Db::$variants as $vid => &$v) {
                if ((int)$v['product_id'] === $pid && (int)$v['concern_id'] === $cid) {
                    $v['is_active'] = 0;
                    $v['sku'] .= '_DEL_' . $params[0];
                }
            }
            return true;
        }
        if (str_contains($sql, 'INSERT INTO product_variants')) {
            $id = ++Phase3Db::$nextId;
            self::$lastInsertId = $id;
            if (count($params) === 9) {
                Phase3Db::$variants[$id] = [
                    'id' => $id,
                    'concern_id' => (int)$params[0],
                    'product_id' => (int)$params[1],
                    'size_id' => $params[2],
                    'color_id' => $params[3],
                    'sku' => $params[4],
                    'mrp' => (float)$params[5],
                    'purchase_price' => (float)$params[6],
                    'production_cost' => (float)$params[7],
                    'selling_price' => (float)$params[8],
                    'is_active' => 1,
                ];
            } elseif (count($params) === 7) {
                Phase3Db::$variants[$id] = [
                    'id' => $id,
                    'concern_id' => (int)$params[0],
                    'product_id' => (int)$params[1],
                    'size_id' => null,
                    'color_id' => null,
                    'sku' => $params[2],
                    'mrp' => (float)$params[3],
                    'purchase_price' => (float)$params[4],
                    'production_cost' => (float)$params[5],
                    'selling_price' => (float)$params[6],
                    'is_active' => 1,
                ];
            } else {
                Phase3Db::$variants[$id] = [
                    'id' => $id,
                    'concern_id' => (int)$params[0],
                    'product_id' => (int)$params[1],
                    'size_id' => $params[2],
                    'color_id' => $params[3],
                    'sku' => $params[4],
                    'mrp' => 0.0,
                    'purchase_price' => 0.0,
                    'production_cost' => 0.0,
                    'selling_price' => 0.0,
                    'is_active' => 1,
                ];
            }
            return true;
        }
        if (str_contains($sql, 'INSERT INTO production_programs')) {
            $id = ++Phase3Db::$nextId;
            self::$lastInsertId = $id;
            Phase3Db::$programs[$id] = [
                'id' => $id,
                'name' => $params[0],
                'program_number' => $params[1],
                'production_unit_id' => $params[2],
                'product_id' => $params[3],
                'seller_roll_id' => $params[4],
                'seller_id' => $params[5],
                'category_id' => $params[8],
                'subcategory_id' => $params[9],
                'sub_subcategory_id' => $params[10],
            ];
            return true;
        }
        if (str_contains($sql, 'UPDATE production_programs SET product_id = ? WHERE id = ?')) {
            if (isset(Phase3Db::$programs[(int)$params[1]])) {
                Phase3Db::$programs[(int)$params[1]]['product_id'] = (int)$params[0];
            }
            return true;
        }
        return true;
    }
}

function currentConcernId(): ?int
{
    $id = Session::get('active_concern_id');
    return $id ? (int)$id : null;
}

function currentProductionUnitId(): ?int
{
    $id = Session::get('active_production_unit_id');
    return $id ? (int)$id : null;
}

function getScopedProductionUnits(): array
{
    $cid = currentConcernId();
    if (!$cid) {
        return [];
    }
    return array_values(array_filter(
        Phase3Db::$units,
        static fn(array $u): bool => (int)$u['concern_id'] === $cid
    ));
}

function url(string $path = ''): string
{
    return 'http://localhost/Inventory/' . ltrim($path, '/');
}

function downloadUrl(string $url, string $filename = ''): string
{
    return $url;
}

function env(string $key, mixed $default = null): mixed
{
    return $default;
}

require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';
require_once BASE_PATH . '/app/Services/ProductionService.php';
require_once BASE_PATH . '/app/Models/ProductionProgram.php';
require_once BASE_PATH . '/app/Controllers/ProductionController.php';

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' (expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . ')');
    }
}

function assertTrueCondition(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function setActiveConcern(int $concernId): void
{
    Session::reset();
    Response::reset();
    $_GET = [];
    $_POST = [];
    $_FILES = [];
    Session::set('user', [
        'id' => 99,
        'role' => 'admin',
        'concern_id' => $concernId,
        'production_unit_id' => $concernId === 1 ? 10 : 20,
    ]);
    Session::set('user_id', 99);
    Session::set('active_concern_id', $concernId);
    Session::set('active_production_unit_id', $concernId === 1 ? 10 : 20);
}

$controller = new ProductionController();

// 1. Sellers isolation & creation concern_id
setActiveConcern(2);
$controller->sellerList();
$c2Sellers = Response::$lastView['data']['sellers'] ?? [];
assertSameValue(1, count($c2Sellers), 'Concern 2 should see only its own supplier');
assertSameValue(21, (int)$c2Sellers[0]['id'], 'Concern 2 should list supplier 21');

$crossViewBlocked = false;
try {
    $controller->viewSeller('11');
} catch (TestPhase3StopException $e) {
    $crossViewBlocked = ($e->statusCode === 404);
}
assertTrueCondition($crossViewBlocked, 'Concern 2 viewSeller must return 404 for Concern 1 supplier');

$crossEditBlocked = false;
try {
    $controller->editSeller('11');
} catch (TestPhase3StopException $e) {
    $crossEditBlocked = ($e->statusCode === 404);
}
assertTrueCondition($crossEditBlocked, 'Concern 2 editSeller must return 404 for Concern 1 supplier');

$_POST = [
    'name' => 'NEW MILL CONCERN TWO',
    'company' => 'NEW MILL CO',
    'phone' => '9876543210',
    'email' => '',
    'city' => 'COIMBATORE',
    'state' => 'TAMIL NADU',
    'address' => 'SULUR ROAD',
    'gst_number' => '',
    'payment_terms' => 'CASH',
];
$controller->storeSeller();
$createdSeller = end(Phase3Db::$sellers);
assertSameValue(2, (int)$createdSeller['concern_id'], 'Newly created seller must get active concern_id');

// Cross-concern deleteSeller & deleteSellerRoll & roll labels blocked
Response::reset();
$controller->deleteSeller('11');
assertSameValue(['This supplier has purchase history and cannot be deleted. Deactivate the supplier to prevent future purchases.'] === Session::getFlash('error') ? 'wrong' : 'ok', 'ok', 'sanity');
assertTrueCondition(isset(Phase3Db::$sellers[11]), 'Concern 2 deleteSeller must never delete Concern 1 seller');

$controller->ajaxSellerRolls('11');
assertSameValue(404, Response::$lastJsonStatus, 'Concern 2 ajaxSellerRolls must reject Concern 1 supplier');
$controller->ajaxSellerRolls('21');
assertSameValue(200, Response::$lastJsonStatus, 'Concern 2 ajaxSellerRolls succeeds for Concern 2 supplier');
assertSameValue(1, count(Response::$lastJson['rolls'] ?? []), 'Concern 2 ajaxSellerRolls returns only Concern 2 rolls');

$labelBlocked = false;
try {
    $controller->printSellerRollLabel('101');
} catch (TestPhase3StopException $e) {
    $labelBlocked = ($e->statusCode === 404);
}
assertTrueCondition($labelBlocked, 'Concern 2 printSellerRollLabel must return 404 for Concern 1 roll');

$_POST = ['count' => 1, 'ajax' => '1'];
$controller->addExtraRollLabel('101');
assertSameValue(404, Response::$lastJsonStatus, 'Concern 2 addExtraRollLabel must reject Concern 1 roll');

$controller->addExtraRollLabel('201');
assertSameValue(200, Response::$lastJsonStatus, 'Concern 2 addExtraRollLabel succeeds for own roll');
assertSameValue(3, (int)Phase3Db::$rolls[201]['quantity'], 'Own roll quantity increments cleanly');

// 2. Purchases: supplier scoping & seller_rolls.concern_id
setActiveConcern(2);
$controller->createProductionPurchase();
$purchaseSellers = Response::$lastView['data']['sellers'] ?? [];
foreach ($purchaseSellers as $ps) {
    assertSameValue(2, (int)$ps['concern_id'], 'Purchase create must only expose active concern suppliers');
}

$_POST = [
    'seller_id' => 11,
    'purchase_date' => '2026-11-11',
    'fabric_description' => 'COTTON',
    'items' => [[
        'item_type' => 'FABRIC_ROLL',
        'roll_number' => 'X-1',
        'color' => 'RED',
        'design_name' => 'CHECK',
        'design_count' => 1,
        'quantity' => 2,
        'weight_kg' => 10,
        'unit_price' => 1500,
        'gst_type' => 'EXEMPTED',
    ]],
];
$controller->storeProductionPurchase();
$err = Session::getFlash('error');
assertSameValue('Select an active registered supplier.', $err[0] ?? '', 'Purchase store must reject cross-concern supplier');

$rollCountBefore = count(Phase3Db::$rolls);
$_POST['seller_id'] = 21;
$controller->storeProductionPurchase();
$purchaseErr = Session::getFlash('error');
assertSameValue($rollCountBefore + 1, count(Phase3Db::$rolls), 'Own-concern fabric purchase must create seller_roll: ' . json_encode($purchaseErr));
$newPurchaseRoll = end(Phase3Db::$rolls);
assertSameValue(2, (int)$newPurchaseRoll['concern_id'], 'Purchase-created seller_roll must store concern_id = 2');

// 3. Fabric Roll Damage isolation
setActiveConcern(2);
$controller->damageList();
$dmgRows = Response::$lastView['data']['damages'] ?? [];
assertSameValue(1, count($dmgRows), 'Concern 2 damageList sees only Concern 2 damage records');
assertSameValue(302, (int)$dmgRows[0]['id'], 'Concern 2 damageList sees record 302');

$_GET = ['code' => 'FB112601'];
$controller->ajaxLookupDamageRoll();
assertSameValue(404, Response::$lastJsonStatus, 'Concern 2 damage scan rejects Concern 1 roll label');

$_GET = ['code' => 'FB112604'];
$controller->ajaxLookupDamageRoll();
assertSameValue(409, Response::$lastJsonStatus, 'Concern 2 finds own damaged QR and reports already-damaged duplicate in Concern 2');

$_GET = ['code' => 'FB112605'];
$controller->ajaxLookupDamageRoll();
assertSameValue(200, Response::$lastJsonStatus, 'Concern 2 damage scan finds undamaged own roll label FB112605');

$_POST = ['qr_code' => 'FB112601', 'damage_reason' => 'Stain'];
$_FILES = ['photo' => ['name' => 'p.jpg', 'error' => UPLOAD_ERR_OK]];
$countDmgBefore = count(Phase3Db::$rollDamages);
$controller->storeDamage();
assertSameValue($countDmgBefore, count(Phase3Db::$rollDamages), 'Concern 2 storeDamage must not create damage for Concern 1 QR');
$dmgErr = Session::getFlash('error');
assertSameValue('No fabric roll was found for QR code FB112601.', $dmgErr[0] ?? '', 'Concern 2 storeDamage rejects Concern 1 QR during roll lookup');

// Test updateDamage isolation & recalculation
$_POST = ['damage_reason' => 'Torn edge', 'defect_notes' => 'cross check'];
$controller->updateDamage('301');
$updErr = Session::getFlash('error');
assertSameValue('Damage record not found.', $updErr[0] ?? '', 'Concern 2 updateDamage must reject Concern 1 record');

$_POST = ['damage_reason' => 'Updated tear reason', 'defect_notes' => 'checked', 'damaged_quantity' => 1];
$_FILES = [];
$controller->updateDamage('302');
assertSameValue('Updated tear reason', Phase3Db::$rollDamages[302]['damage_reason'], 'Concern 2 updateDamage updates own record cleanly');

$dmgViewBlocked = false;
try {
    $controller->viewDamage('301');
} catch (TestPhase3StopException $e) {
    $dmgViewBlocked = ($e->statusCode === 404);
}
assertTrueCondition($dmgViewBlocked, 'Concern 2 viewDamage must return 404 for Concern 1 damage');

$_POST = ['status' => 'REPLACEMENT_RECEIVED', 'resolution_type' => 'REPLACEMENT', 'create_replacement_roll' => '1', 'replacement_roll_number' => 'R-201-REPL'];
$rollsBeforeRepl = count(Phase3Db::$rolls);
$controller->resolveDamage('302');
assertSameValue($rollsBeforeRepl + 1, count(Phase3Db::$rolls), 'resolveDamage replacement creates new roll');
$replRoll = end(Phase3Db::$rolls);
assertSameValue(2, (int)$replRoll['concern_id'], 'Replacement roll inherits concern_id = 2');

$dmgCountBeforeDel = count(Phase3Db::$rollDamages);
$controller->deleteDamage('301');
assertSameValue($dmgCountBeforeDel, count(Phase3Db::$rollDamages), 'Concern 2 deleteDamage must not delete Concern 1 damage');

// 4. Catalog isolation & per-concern GRM-STY-XXXX
setActiveConcern(1);
assertSameValue('GRM-STY-0003', ProductionService::generateNextCatalogCode(1), 'Concern 1 next code advances past GRM-STY-0002_DEL without regressing');

setActiveConcern(2);
$controller->createCatalog();
assertSameValue('GRM-STY-0001', Response::$lastView['data']['suggestedCode'] ?? '', 'Concern 2 catalog starts fresh at GRM-STY-0001');

// Same QR prefix & code in Concern 2 must succeed independently of Concern 1
$_GET = ['code' => 'NCA'];
$controller->ajaxCheckQRCode();
assertSameValue(false, Response::$lastJson['exists'] ?? true, 'ajaxCheckQRCode allows Concern 2 to reuse prefix from Concern 1');

$_POST = [
    'name' => 'CONCERN TWO NIGHTY',
    'code' => '',
    'category_id' => 1,
    'subcategory_id' => 10,
    'sub_subcategory_id' => 100,
    'category_code' => 'N',
    'subcategory_code' => 'C',
    'sub_subcategory_code' => 'A',
    'qr_code_prefix' => 'NCA',
    'default_production_cost' => 210,
    'default_mrp' => 550,
    'default_selling_price' => 450,
];
$controller->storeCatalog();
$c2Product = end(Phase3Db::$products);
$c2Variant = end(Phase3Db::$variants);
assertSameValue(2, (int)$c2Product['concern_id'], 'storeCatalog writes concern_id = 2 on product');
assertSameValue('GRM-STY-0001', $c2Product['code'], 'storeCatalog auto-generates GRM-STY-0001 for Concern 2');
assertSameValue(2, (int)$c2Variant['concern_id'], 'storeCatalog writes concern_id = 2 on product_variants');

// ajaxCheckQRCode now detects duplicate inside Concern 2
$_GET = ['code' => 'NCA'];
$controller->ajaxCheckQRCode();
assertSameValue(true, Response::$lastJson['exists'] ?? false, 'ajaxCheckQRCode catches duplicate inside active Concern 2');

$controller->catalogList();
$c2Catalog = Response::$lastView['data']['products'] ?? [];
assertSameValue(1, count($c2Catalog), 'Concern 2 catalogList sees only Concern 2 product');
assertSameValue((int)$c2Product['id'], (int)$c2Catalog[0]['id'], 'Concern 2 catalogList shows own product');

$controller->viewCatalog(401);
assertSameValue(url('production/catalog'), Response::$lastRedirect, 'Concern 2 viewCatalog redirects cleanly on Concern 1 product');

$controller->editCatalog(401);
assertSameValue(url('production/catalog'), Response::$lastRedirect, 'Concern 2 editCatalog redirects cleanly on Concern 1 product');

$_POST = ['name' => 'HIJACK', 'category_id' => 1];
$controller->updateCatalog(401);
assertSameValue('NIGHTY - COTTON - A-LINE', Phase3Db::$products[401]['name'], 'Concern 2 updateCatalog must not mutate Concern 1 product 401');

$_POST = [
    'name' => 'CONCERN TWO NIGHTY UPDATED',
    'code' => 'GRM-STY-0001',
    'category_id' => 1,
    'qr_code_prefix' => 'NCA2',
    'variants' => [[
        'size_id' => 1,
        'color_id' => 2,
        'sku' => 'GRM-STY-0001-L-BLUE',
        'production_cost' => 220,
        'mrp' => 599,
        'selling_price' => 499,
    ]],
];
$controller->updateCatalog((int)$c2Product['id']);
$c2Variant = end(Phase3Db::$variants);
assertSameValue('CONCERN TWO NIGHTY UPDATED', Phase3Db::$products[(int)$c2Product['id']]['name'], 'Concern 2 updateCatalog updates own product');
assertSameValue(2, (int)$c2Variant['concern_id'], 'updateCatalog writes concern_id = 2 on synced variants');

$_GET = [];
$controller->searchProducts();
assertSameValue(1, count(Response::$lastJson ?? []), 'searchProducts returns only Concern 2 products');
assertSameValue('CONCERN TWO NIGHTY UPDATED', Response::$lastJson[0]['name'] ?? '', 'searchProducts returns Concern 2 style');

$controller->searchVariants();
assertSameValue(1, count(Response::$lastJson ?? []), 'searchVariants returns only Concern 2 variants');
assertSameValue('GRM-STY-0001-L-BLUE', Response::$lastJson[0]['sku'] ?? '', 'searchVariants returns Concern 2 variant');

$_GET = ['category_id' => 1, 'subcategory_id' => 10, 'sub_subcategory_id' => 100];
$controller->ajaxCatalogVariants();
assertTrueCondition(true, 'ajaxCatalogVariants completed for Concern 2');

// Taxonomy ?catalog_only=1 isolation between Concern 2 and Concern 1
$_GET = ['catalog_only' => '1'];
$controller->ajaxCategories();
assertSameValue(1, count(Response::$lastJson ?? []), 'Concern 2 catalog_only categories reflects only Concern 2 products');

// Cross-concern ProductionProgram::create foreign id neutralization
$createdProgId = ProductionProgram::create([
    'production_unit_id' => 20,
    'program_number' => 'PRG-C2-1',
    'name' => 'C2 Prog',
    'category_id' => 1,
    'subcategory_id' => 10,
    'sub_subcategory_id' => 100,
    'product_id' => 401,     // belongs to Concern 1 -> must be nullified
    'seller_roll_id' => 101, // belongs to Concern 1 -> must be nullified
    'seller_id' => 11,       // belongs to Concern 1 -> must be nullified
    'total_target_qty' => 10,
]);
assertTrueCondition($createdProgId > 0, 'ProductionProgram::create succeeded for Concern 2 unit');
assertSameValue(null, Phase3Db::$programs[$createdProgId]['product_id'], 'ProductionProgram::create must nullify foreign product_id');
assertSameValue(null, Phase3Db::$programs[$createdProgId]['seller_roll_id'], 'ProductionProgram::create must nullify foreign seller_roll_id');
assertSameValue(null, Phase3Db::$programs[$createdProgId]['seller_id'], 'ProductionProgram::create must nullify foreign seller_id');

$autoVariantId = ProductionService::findOrCreateVariant((int)$c2Product['id'], 'XL', 'RED');
assertTrueCondition($autoVariantId > 0, 'findOrCreateVariant creates new variant cleanly');
assertSameValue(2, (int)Phase3Db::$variants[$autoVariantId]['concern_id'], 'findOrCreateVariant inherits concern_id = 2 from product');

// deleteCatalog ignores Concern 1 id and deletes own Concern 2 product cleanly
$controller->deleteCatalog(401);
assertSameValue(1, (int)Phase3Db::$products[401]['is_active'], 'Concern 2 deleteCatalog must not deactivate Concern 1 product 401');

$controller->deleteCatalog((int)$c2Product['id']);
assertSameValue(0, (int)Phase3Db::$products[(int)$c2Product['id']]['is_active'], 'Concern 2 deleteCatalog deactivates own product');
assertSameValue(0, (int)Phase3Db::$variants[(int)$c2Variant['id']]['is_active'], 'Concern 2 deleteCatalog deactivates own variant');

// findOrCreateProductForProgram creates Concern 2 product (not reusing Concern 1's active product 401)
$autoProductId = ProductionService::findOrCreateProductForProgram($createdProgId);
assertTrueCondition($autoProductId > 0 && $autoProductId !== 401, 'findOrCreateProductForProgram must not reuse Concern 1 product 401');
assertSameValue(2, (int)Phase3Db::$products[$autoProductId]['concern_id'], 'findOrCreateProductForProgram sets concern_id = 2');

echo "PASS: Phase 3 Sellers, Purchases, Roll Damage, and Catalog isolation checks passed.\n";
