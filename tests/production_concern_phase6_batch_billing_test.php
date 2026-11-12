<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

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
        return array_key_exists($key, self::$data) || array_key_exists($key, self::$flash);
    }

    public static function remove(string $key): void
    {
        unset(self::$data[$key]);
    }

    public static function flash(string $key, ?string $message = null): mixed
    {
        if ($message !== null) {
            self::$flash[$key] = $message;
            return null;
        }
        $val = self::$flash[$key] ?? null;
        unset(self::$flash[$key]);
        return $val;
    }
}

final class CSRF
{
    public static function validateRequest(): bool
    {
        return true;
    }

    public static function field(): string
    {
        return '';
    }
}

final class Auth
{
    public static function id(): ?int
    {
        return Session::userId();
    }

    public static function user(): ?array
    {
        return Session::user();
    }
}

final class Concern
{
    public static array $concerns = [
        1 => ['id' => 1, 'name' => 'MAIN FACTORY', 'code' => 'C1', 'is_active' => 1, 'description' => 'Factory Unit'],
        2 => ['id' => 2, 'name' => 'SULUR SHOP', 'code' => 'C2', 'is_active' => 1, 'description' => 'Retail Shop'],
        3 => ['id' => 3, 'name' => 'OTHER BRANCH', 'code' => 'C3', 'is_active' => 1, 'description' => 'Third Branch'],
    ];

    public static function getActiveForCurrentUser(): ?array
    {
        $id = (int)Session::get('active_concern_id', 0);
        return self::$concerns[$id] ?? null;
    }

    public static function hasFacilityModules(int $concernId, string $facilityType): bool
    {
        return strtoupper($facilityType) === 'PRODUCTION';
    }
}

final class ProductionUnit
{
    public static array $units = [
        1 => ['id' => 1, 'concern_id' => 1, 'name' => 'Factory Unit 1', 'code' => 'PU1', 'is_active' => 1],
    ];

    public static function find(int $id): ?array
    {
        return self::$units[$id] ?? null;
    }

    public static function getAll(array $filters = []): array
    {
        $cid = (int)Session::get('active_concern_id', 0);
        return array_values(array_filter(self::$units, static fn(array $u): bool => (int)$u['concern_id'] === $cid));
    }
}

final class Response
{
    public static ?array $lastJson = null;
    public static int $lastStatus = 200;
    public static ?array $lastView = null;
    public static ?string $lastRedirect = null;

    public static function reset(): void
    {
        self::$lastJson = null;
        self::$lastStatus = 200;
        self::$lastView = null;
        self::$lastRedirect = null;
    }

    public static function json(array $data, int $status = 200): void
    {
        self::$lastStatus = $status;
        self::$lastJson = $data;
    }

    public static function error(int $code, string $message = ''): void
    {
        self::$lastStatus = $code;
        self::$lastJson = ['error' => $message];
    }

    public static function view(string $view, array $data = []): void
    {
        self::$lastView = ['view' => $view, 'data' => $data];
    }

    public static function viewOnly(string $view, array $data = []): void
    {
        self::$lastView = ['view' => $view, 'data' => $data];
    }

    public static function back(): void
    {
        self::$lastRedirect = 'BACK';
    }

    public static function redirect(string $url, ?string $flashKey = null, ?string $flashMsg = null): void
    {
        self::$lastRedirect = $url;
        if ($flashKey !== null && $flashMsg !== null) {
            Session::flash($flashKey, $flashMsg);
        }
    }
}

// Real AuditService, SettingsService, ProductionProgram, and QCBatch are loaded via ProductionController.php

final class Database
{
    public static array $queries = [];
    public static int $nextId = 200;
    public static array $batches = [];
    public static array $batchItems = [];
    public static array $outwards = [];
    public static array $bills = [];
    public static array $billItems = [];
    public static array $billTags = [];
    public static array $inwards = [];
    public static array $inwardItems = [];
    public static array $purchases = [];
    public static array $purchaseItems = [];
    public static array $sellers = [];
    public static array $cashTx = [];
    public static int $sellerRollInsertCount = 0;
    public static int $stockMutationCount = 0;
    public static bool $tx = false;

    public static function reset(): void
    {
        self::$queries = [];
        self::$nextId = 200;
        self::$batches = [];
        self::$batchItems = [];
        self::$outwards = [];
        self::$bills = [];
        self::$billItems = [];
        self::$billTags = [];
        self::$inwards = [];
        self::$inwardItems = [];
        self::$purchases = [];
        self::$purchaseItems = [];
        self::$sellers = [
            10 => ['id' => 10, 'concern_id' => 2, 'linked_concern_id' => 1, 'seller_type' => 'MATERIAL_SUPPLIER', 'name' => 'MAIN FACTORY', 'company' => 'MAIN FACTORY', 'is_active' => 1],
        ];
        self::$cashTx = [];
        self::$sellerRollInsertCount = 0;
        self::$stockMutationCount = 0;
        self::$tx = false;
    }

