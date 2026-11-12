<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

final class TestPhase5Exception extends RuntimeException
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
        1 => ['id' => 1, 'name' => 'MAIN FACTORY', 'code' => 'C1', 'is_active' => 1, 'module_keys' => ['production', 'billing', 'inventory_damages', 'batches', 'outward', 'production_cash', 'reports']],
        2 => ['id' => 2, 'name' => 'SULUR SHOP', 'code' => 'C2', 'is_active' => 1, 'module_keys' => ['production', 'billing', 'inventory_damages', 'batches', 'outward', 'production_cash', 'reports']],
        3 => ['id' => 3, 'name' => 'UNITLESS CONCERN', 'code' => 'C3', 'is_active' => 1, 'module_keys' => ['production', 'billing', 'production_cash', 'reports']],
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
        2 => ['id' => 2, 'concern_id' => 2, 'name' => 'Sulur Unit', 'code' => 'PU2', 'is_active' => 1],
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
        self::$lastJson = $data;
        self::$lastStatus = $status;
    }

    public static function view(string $view, array $data = []): void
    {
        self::$lastView = ['view' => $view, 'data' => $data];
    }

    public static function viewOnly(string $view, array $data = []): void
    {
        self::$lastView = ['view' => $view, 'data' => $data];
    }

    public static function redirect(string $path, ?string $flashKey = null, ?string $flashMsg = null): void
    {
        if ($flashKey !== null && $flashMsg !== null) {
            Session::flash($flashKey, $flashMsg);
        }
        self::$lastRedirect = $path;
    }

    public static function back(): void
    {
        self::$lastRedirect = 'back';
    }

    public static function error(int $code, string $message): void
    {
        throw new TestPhase5Exception($code, $message);
    }
}

final class Database
{
    public static array $queries = [];
    public static array $damages = [];
    public static array $batches = [];
    public static array $batchItems = [];
    public static array $outwards = [];
    public static array $cashRegisters = [];
    public static array $cashTransactions = [];
    public static array $balances = [];
    public static int $nextInsert = 100;
    private static bool $inTx = false;

    public static function reset(): void
    {
        self::$queries = [];
        self::$damages = [];
        self::$batches = [];
        self::$batchItems = [];
        self::$outwards = [];
        self::$cashRegisters = [];
        self::$cashTransactions = [];
        self::$balances = [
            '6|CONCERN|2|AVAILABLE' => 2.0,
            '6|CONCERN|2|RESERVED' => 0.0,
            '6|CONCERN|2|DAMAGED' => 0.0,
        ];
        self::$nextInsert = 100;
        self::$inTx = false;
    }

    public static function balance(int $variantId, string $locationType, int $locationId, string $state = 'AVAILABLE'): float
    {
        return (float)(self::$balances["{$variantId}|{$locationType}|{$locationId}|{$state}"] ?? 0.0);
    }

    public static function beginTransaction(): bool { self::$inTx = true; return true; }
    public static function commit(): bool { self::$inTx = false; return true; }
    public static function rollback(): bool { self::$inTx = false; return true; }
    public static function inTransaction(): bool { return self::$inTx; }
    public static function lastInsertId(): string { return (string)self::$nextInsert; }

    public static function execute(string $sql, array $params = []): int
    {
        self::$queries[] = ['sql' => $sql, 'params' => $params];

        if (str_contains($sql, 'INSERT INTO stock_balances')) {
            $key = "{$params[0]}|{$params[1]}|{$params[2]}|{$params[3]}";
            self::$balances[$key] = (float)$params[4];
            return 1;
        }

        if (str_contains($sql, 'INSERT INTO stock_transactions')) {
            self::$nextInsert++;
            return 1;
        }

        if (str_contains($sql, 'INSERT INTO production_inventory_damages')) {
            self::$nextInsert++;
            $id = self::$nextInsert;
            self::$damages[$id] = [
                'id' => $id,
                'concern_id' => $params[0],
                'damage_number' => $params[1],
                'qc_batch_id' => $params[2],
                'qc_item_id' => $params[3],
                'variant_id' => $params[4],
                'program_id' => $params[5],
                'production_unit_id' => $params[6],
                'stock_location_type' => $params[7],
                'stock_location_id' => $params[8],
                'tag_code' => $params[9],
                'source_stage' => $params[10],
                'damage_reason' => $params[11],
                'defect_notes' => $params[12],
                'photo_url' => $params[13],
                'unit_value' => $params[14],
                'status' => 'LOGGED',
                'reported_by' => $params[15],
            ];
            return 1;
        }

        if (str_contains($sql, 'UPDATE production_inventory_damages') && str_contains($sql, 'damage_reason = ?')) {
            $id = (int)$params[14];
            if (isset(self::$damages[$id])) {
                self::$damages[$id]['damage_number'] = $params[0];
                self::$damages[$id]['stock_location_type'] = $params[6];
                self::$damages[$id]['stock_location_id'] = $params[7];
                self::$damages[$id]['source_stage'] = $params[8];
                self::$damages[$id]['damage_reason'] = $params[9];
                self::$damages[$id]['status'] = 'LOGGED';
            }
            return 1;
        }

        if (str_contains($sql, 'UPDATE production_inventory_damages') && str_contains($sql, '`status` = ?')) {
            $id = (int)end($params);
            if (isset(self::$damages[$id])) {
                self::$damages[$id]['status'] = $params[0];
            }
            return 1;
        }

        if (str_contains($sql, 'INSERT INTO production_batches')) {
            self::$nextInsert++;
            $id = self::$nextInsert;
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
            ];
            return 1;
        }

