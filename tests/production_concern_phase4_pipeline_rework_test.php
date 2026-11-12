<?php

define('BASE_PATH', dirname(__DIR__));

final class TestPhase4StopException extends RuntimeException
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
        1 => ['id' => 1, 'name' => 'MAIN FACTORY', 'module_keys' => ['production', 'programs', 'cutting', 'stitching', 'labels', 'qc', 'wastage', 'stitching_wastage', 'production_expenses', 'warehouse_rework']],
        2 => ['id' => 2, 'name' => 'CONCERN TWO', 'module_keys' => ['production', 'programs', 'cutting', 'stitching', 'labels', 'qc', 'wastage', 'stitching_wastage', 'production_expenses', 'warehouse_rework']],
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
        return in_array(strtoupper($facilityType), ['PRODUCTION', 'WAREHOUSE'], true);
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
        throw new TestPhase4StopException($code, $message);
    }
}

final class Phase4Db
{
    public static int $nextId = 2000;
    public static array $queries = [];

    public static array $units = [
        10 => ['id' => 10, 'concern_id' => 1, 'name' => 'C1 Unit', 'code' => 'PU-C1', 'is_active' => 1],
        20 => ['id' => 20, 'concern_id' => 2, 'name' => 'C2 Unit', 'code' => 'PU-C2', 'is_active' => 1],
    ];

    public static array $warehouses = [
        31 => ['id' => 31, 'concern_id' => 1, 'name' => 'WH 1', 'code' => 'W1', 'is_active' => 1],
        32 => ['id' => 32, 'concern_id' => 2, 'name' => 'WH 2', 'code' => 'W2', 'is_active' => 1],
    ];

    public static array $users = [
        1 => ['id' => 1, 'full_name' => 'Super Admin', 'production_unit_id' => null, 'is_active' => 1],
        11 => ['id' => 11, 'full_name' => 'Concern 1 Tailor', 'production_unit_id' => 10, 'is_active' => 1],
        21 => ['id' => 21, 'full_name' => 'Concern 2 Cutter', 'production_unit_id' => 20, 'is_active' => 1],
    ];

    public static array $userConcerns = [
        ['user_id' => 11, 'concern_id' => 1],
        ['user_id' => 21, 'concern_id' => 2],
    ];

    public static array $sellers = [
        11 => ['id' => 11, 'concern_id' => 1, 'name' => 'C1 Mill', 'company' => 'C1 Co', 'material_type' => 'COTTON', 'seller_type' => 'MATERIAL_SUPPLIER', 'is_active' => 1],
        21 => ['id' => 21, 'concern_id' => 2, 'name' => 'C2 Mill', 'company' => 'C2 Co', 'material_type' => 'RAYON', 'seller_type' => 'MATERIAL_SUPPLIER', 'is_active' => 1],
    ];

    public static array $rolls = [
        101 => ['id' => 101, 'concern_id' => 1, 'seller_id' => 11, 'roll_number' => 'R-C1', 'color' => 'RED', 'design_name' => 'PLAIN', 'quantity' => 5, 'weight' => 50.0, 'price' => 5000.0],
        201 => ['id' => 201, 'concern_id' => 2, 'seller_id' => 21, 'roll_number' => 'R-C2', 'color' => 'BLUE', 'design_name' => 'STRIPE', 'quantity' => 5, 'weight' => 40.0, 'price' => 4000.0],
    ];

    public static array $products = [
        401 => ['id' => 401, 'concern_id' => 1, 'name' => 'C1 Nighty', 'code' => 'GRM-STY-0001', 'category_id' => 1, 'subcategory_id' => 10, 'sub_subcategory_id' => 100, 'source_type' => 'OWN_PRODUCTION', 'is_active' => 1],
        402 => ['id' => 402, 'concern_id' => 2, 'name' => 'C2 Kurti', 'code' => 'GRM-STY-0001', 'category_id' => 2, 'subcategory_id' => 20, 'sub_subcategory_id' => 200, 'source_type' => 'OWN_PRODUCTION', 'is_active' => 1],
    ];

    public static array $variants = [
        501 => ['id' => 501, 'concern_id' => 1, 'product_id' => 401, 'size_id' => 1, 'color_id' => 1, 'sku' => 'C1-L-RED', 'is_active' => 1],
        502 => ['id' => 502, 'concern_id' => 2, 'product_id' => 402, 'size_id' => 1, 'color_id' => 2, 'sku' => 'C2-L-BLUE', 'is_active' => 1],
    ];

    public static array $programs = [];
    public static array $cuttingBatches = [];
    public static array $stitchingBatches = [];
    public static array $labelBatches = [];
    public static array $labelItems = [];
    public static array $qcBatches = [];
    public static array $productionBatches = [];
    public static array $batchItems = [];
    public static array $outwards = [];
    public static array $expenses = [];
    public static array $wastageSales = [];
    public static array $stitchingWastageSales = [];
    public static array $reworks = [];
    public static array $deletedLabelCodes = [];
}