    public static function inTransaction(): bool { return self::$tx; }
    public static function beginTransaction(): void { self::$tx = true; }
    public static function commit(): void { self::$tx = false; }
    public static function rollback(): void { self::$tx = false; }
    public static function lastInsertId(): string
    {
        return (string)self::$nextId;
    }

    public static function execute(string $sql, array $params = []): int
    {
        self::$queries[] = ['sql' => $sql, 'params' => $params];

        if (str_contains($sql, 'INSERT INTO seller_rolls')) {
            self::$sellerRollInsertCount++;
            self::$nextId++;
            return 1;
        }
        if (str_contains($sql, 'stock_balances')) {
            self::$stockMutationCount++;
            return 1;
        }
        if (str_contains($sql, 'INSERT INTO sellers')) {
            self::$nextId++;
            self::$sellers[self::$nextId] = [
                'id' => self::$nextId,
                'concern_id' => (int)$params[0],
                'linked_concern_id' => (int)$params[1],
                'seller_type' => 'MATERIAL_SUPPLIER',
                'name' => (string)$params[3],
                'company' => (string)$params[4],
                'is_active' => 1,
            ];
            return 1;
        }
        if (str_contains($sql, 'INSERT INTO production_batches')) {
            self::$nextId++;
            $id = self::$nextId;
            self::$batches[$id] = [
                'id' => $id,
                'batch_number' => $params[0],
                'program_id' => $params[1],
                'qc_batch_id' => $params[2],
                'source_type' => $params[3],
                'source_location_type' => $params[4],
                'source_location_id' => $params[5],
                'total_qty' => $params[6],
                'box_count' => $params[7],
                'destination_warehouse_id' => $params[8],
                'destination_concern_id' => $params[9],
                'sale_bill_id' => $params[10],
                'concern_id' => $params[11],
                'status' => $params[12],
                'prepared_by' => $params[13],
                'source_concern_name' => Concern::$concerns[(int)$params[11]]['name'] ?? '',
                'destination_concern_name' => Concern::$concerns[(int)$params[9]]['name'] ?? '',
            ];
            return 1;
        }
        if (str_contains($sql, 'INSERT INTO production_batch_items')) {
            self::$nextId++;
            self::$batchItems[] = [
                'id' => self::$nextId,
                'production_batch_id' => (int)$params[0],
                'variant_id' => (int)$params[1],
                'qc_item_id' => $params[2],
                'source_kind' => $params[3],
                'source_location_type' => $params[4],
                'source_location_id' => $params[5],
                'quantity' => (int)$params[6],
                'piece_tags' => $params[7],
            ];
            return 1;
        }
        if (str_contains($sql, 'UPDATE production_batches') && str_contains($sql, 'SET sale_bill_id = ?')) {
            $id = (int)$params[1];
            if (isset(self::$batches[$id])) {
                self::$batches[$id]['sale_bill_id'] = (int)$params[0];
            }
            return 1;
        }
        if (str_contains($sql, 'UPDATE production_batches') && str_contains($sql, "SET status = 'APPROVED'")) {
            $id = (int)$params[1];
            if (isset(self::$batches[$id])) {
                self::$batches[$id]['status'] = 'APPROVED';
            }
            return 1;
        }
        if (str_contains($sql, 'UPDATE production_batches SET status = ?')) {
            $id = (int)$params[1];
            if (isset(self::$batches[$id])) {
                self::$batches[$id]['status'] = $params[0];
            }
            return 1;
        }
        if (str_contains($sql, 'INSERT INTO production_outwards')) {
            self::$nextId++;
            $id = self::$nextId;
            self::$outwards[$id] = [
                'id' => $id,
                'outward_number' => $params[0],
                'production_batch_id' => (int)$params[1],
                'concern_id' => (int)$params[3],
                'destination_concern_id' => (int)$params[5],
                'status' => 'DISPATCHED',
            ];
            return 1;
        }
        if (str_contains($sql, 'INSERT INTO production_bills')) {
            self::$nextId++;
            $id = self::$nextId;
            self::$bills[$id] = [
                'id' => $id,
                'bill_number' => $params[0],
                'concern_id' => (int)$params[1],
                'bill_source' => 'BATCH_OUTWARD',
                'source_batch_id' => (int)$params[2],
                'destination_concern_id' => (int)$params[3],
                'production_unit_id' => $params[4],
                'buyer_type' => $params[5],
                'company_name' => $params[6],
                'customer_name' => $params[11],
                'bill_date' => $params[17],
                'payment_method' => $params[18],
                'payment_status' => $params[19],
                'cash_amount' => (float)$params[21],
                'upi_amount' => (float)$params[22],
                'total_amount' => (float)$params[31],
            ];
            return 1;
        }
        if (str_contains($sql, 'INSERT INTO production_bill_items')) {
            self::$nextId++;
            self::$billItems[] = [
                'id' => self::$nextId,
                'bill_id' => (int)$params[0],
                'qc_item_id' => $params[1],
                'variant_id' => (int)$params[2],
                'product_name' => $params[3],
                'sku' => $params[4],
                'size_name' => $params[5],
                'color_name' => $params[6],
                'quantity' => (int)$params[7],
                'unit_price' => (float)$params[9],
                'gst_type' => $params[13],
                'tax_input_type' => $params[14],
                'sgst_rate' => (float)$params[15],
                'cgst_rate' => (float)$params[16],
                'igst_rate' => (float)$params[17],
                'total_amount' => (float)$params[23],
            ];
            return 1;
        }
        if (str_contains($sql, 'INSERT INTO production_bill_item_tags')) {
            self::$nextId++;
            self::$billTags[] = [
                'id' => self::$nextId,
                'bill_id' => (int)$params[0],
                'bill_item_id' => (int)$params[1],
                'concern_id' => (int)$params[2],
                'variant_id' => (int)$params[3],
                'tag_code' => (string)$params[4],
            ];
            return 1;
        }
        if (str_contains($sql, 'INSERT INTO production_purchases') && !str_contains($sql, 'production_purchase_items')) {
            self::$nextId++;
            $id = self::$nextId;
            self::$purchases[$id] = [
                'id' => $id,
                'purchase_number' => $params[0],
                'concern_id' => (int)$params[1],
                'production_unit_id' => $params[2],
                'source_batch_id' => $params[3],
                'source_sale_bill_id' => $params[4],
                'source_inward_id' => $params[5],
                'seller_id' => (int)$params[6],
                'purchase_date' => $params[7],
                'invoice_number' => $params[8],
                'total_amount' => (float)$params[11],
            ];
            return 1;
        }
        if (str_contains($sql, 'UPDATE production_purchases SET purchase_number = ?')) {
            $id = (int)$params[1];
            if (isset(self::$purchases[$id])) {
                self::$purchases[$id]['purchase_number'] = $params[0];
            }
            return 1;
        }
        if (str_contains($sql, 'INSERT INTO production_purchase_items')) {
            self::$nextId++;
            self::$purchaseItems[] = [
                'id' => self::$nextId,
                'purchase_id' => (int)$params[0],
                'variant_id' => $params[1],
                'item_type' => $params[2],
                'item_name' => $params[3],
                'sku' => $params[4],
                'size_name' => $params[5],
                'color_name' => $params[6],
                'quantity' => (float)$params[11],
                'unit' => $params[12],
                'unit_price' => (float)$params[14],
                'total_price' => (float)$params[24],
            ];
            return 1;
        }
        return 1;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        self::$queries[] = ['sql' => $sql, 'params' => $params];

        if (str_contains($sql, 'SELECT id FROM users LIMIT 1')) {
            return ['id' => 9];
        }
        if (str_contains($sql, 'FROM concerns c') && str_contains($sql, "module_key = 'production_inward'")) {
            $cid = (int)$params[0];
            return Concern::$concerns[$cid] ?? null;
        }
        if (str_contains($sql, 'FROM concerns WHERE id = ?')) {
            return Concern::$concerns[(int)$params[0]] ?? null;
        }
        if (str_contains($sql, 'FROM production_units') && str_contains($sql, 'WHERE id = ?')) {
            $uid = (int)$params[0];
            $u = ProductionUnit::$units[$uid] ?? null;
            if (!$u) return null;
            if (array_key_exists(1, $params) && (int)$u['concern_id'] !== (int)$params[1]) {
                return null;
            }
            return $u;
        }
        if (str_contains($sql, 'FROM qc_batches qb') && str_contains($sql, 'WHERE qb.id = ? AND pu.concern_id = ?')) {
            return ((int)$params[0] === 10 && (int)$params[1] === 1) ? ['id' => 10, 'production_unit_id' => 1] : null;
        }
        if (str_contains($sql, 'FROM qc_items WHERE id = ?') && str_contains($sql, 'SELECT base_price')) {
            return null;
        }
        if (str_contains($sql, 'SELECT id FROM product_variants WHERE id = ?')) {
            return ['id' => (int)$params[0]];
        }
        if (str_contains($sql, 'FROM qc_batches WHERE id = ?')) {
            return ['id' => (int)$params[0], 'program_id' => 1, 'passed_qty' => 2, 'pieces_data' => json_encode([
                ['item_id' => 55, 'tag_code' => 'QR-QC-101', 'status' => 'APPROVED'],
                ['item_id' => 55, 'tag_code' => 'QR-QC-102', 'status' => 'APPROVED'],
            ])];
        }
        if (str_contains($sql, 'FROM qc_batches qb') && str_contains($sql, 'WHERE qb.id = ?')) {
            return ['id' => (int)$params[0], 'concern_id' => 1, 'program_id' => 1, 'passed_qty' => 2, 'pieces_data' => json_encode([
                ['item_id' => 55, 'tag_code' => 'QR-QC-101', 'status' => 'APPROVED'],
                ['item_id' => 55, 'tag_code' => 'QR-QC-102', 'status' => 'APPROVED'],
            ])];
        }
        if (str_contains($sql, 'FROM qc_items WHERE qc_batch_id = ?') && str_contains($sql, 'SUM(passed_qty)')) {
            return ['total' => 4];
        }
        if (str_contains($sql, 'FROM production_batch_items pbi') && str_contains($sql, 'COALESCE(SUM(pbi.quantity), 0)')) {
            return ['total' => 0];
        }
        if (str_contains($sql, 'FROM production_batches pb') && str_contains($sql, 'pb.concern_id = ? OR pb.destination_concern_id = ?')) {
            $bid = (int)$params[0];
            $cid = (int)$params[1];
            $b = self::$batches[$bid] ?? null;
            if (!$b) {
                return null;
            }
            if ((int)$b['concern_id'] !== $cid && (int)($b['destination_concern_id'] ?? 0) !== $cid) {
                return null;
            }
            return array_merge($b, ['source_concern_id' => $b['concern_id']]);
        }
        if (str_contains($sql, 'FROM production_batches pb') && str_contains($sql, 'pb.destination_concern_id = ?')) {
            $bid = (int)$params[0];
            $cid = (int)$params[1];
            $b = self::$batches[$bid] ?? null;
            if (!$b || (int)($b['destination_concern_id'] ?? 0) !== $cid || !in_array($b['status'], ['DISPATCHED', 'RECEIVED'], true)) {
                return null;
            }
            $bill = self::$bills[(int)($b['sale_bill_id'] ?? 0)] ?? [];
            return array_merge($b, [
                'bill_number' => $bill['bill_number'] ?? null,
                'bill_date' => $bill['bill_date'] ?? null,
                'sale_bill_total' => $bill['total_amount'] ?? null,
                'source_concern_name' => Concern::$concerns[(int)$b['concern_id']]['name'] ?? '',
            ]);
        }
        if (str_contains($sql, 'FROM production_batches pb') && str_contains($sql, 'WHERE pb.id = ?')) {
            $id = (int)$params[0];
            $b = self::$batches[$id] ?? null;
            if (!$b) {
                return null;
            }
            if (array_key_exists(1, $params)) {
                return (int)$b['concern_id'] === (int)$params[1] ? $b : null;
            }
            return $b;
        }
        if (str_contains($sql, 'FROM production_bills pb') && str_contains($sql, 'WHERE pb.id = ? AND pb.concern_id = ?')) {
            return self::$bills[(int)$params[0]] ?? null;
        }
        if (str_contains($sql, 'FROM production_bills') && str_contains($sql, 'WHERE id = ? AND concern_id = ?')) {
            $b = self::$bills[(int)$params[0]] ?? null;
            return ($b && (int)$b['concern_id'] === (int)$params[1]) ? $b : null;
        }
        if (str_contains($sql, 'FROM production_bills') && str_contains($sql, 'WHERE id = ?')) {
            return self::$bills[(int)$params[0]] ?? null;
        }
        if (str_contains($sql, 'FROM production_purchases') && str_contains($sql, 'source_batch_id = ?')) {
            $cid = (int)$params[0];
            $bid = (int)$params[1];
            foreach (self::$purchases as $p) {
                if ((int)$p['concern_id'] === $cid && (int)$p['source_batch_id'] === $bid) {
                    return $p;
                }
            }
            return null;
        }
        if (str_contains($sql, 'FROM production_inwards')) {
            $cid = (int)$params[0];
            $bid = (int)end($params);
            foreach (self::$inwards as $inw) {
                if ((int)$inw['concern_id'] === $cid && (int)$inw['source_id'] === $bid && $inw['status'] === 'RECEIVED') {
                    return $inw;
                }
            }
            return null;
        }
        if (str_contains($sql, 'FROM sellers') && str_contains($sql, 'linked_concern_id')) {
            $cid = (int)$params[0];
            $linked = (int)$params[1];
            foreach (self::$sellers as $s) {
                if ((int)$s['concern_id'] === $cid && (int)$s['linked_concern_id'] === $linked) {
                    return $s;
                }
            }
            return null;
        }
        if (str_contains($sql, 'FROM sellers') && str_contains($sql, 'WHERE id = ? AND seller_type')) {
            $sid = (int)$params[0];
            $cid = (int)$params[1];
            $s = self::$sellers[$sid] ?? null;
            return ($s && (int)$s['concern_id'] === $cid) ? $s : null;
        }
        if (str_contains($sql, 'FROM production_outwards WHERE production_batch_id = ?')) {
            return null;
        }
        if (str_contains($sql, 'FROM production_programs WHERE id = ?')) {
            return ['production_unit_id' => 1];
        }
        if (str_contains($sql, 'FROM production_batches') && str_contains($sql, 'destination_concern_id = ?') && str_contains($sql, 'status IN')) {
            $bid = (int)$params[0];
            $cid = (int)$params[1];
            $b = self::$batches[$bid] ?? null;
            return ($b && (int)$b['destination_concern_id'] === $cid && in_array($b['status'], ['DISPATCHED', 'RECEIVED'], true)) ? $b : null;
        }
        if (str_contains($sql, 'FROM production_batches WHERE id = ? AND destination_concern_id = ?')) {
            $bid = (int)$params[0];
            $cid = (int)$params[1];
            $b = self::$batches[$bid] ?? null;
            return ($b && (int)$b['destination_concern_id'] === $cid) ? $b : null;
        }
        if (str_contains($sql, 'FROM production_batches WHERE batch_number LIKE') || str_contains($sql, 'outward_number LIKE') || str_contains($sql, 'purchase_number LIKE')) {
            return null;
        }
        return null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        self::$queries[] = ['sql' => $sql, 'params' => $params];

        if (str_contains($sql, 'FROM qc_items qi') && str_contains($sql, 'JOIN qc_batches qb')) {
            // Two QC pieces in Concern 1
            return [[
                'id' => 55,
                'qc_item_id' => 55,
                'qc_batch_id' => 10,
                'program_id' => 1,
                'program_number' => 'PRG-001',
                'product_id' => 7,
                'product_name' => 'Maternity Kurti',
                'variant_id' => 11,
                'sku' => 'MK-M-BLK',
                'size_name' => 'M',
                'color_name' => 'BLACK',
                'stock_location_type' => 'PRODUCTION_UNIT',
                'stock_location_id' => 1,
                'location_label' => 'Factory Unit 1',
                'mrp' => 800,
                'selling_price' => 650,
                'qc_base_price' => 800,
                'qc_discount_price' => 650,
                'gst_percentage' => 5.0,
                'passed_qty' => 2,
                'piece_tags' => json_encode(['QR-QC-101', 'QR-QC-102']),
            ]];
        }
        if (str_contains($sql, 'FROM qc_items qi') && str_contains($sql, 'WHERE qi.qc_batch_id = ?')) {
            return [[
                'id' => 55,
                'qc_batch_id' => 10,
                'variant_id' => 11,
                'passed_qty' => 2,
                'piece_tags' => json_encode(['QR-QC-101', 'QR-QC-102']),
            ]];
        }
        if (str_contains($sql, 'FROM stock_balances sb') && str_contains($sql, 'JOIN product_variants pv')) {
            return [];
        }
        if (str_contains($sql, 'FROM production_bill_item_tags t') && !str_contains($sql, 'qi.qc_batch_id')) {
            $cid = (int)$params[0];
            $rows = [];
            foreach (self::$billTags as $tag) {
                if ((int)$tag['concern_id'] === $cid) {
                    $rows[] = ['tag_code' => $tag['tag_code']];
                }
            }
            return $rows;
        }
        if (str_contains($sql, 'FROM production_inventory_damages d')) {
            return [];
        }
        if (str_contains($sql, 'FROM production_batch_items pbi') && str_contains($sql, 'JOIN production_batches pb ON pb.id = pbi.production_batch_id')) {
            if (str_contains($sql, 'GROUP BY pbi.variant_id')) {
                $cid = (int)$params[0];
                $qcId = (int)$params[1];
                $grouped = [];
                foreach (self::$batchItems as $bi) {
                    $b = self::$batches[(int)$bi['production_batch_id']] ?? null;
                    if (!$b || (int)$b['concern_id'] !== $cid || $b['status'] === 'CANCELLED') {
                        continue;
                    }
                    $matchesQc = ((int)($b['qc_batch_id'] ?? 0) === $qcId)
                        || (($b['source_type'] ?? '') === 'SCAN' && ($bi['source_kind'] ?? '') === 'QC' && (int)($bi['qc_item_id'] ?? 0) === 55);
                    if ($matchesQc) {
                        $vid = (int)$bi['variant_id'];
                        $grouped[$vid] = ($grouped[$vid] ?? 0) + (int)$bi['quantity'];
                    }
                }
                $out = [];
                foreach ($grouped as $vid => $qty) {
                    $out[] = ['variant_id' => $vid, 'packed_qty' => $qty];
                }
                return $out;
            }
            $out = [];
            foreach (self::$batchItems as $bi) {
                $b = self::$batches[(int)$bi['production_batch_id']] ?? null;
                $out[] = array_merge($bi, ['qc_batch_id' => $b['qc_batch_id'] ?? null]);
            }
            return $out;
        }
        if (str_contains($sql, 'FROM production_batch_items') && str_contains($sql, 'WHERE production_batch_id = ?')) {
            $bid = (int)$params[0];
            return array_values(array_filter(self::$batchItems, static fn(array $bi): bool => (int)$bi['production_batch_id'] === $bid));
        }
        if (str_contains($sql, 'FROM production_bill_items') && str_contains($sql, 'bill_id = ?')) {
            $billId = (int)$params[0];
            return array_values(array_filter(self::$billItems, static fn(array $it): bool => (int)$it['bill_id'] === $billId));
        }
        if (str_contains($sql, 'FROM production_inward_items WHERE inward_id = ?')) {
            $inwId = (int)$params[0];
            return self::$inwardItems[$inwId] ?? [];
        }
        if (str_contains($sql, 'FROM production_bills') && str_contains($sql, 'bill_source')) {
            return [];
        }
        return [];
    }
}