        if (str_contains($sql, 'INSERT INTO production_batch_items')) {
            self::$nextInsert++;
            self::$batchItems[] = [
                'id' => self::$nextInsert,
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
            self::$nextInsert++;
            $id = self::$nextInsert;
            self::$outwards[$id] = [
                'id' => $id,
                'outward_number' => $params[0],
                'production_batch_id' => $params[1],
                'production_unit_id' => $params[2],
                'concern_id' => $params[3],
                'destination_concern_id' => $params[5],
                'status' => 'DISPATCHED',
            ];
            return 1;
        }

        if (str_contains($sql, 'INSERT INTO production_cash_registers')) {
            self::$nextInsert++;
            $id = self::$nextInsert;
            self::$cashRegisters[$id] = [
                'id' => $id,
                'production_unit_id' => $params[0],
                'concern_id' => (int)$params[1],
                'register_number' => $params[2],
                'cashier_id' => $params[3],
                'cashier_name' => $params[4],
                'opening_balance' => (float)$params[5],
                'opening_time' => date('Y-m-d H:i:s'),
                'status' => 'OPEN',
                'cash_sales_amount' => 0.0,
                'upi_sales_amount' => 0.0,
                'card_sales_amount' => 0.0,
                'cash_in_amount' => 0.0,
                'cash_out_amount' => 0.0,
                'cash_refund_amount' => 0.0,
                'bank_deposit_amount' => 0.0,
                'expected_cash' => (float)$params[5],
            ];
            return 1;
        }

        if (str_contains($sql, 'UPDATE production_cash_registers') && str_contains($sql, 'SET cash_sales_amount = ?')) {
            $id = (int)$params[8];
            if (isset(self::$cashRegisters[$id])) {
                self::$cashRegisters[$id]['cash_sales_amount'] = (float)$params[0];
                self::$cashRegisters[$id]['upi_sales_amount'] = (float)$params[1];
                self::$cashRegisters[$id]['card_sales_amount'] = (float)$params[2];
                self::$cashRegisters[$id]['cash_in_amount'] = (float)$params[3];
                self::$cashRegisters[$id]['cash_out_amount'] = (float)$params[4];
                self::$cashRegisters[$id]['cash_refund_amount'] = (float)$params[5];
                self::$cashRegisters[$id]['bank_deposit_amount'] = (float)$params[6];
                self::$cashRegisters[$id]['expected_cash'] = (float)$params[7];
            }
            return 1;
        }

        if (str_contains($sql, 'DELETE ct') && str_contains($sql, 'FROM production_cash_transactions ct')) {
            $billId = (int)$params[0];
            $concernId = (int)$params[1];
            self::$cashTransactions = array_values(array_filter(
                self::$cashTransactions,
                static function (array $tx) use ($billId, $concernId): bool {
                    if ($tx['source_table'] !== 'production_bills' || (int)$tx['source_id'] !== $billId || (int)$tx['concern_id'] !== $concernId) {
                        return true;
                    }
                    $regStatus = $tx['register_id'] ? (self::$cashRegisters[(int)$tx['register_id']]['status'] ?? 'OPEN') : null;
                    return $regStatus === 'CLOSED';
                }
            ));
            return 1;
        }

        if (str_contains($sql, 'INSERT INTO production_cash_transactions')) {
            self::$nextInsert++;
            self::$cashTransactions[] = [
                'id' => self::$nextInsert,
                'production_unit_id' => $params[0],
                'concern_id' => (int)$params[1],
                'register_id' => $params[2],
                'transaction_number' => $params[3],
                'transaction_type' => $params[4],
                'flow_direction' => $params[5],
                'amount' => (float)$params[6],
                'payment_mode' => $params[7],
                'category' => $params[8],
                'reference_id' => $params[9],
                'source_table' => $params[10],
                'source_id' => $params[11],
                'handled_by' => $params[12],
                'description' => $params[13],
                'created_at' => date('Y-m-d H:i:s'),
            ];
            return 1;
        }

        return 1;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        self::$queries[] = ['sql' => $sql, 'params' => $params];

        if (str_contains($sql, 'FROM production_inventory_damages') && str_contains($sql, 'WHERE concern_id = ? AND tag_code = ?')) {
            $cid = (int)$params[0];
            $tag = strtoupper((string)$params[1]);
            foreach (self::$damages as $d) {
                if ((int)$d['concern_id'] === $cid && strtoupper($d['tag_code']) === $tag) {
                    return ['id' => $d['id'], 'status' => $d['status']];
                }
            }
            return null;
        }

        if (str_contains($sql, 'FROM production_inventory_damages d') && str_contains($sql, 'WHERE d.id = ?')) {
            $id = (int)$params[0];
            $cid = (int)$params[1];
            $d = self::$damages[$id] ?? null;
            return ($d && (int)$d['concern_id'] === $cid) ? $d : null;
        }

        if (str_contains($sql, 'FROM production_inventory_damages') && str_contains($sql, 'damage_number LIKE')) {
            return null;
        }

        if (str_contains($sql, 'SELECT quantity FROM stock_balances') || (str_contains($sql, 'FROM stock_balances') && str_contains($sql, 'stock_state = ?'))) {
            $key = "{$params[0]}|{$params[1]}|{$params[2]}|{$params[3]}";
            return ['quantity' => self::$balances[$key] ?? 0.0];
        }

        if (str_contains($sql, 'FROM stock_balances sb') && str_contains($sql, 'SUM(sb.quantity)')) {
            return ['qty' => self::balance((int)$params[1], (string)$params[2], (int)$params[3], 'AVAILABLE')];
        }

        if (str_contains($sql, 'FROM stock_balances sb') && str_contains($sql, 'JOIN product_variants pv')) {
            // Concern 2 holds inward stock CONCERN:2 for variant 6; Concern 1 has none.
            $cid = (int)$params[0];
            $variantId = (int)$params[1];
            if ($cid === 2 && $variantId === 6 && self::balance(6, 'CONCERN', 2, 'AVAILABLE') > 0) {
                return [
                    'location_type' => 'CONCERN',
                    'location_id' => 2,
                    'quantity' => self::balance(6, 'CONCERN', 2, 'AVAILABLE'),
                    'sku' => 'WMF-S-RED',
                    'variant_mrp' => '500.00',
                    'selling_price' => '400.00',
                    'product_name' => 'Maternity Kurta',
                    'product_code' => 'WMF',
                    'gst_percentage' => '5.00',
                    'size_name' => 'S',
                    'color_name' => 'RED',
                    'location_label' => 'SULUR SHOP (Concern Inventory)',
                ];
            }
            return null;
        }

        if (str_contains($sql, 'FROM qc_items qi') && str_contains($sql, 'JOIN qc_batches qb') && str_contains($sql, 'SELECT')) {
            return null;
        }

        if (str_contains($sql, 'FROM stock_transactions st') && str_contains($sql, 'st.reference_type = \'production_inward\'')) {
            return null;
        }

        if (str_contains($sql, 'FROM concerns WHERE id = ?')) {
            $id = (int)$params[0];
            $c = Concern::$concerns[$id] ?? null;
            return ($c && (!str_contains($sql, 'is_active = 1') || !empty($c['is_active']))) ? $c : null;
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

        if (str_contains($sql, 'FROM production_batches WHERE batch_number LIKE')) {
            return null;
        }

        if (str_contains($sql, 'FROM production_outwards') && str_contains($sql, 'outward_number LIKE')) {
            return null;
        }

        if (str_contains($sql, 'FROM production_outwards WHERE production_batch_id = ?')) {
            return null;
        }

        if (str_contains($sql, 'SELECT id FROM production_cash_registers WHERE id = ?')) {
            $regId = (int)$params[0];
            $cid = (int)end($params);
            $reg = self::$cashRegisters[$regId] ?? null;
            return ($reg && (int)$reg['concern_id'] === $cid) ? ['id' => $regId] : null;
        }

        if (str_contains($sql, 'FROM production_cash_registers cr') && str_contains($sql, 'cr.status = \'OPEN\'')) {
            $cid = (int)end($params);
            $wantUnit = str_contains($sql, 'cr.production_unit_id = ?') ? (int)$params[0] : null;
            foreach (array_reverse(self::$cashRegisters) as $reg) {
                if ((int)$reg['concern_id'] === $cid && $reg['status'] === 'OPEN' && ($reg['production_unit_id'] ?? null) === $wantUnit) {
                    return $reg;
                }
            }
            return null;
        }

        if (str_contains($sql, 'FROM production_cash_transactions WHERE register_id = ?')) {
            $regId = (int)$params[0];
            $cid = (int)$params[1];
            $agg = ['cash_sales' => 0.0, 'upi_sales' => 0.0, 'card_sales' => 0.0, 'cash_in' => 0.0, 'cash_out' => 0.0, 'cash_refund' => 0.0, 'bank_deposit' => 0.0];
            foreach (self::$cashTransactions as $tx) {
                if ((int)$tx['register_id'] !== $regId || (int)$tx['concern_id'] !== $cid) {
                    continue;
                }
                if ($tx['transaction_type'] === 'SALE_CASH' && $tx['flow_direction'] === 'IN') $agg['cash_sales'] += $tx['amount'];
                if ($tx['transaction_type'] === 'SALE_UPI' && $tx['flow_direction'] === 'IN') $agg['upi_sales'] += $tx['amount'];
                if ($tx['transaction_type'] === 'SALE_CARD' && $tx['flow_direction'] === 'IN') $agg['card_sales'] += $tx['amount'];
                if ($tx['transaction_type'] === 'CASH_IN') $agg['cash_in'] += $tx['amount'];
                if ($tx['transaction_type'] === 'EXPENSE' && $tx['payment_mode'] === 'CASH') $agg['cash_out'] += $tx['amount'];
                if ($tx['transaction_type'] === 'REFUND_CASH' && $tx['flow_direction'] === 'OUT') $agg['cash_refund'] += $tx['amount'];
                if ($tx['transaction_type'] === 'BANK_DEPOSIT') $agg['bank_deposit'] += $tx['amount'];
            }
            return $agg;
        }

        if (str_contains($sql, 'FROM production_cash_registers WHERE') && str_contains($sql, 'status = \'CLOSED\'')) {
            return null;
        }

        if (str_contains($sql, 'FROM production_cash_registers') && str_contains($sql, 'LIKE ?')) {
            return ['max_code' => 'PREG-CONC3-' . date('Ymd') . '-0005'];
        }

        if (str_contains($sql, 'FROM production_cash_transactions') && str_contains($sql, 'LIKE ?')) {
            return null;
        }

        if (str_contains($sql, 'FROM production_bills')) {
            return ['orders' => 2, 'count' => 2, 'total' => '1500.00', 'revenue' => '1500.00', 'discount' => '50.00', 'tax' => '75.00', 'paid' => '1500.00'];
        }
        if (str_contains($sql, 'FROM asset_maintenance_logs m')) {
            return ['total' => '300.00', 'count' => 1];
        }
        if (str_contains($sql, 'FROM roll_damages')) {
            return ['total_loss' => '120.00', 'total_meters' => '3.00', 'count' => 1];
        }
        if (str_contains($sql, 'FROM purchase_orders')) {
            return ['total' => '1000.00', 'discount' => '0', 'tax' => '50.00', 'paid' => '1000.00', 'count' => 1, 'orders' => 1, 'revenue' => '1000.00'];
        }
        if (str_contains($sql, 'FROM sales')) {
            return ['total' => '500.00', 'discount' => '0', 'tax' => '25.00', 'paid' => '500.00', 'count' => 1, 'sales' => 1, 'revenue' => '500.00'];
        }
        if (str_contains($sql, 'FROM wastage_sales')) {
            return ['total' => '0', 'total_kg' => '0', 'count' => 0];
        }
        if (str_contains($sql, 'FROM stitching_wastage_sales')) {
            return ['total' => '0', 'total_qty' => '0', 'count' => 0];
        }
        if (str_contains($sql, 'FROM production_batches pb')) {
            return ['total_cost' => '400.00', 'total_pieces' => 10];
        }
        if (str_contains($sql, 'FROM production_expenses') || str_contains($sql, 'FROM cash_expenses') || str_contains($sql, 'FROM expenses') || str_contains($sql, 'FROM warehouse_damages') || str_contains($sql, 'FROM warehouse_return_items')) {
            return ['total' => '0', 'wh_total' => '0', 'wa_total' => '0', 'web_total' => '0', 'count' => 0, 'total_loss' => '0', 'total_qty' => 0, 'return_loss' => '0', 'defect_qty' => 0];
        }
        if (str_contains($sql, 'SELECT (') && str_contains($sql, 'total_revenue')) {
            return ['total_revenue' => '3000.00'];
        }
        if (str_contains($sql, 'SELECT (') && str_contains($sql, 'total_expense')) {
            return ['total_expense' => '0.00'];
        }
        if (str_contains($sql, 'SELECT (') && str_contains($sql, 'total_loss')) {
            return ['total_loss' => '120.00'];
        }

        return null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        self::$queries[] = ['sql' => $sql, 'params' => $params];

        if (str_contains($sql, 'FROM production_inventory_damages') && str_contains($sql, "NOT IN ('REPAIRED','RESTOCKED')")) {
            $cid = (int)$params[0];
            $out = [];
            foreach (self::$damages as $d) {
                if ((int)$d['concern_id'] === $cid && !in_array($d['status'], ['REPAIRED', 'RESTOCKED'], true)) {
                    $out[] = ['tag_code' => $d['tag_code']];
                }
            }
            return $out;
        }

        if (str_contains($sql, 'FROM production_inward_items pii') && str_contains($sql, 'JOIN production_inwards pi')) {
            $cid = (int)$params[0];
            if ($cid === 2) {
                return [[
                    'inward_id' => 51,
                    'grn_number' => 'PGRN-2026-000051',
                    'source_type' => 'PRODUCTION_BATCH',
                    'source_id' => 10,
                    'destination_type' => 'CONCERN',
                    'destination_concern_id' => 2,
                    'destination_unit_id' => null,
                    'stock_location_type' => 'CONCERN',
                    'stock_location_id' => 2,
                    'source_batch_number' => 'BAT-0010',
                    'received_at' => '2026-11-10 12:00:00',
                    'location_label' => 'SULUR SHOP (Concern Inventory)',
                    'variant_id' => 6,
                    'scanned_codes' => json_encode(['INW112601', 'INW112602']),
                    'damaged_codes' => '[]',
                    'missing_codes' => '[]',
                    'batch_number' => 'BAT-0010',
                    'qc_batch_id' => 10,
                    'origin_qc_item_id' => 1,
                    'qc_base_price' => '500.00',
                    'qc_discount_price' => '400.00',
                    'qc_size_name' => 'S',
                    'qc_color_name' => 'RED',
                    'program_number' => 'PP-001',
                    'product_name' => 'Maternity Kurta',
                    'product_code' => 'WMF',
                    'gst_percentage' => '5.00',
                    'sku' => 'WMF-S-RED',
                    'variant_mrp' => '500.00',
                    'selling_price' => '400.00',
                    'color_from_variant' => 'RED',
                    'size_from_variant' => 'S',
                ]];
            }
            return [];
        }

        if (str_contains($sql, 'FROM stock_balances') && str_contains($sql, "stock_state = 'AVAILABLE'")) {
            $cid = (int)$params[0];
            if ($cid === 2 && self::balance(6, 'CONCERN', 2, 'AVAILABLE') > 0) {
                return [[
                    'variant_id' => 6,
                    'location_type' => 'CONCERN',
                    'location_id' => 2,
                    'quantity' => (string)self::balance(6, 'CONCERN', 2, 'AVAILABLE'),
                    'sku' => 'WMF-S-RED',
                    'product_name' => 'Maternity Kurta',
                    'product_code' => 'WMF',
                    'size_name' => 'S',
                    'color_name' => 'RED',
                    'location_label' => 'SULUR SHOP (Concern Inventory)',
                ]];
            }
            return [];
        }

        if (str_contains($sql, 'FROM production_batch_items pbi') && str_contains($sql, "pb.source_type = 'INWARD'")) {
            return array_values(self::$batchItems);
        }

        if (str_contains($sql, 'FROM production_batch_items') && str_contains($sql, 'WHERE production_batch_id = ?')) {
            $bid = (int)$params[0];
            return array_values(array_filter(self::$batchItems, static fn(array $bi): bool => (int)$bi['production_batch_id'] === $bid));
        }

        if (str_contains($sql, 'AS item_number FROM `production_cash_registers`')) {
            return [['item_number' => 'PREG-CONC3-' . date('Ymd') . '-0005']];
        }

        if (str_contains($sql, 'FROM production_bills pb')) {
            return [[
                'id' => 77,
                'bill_number' => 'PB-2026-0077',
                'ref_no' => 'PB-2026-0077',
                'invoice_no' => 'PB-2026-0077',
                'source' => 'Production Billing',
                'channel' => 'Production Billing',
                'customer_name' => 'Walk-in Buyer',
                'customer_phone' => '9876543210',
                'total_amount' => '1500.00',
                'discount_amount' => '50.00',
                'tax_amount' => '75.00',
                'paid_amount' => '1500.00',
                'payment_method' => 'CASH_UPI',
                'payment_status' => 'PAID',
                'status' => 'COMPLETED',
                'production_unit_id' => null,
                'location_name' => 'SULUR SHOP (Concern Stock)',
                'created_at' => '2026-11-11 10:00:00',
            ]];
        }

        return [];
    }
}

function isSuperAdmin(): bool { return !empty((Session::user() ?? [])['is_super_admin']); }
function currentConcernId(): ?int {
    $c = Concern::getActiveForCurrentUser();
    return $c ? (int)$c['id'] : null;
}
function currentConcern(): ?array { return Concern::getActiveForCurrentUser(); }
function currentProductionUnitId(): ?int {
    $u = Session::user();
    return isset($u['assigned_production_unit_id']) ? (int)$u['assigned_production_unit_id'] : null;
}
function currentWarehouseId(): ?int { return null; }
function currentShopId(): ?int { return null; }
function getScopedProductionUnits(): array { return ProductionUnit::getAll(['is_active' => 1]); }
function getScopedWarehouses(): array { return []; }
function getScopedShops(): array { return []; }
function url(string $path = ''): string { return '/' . ltrim($path, '/'); }
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function hasPermission(string $module, string $action): bool { return true; }

require_once BASE_PATH . '/app/Controllers/ProductionInventoryDamageController.php';
require_once BASE_PATH . '/app/Controllers/ProductionCashController.php';
require_once BASE_PATH . '/app/Controllers/ReportController.php';

function assertSame5(mixed $expected, mixed $actual, string $msg): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$msg}\nExpected: " . var_export($expected, true) . "\nActual:   " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrue5(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