$today = date('Ymd');
$year = date('Y');
Phase4Db::$programs = [
    1001 => ['id' => 1001, 'concern_id' => 1, 'production_unit_id' => 10, 'program_number' => "PRG-{$today}-0002", 'name' => 'C1 Program', 'product_id' => 401, 'seller_id' => 11, 'seller_roll_id' => 101, 'category_id' => 1, 'subcategory_id' => 10, 'sub_subcategory_id' => 100, 'status' => 'DRAFT', 'is_active' => 1, 'planned_quantity' => 50, 'created_at' => '2026-11-11 09:00:00'],
    1002 => ['id' => 1002, 'concern_id' => 2, 'production_unit_id' => 20, 'program_number' => "PRG-{$today}-0003", 'name' => 'C2 Program', 'product_id' => 402, 'seller_id' => 21, 'seller_roll_id' => 201, 'category_id' => 2, 'subcategory_id' => 20, 'sub_subcategory_id' => 200, 'status' => 'DRAFT', 'is_active' => 1, 'planned_quantity' => 30, 'created_at' => '2026-11-11 10:00:00'],
];
Phase4Db::$cuttingBatches = [
    1101 => ['id' => 1101, 'program_id' => 1001, 'production_unit_id' => 10, 'batch_number' => "CUT-{$today}-0002", 'name' => 'C1 Cut', 'seller_roll_id' => 101, 'roll_weight' => 50.0, 'wastage_weight' => 5.0, 'used_weight' => 45.0, 'status' => 'IN_PROGRESS', 'is_active' => 1],
    1102 => ['id' => 1102, 'program_id' => 1002, 'production_unit_id' => 20, 'batch_number' => "CUT-{$today}-0003", 'name' => 'C2 Cut', 'seller_roll_id' => 201, 'roll_weight' => 40.0, 'wastage_weight' => 4.0, 'used_weight' => 36.0, 'status' => 'IN_PROGRESS', 'is_active' => 1],
];
Phase4Db::$stitchingBatches = [
    1201 => ['id' => 1201, 'program_id' => 1001, 'cutting_batch_id' => 1101, 'production_unit_id' => 10, 'batch_number' => "STCH-{$today}-0002", 'name' => 'C1 Stitch', 'status' => 'IN_PROGRESS', 'is_active' => 1],
    1202 => ['id' => 1202, 'program_id' => 1002, 'cutting_batch_id' => 1102, 'production_unit_id' => 20, 'batch_number' => "STCH-{$today}-0003", 'name' => 'C2 Stitch', 'status' => 'IN_PROGRESS', 'is_active' => 1],
];
Phase4Db::$labelBatches = [
    1301 => ['id' => 1301, 'program_id' => 1001, 'stitching_batch_id' => 1201, 'batch_number' => "LBL-{$today}-0002", 'status' => 'IN_PROGRESS'],
    1302 => ['id' => 1302, 'program_id' => 1002, 'stitching_batch_id' => 1202, 'batch_number' => "LBL-{$today}-0003", 'status' => 'IN_PROGRESS'],
];
Phase4Db::$labelItems = [
    ['id' => 1, 'label_batch_id' => 1301, 'start_code' => 'NCA112601'],
    ['id' => 2, 'label_batch_id' => 1302, 'start_code' => 'KRS112601'],
];
Phase4Db::$qcBatches = [
    1401 => ['id' => 1401, 'program_id' => 1001, 'stitching_batch_id' => 1201, 'label_batch_id' => 1301, 'status' => 'PENDING'],
    1402 => ['id' => 1402, 'program_id' => 1002, 'stitching_batch_id' => 1202, 'label_batch_id' => 1302, 'status' => 'PENDING'],
];
Phase4Db::$productionBatches = [
    1501 => ['id' => 1501, 'concern_id' => 1, 'program_id' => 1001, 'batch_number' => 'PB-1', 'destination_warehouse_id' => 31, 'status' => 'APPROVED'],
    1502 => ['id' => 1502, 'concern_id' => 2, 'program_id' => 1002, 'batch_number' => 'PB-2', 'destination_warehouse_id' => 32, 'status' => 'APPROVED'],
];
Phase4Db::$batchItems = [
    ['production_batch_id' => 1501, 'variant_id' => 501, 'quantity' => 10],
    ['production_batch_id' => 1502, 'variant_id' => 502, 'quantity' => 12],
];
Phase4Db::$outwards = [
    1601 => ['id' => 1601, 'production_batch_id' => 1501, 'outward_number' => 'OUT-1'],
    1602 => ['id' => 1602, 'production_batch_id' => 1502, 'outward_number' => 'OUT-2'],
];
Phase4Db::$expenses = [
    ['id' => 1, 'expense_number' => "EXP-{$year}-0003", 'concern_id' => 1],
];
Phase4Db::$wastageSales = [
    ['id' => 1, 'sale_number' => "WST-{$year}-0002", 'concern_id' => 1, 'cutting_batch_id' => 1101],
];
Phase4Db::$stitchingWastageSales = [
    ['id' => 1, 'sale_number' => "STW-{$year}-0002", 'concern_id' => 1, 'stitching_batch_id' => 1201],
    ['id' => 2, 'sale_number' => "STW-{$year}-0003", 'concern_id' => 2, 'stitching_batch_id' => 1202],
];
Phase4Db::$reworks = [
    1701 => ['id' => 1701, 'rework_number' => "RWK-WH-{$today}-0003", 'warehouse_id' => 31, 'production_batch_id' => 1501, 'production_unit_id' => 10, 'variant_id' => 501, 'product_id' => 401, 'quantity' => 2, 'status' => 'RECEIVED'],
    1702 => ['id' => 1702, 'rework_number' => "RWK-WH-{$today}-0004", 'warehouse_id' => 32, 'production_batch_id' => 1502, 'production_unit_id' => 20, 'variant_id' => 502, 'product_id' => 402, 'quantity' => 1, 'status' => 'RECEIVED'],
];
Phase4Db::$deletedLabelCodes = [
    ['code' => 'NCA112605', 'created_by' => 11],
    ['code' => 'KRS112602', 'created_by' => 21],
];

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
        Phase4Db::$queries[] = ['one', $sql, $params];

        // Ownership checks by unit & warehouse
        if (str_contains($sql, 'FROM production_units WHERE id = ? AND concern_id = ?')) {
            $u = Phase4Db::$units[(int)$params[0]] ?? null;
            return ($u && (int)$u['concern_id'] === (int)$params[1]) ? $u : null;
        }
        if (str_contains($sql, 'FROM warehouses WHERE id = ? AND concern_id = ?')) {
            $w = Phase4Db::$warehouses[(int)$params[0]] ?? null;
            return ($w && (int)$w['concern_id'] === (int)$params[1]) ? $w : null;
        }
        if (str_contains($sql, 'SELECT code FROM production_units WHERE id = ? AND concern_id = ?')) {
            $u = Phase4Db::$units[(int)$params[0]] ?? null;
            return ($u && (int)$u['concern_id'] === (int)$params[1]) ? ['code' => $u['code']] : null;
        }

        // Batch ownership
        if (str_contains($sql, 'FROM production_batches pb JOIN production_programs pp ON pp.id = pb.program_id JOIN production_units pu ON pu.id = pp.production_unit_id WHERE pb.id = ? AND pb.concern_id = ? AND pu.concern_id = ?')) {
            $b = Phase4Db::$productionBatches[(int)$params[0]] ?? null;
            if (!$b || (int)$b['concern_id'] !== (int)$params[1]) return null;
            $prog = Phase4Db::$programs[(int)$b['program_id']] ?? null;
            $u = $prog ? (Phase4Db::$units[(int)$prog['production_unit_id']] ?? null) : null;
            if (!$u || (int)$u['concern_id'] !== (int)$params[2]) return null;
            return ['id' => $b['id']];
        }
        if (str_contains($sql, 'FROM production_batches pb') && str_contains($sql, 'WHERE pb.id = ? AND pb.concern_id = ?')) {
            $b = Phase4Db::$productionBatches[(int)$params[0]] ?? null;
            if (!$b || (int)$b['concern_id'] !== (int)$params[1]) return null;
            $prog = Phase4Db::$programs[(int)$b['program_id']] ?? null;
            return ['production_unit_id' => $prog['production_unit_id'] ?? null];
        }
        if (str_contains($sql, 'FROM production_outwards po JOIN production_batches pb ON pb.id = po.production_batch_id WHERE po.id = ? AND pb.concern_id = ?')) {
            $po = Phase4Db::$outwards[(int)$params[0]] ?? null;
            $pb = $po ? (Phase4Db::$productionBatches[(int)$po['production_batch_id']] ?? null) : null;
            return ($pb && (int)$pb['concern_id'] === (int)$params[1]) ? ['id' => $po['id']] : null;
        }
        if (str_contains($sql, 'FROM production_outwards po JOIN production_batches pb ON pb.id = po.production_batch_id WHERE po.production_batch_id = ? AND pb.concern_id = ?')) {
            foreach (Phase4Db::$outwards as $po) {
                if ((int)$po['production_batch_id'] === (int)$params[0]) {
                    $pb = Phase4Db::$productionBatches[(int)$po['production_batch_id']] ?? null;
                    if ($pb && (int)$pb['concern_id'] === (int)$params[1]) {
                        return ['id' => $po['id']];
                    }
                }
            }
            return null;
        }
        if (str_contains($sql, 'FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE pv.id = ?')) {
            $v = Phase4Db::$variants[(int)$params[0]] ?? null;
            if (!$v) return null;
            $cid = (int)$params[1];
            $p = Phase4Db::$products[(int)$v['product_id']] ?? null;
            return ((int)$v['concern_id'] === $cid || ($p && (int)$p['concern_id'] === $cid)) ? ['id' => $v['id']] : null;
        }

        // Sequential code uniqueness probes
        if (preg_match('/SELECT id FROM `([^`]+)` WHERE `([^`]+)` = \? LIMIT 1/', $sql, $m)) {
            $table = $m[1];
            $col = $m[2];
            $val = (string)$params[0];
            $map = [
                'production_programs' => Phase4Db::$programs,
                'cutting_batches' => Phase4Db::$cuttingBatches,
                'stitching_batches' => Phase4Db::$stitchingBatches,
                'label_batches' => Phase4Db::$labelBatches,
                'wastage_sales' => Phase4Db::$wastageSales,
                'stitching_wastage_sales' => Phase4Db::$stitchingWastageSales,
                'production_expenses' => Phase4Db::$expenses,
                'production_warehouse_reworks' => Phase4Db::$reworks,
            ];
            foreach ($map[$table] ?? [] as $row) {
                if (($row[$col] ?? '') === $val) {
                    return ['id' => $row['id']];
                }
            }
            return null;
        }

        // Models find() queries
        if (str_contains($sql, 'FROM production_programs pp') && str_contains($sql, 'WHERE pp.id = ? AND pu.concern_id = ?')) {
            $row = Phase4Db::$programs[(int)$params[0]] ?? null;
            if (!$row) return null;
            $u = Phase4Db::$units[(int)$row['production_unit_id']] ?? null;
            return ($u && (int)$u['concern_id'] === (int)$params[1]) ? ($row + ['unit_name' => $u['name']]) : null;
        }
        if (str_contains($sql, 'FROM cutting_batches cb') && str_contains($sql, 'WHERE cb.id = ? AND pu.concern_id = ?')) {
            $row = Phase4Db::$cuttingBatches[(int)$params[0]] ?? null;
            if (!$row) return null;
            $u = Phase4Db::$units[(int)$row['production_unit_id']] ?? null;
            return ($u && (int)$u['concern_id'] === (int)$params[1]) ? ($row + ['unit_name' => $u['name']]) : null;
        }
        if (str_contains($sql, 'FROM stitching_batches sb') && str_contains($sql, 'WHERE sb.id = ? AND pu.concern_id = ?')) {
            $row = Phase4Db::$stitchingBatches[(int)$params[0]] ?? null;
            if (!$row) return null;
            $u = Phase4Db::$units[(int)$row['production_unit_id']] ?? null;
            return ($u && (int)$u['concern_id'] === (int)$params[1]) ? $row : null;
        }
        if (str_contains($sql, 'FROM label_batches lb') && str_contains($sql, 'WHERE lb.id = ? AND pu.concern_id = ?')) {
            $row = Phase4Db::$labelBatches[(int)$params[0]] ?? null;
            if (!$row) return null;
            $prog = Phase4Db::$programs[(int)$row['program_id']] ?? null;
            $u = $prog ? (Phase4Db::$units[(int)$prog['production_unit_id']] ?? null) : null;
            return ($u && (int)$u['concern_id'] === (int)$params[1]) ? $row : null;
        }
        if (str_contains($sql, 'FROM qc_batches qb') && str_contains($sql, 'WHERE qb.id = ? AND pu.concern_id = ?')) {
            $row = Phase4Db::$qcBatches[(int)$params[0]] ?? null;
            if (!$row) return null;
            $prog = Phase4Db::$programs[(int)$row['program_id']] ?? null;
            $u = $prog ? (Phase4Db::$units[(int)$prog['production_unit_id']] ?? null) : null;
            return ($u && (int)$u['concern_id'] === (int)$params[1]) ? $row : null;
        }
        if (str_contains($sql, 'FROM production_warehouse_reworks pwr') && str_contains($sql, 'WHERE pwr.id = ?')) {
            $row = Phase4Db::$reworks[(int)$params[0]] ?? null;
            if (!$row) return null;
            $u = Phase4Db::$units[(int)($row['production_unit_id'] ?? 0)] ?? null;
            $pb = Phase4Db::$productionBatches[(int)($row['production_batch_id'] ?? 0)] ?? null;
            $w = Phase4Db::$warehouses[(int)($row['warehouse_id'] ?? 0)] ?? null;
            $rowConcern = $u['concern_id'] ?? ($pb['concern_id'] ?? ($w['concern_id'] ?? null));
            return ((int)$rowConcern === (int)$params[1]) ? ($row + ['warehouse_name' => $w['name'] ?? '']) : null;
        }

        // Seller roll & product checks
        if (str_contains($sql, 'FROM seller_rolls sr') && str_contains($sql, 'sr.id = ? AND sr.concern_id = ?')) {
            $r = Phase4Db::$rolls[(int)$params[0]] ?? null;
            if (!$r || (int)$r['concern_id'] !== (int)$params[1]) return null;
            $s = Phase4Db::$sellers[(int)$r['seller_id']] ?? null;
            if (str_contains($sql, 's.concern_id = ?') && (!$s || (int)$s['concern_id'] !== (int)$params[2])) return null;
            return $r + ['available_quantity' => $r['quantity']];
        }
        if (str_contains($sql, 'FROM products WHERE concern_id = ?')) {
            $cid = (int)$params[0];
            foreach (Phase4Db::$products as $p) {
                if ((int)$p['concern_id'] !== $cid || (int)$p['is_active'] !== 1) continue;
                if (str_contains($sql, 'sub_subcategory_id = ?') && (int)$p['sub_subcategory_id'] === (int)$params[1]) return $p;
                if (str_contains($sql, 'subcategory_id = ?') && (int)$p['subcategory_id'] === (int)$params[1]) return $p;
                if (str_contains($sql, 'category_id = ?') && (int)$p['category_id'] === (int)$params[1]) return $p;
            }
            return null;
        }
        if (str_contains($sql, 'FROM products WHERE id = ? AND concern_id = ?')) {
            $p = Phase4Db::$products[(int)$params[0]] ?? null;
            return ($p && (int)$p['concern_id'] === (int)$params[1]) ? $p : null;
        }

        // Stitching wastage duplicate & cutting batch check
        if (str_contains($sql, 'FROM stitching_wastage_sales WHERE stitching_batch_id = ? AND concern_id = ?')) {
            foreach (Phase4Db::$stitchingWastageSales as $sw) {
                if ((int)$sw['stitching_batch_id'] === (int)$params[0] && (int)$sw['concern_id'] === (int)$params[1]) {
                    return $sw + ['quantity' => 5, 'unit_type' => 'KG', 'sale_date' => '2026-11-11'];
                }
            }
            return null;
        }

        return null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        Phase4Db::$queries[] = ['all', $sql, $params];

        // Sequential doc_code list
        if (preg_match('/SELECT `([^`]+)` AS doc_code FROM `([^`]+)` WHERE `[^`]+` LIKE \?/', $sql, $m)) {
            $col = $m[1];
            $table = $m[2];
            $prefix = rtrim((string)$params[0], '%');
            $map = [
                'production_programs' => Phase4Db::$programs,
                'cutting_batches' => Phase4Db::$cuttingBatches,
                'stitching_batches' => Phase4Db::$stitchingBatches,
                'label_batches' => Phase4Db::$labelBatches,
                'wastage_sales' => Phase4Db::$wastageSales,
                'stitching_wastage_sales' => Phase4Db::$stitchingWastageSales,
                'production_expenses' => Phase4Db::$expenses,
                'production_warehouse_reworks' => Phase4Db::$reworks,
            ];
            $out = [];
            foreach ($map[$table] ?? [] as $row) {
                if (str_starts_with((string)($row[$col] ?? ''), $prefix)) {
                    $out[] = ['doc_code' => $row[$col]];
                }
            }
            return $out;
        }

        // Concern active users dropdown
        if (str_contains($sql, 'FROM users u') && str_contains($sql, 'user_concerns uc')) {
            $cid1 = (int)$params[0];
            $cid2 = (int)$params[1];
            $out = [];
            foreach (Phase4Db::$users as $u) {
                if ((int)$u['is_active'] !== 1) continue;
                $inUc = false;
                foreach (Phase4Db::$userConcerns as $uc) {
                    if ((int)$uc['user_id'] === (int)$u['id'] && (int)$uc['concern_id'] === $cid1) {
                        $inUc = true;
                        break;
                    }
                }
                $unit = !empty($u['production_unit_id']) ? (Phase4Db::$units[(int)$u['production_unit_id']] ?? null) : null;
                $inUnit = ($unit && (int)$unit['concern_id'] === $cid2);
                if ($inUc || $inUnit) {
                    $out[] = $u;
                }
            }
            return $out;
        }

        // Deleted label codes scoped to concern
        if (str_contains($sql, 'FROM production_deleted_label_codes pdlc')) {
            $cid = (int)$params[0];
            $out = [];
            foreach (Phase4Db::$deletedLabelCodes as $row) {
                $creator = Phase4Db::$users[(int)$row['created_by']] ?? null;
                $uUnit = !empty($creator['production_unit_id']) ? (Phase4Db::$units[(int)$creator['production_unit_id']] ?? null) : null;
                if ($uUnit && (int)$uUnit['concern_id'] === $cid) {
                    $out[] = ['code' => $row['code']];
                }
            }
            return $out;
        }

        // Seller rolls filtered by concern
        if (str_contains($sql, 'FROM seller_rolls sr') && str_contains($sql, 'WHERE sr.concern_id = ?')) {
            $cid = (int)end($params);
            return array_values(array_filter(Phase4Db::$rolls, static fn(array $r): bool => (int)$r['concern_id'] === $cid));
        }

        // Programs / cutting batches scoped by pu.concern_id
        if (str_contains($sql, 'FROM production_programs pp') && str_contains($sql, 'WHERE pu.concern_id = ?')) {
            $cid = (int)$params[0];
            return array_values(array_filter(Phase4Db::$programs, static fn(array $p): bool => (int)(Phase4Db::$units[(int)$p['production_unit_id']]['concern_id'] ?? 0) === $cid));
        }
        if (str_contains($sql, 'FROM cutting_batches cb') && str_contains($sql, 'WHERE pu.concern_id = ?')) {
            $cid = (int)$params[0];
            return array_values(array_filter(Phase4Db::$cuttingBatches, static fn(array $c): bool => (int)(Phase4Db::$units[(int)$c['production_unit_id']]['concern_id'] ?? 0) === $cid));
        }

        // Rework batch variants lookup
        if (str_contains($sql, 'FROM production_batch_items pbi')) {
            $bid = (int)$params[0];
            $out = [];
            foreach (Phase4Db::$batchItems as $bi) {
                if ((int)$bi['production_batch_id'] === $bid) {
                    $v = Phase4Db::$variants[(int)$bi['variant_id']] ?? [];
                    $p = Phase4Db::$products[(int)($v['product_id'] ?? 0)] ?? [];
                    $out[] = ['variant_id' => $bi['variant_id'], 'quantity' => $bi['quantity'], 'sku' => $v['sku'] ?? '', 'product_name' => $p['name'] ?? ''];
                }
            }
            return $out;
        }

        // Rework list allVariants
        if (str_contains($sql, 'FROM product_variants pv') && str_contains($sql, 'pv.concern_id = ?')) {
            $cid = (int)$params[0];
            return array_values(array_filter(Phase4Db::$variants, static fn(array $v): bool => (int)$v['concern_id'] === $cid));
        }

        return [];
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        $placeholderCount = substr_count($sql, '?');
        if ($placeholderCount !== count($params)) {
            throw new RuntimeException("SQL placeholder mismatch ({$placeholderCount} '?' vs " . count($params) . " params) in: {$sql}");
        }

        if (str_starts_with($sql, 'INSERT INTO production_expenses ')) {
            $id = ++Phase4Db::$nextId;
            self::$lastInsertId = $id;
            Phase4Db::$expenses[] = ['id' => $id, 'expense_number' => $params[0], 'concern_id' => (int)$params[1], 'production_unit_id' => $params[2]];
            return true;
        }
        if (str_starts_with($sql, 'INSERT INTO production_warehouse_reworks ')) {
            $id = ++Phase4Db::$nextId;
            self::$lastInsertId = $id;
            Phase4Db::$reworks[$id] = [
                'id' => $id,
                'rework_number' => $params[0],
                'warehouse_id' => $params[1],
                'production_batch_id' => $params[3],
                'variant_id' => $params[5],
                'production_unit_id' => $params[7],
                'quantity' => $params[8],
                'status' => $params[19],
            ];
            return true;
        }
        if (str_starts_with($sql, 'DELETE FROM production_warehouse_reworks WHERE id = ?')) {
            unset(Phase4Db::$reworks[(int)$params[0]]);
            return true;
        }
        if (str_starts_with($sql, 'UPDATE production_programs SET status = ')) {
            $pid = (int)$params[1];
            if (isset(Phase4Db::$programs[$pid])) {
                Phase4Db::$programs[$pid]['status'] = $params[0];
            }
            return true;
        }
        if (str_starts_with($sql, 'UPDATE cutting_batches SET ') || str_starts_with($sql, 'UPDATE stitching_batches SET ') || str_starts_with($sql, 'UPDATE label_batches SET ')) {
            $id = (int)end($params);
            if ($id === 1101 || $id === 1201 || $id === 1301) {
                throw new RuntimeException("Unauthorized cross-concern UPDATE executed on id {$id}: {$sql}");
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
    return $id !== null ? (int)$id : null;
}

function getScopedProductionUnits(): array
{
    $cid = currentConcernId();
    if (!$cid) return [];
    return array_values(array_filter(Phase4Db::$units, static fn(array $u): bool => (int)$u['concern_id'] === $cid));
}

function getScopedWarehouses(): array
{
    $cid = currentConcernId();
    if (!$cid) return [];
    return array_values(array_filter(Phase4Db::$warehouses, static fn(array $w): bool => (int)$w['concern_id'] === $cid));
}

function hasPermission(string $module, string $action): bool
{
    return true;
}

function url(string $path = ''): string
{
    return 'http://localhost/Inventory/' . ltrim($path, '/');
}

function env(string $key, mixed $default = null): mixed
{
    return $default;
}

require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';
require_once BASE_PATH . '/app/Services/ProductionService.php';
require_once BASE_PATH . '/app/Models/ProductionProgram.php';
require_once BASE_PATH . '/app/Models/CuttingBatch.php';
require_once BASE_PATH . '/app/Models/CuttingItem.php';
require_once BASE_PATH . '/app/Models/StitchingBatch.php';
require_once BASE_PATH . '/app/Models/StitchingItem.php';
require_once BASE_PATH . '/app/Models/LabelItem.php';
require_once BASE_PATH . '/app/Models/ProductionWarehouseRework.php';
require_once BASE_PATH . '/app/Controllers/ProductionController.php';

function assertSame4(mixed $expected, mixed $actual, string $msg): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($msg . ' (expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . ')');
    }
}