function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function url(string $path = ''): string
{
    return 'http://localhost/' . ltrim($path, '/');
}
function hasPermission(string $m, string $a): bool
{
    return true;
}
function currentConcernId(): ?int
{
    return Session::has('active_concern_id') ? (int)Session::get('active_concern_id') : null;
}
function currentProductionUnitId(): ?int
{
    return null;
}
function getScopedProductionUnits(): array
{
    return ProductionUnit::getAll();
}

require_once BASE_PATH . '/app/Models/ProductionInventoryDamage.php';
require_once BASE_PATH . '/app/Services/BatchBillingService.php';
require_once BASE_PATH . '/app/Controllers/ProductionBillingController.php';
require_once BASE_PATH . '/app/Controllers/ProductionController.php';

function assertSame6(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$label}\nExpected: " . var_export($expected, true) . "\nActual:   " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrue6(bool $cond, string $label): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$label}\n");
        exit(1);
    }
}

// =========================================================================
// 1. Batch QC mode + Sale Bill (COMPANY) + Immediate Dispatch Outward
// =========================================================================
Database::reset();
Session::reset();
Response::reset();
$_POST = [];
Session::set('user', ['id' => 9, 'is_super_admin' => 0, 'role_name' => 'factory_mgr']);
Session::set('active_concern_id', 1);