// =========================================================================
// PART 1: Finished Goods Damage for Inward Stock + Billing Exclusion
// =========================================================================
Database::reset();
Response::reset();
Session::reset();
Session::set('user', ['id' => 9, 'is_super_admin' => 0, 'role_name' => 'manager', 'full_name' => 'Sulur Manager']);
Session::set('active_concern_id', 2);

$dmgCtrl = new ProductionInventoryDamageController();
$_GET['code'] = 'inw112601';
$dmgCtrl->lookup();
assertSame5(200, Response::$lastStatus, 'Concern 2 can look up its own inward QR piece');
assertSame5(true, Response::$lastJson['success'] ?? false, 'Lookup returns success=true');
assertSame5('INWARD', Response::$lastJson['piece']['source_kind'] ?? '', 'Piece source_kind is INWARD');
assertSame5('CONCERN', Response::$lastJson['piece']['stock_location_type'] ?? '', 'Piece location_type is CONCERN');

// Concern 1 cannot see Concern 2 inward QR
Session::set('active_concern_id', 1);
Response::reset();
$dmgCtrl->lookup();
assertSame5(404, Response::$lastStatus, 'Concern 1 fails closed when scanning Concern 2 inward piece');

// Quarantine INW112601 under Concern 2
Session::set('active_concern_id', 2);
Response::reset();
$_POST = [
    'items' => json_encode([['tag_code' => 'inw112601']]),
    'damage_reason' => 'Seam Tear',
    'source_stage' => 'INWARD',
    'defect_notes' => 'Caught during shelf check',
];
$dmgCtrl->store();
assertSame5(200, Response::$lastStatus, 'Inward finished-goods damage creation succeeds');
assertSame5(1.0, StockService::getBalance(6, 'CONCERN', 2, 'AVAILABLE'), 'Available concern stock decreases from 2 to 1 on quarantine');
assertSame5(1.0, StockService::getBalance(6, 'CONCERN', 2, 'DAMAGED'), 'Damaged concern stock increases from 0 to 1 on quarantine');