function assertTrue4(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

function switchConcern(int $concernId, ?int $unitId = null): void
{
    Session::reset();
    Response::reset();
    $_GET = [];
    $_POST = [];
    $_FILES = [];
    Session::set('user', ['id' => 21, 'concern_id' => $concernId, 'production_unit_id' => $unitId]);
    Session::set('user_id', 21);
    Session::set('active_concern_id', $concernId);
    if ($unitId !== null) {
        Session::set('active_production_unit_id', $unitId);
    }
}

$controller = new ProductionController();

// 1. Sequential code generation skips gaps & never collides after deletions
assertSame4("PRG-{$today}-0004", ProductionService::generateNextSequentialCode('production_programs', 'program_number', "PRG-{$today}-")['code'], 'Programs sequential code skips gap to 0004');
assertSame4("CUT-{$today}-0004", ProductionService::generateNextSequentialCode('cutting_batches', 'batch_number', "CUT-{$today}-")['code'], 'Cutting sequential code skips gap to 0004');
assertSame4("STCH-{$today}-0004", ProductionService::generateNextSequentialCode('stitching_batches', 'batch_number', "STCH-{$today}-")['code'], 'Stitching sequential code skips gap to 0004');
assertSame4("LBL-{$today}-0004", ProductionService::generateNextSequentialCode('label_batches', 'batch_number', "LBL-{$today}-")['code'], 'Labels sequential code skips gap to 0004');
assertSame4("WST-{$year}-0003", ProductionService::generateNextSequentialCode('wastage_sales', 'sale_number', "WST-{$year}-")['code'], 'Wastage sequential code skips gap to 0003');
assertSame4("STW-{$year}-0004", ProductionService::generateNextSequentialCode('stitching_wastage_sales', 'sale_number', "STW-{$year}-")['code'], 'Stitching wastage sequential code skips gap to 0004');
assertSame4("EXP-{$year}-0004", ProductionService::generateNextSequentialCode('production_expenses', 'expense_number', "EXP-{$year}-")['code'], 'Expenses sequential code skips gap to 0004');
assertSame4("RWK-WH-{$today}-0005", ProductionService::generateNextSequentialCode('production_warehouse_reworks', 'rework_number', "RWK-WH-{$today}-")['code'], 'Rework sequential code skips gap to 0005');

// 2. Expenses storeExpense(): multi-line save + non-colliding EXP number
switchConcern(2, 20);
$_POST = [
    'item_name' => ['Needles', 'Bobbins'],
    'quantity' => [2, 3],
    'unit_cost' => [100, 50],
    'expense_date' => '2026-11-11',
];
$expBefore = count(Phase4Db::$expenses);
$controller->storeExpense();
assertSame4($expBefore + 2, count(Phase4Db::$expenses), 'storeExpense saves both expense lines cleanly');
assertSame4("EXP-{$year}-0004", Phase4Db::$expenses[$expBefore]['expense_number'], 'First expense line gets non-colliding EXP-0004');
assertSame4("EXP-{$year}-0005", Phase4Db::$expenses[$expBefore + 1]['expense_number'], 'Second expense line gets sequential EXP-0005');

// 3. Programs: global dropdown isolation & missing find() checks
switchConcern(2, 20);
$controller->createProgram();
$pUsers = Response::$lastView['data']['users'] ?? [];
$pRolls = Response::$lastView['data']['rolls'] ?? [];
assertSame4(1, count($pUsers), 'createProgram users dropdown scoped to Concern 2');
assertSame4(21, (int)$pUsers[0]['id'], 'createProgram shows only Concern 2 user');
assertSame4(1, count($pRolls), 'createProgram rolls dropdown scoped to Concern 2');
assertSame4(201, (int)$pRolls[0]['id'], 'createProgram shows only Concern 2 roll');
assertSame4("PRG-{$today}-0004", Response::$lastView['data']['suggestedProgramNumber'] ?? '', 'createProgram suggests non-colliding number');

// Cross-concern approveProgram & updateProgramStatus must fail before mutating Concern 1 program 1001
$controller->approveProgram('1001');
assertSame4('DRAFT', Phase4Db::$programs[1001]['status'], 'Concern 2 approveProgram must not approve Concern 1 program');
assertSame4('Program not found.', Session::getFlash('error')[0] ?? '', 'approveProgram flashes Program not found');

$_POST = ['status' => 'APPROVED'];
$controller->updateProgramStatus('1001');
assertSame4('DRAFT', Phase4Db::$programs[1001]['status'], 'Concern 2 updateProgramStatus must not mutate Concern 1 program');
assertSame4('Program not found.', Session::getFlash('error')[0] ?? '', 'updateProgramStatus flashes Program not found');

$_POST = ['name' => 'Hijack', 'rolls' => [['seller_roll_id' => 201]]];
$controller->updateProgram('1001');
assertSame4('Program not found.', Session::getFlash('error')[0] ?? '', 'Concern 2 updateProgram rejects Concern 1 program');

// ajaxProgramDetails for Concern 2 program sees only Concern 2 rolls & products
$controller->ajaxProgramDetails(1002);
assertSame4(200, Response::$lastJsonStatus, 'ajaxProgramDetails succeeds for Concern 2 program');
assertSame4(402, (int)(Response::$lastJson['product']['id'] ?? 0), 'ajaxProgramDetails resolves Concern 2 product');
assertSame4(1, count(Response::$lastJson['allRolls'] ?? []), 'ajaxProgramDetails allRolls contains only Concern 2 rolls');
assertSame4(201, (int)(Response::$lastJson['allRolls'][0]['id'] ?? 0), 'ajaxProgramDetails allRolls contains Concern 2 roll 201');

// 4. Cutting: users, rolls, & ownership checks
switchConcern(2, 20);
$controller->createCutting();
assertSame4(1, count(Response::$lastView['data']['users'] ?? []), 'createCutting users scoped to Concern 2');
assertSame4(1, count(Response::$lastView['data']['rolls'] ?? []), 'createCutting rolls scoped to Concern 2');
assertSame4("CUT-{$today}-0004", Response::$lastView['data']['suggestedBatchNumber'] ?? '', 'createCutting skips gap to CUT-0004');

$_POST = ['name' => 'Cross Cut', 'program_id' => 1001];
$controller->storeCutting();
assertSame4('Selected production program was not found in the active concern.', Session::getFlash('error')[0] ?? '', 'storeCutting rejects Concern 1 program');

$_POST = ['name' => 'Hijack Cut'];
$controller->updateCutting('1101');
assertSame4('Cutting batch not found.', Session::getFlash('error')[0] ?? '', 'updateCutting rejects Concern 1 batch without mutating');

// 5. Stitching: editStitching scoping & updateStitching guard
switchConcern(2, 20);
$controller->editStitching('1202');
assertSame4(1, count(Response::$lastView['data']['programs'] ?? []), 'editStitching loads only Concern 2 programs');
assertSame4(1002, (int)Response::$lastView['data']['programs'][0]['id'], 'editStitching loads Concern 2 program 1002');
assertSame4(1, count(Response::$lastView['data']['cuttingBatches'] ?? []), 'editStitching loads only Concern 2 cutting batches');
assertSame4(1102, (int)Response::$lastView['data']['cuttingBatches'][0]['id'], 'editStitching loads Concern 2 cutting batch 1102');
assertSame4(1, count(Response::$lastView['data']['users'] ?? []), 'editStitching loads only Concern 2 users');

$_POST = ['name' => 'Hijack Stitch'];
$controller->updateStitching('1201');
assertSame4('Stitching batch not found.', Session::getFlash('error')[0] ?? '', 'updateStitching rejects Concern 1 stitching batch');

$_POST = ['cutting_batch_id' => 1101, 'name' => 'Bad Stitch'];
$controller->storeStitching();
assertSame4('Selected cutting batch was not found in the active concern.', Session::getFlash('error')[0] ?? '', 'storeStitching rejects Concern 1 cutting batch');

// 6. Labels: ajaxGetDeletedLabelCodes scoping & updateLabel guard
switchConcern(2, 20);
$controller->ajaxGetDeletedLabelCodes();
assertSame4(['KRS112602'], Response::$lastJson['codes'] ?? [], 'ajaxGetDeletedLabelCodes returns only active Concern 2 deleted codes');

$controller->createLabel();
assertSame4("LBL-{$today}-0004", Response::$lastView['data']['suggestedBatchNumber'] ?? '', 'createLabel skips gap to LBL-0004');

$_POST = ['stitching_batch_id' => 1201];
$controller->storeLabel();
assertSame4('Selected stitching batch was not found in the active concern.', Session::getFlash('error')[0] ?? '', 'storeLabel rejects Concern 1 stitching batch');

$_POST = ['status' => 'COMPLETED'];
$controller->updateLabel('1301');
assertSame4('Label batch not found.', Session::getFlash('error')[0] ?? '', 'updateLabel rejects Concern 1 label batch');

// 7. QC: createQC & inspectQC user dropdowns
switchConcern(2, 20);
$controller->createQC();
assertSame4(1, count(Response::$lastView['data']['users'] ?? []), 'createQC users scoped to Concern 2');
$controller->inspectQC('1402');
assertSame4(1, count(Response::$lastView['data']['users'] ?? []), 'inspectQC users scoped to Concern 2');

// 8. Wastage duplicate lookup avoids unscoped stitching_batches re-query
switchConcern(2, 20);
$_POST = ['stitching_batch_id' => 1202];
Phase4Db::$queries = [];
$controller->storeStitchingWastage();
$unscopedStitchingQuery = array_filter(
    Phase4Db::$queries,
    static fn(array $q): bool => str_contains($q[1], 'FROM stitching_batches WHERE id = ?')
);
assertSame4(0, count($unscopedStitchingQuery), 'storeStitchingWastage no longer runs unscoped FROM stitching_batches WHERE id = ?');

// 9. Rework from Warehouse: lookupBatchVariantsAjax, storeWarehouseRework, deleteWarehouseRework, warehouseReworkList
switchConcern(2, 20);
$controller->warehouseReworkList();
$rwAllVars = Response::$lastView['data']['allVariants'] ?? [];
assertSame4(1, count($rwAllVars), 'warehouseReworkList allVariants scoped to Concern 2');
assertSame4(502, (int)$rwAllVars[0]['id'], 'warehouseReworkList shows Concern 2 variant 502');

$controller->lookupBatchVariantsAjax('1501');
assertSame4(404, Response::$lastJsonStatus, 'lookupBatchVariantsAjax rejects Concern 1 batch 1501');

$controller->lookupBatchVariantsAjax('1502');
assertSame4(200, Response::$lastJsonStatus, 'lookupBatchVariantsAjax allows Concern 2 batch 1502');
assertSame4(502, (int)(Response::$lastJson['variants'][0]['variant_id'] ?? 0), 'lookupBatchVariantsAjax returns variant 502');

// Reject cross-concern warehouse / batch / unit / outward / variant
$_POST = ['warehouse_id' => 31, 'variant_id' => 502, 'damage_reason' => 'Stain'];
$rwCountBefore = count(Phase4Db::$reworks);
$controller->storeWarehouseRework();
assertSame4($rwCountBefore, count(Phase4Db::$reworks), 'storeWarehouseRework blocks cross-concern warehouse 31');

$_POST = ['warehouse_id' => 32, 'production_batch_id' => 1501, 'variant_id' => 502, 'damage_reason' => 'Stain'];
$controller->storeWarehouseRework();
assertSame4($rwCountBefore, count(Phase4Db::$reworks), 'storeWarehouseRework blocks cross-concern batch 1501');

$_POST = ['warehouse_id' => 32, 'production_unit_id' => 10, 'variant_id' => 502, 'damage_reason' => 'Stain'];
$controller->storeWarehouseRework();
assertSame4($rwCountBefore, count(Phase4Db::$reworks), 'storeWarehouseRework blocks cross-concern unit 10');

$_POST = ['warehouse_id' => 32, 'production_batch_id' => 1502, 'variant_id' => 501, 'damage_reason' => 'Stain'];
$controller->storeWarehouseRework();
assertSame4($rwCountBefore, count(Phase4Db::$reworks), 'storeWarehouseRework blocks cross-concern variant 501');

// Valid own-concern rework succeeds & assigns non-colliding RWK-WH number
$_POST = ['warehouse_id' => 32, 'production_batch_id' => 1502, 'production_unit_id' => 20, 'variant_id' => 502, 'quantity' => 1, 'damage_reason' => 'Loose seam'];
$controller->storeWarehouseRework();
assertSame4($rwCountBefore + 1, count(Phase4Db::$reworks), 'storeWarehouseRework creates rework for active concern');
$newRework = end(Phase4Db::$reworks);
assertSame4("RWK-WH-{$today}-0005", $newRework['rework_number'], 'ProductionWarehouseRework::create uses non-colliding RWK-WH number');

// Cross-concern deleteWarehouseRework must NOT delete Concern 1 rework 1701
$controller->deleteWarehouseRework('1701');
assertTrue4(isset(Phase4Db::$reworks[1701]), 'deleteWarehouseRework must never delete Concern 1 rework 1701');

$controller->deleteWarehouseRework('1702');
assertTrue4(!isset(Phase4Db::$reworks[1702]), 'deleteWarehouseRework deletes own Concern 2 rework 1702');

echo "PASS: Phase 4 Expenses, Pipeline guards/dropdowns/sequences, Labels, and Rework checks passed.\n";