$resultQc = BatchBillingService::createBatchFromRequest([
    'source_mode' => 'qc',
    'qc_batch_id' => 10,
    'destination_concern_id' => 2,
    'box_count' => 2,
    'generate_sale_bill' => '1',
    'dispatch_now' => '1',
    'vehicle_number' => 'TN 39 AA 1234',
    'driver_name' => 'Karthik',
    'driver_contact' => '9876543210',
    'transporter_name' => 'Self Tempo',
    'dispatch_date' => '2026-11-12 10:30:00',
    'buyer_type' => 'COMPANY',
    'company_name' => 'SULUR SHOP',
    'bill_date' => '2026-11-12',
    'payment_method' => 'CREDIT',
    'items' => [
        0 => [
            'qc_item_id' => 55,
            'variant_id' => 11,
            'piece_tags' => ['QR-QC-101'],
            'quantity' => 1,
            'unit_price' => 600.00,
            'gst_type' => 'BOTH',
            'tax_input_type' => 'PERCENT',
            'cgst_rate' => 2.5,
            'sgst_rate' => 2.5,
        ],
    ],
], 9);

$qcBatchId = (int)$resultQc['batch_id'];
$qcBillId = (int)$resultQc['sale_bill_id'];
assertTrue6($qcBatchId > 0 && $qcBillId > 0 && !empty($resultQc['outward_id']), 'QC batch + Sale Bill + immediate outward returns IDs');
assertSame6('DISPATCHED', Database::$batches[$qcBatchId]['status'], 'dispatch_now marks batch DISPATCHED');
assertSame6($qcBillId, Database::$batches[$qcBatchId]['sale_bill_id'], 'Batch links back to sale_bill_id');
assertSame6(630.0, Database::$bills[$qcBillId]['total_amount'], 'Batch Sale Bill computes 600 + 5% GST = 630.00');
assertSame6(2, Database::$bills[$qcBillId]['destination_concern_id'], 'Sale Bill stores destination_concern_id');