$dmgId = array_key_first(Database::$damages);
assertSame5(null, Database::$damages[$dmgId]['qc_batch_id'], 'Inward damage row allows NULL qc_batch_id');
assertSame5('CONCERN', Database::$damages[$dmgId]['stock_location_type'], 'Inward damage stores stock_location_type');

// Billing exclusion check
$quarantinedSet = ProductionBillingService::getQuarantinedConcernTagSet(2);
assertTrue5(isset($quarantinedSet['INW112601']), 'Quarantined inward tag INW112601 appears in concern quarantined set');
$availInv = ProductionBillingService::getAvailableInventory();
$allAvailTags = [];
foreach ($availInv as $row) {
    foreach ($row['available_tags'] as $t) {
        $allAvailTags[strtoupper($t)] = true;
    }
}
assertTrue5(!isset($allAvailTags['INW112601']), 'Quarantined inward tag is excluded from Billing available inventory');
assertTrue5(isset($allAvailTags['INW112602']), 'Healthy inward tag INW112602 remains available for Billing');

// Restore to REPAIRED -> moves DAMAGED back to AVAILABLE
Response::reset();
$_POST = ['status' => 'REPAIRED'];
$dmgCtrl->updateStatus((string)$dmgId);
assertSame5('/production/inventory-damages/view/' . $dmgId, Response::$lastRedirect, 'Restoring inward damage piece to REPAIRED redirects to view');
assertSame5(2.0, StockService::getBalance(6, 'CONCERN', 2, 'AVAILABLE'), 'Available concern stock restored back to 2 on REPAIRED');
assertSame5(0.0, StockService::getBalance(6, 'CONCERN', 2, 'DAMAGED'), 'Damaged concern stock reduced back to 0 on REPAIRED');