// Remaining QC qty reflects the 1 piece packed by QC batch
$packedMap = BatchBillingService::getPackedQtyMapForQCBatch(10, 1);
assertSame6(1, $packedMap[11] ?? 0, 'getPackedQtyMapForQCBatch counts packed QC piece');

// =========================================================================
// 2. SCAN mode + Sale Bill (CUSTOMER) + Delayed Outward + Packed Qty Tracking
// =========================================================================
$resultScan = BatchBillingService::createBatchFromRequest([
    'source_mode' => 'scan',
    'destination_concern_id' => 2,
    'box_count' => 1,
    'generate_sale_bill' => '1',
    'dispatch_now' => '0',
    'buyer_type' => 'CUSTOMER',
    'customer_name' => 'Retail Buyer',
    'customer_phone' => '9876500000',
    'bill_date' => '2026-11-12',
    'payment_method' => 'CASH',
    'scan_items' => [
        [
            'item_key' => 'QC:55',
            'qc_item_id' => 55,
            'tags' => ['QR-QC-102'],
            'unit_price' => 500.00,
            'gst_type' => 'EXEMPTED',
            'tax_input_type' => 'PERCENT',
        ],
    ],
], 9);

$scanBatchId = (int)$resultScan['batch_id'];
assertSame6('SCAN', Database::$batches[$scanBatchId]['source_type'], 'Scan batch stores source_type SCAN');
assertSame6('PREPARING', Database::$batches[$scanBatchId]['status'], 'Delayed outward scan batch stops at PREPARING awaiting approval/outward');
assertSame6(null, $resultScan['outward_id'], 'Delayed outward returns null outward_id');

$packedMapAfterScan = BatchBillingService::getPackedQtyMapForQCBatch(10, 1);
assertSame6(2, $packedMapAfterScan[11] ?? 0, 'getPackedQtyMapForQCBatch includes SCAN QC piece toward packed qty');

// Double-scan of QR-QC-102 fails closed
$doubleScanRejected = false;
try {
    BatchBillingService::createBatchFromRequest([
        'source_mode' => 'scan',
        'destination_concern_id' => 2,
        'box_count' => 1,
        'generate_sale_bill' => '0',
        'scan_items' => [['item_key' => 'QC:55', 'qc_item_id' => 55, 'tags' => ['QR-QC-102']]],
    ], 9);
} catch (InvalidArgumentException $ex) {
    $doubleScanRejected = true;
}
assertTrue6($doubleScanRejected, 'Scanning an already packed/billed QR tag fails closed');

// =========================================================================
// 3. Per-Concern Tag Isolation & Cross-Concern Sale Bill Authorization
// =========================================================================
// Sender (Concern 1) sees its billed tags as sold in Concern 1
$soldInC1 = ProductionBillingService::getSoldTagSet(0);
assertTrue6(isset($soldInC1['QR-QC-101']) && isset($soldInC1['QR-QC-102']), 'Sender concern sees batch-billed QR tags as sold');