// Re-logging the same tag INW112601 updates existing record cleanly
Response::reset();
$_POST = [
    'items' => json_encode([['tag_code' => 'INW112601']]),
    'damage_reason' => 'Button detached',
    'source_stage' => 'STORAGE_HANDLING',
    'defect_notes' => 'Second damage event',
];
$dmgCtrl->store();
assertSame5(200, Response::$lastStatus, 'Re-quarantining previously repaired tag succeeds without unique key collision');
assertSame5(1, count(Database::$damages), 'Existing tag row is reopened rather than duplicated');
assertSame5(1.0, StockService::getBalance(6, 'CONCERN', 2, 'DAMAGED'), 'Re-quarantine decrements AVAILABLE and increments DAMAGED again');

// =========================================================================
// PART 2: Inward Batch Packing + Approve + Outward Deduction + Pending View
// =========================================================================
// Restore piece first so 2 pcs AVAILABLE again
$_POST = ['status' => 'RESTOCKED'];
$dmgCtrl->updateStatus((string)$dmgId);
assertSame5(2.0, StockService::getBalance(6, 'CONCERN', 2, 'AVAILABLE'), 'RESTOCKED restores full concern stock before packing');

$batchId = ProductionService::createProductionBatch([
    'source_type' => 'INWARD',
    'source_location_type' => 'CONCERN',
    'source_location_id' => 2,
    'notes' => 'Transfer to Main Factory',
], [[
    'variant_id' => 6,
    'quantity' => 1,
    'piece_tags' => ['INW112601'],
]], 9);

assertTrue5($batchId > 0, 'Inward batch creation succeeds');
assertSame5(null, Database::$batches[$batchId]['program_id'], 'Inward batch stores NULL program_id');
assertSame5(1.0, StockService::getBalance(6, 'CONCERN', 2, 'AVAILABLE'), 'Packing inward batch moves 1 pc out of AVAILABLE');
assertSame5(1.0, StockService::getBalance(6, 'CONCERN', 2, 'RESERVED'), 'Packing inward batch reserves 1 pc into RESERVED');

$approved = ProductionService::approveProductionBatch($batchId, 9);
assertTrue5($approved, 'approveProductionBatch handles NULL program_id inward batches cleanly');

// Outward deducts RESERVED stock on dispatch
$outwardId = ProductionService::createOutward([
    'production_batch_id' => $batchId,
    'destination_type' => 'CONCERN',
    'destination_concern_id' => 1,
    'dispatch_date' => '2026-11-11',
], 9);
assertTrue5($outwardId > 0, 'createOutward succeeds for inward-packed batch');
assertSame5(0.0, StockService::getBalance(6, 'CONCERN', 2, 'RESERVED'), 'Outward dispatch deducts RESERVED stock to 0');

// SQL structure check for LEFT JOIN production_programs on Batch / Outward / Inward
Session::set('active_concern_id', 1);
ProductionInwardService::getPendingConcernBatches();
$foundPendingLeftJoin = false;
foreach (Database::$queries as $q) {
    if (str_contains($q['sql'], 'FROM production_batches pb') && str_contains($q['sql'], 'LEFT JOIN production_programs pp')) {
        $foundPendingLeftJoin = true;
    }
}
assertTrue5($foundPendingLeftJoin, 'getPendingConcernBatches uses LEFT JOIN production_programs for NULL-program batches');