// Switch to Receiver (Concern 2 = SULUR SHOP): tags billed TO Sulur Shop are NOT marked sold IN Sulur Shop!
Session::set('active_concern_id', 2);
$soldInC2 = ProductionBillingService::getSoldTagSet(0);
assertTrue6(!isset($soldInC2['QR-QC-101']), 'Per-concern sold tag isolation lets receiving concern later retail-sell QR-QC-101');

// Receiver can view Sale Bill and is marked isReceiver = true
$authReceiver = BatchBillingService::findAuthorizedBatchSaleBill($qcBatchId);
assertTrue6($authReceiver !== null && $authReceiver['isReceiver'] === true, 'Receiving concern is authorized to view sender Sale Bill');

// Unrelated 3rd concern is blocked from viewing Sale Bill or prefilling Purchase Bill
Session::set('active_concern_id', 3);
assertSame6(null, BatchBillingService::findAuthorizedBatchSaleBill($qcBatchId), 'Unrelated 3rd concern cannot access batch Sale Bill');
assertSame6(null, BatchBillingService::getBatchPurchasePrefill($qcBatchId), 'Unrelated 3rd concern cannot prefill purchase from batch');

// =========================================================================
// 4. ProductionBillingController guards BATCH_OUTWARD Edit / Delete
// =========================================================================
Session::set('active_concern_id', 1);
$billCtrl = new ProductionBillingController();
Response::reset();
$billCtrl->editBill((string)$qcBillId);
assertTrue6(str_contains((string)Response::$lastRedirect, 'production/billing/view/'), 'Standalone editBill redirects to view for BATCH_OUTWARD bill');
assertTrue6(str_contains((string)Session::flash('error'), 'Finished Production Batches'), 'Standalone editBill flashes error for BATCH_OUTWARD');

Response::reset();
$billCtrl->deleteBill((string)$qcBillId);
assertTrue6(str_contains((string)Response::$lastRedirect, 'production/billing/view/'), 'Standalone deleteBill redirects to view for BATCH_OUTWARD bill');
assertTrue6(str_contains((string)Session::flash('error'), 'Finished Production Batches'), 'Standalone deleteBill flashes error for BATCH_OUTWARD');

// =========================================================================
// 5. Receiving Concern Purchase Prefill (Partial Receipt Good Qty) & Store
// =========================================================================
Session::set('active_concern_id', 2);
Database::$inwards[301] = ['id' => 301, 'concern_id' => 2, 'source_type' => 'PRODUCTION_BATCH', 'source_id' => $qcBatchId, 'status' => 'RECEIVED', 'grn_number' => 'GRN-SUL-301'];
Database::$inwardItems[301] = [['variant_id' => 11, 'quantity' => 1, 'damaged_qty' => 0, 'missing_qty' => 0, 'excess_qty' => 0]];

$prefill = BatchBillingService::getBatchPurchasePrefill($qcBatchId, 301);
assertTrue6($prefill !== null && count($prefill['prefillItems']) === 1, 'Receiving concern gets Sale Bill items prefilled for Purchase Bill');
assertSame6(1, $prefill['prefillItems'][0]['quantity'], 'Prefill quantity defaults to received good quantity');
assertSame6(600.0, $prefill['prefillItems'][0]['unit_price'], 'Prefill copies Sale Bill unit price');
assertSame6(10, (int)($prefill['matchedSeller']['id'] ?? 0), 'Prefill auto-matches linked sender concern supplier');