// Cross-concern find fails closed
assertSame5(null, ProductionBatch::find($batchId), 'ProductionBatch::find fails closed for non-owning concern');

// =========================================================================
// PART 3: Cash & Cash Flow (Unit-less Concern + Bill/Return Sync + nextNumber)
// =========================================================================
Session::reset();
Session::set('user', ['id' => 15, 'is_super_admin' => 0, 'role_name' => 'cashier', 'full_name' => 'Unitless Cashier']);
Session::set('active_concern_id', 3); // Concern 3 has zero production_units

Response::reset();
$_GET = ['tab' => 'ledger'];
$cashCtrl = new ProductionCashController();
$cashCtrl->index();
$cashViewData = Response::$lastView['data'] ?? [];
assertSame5(true, $cashViewData['scopeAvailable'] ?? false, 'Unit-less concern still gets Cash Register scopeAvailable=true');
assertSame5(true, $cashViewData['isConcernScope'] ?? false, 'Unit-less concern defaults to isConcernScope=true');
assertSame5('concern', $cashViewData['unitParam'] ?? '', 'Unit-less concern exposes unitParam=concern');

// Open Concern-wide shift (production_unit_id = NULL)
$openRes = ProductionCashService::openRegister(null, 15, 500.0, [], 0.0, '', 'Unitless Cashier', 'Opening concern till');
assertTrue5(str_starts_with($openRes['register_number'], 'PREG-CONC3-'), 'Concern-wide register uses PREG-CONC{id} prefix');
assertSame5('PREG-CONC3-' . date('Ymd') . '-0006', $openRes['register_number'], 'nextNumber scans globally past existing sequence numbers');

// Verify nextNumber SQL did not filter by concern_id on register_number lookup
$regNextSql = '';
foreach (Database::$queries as $q) {
    if (str_contains($q['sql'], 'AS item_number FROM `production_cash_registers`')) {
        $regNextSql = $q['sql'];
    }
}
assertTrue5($regNextSql !== '' && !str_contains($regNextSql, 'concern_id = ?'), 'ProductionCashService::nextNumber scans globally across concerns to avoid duplicate keys');

// Split CASH_UPI bill sync (+600 Cash, +400 UPI)
ProductionCashService::syncBillCollections(77, [
    'production_unit_id' => null,
    'bill_number' => 'PB-2026-0077',
    'payment_method' => 'CASH_UPI',
    'payment_status' => 'PAID',
    'total_amount' => 1000.0,
    'cash_amount' => 600.0,
    'upi_amount' => 400.0,
    'upi_reference' => 'UPI-REF-1',
], 15);
$reg = ProductionCashService::getActiveRegister(null);
assertSame5(600.0, (float)$reg['cash_sales_amount'], 'Split CASH_UPI posts 600 to cash_sales_amount');
assertSame5(400.0, (float)$reg['upi_sales_amount'], 'Split CASH_UPI posts 400 to upi_sales_amount');
assertSame5(1100.0, (float)$reg['expected_cash'], 'Expected cash equals float(500) + cash sales(600), excluding UPI');

// Return refund (-150 CASH) reduces expected_cash
ProductionCashService::recordBillReturnSettlement(31, [
    'production_unit_id' => null,
    'return_number' => 'PBR-0031',
    'bill_number' => 'PB-2026-0077',
    'payment_action' => 'REFUND',
    'settlement_mode' => 'CASH',
    'amount' => 150.0,
], 15);
$reg = ProductionCashService::getActiveRegister(null);
assertSame5(150.0, (float)$reg['cash_refund_amount'], 'Cash refund tracked in register');
assertSame5(950.0, (float)$reg['expected_cash'], 'Expected cash drops to 950 after 150 cash refund');

// Bill delete removal zeroes open-shift bill collections
ProductionCashService::removeBillCollections(77, null, 15);
$reg = ProductionCashService::getActiveRegister(null);
assertSame5(0.0, (float)$reg['cash_sales_amount'], 'Bill deletion removes open-shift cash collection');
assertSame5(0.0, (float)$reg['upi_sales_amount'], 'Bill deletion removes open-shift UPI collection');

// =========================================================================
// PART 4: Reports Concern Isolation & Production Billing Revenue
// =========================================================================
Database::$queries = [];
Session::set('active_concern_id', 2);
$repCtrl = new ReportController();
$ref = new ReflectionClass($repCtrl);

$pnlMethod = $ref->getMethod('getProfitabilityMetrics');
$pnlMethod->setAccessible(true);
$pnl = $pnlMethod->invoke($repCtrl, '2026-11-01 00:00:00', '2026-11-30 23:59:59');

assertSame5(1500.0, (float)$pnl['prod_sales_revenue'], 'getProfitabilityMetrics exposes prod_sales_revenue');
assertSame5(3000.0, (float)$pnl['gross_revenue'], 'getProfitabilityMetrics includes production_bills in gross_revenue (1000+500+1500)');
assertSame5(4, (int)$pnl['invoices_count'], 'getProfitabilityMetrics counts production_bills in invoices_count');

$checkedRollScope = false;
$checkedAssetScope = false;
$checkedProdBillDayParam = false;
foreach (Database::$queries as $q) {
    if (str_contains($q['sql'], 'FROM roll_damages') && str_contains($q['sql'], 'concern_id = ?')) {
        $checkedRollScope = true;
    }
    if (str_contains($q['sql'], 'FROM asset_maintenance_logs m') && str_contains($q['sql'], 'JOIN assets a ON a.id = m.asset_id') && str_contains($q['sql'], 'pu_scope.concern_id = ?')) {
        $checkedAssetScope = true;
    }
    if (str_contains($q['sql'], 'FROM production_bills') && ($q['params'][1] ?? '') === '2026-11-01' && ($q['params'][2] ?? '') === '2026-11-30') {
        $checkedProdBillDayParam = true;
    }
}
assertTrue5($checkedRollScope, 'roll_damages report query scopes by concern_id');
assertTrue5($checkedAssetScope, 'asset_maintenance_logs report query joins assets and scopes by concern_id');
assertTrue5($checkedProdBillDayParam, 'production_bills report query compares DATE column against Y-m-d string');

$salesSumMethod = $ref->getMethod('getSalesSummary');
$salesSumMethod->setAccessible(true);
$salesSum = $salesSumMethod->invoke($repCtrl, '2026-11-01 00:00:00', '2026-11-30 23:59:59');
assertSame5(3000.0, (float)$salesSum['total_revenue'], 'getSalesSummary includes production_bills revenue');
assertSame5(1500.0, (float)$salesSum['prod_revenue'], 'getSalesSummary returns prod_revenue');

$detSalesMethod = $ref->getMethod('getDetailedSalesReport');
$detSalesMethod->setAccessible(true);
$detSales = $detSalesMethod->invoke($repCtrl, '2026-11-01 00:00:00', '2026-11-30 23:59:59', 'all', 'all');
assertSame5(1500.0, (float)$detSales['channels']['production_billing']['total_amount'], 'getDetailedSalesReport includes production_billing channel');
assertTrue5(!empty($detSales['production_breakdown']), 'getDetailedSalesReport populates production_breakdown');

$trendMethod = $ref->getMethod('get12MonthTrend');
$trendMethod->setAccessible(true);
Database::$queries = [];
$trend = $trendMethod->invoke($repCtrl, 2026);
assertSame5(12, count($trend), 'get12MonthTrend returns 12 months');
$trendRollScoped = false;
$trendHasProdBills = false;
foreach (Database::$queries as $q) {
    if (str_contains($q['sql'], 'FROM roll_damages WHERE concern_id = ?')) {
        $trendRollScoped = true;
    }
    if (str_contains($q['sql'], 'FROM production_bills WHERE concern_id = ?')) {
        $trendHasProdBills = true;
    }
}
assertTrue5($trendRollScoped, 'get12MonthTrend scopes roll_damages by concern_id');
assertTrue5($trendHasProdBills, 'get12MonthTrend includes production_bills revenue');

echo "PASS: Phase 5 Finished Goods Damage, Inward Batches/Outward, Unit-less & Bill-linked Cash Flow, and Scoped Reports checks passed.\n";