// Store FINISHED_GOODS Purchase Bill with __AUTO_CONCERN_SUPPLIER__ (and verify NO rolls/stock double-post)
$prodCtrl = new ProductionController();
$rollCountBefore = Database::$sellerRollInsertCount;
$stockCountBefore = Database::$stockMutationCount;
Response::reset();
$_POST = [
    'source_batch_id' => (string)$qcBatchId,
    'source_sale_bill_id' => (string)$qcBillId,
    'source_inward_id' => '301',
    'seller_id' => '__AUTO_CONCERN_SUPPLIER__',
    'purchase_date' => '2026-11-12',
    'invoice_number' => Database::$bills[$qcBillId]['bill_number'],
    'notes' => 'Sulur Purchase Entry',
    'items' => [
        'fg_0' => [
            'item_type' => 'FINISHED_GOODS',
            'variant_id' => '11',
            'sku' => 'MK-M-BLK',
            'size_name' => 'M',
            'color_name' => 'BLACK',
            'item_name' => 'MATERNITY KURTI',
            'unit' => 'PCS',
            'quantity' => '1',
            'unit_price' => '600',
            'gst_type' => 'BOTH',
            'tax_mode' => 'PERCENT',
            'tax_value' => '2.5',
            'cgst_tax_value' => '2.5',
        ],
    ],
];
$_FILES = [];
$prodCtrl->storeProductionPurchase();
assertSame6($rollCountBefore, Database::$sellerRollInsertCount, 'FINISHED_GOODS purchase creates zero seller_rolls');
assertSame6($stockCountBefore, Database::$stockMutationCount, 'FINISHED_GOODS purchase does not double-credit stock_balances');
assertTrue6(count(Database::$purchases) === 1, 'Inter-concern FINISHED_GOODS Purchase Bill saved cleanly');

// =========================================================================
// 6. Routes & Schema File Static Verification
// =========================================================================
$routesSql = file_get_contents(BASE_PATH . '/routes/web.php');
foreach ([
    'api/production/batches/search',
    'production/batches/sale-bill/print/{id}',
    'production/batches/sale-bill/{id}',
    'production/inward/sale-bill/print/{id}',
    'production/inward/sale-bill/{id}',
    'production/purchases/sale-bill/print/{id}',
    'production/purchases/sale-bill/{id}',
] as $routePattern) {
    assertTrue6(str_contains($routesSql, $routePattern), "Route {$routePattern} is registered in routes/web.php");
}

foreach (['schema.sql', 'schema_fresh.sql', 'migrations/hostinger_live_upgrade.sql'] as $schemaFile) {
    $content = file_get_contents(BASE_PATH . '/database/' . $schemaFile);
    assertTrue6(str_contains($content, 'uniq_production_bill_concern_tag'), "{$schemaFile} includes per-concern uniq_production_bill_concern_tag");
    assertTrue6(str_contains($content, 'FINISHED_GOODS'), "{$schemaFile} includes FINISHED_GOODS purchase item type");
    assertTrue6(str_contains($content, 'sale_bill_id'), "{$schemaFile} includes production_batches.sale_bill_id");
}

// =========================================================================
// 7. Dual Print Format (A4 Tax Invoice + POS Thermal Receipt)
// =========================================================================
Session::set('active_concern_id', 1);
$scanBillId = (int)Database::$batches[$scanBatchId]['sale_bill_id']; // CUSTOMER bill
$_GET = [];
Response::reset();
$billCtrl->printBill((string)$scanBillId);
assertSame6('pos', Response::$lastView['data']['printFormat'] ?? null, 'CUSTOMER bill print defaults to POS thermal receipt format');

$_GET = ['format' => 'a4'];
Response::reset();
$billCtrl->printBill((string)$scanBillId);
assertSame6('a4', Response::$lastView['data']['printFormat'] ?? null, 'Explicit ?format=a4 switches CUSTOMER bill to A4 Tax Invoice');

$_GET = ['format' => 'pos'];
Response::reset();
$billCtrl->printBill((string)$qcBillId); // COMPANY bill
assertSame6('pos', Response::$lastView['data']['printFormat'] ?? null, 'Explicit ?format=pos renders POS receipt even on COMPANY bills');

ob_start();
$bill = Response::$lastView['data']['bill'];
$billItems = Response::$lastView['data']['billItems'];
$sellerCompany = Response::$lastView['data']['sellerCompany'];
$printFormat = 'pos';
$backUrl = Response::$lastView['data']['backUrl'];
$printBaseUrl = Response::$lastView['data']['printBaseUrl'];
include BASE_PATH . '/views/production/billing/print.php';
$posHtml = ob_get_clean();
assertTrue6(str_contains($posHtml, 'Net Amount') && str_contains($posHtml, 'Taxable Base:') && str_contains($posHtml, 'Visit Again'), 'POS receipt view renders expected thermal summary & footer');

ob_start();
$printFormat = 'a4';
include BASE_PATH . '/views/production/billing/print.php';
$a4Html = ob_get_clean();
assertTrue6(str_contains($a4Html, 'TAX INVOICE') && str_contains($a4Html, 'Taxable subtotal'), 'A4 view renders full Tax Invoice layout');

echo "PASS: Phase 6 Inter-Concern Batch Billing, QR Scan Mode, Cross-Concern Sale Bill & Receiving Purchase Bill checks passed.\n";
