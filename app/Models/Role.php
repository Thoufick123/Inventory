<?php
/**
 * Role Model
 * Handles role and permission data access.
 */

class Role {
    /**
     * Get all roles (excluding root Super Admin).
     */
    public static function getAll(): array {
        return Database::fetchAll("SELECT * FROM roles WHERE id != 1 ORDER BY name ASC");
    }

    /**
     * Map of operational departments to their respective functional modules.
     */
    /**
     * Map of operational departments to their respective functional modules.
     */
    public const DEPT_MODULE_MAP = [
        'PRODUCTION' => [
            'production', 'sellers', 'damages', 'catalog', 'programs', 'cutting', 'wastage',
            'stitching', 'stitching_wastage', 'production_expenses', 'labels', 'qc', 'inventory',
            'batches', 'outward', 'warehouse_rework', 'reports_production'
        ],
        'WAREHOUSE'  => [
            'warehouse', 'inward', 'stock', 'wh_sellers', 'wh_catalog', 'wh_labels', 'purchases', 'purchases_returns', 'allocation',
            'wh_batches', 'allocation_outward', 'damage', 'returns', 'shop_requests',
            'warehouse_expenses', 'sales_channels',
            'reports_warehouse', 'reports_purchase', 'reports_inventory'
        ],
        'WHATSAPP'   => [
            'whatsapp_dashboard', 'whatsapp_orders', 'whatsapp_inward', 'whatsapp_inventory', 'whatsapp_damage', 'whatsapp_returns', 'whatsapp_return_to_wh', 'whatsapp_customers', 'whatsapp_expenses',
            'reports_whatsapp', 'reports_online'
        ],
        'WEBSITE'    => [
            'website_dashboard', 'website_orders', 'website_inward', 'website_inventory', 'website_damage', 'website_returns', 'website_return_to_wh', 'website_customers', 'website_expenses',
            'reports_website', 'reports_online'
        ],
        'SHOP'       => [
            'shop', 'shop_cash', 'shop_inward', 'shop_stock', 'shop_damage', 'pos', 'sales',
            'customers', 'shop_returns', 'return_to_warehouse', 'reports_shop'
        ],
    ];

    /**
     * Get all active roles with their detected department scopes based on assigned permissions.
     */
    public static function getAllWithDepartmentScope(bool $onlyActive = true): array {
        $sql = "SELECT * FROM roles WHERE id != 1";
        if ($onlyActive) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        $roles = Database::fetchAll($sql);

        foreach ($roles as &$role) {
            $roleId = (int)$role['id'];
            $permModules = Database::fetchAll(
                "SELECT DISTINCT p.module FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?",
                [$roleId]
            );
            $modules = array_column($permModules, 'module');

            $matchedDepts = [];
            foreach (self::DEPT_MODULE_MAP as $deptName => $deptMods) {
                if (!empty(array_intersect($modules, $deptMods))) {
                    $matchedDepts[] = $deptName;
                }
            }

            if (empty($matchedDepts)) {
                $scope = 'ALL';
            } elseif (count($matchedDepts) === 1) {
                $scope = $matchedDepts[0];
            } else {
                $scope = 'ALL';
            }

            $role['departments'] = $matchedDepts;
            $role['primary_department'] = $scope;
            $role['modules_count'] = count($modules);
        }

        return $roles;
    }

    /**
     * Check if a role is compatible with a given department.
     */
    public static function isRoleCompatibleWithDepartment(int $roleId, string $department): bool {
        if ($roleId === 1) return true;
        $department = strtoupper($department);
        if ($department === 'GENERAL' || $department === 'ALL') return true;

        $deptMods = self::DEPT_MODULE_MAP[$department] ?? [];
        if (empty($deptMods)) return true;

        $count = (int)(Database::fetchOne(
            "SELECT COUNT(p.id) as cnt
             FROM role_permissions rp
             JOIN permissions p ON rp.permission_id = p.id
             WHERE rp.role_id = ? AND p.module IN (" . implode(',', array_fill(0, count($deptMods), '?')) . ")",
            array_merge([$roleId], $deptMods)
        )['cnt'] ?? 0);

        if ($count > 0) return true;

        $allDeptMods = array_merge(
            self::DEPT_MODULE_MAP['PRODUCTION'],
            self::DEPT_MODULE_MAP['WAREHOUSE'],
            self::DEPT_MODULE_MAP['WHATSAPP'],
            self::DEPT_MODULE_MAP['WEBSITE'],
            self::DEPT_MODULE_MAP['SHOP']
        );
        $otherDeptsCount = (int)(Database::fetchOne(
            "SELECT COUNT(p.id) as cnt
             FROM role_permissions rp
             JOIN permissions p ON rp.permission_id = p.id
             WHERE rp.role_id = ? AND p.module IN (" . implode(',', array_fill(0, count($allDeptMods), '?')) . ")",
            array_merge([$roleId], $allDeptMods)
        )['cnt'] ?? 0);

        return ($otherDeptsCount === 0);
    }

    /**
     * Filter permission IDs so that only permissions allowed for the given department are retained.
     */
    public static function filterPermissionsForDepartment(array $permissionIds, string $department): array {
        $department = strtoupper($department);
        if (empty($permissionIds) || $department === 'ALL' || $department === 'GENERAL') {
            return array_values(array_filter(array_map('intval', $permissionIds)));
        }

        $allDeptMap = self::DEPT_MODULE_MAP;
        if (!isset($allDeptMap[$department])) {
            return array_values(array_filter(array_map('intval', $permissionIds)));
        }

        $moduleDepartments = [];
        foreach ($allDeptMap as $dept => $mods) {
            foreach ($mods as $mod) {
                $moduleDepartments[$mod][] = $dept;
            }
        }

        // Fetch modules for the given permission IDs
        $cleanIds = array_values(array_filter(array_map('intval', $permissionIds)));
        if (empty($cleanIds)) return [];

        $inClause = implode(',', array_fill(0, count($cleanIds), '?'));
        $rows = Database::fetchAll(
            "SELECT id, module FROM permissions WHERE id IN ($inClause)",
            $cleanIds
        );

        $allowedIds = [];
        foreach ($rows as $row) {
            $moduleDepts = $moduleDepartments[$row['module']] ?? [];
            if ($moduleDepts && !in_array($department, $moduleDepts, true)) {
                continue;
            }
            $allowedIds[] = (int)$row['id'];
        }

        return $allowedIds;
    }

    /**
     * Get all active roles.
     */
    public static function getActive(): array {
        return self::getAllWithDepartmentScope(true);
    }

    /**
     * Find role by ID.
     */
    public static function findById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM roles WHERE id = ?", [$id]);
    }

    /**
     * Create a new role.
     */
    public static function create(array $data): int {
        Database::execute(
            "INSERT INTO roles (name, slug, description, is_active, created_at) VALUES (?, ?, ?, 1, NOW())",
            [$data['name'], $data['slug'], $data['description'] ?? '']
        );
        return (int) Database::lastInsertId();
    }

    /**
     * Update a role.
     */
    public static function update(int $id, array $data): void {
        Database::execute(
            "UPDATE roles SET name = ?, slug = ?, description = ?, is_active = ? WHERE id = ?",
            [$data['name'], $data['slug'], $data['description'] ?? '', $data['is_active'] ?? 1, $id]
        );
    }

    /**
     * Get map of role_id => [permission_id_1, permission_id_2, ...] for all roles.
     */
    public static function getAllRolePermissionsMap(): array {
        $rows = Database::fetchAll("SELECT role_id, permission_id FROM role_permissions");
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['role_id']][] = (int)$row['permission_id'];
        }
        return $map;
    }

    /**
     * Get all permissions.
     */
    public static function getAllPermissions(): array {
        return Database::fetchAll("SELECT * FROM permissions ORDER BY module ASC, action ASC");
    }

    /**
     * Get permissions grouped by module.
     */
    public static function getPermissionsGrouped(): array {
        $permissions = self::getAllPermissions();
        $grouped = [];
        foreach ($permissions as $p) {
            $grouped[$p['module']][] = $p;
        }
        return $grouped;
    }

    /**
     * Get structured module & submodule hierarchy with mapped permissions.
     * Ordering and naming strictly matches Admin Panel Sidebar navigation.
     */
    public static function getHierarchicalPermissions(): array {
        $allPerms = self::getAllPermissions();
        $permsMap = [];
        foreach ($allPerms as $p) {
            if (
                ($p['module'] === 'whatsapp_orders' && in_array($p['action'], ['edit', 'delete'], true))
                || ($p['module'] === 'whatsapp_damage' && $p['action'] === 'delete')
                || ($p['module'] === 'website_orders' && in_array($p['action'], ['edit', 'delete'], true))
                || ($p['module'] === 'website_inward' && $p['action'] === 'create')
                || ($p['module'] === 'website_inventory' && $p['action'] === 'edit')
                || ($p['module'] === 'website_damage' && $p['action'] === 'delete')
            ) {
                continue;
            }
            $permsMap[$p['module']][$p['action']] = $p;
        }

        $modules = [
            'dashboard' => [
                'title'      => 'Dashboard',
                'icon'       => 'bi-speedometer2',
                'badge'      => 'bg-primary-subtle text-primary border-primary',
                'department' => 'ALL',
                'submodules' => [
                    'dashboard' => [
                        'name'       => 'Dashboard',
                        'icon'       => 'bi-speedometer2',
                        'desc'       => 'High-level KPI metrics, operational graphs, and summary statistics',
                        'department' => 'ALL',
                    ],
                ]
            ],
            'production' => [
                'title'      => 'Production',
                'icon'       => 'bi-gear-wide-connected',
                'badge'      => 'bg-indigo-subtle text-indigo border-indigo',
                'department' => 'PRODUCTION',
                'submodules' => [
                    'production'          => ['name' => 'Dashboard', 'icon' => 'bi-speedometer', 'desc' => 'Plant overview, line throughput, and live tracking', 'department' => 'PRODUCTION'],
                    'production_billing'  => ['name' => 'Billing', 'icon' => 'bi-receipt', 'desc' => 'Production product billing and tax invoices', 'department' => 'PRODUCTION'],
                    'production_billing_returns' => ['name' => 'Billing Returns & Exchanges', 'icon' => 'bi-arrow-left-right', 'desc' => 'Production customer returns, exchanges, refunds, and QR restocking', 'department' => 'PRODUCTION'],
                    'sellers'             => ['name' => 'Sellers', 'icon' => 'bi-person-badge', 'desc' => 'Job worker directory, vendor ledgers, and contract rates', 'department' => 'PRODUCTION'],
                    'damages'             => ['name' => 'Damages', 'icon' => 'bi-exclamation-octagon', 'desc' => 'Defective fabric, floor damages, and scrap recovery logs', 'department' => 'PRODUCTION'],
                    'catalog'             => ['name' => 'Catalog', 'icon' => 'bi-journal-bookmark', 'desc' => 'Design catalogs, style codes, and Bill of Materials (BOM)', 'department' => 'PRODUCTION'],
                    'programs'            => ['name' => 'Programs', 'icon' => 'bi-calendar-check', 'desc' => 'Cutting programs, size breakdowns, and work order releases', 'department' => 'PRODUCTION'],
                    'cutting'             => ['name' => 'Cutting', 'icon' => 'bi-scissors', 'desc' => 'Roll laying, table cuts, cut piece counts, and bundle creation', 'department' => 'PRODUCTION'],
                    'wastage'             => ['name' => 'Wastage Selling', 'icon' => 'bi-recycle', 'desc' => 'Fabric scrap selling tickets, scrap weights, and scrap invoices', 'department' => 'PRODUCTION'],
                    'stitching'           => ['name' => 'Stitching', 'icon' => 'bi-bounding-box', 'desc' => 'Line allocation, tailor piece-rate tracking, and bundle stitching', 'department' => 'PRODUCTION'],
                    'stitching_wastage'   => ['name' => 'Stitching Wastage', 'icon' => 'bi-trash3', 'desc' => 'Thread/trimming scrap, defect logs, and floor disposal', 'department' => 'PRODUCTION'],
                    'production_expenses' => ['name' => 'Expenses & Assets', 'icon' => 'bi-currency-rupee', 'desc' => 'Machine spare parts, repair costs, and daily floor expenses', 'department' => 'PRODUCTION'],
                    'labels'              => ['name' => 'Labels', 'icon' => 'bi-tag', 'desc' => 'Brand tags, care labels, barcode sticker print batches', 'department' => 'PRODUCTION'],
                    'qc'                  => ['name' => 'QC', 'icon' => 'bi-shield-check', 'desc' => 'AQL inspections, passed/failed piece audits, and rework queues', 'department' => 'PRODUCTION'],
                    'inventory'           => ['name' => 'Inventory', 'icon' => 'bi-boxes', 'desc' => 'Unassigned lot staging bays and finished goods floor storage', 'department' => 'PRODUCTION'],
                    'batches'             => ['name' => 'Batches', 'icon' => 'bi-box-seam', 'desc' => 'Carton packing, SKU bundles, and master box packaging', 'department' => 'PRODUCTION'],
                    'outward'             => ['name' => 'Outward', 'icon' => 'bi-truck', 'desc' => 'Delivery challans and outward dispatch to Central Warehouses', 'department' => 'PRODUCTION'],
                    'warehouse_rework'    => ['name' => 'Warehouse Rework', 'icon' => 'bi-tools', 'desc' => 'Repair and dispatch damaged warehouse goods', 'department' => 'PRODUCTION'],
                ]
            ],
            'warehouse' => [
                'title'      => 'Warehouse',
                'icon'       => 'bi-buildings',
                'badge'      => 'bg-info-subtle text-info border-info',
                'department' => 'WAREHOUSE',
                'submodules' => [
                    'warehouse'          => ['name' => 'Dashboard', 'icon' => 'bi-speedometer2', 'desc' => 'Storage capacity, bay occupancy, and dispatch workflows', 'department' => 'WAREHOUSE'],
                    'inward'             => ['name' => 'Inward / GRN', 'icon' => 'bi-box-arrow-in-down', 'desc' => 'Goods receipt notes, production receipts, and seller inwarding', 'department' => 'WAREHOUSE'],
                    'stock'              => ['name' => 'Stock', 'icon' => 'bi-grid-3x3-gap', 'desc' => 'Bin/rack storage, live SKU quantities, count audits, and price editing', 'department' => 'WAREHOUSE'],
                    'wh_sellers'         => ['name' => 'Suppliers', 'icon' => 'bi-truck-front', 'desc' => 'Fabric/apparel suppliers, procurement logs, and vendor ledgers', 'department' => 'WAREHOUSE'],
                    'wh_catalog'         => ['name' => 'Catalog', 'icon' => 'bi-card-list', 'desc' => 'Master SKU codes, wholesale pricing, and barcode mappings', 'department' => 'WAREHOUSE'],
                    'wh_labels'          => ['name' => 'QR Labels', 'icon' => 'bi-qr-code', 'desc' => 'Shelf labels, bulk carton QR stickers, and barcode printing', 'department' => 'WAREHOUSE'],
                    'purchases'          => ['name' => 'Direct Purchases', 'icon' => 'bi-cart-check', 'desc' => 'Purchase orders, seller invoices, and procurement bills', 'department' => 'WAREHOUSE'],
                    'purchases_returns'  => ['name' => 'Purchases Returns & Exchanges', 'icon' => 'bi-arrow-left-right', 'desc' => 'Direct customer purchase returns, exchanges, invoice credit notes, and restock', 'department' => 'WAREHOUSE'],
                    'allocation'         => ['name' => 'Allocation', 'icon' => 'bi-diagram-3', 'desc' => 'Shop demand planning, box picking, and stock distribution', 'department' => 'WAREHOUSE'],
                    'wh_batches'         => ['name' => 'Batches', 'icon' => 'bi-boxes', 'desc' => 'Carton packing manifests, QR barcodes, and lot consolidation', 'department' => 'WAREHOUSE'],
                    'allocation_outward' => ['name' => 'Outward', 'icon' => 'bi-truck', 'desc' => 'Vehicle loading manifests, transport LR notes, shop deliveries', 'department' => 'WAREHOUSE'],
                    'damage'             => ['name' => 'Damage', 'icon' => 'bi-shield-slash', 'desc' => 'Transit damage, water defect write-offs, and salvage storage', 'department' => 'WAREHOUSE'],
                    'returns'            => ['name' => 'Returns', 'icon' => 'bi-arrow-return-left', 'desc' => 'Shop return packages, restocking evaluation, and RTO parcels', 'department' => 'WAREHOUSE'],
                    'shop_requests'      => ['name' => 'Shop Requests', 'icon' => 'bi-inbox', 'desc' => 'Store urgent replenishment indents and picking authorizations', 'department' => 'WAREHOUSE'],
                    'warehouse_expenses' => ['name' => 'Warehouse Expenses', 'icon' => 'bi-cash-stack', 'desc' => 'Freight charges, utility bills, warehouse maintenance, and vouchers', 'department' => 'WAREHOUSE'],
                    'sales_channels'     => ['name' => 'Sales Channels', 'icon' => 'bi-broadcast', 'desc' => 'Manage warehouse sales-channel connections', 'department' => 'WAREHOUSE'],
                ]
            ],
            'whatsapp' => [
                'title'      => 'WhatsApp Orders',
                'icon'       => 'bi-whatsapp',
                'badge'      => 'bg-success-subtle text-success border-success',
                'department' => 'WHATSAPP',
                'submodules' => [
                    'whatsapp_dashboard' => ['name' => 'Dashboard', 'icon' => 'bi-speedometer2', 'desc' => 'WhatsApp B2B sales throughput, orders summary, and channel KPIs', 'department' => 'WHATSAPP'],
                    'whatsapp_inward'    => ['name' => 'Inward / GRN', 'icon' => 'bi-box-arrow-in-down', 'desc' => 'Direct stock receipts, batch manifests, and inbound goods confirmation', 'department' => 'WHATSAPP'],
                    'whatsapp_inventory' => ['name' => 'Inventory', 'icon' => 'bi-boxes', 'desc' => 'Dedicated channel inventory allocations, SKU balances, and bin stock', 'department' => 'WHATSAPP'],
                    'whatsapp_orders'    => ['name' => 'Orders History', 'icon' => 'bi-clock-history', 'desc' => 'WhatsApp B2B customer orders, order processing, and packing queues', 'department' => 'WHATSAPP'],
                    'whatsapp_damage'    => ['name' => 'Damage Management', 'icon' => 'bi-exclamation-octagon', 'desc' => 'Defective parcels, transit damages, salvage logs, and gate passes', 'department' => 'WHATSAPP'],
                    'whatsapp_returns'   => ['name' => 'Returns & Exchanges', 'icon' => 'bi-arrow-left-right', 'desc' => 'Customer returns, exchanges, restocking inspections, and credit notes', 'department' => 'WHATSAPP'],
                    'whatsapp_return_to_wh' => ['name' => 'Return to Warehouse', 'icon' => 'bi-box-arrow-up-right', 'desc' => 'Return WhatsApp channel products to the warehouse', 'department' => 'WHATSAPP'],
                    'whatsapp_customers' => ['name' => 'Customers', 'icon' => 'bi-people', 'desc' => 'WhatsApp channel customer directory and order history', 'department' => 'WHATSAPP'],
                    'whatsapp_expenses'  => ['name' => 'Channel Expenses', 'icon' => 'bi-cash-stack', 'desc' => 'Channel packaging costs, courier freight, petty cash, and payment vouchers', 'department' => 'WHATSAPP'],
                ]
            ],
            'website' => [
                'title'      => 'Website Orders',
                'icon'       => 'bi-globe',
                'badge'      => 'bg-info-subtle text-info border-info',
                'department' => 'WEBSITE',
                'submodules' => [
                    'website_dashboard' => ['name' => 'Dashboard', 'icon' => 'bi-speedometer2', 'desc' => 'Online store sales volume, conversion stats, and eCommerce KPIs', 'department' => 'WEBSITE'],
                    'website_inward'    => ['name' => 'Inward / GRN', 'icon' => 'bi-box-arrow-in-down', 'desc' => 'Goods inward receipts allocated specifically for website stock inventory', 'department' => 'WEBSITE'],
                    'website_inventory' => ['name' => 'Inventory', 'icon' => 'bi-boxes', 'desc' => 'Online live store stock counts, available SKU variants, and low stock alerts', 'department' => 'WEBSITE'],
                    'website_orders'    => ['name' => 'Orders History', 'icon' => 'bi-clock-history', 'desc' => 'Direct web store orders, payment verifications, and shipping manifests', 'department' => 'WEBSITE'],
                    'website_damage'    => ['name' => 'Damage Management', 'icon' => 'bi-exclamation-octagon', 'desc' => 'Customer trial defects, warehouse damages, write-offs, and outward salvage', 'department' => 'WEBSITE'],
                    'website_returns'   => ['name' => 'Returns & Exchanges', 'icon' => 'bi-arrow-left-right', 'desc' => 'Courier RTO packages, customer return requests, and refund verifications', 'department' => 'WEBSITE'],
                    'website_return_to_wh' => ['name' => 'Return to Warehouse', 'icon' => 'bi-box-arrow-up-right', 'desc' => 'Return Website channel products to the warehouse', 'department' => 'WEBSITE'],
                    'website_customers' => ['name' => 'Customers', 'icon' => 'bi-people', 'desc' => 'Website customer directory and order history', 'department' => 'WEBSITE'],
                    'website_expenses'  => ['name' => 'Channel Expenses', 'icon' => 'bi-cash-stack', 'desc' => 'Hosting, domain fees, courier charges, gateway commissions, and vouchers', 'department' => 'WEBSITE'],
                ]
            ],
            'shop' => [
                'title'      => 'Shop',
                'icon'       => 'bi-shop',
                'badge'      => 'bg-success-subtle text-success border-success',
                'department' => 'SHOP',
                'submodules' => [
                    'shop'                => ['name' => 'Dashboard', 'icon' => 'bi-speedometer2', 'desc' => 'Store daily revenue, top seller items, and counter performance', 'department' => 'SHOP'],
                    'shop_cash'           => ['name' => 'Cash & Cash Flow', 'icon' => 'bi-cash-stack', 'desc' => 'Daily register opening, cash drops, shift handover, and expenses', 'department' => 'SHOP'],
                    'shop_inward'         => ['name' => 'Inward / GRN', 'icon' => 'bi-box-arrow-in-down', 'desc' => 'Scan carton barcodes and receive warehouse allocation shipments', 'department' => 'SHOP'],
                    'shop_stock'          => ['name' => 'Inventory', 'icon' => 'bi-columns-gap', 'desc' => 'On-floor rack counts, size/color breakdown, and price adjustments', 'department' => 'SHOP'],
                    'shop_damage'         => ['name' => 'Damage Management', 'icon' => 'bi-exclamation-octagon', 'desc' => 'Store shelf defects, damaged garments, salvage logs, and write-offs', 'department' => 'SHOP'],
                    'pos'                 => ['name' => 'POS / Billing', 'icon' => 'bi-receipt-cutoff', 'desc' => 'High-speed barcode checkout, discounts, Cash/UPI/Card billing', 'department' => 'SHOP'],
                    'sales'               => ['name' => 'Selled Products', 'icon' => 'bi-file-earmark-text', 'desc' => 'Customer receipts, invoice reprinting, and billing search', 'department' => 'SHOP'],
                    'customers'           => ['name' => 'Customer Details', 'icon' => 'bi-people', 'desc' => 'Shopper directory and purchase histories; create adds customer profiles, edit updates profiles', 'department' => 'SHOP'],
                    'shop_returns'        => ['name' => 'Customer Returns & Exchanges', 'icon' => 'bi-arrow-left-right', 'desc' => 'Customer return slips, item exchanges, and credit note refunds', 'department' => 'SHOP'],
                    'return_to_warehouse' => ['name' => 'Return to Warehouse', 'icon' => 'bi-box-arrow-up-right', 'desc' => 'Defective goods, slow-moving items, and seasonal returns to warehouse', 'department' => 'SHOP'],
                ]
            ],
            'assets' => [
                'title'      => 'Assets',
                'icon'       => 'bi-cpu',
                'badge'      => 'bg-warning-subtle text-warning border-warning',
                'department' => 'ALL',
                'submodules' => [
                    'assets'            => ['name' => 'Overview & Dashboard', 'icon' => 'bi-bar-chart-fill', 'desc' => 'Machinery & equipment registry, live valuation, and depreciation', 'department' => 'ALL'],
                    'asset_scanner'     => ['name' => 'QR & Tag Scanner', 'icon' => 'bi-qr-code-scan', 'desc' => 'Live hardware gun inspection, QR tag decoder, history lookup', 'department' => 'ALL'],
                    'asset_transfers'   => ['name' => 'Inter-Unit Transfers', 'icon' => 'bi-arrow-left-right', 'desc' => 'Movement audit trails between Production, Warehouses, and Shops', 'department' => 'ALL'],
                    'asset_maintenance' => ['name' => 'Maintenance & AMC', 'icon' => 'bi-wrench-adjustable', 'desc' => 'Service tickets, breakdown repairs, AMC logs, technician costs', 'department' => 'ALL'],
                    'asset_bulk_labels' => ['name' => 'Bulk QR Labels', 'icon' => 'bi-qr-code', 'desc' => 'Multi-tag asset sticker printing studio with barcodes', 'department' => 'ALL'],
                ]
            ],
            'reports' => [
                'title'      => 'Reports',
                'icon'       => 'bi-bar-chart-line',
                'badge'      => 'bg-danger-subtle text-danger border-danger',
                'department' => 'ALL',
                'submodules' => [
                    'reports_overview'      => ['name' => 'Overview & P&L', 'icon' => 'bi-pie-chart', 'desc' => 'Executive summary, revenue, gross profit, expenditure, and net profitability', 'department' => 'ALL'],
                    'reports_profitability' => ['name' => 'Revenue & Loss', 'icon' => 'bi-cash-coin', 'desc' => 'Cost of goods sold (COGS), margins, revenue breakdown, and financial health', 'department' => 'ALL'],
                    'reports_sales'         => ['name' => 'Multi-Channel Sales', 'icon' => 'bi-cart-check', 'desc' => 'Combined sales analytics across POS Counter, WhatsApp, and Website', 'department' => 'ALL'],
                    'reports_online'       => ['name' => 'Online & Digital Reports', 'icon' => 'bi-broadcast', 'desc' => 'Consolidated Website and WhatsApp channel sales, orders, payments, and performance', 'department' => 'WHATSAPP,WEBSITE'],
                    'reports_website'       => ['name' => 'Website Reports', 'icon' => 'bi-globe2', 'desc' => 'Online visitor conversion, sales volume, courier delivery rate, and returns', 'department' => 'WEBSITE'],
                    'reports_whatsapp'      => ['name' => 'WhatsApp Reports', 'icon' => 'bi-whatsapp', 'desc' => 'WhatsApp B2B sales throughput, top buyers, channel revenue, and growth', 'department' => 'WHATSAPP'],
                    'reports_production'    => ['name' => 'Production Reports', 'icon' => 'bi-gear', 'desc' => 'Floor cutting yields, tailor piece-rates, sewing output, and scrap wastage', 'department' => 'PRODUCTION'],
                    'reports_warehouse'     => ['name' => 'Warehouse Reports', 'icon' => 'bi-buildings', 'desc' => 'Storage utilization, inbound receipts, dispatch turnaround, and stock turnover', 'department' => 'WAREHOUSE'],
                    'reports_purchase'      => ['name' => 'Purchase Reports', 'icon' => 'bi-truck', 'desc' => 'Supplier procurement volume, vendor purchase bills, returns, and payment dues', 'department' => 'WAREHOUSE'],
                    'reports_shop'          => ['name' => 'Shop Reports', 'icon' => 'bi-shop', 'desc' => 'Store sales receipts, cashier performance, daily cash register, and customer CRM', 'department' => 'SHOP'],
                    'reports_inventory'     => ['name' => 'Inventory Reports', 'icon' => 'bi-boxes', 'desc' => 'Real-time stock valuation across all units, slow-moving items, and dead stock', 'department' => 'WAREHOUSE'],
                    'reports'               => ['name' => 'General Reports', 'icon' => 'bi-file-earmark-bar-graph', 'desc' => 'Access the general production reports page', 'department' => 'ALL'],
                ]
            ],
            'staff' => [
                'title'      => 'Staff',
                'icon'       => 'bi-people',
                'badge'      => 'bg-primary-subtle text-primary border-primary',
                'department' => 'ALL',
                'submodules' => [
                    'staff' => ['name' => 'Total Staffs', 'icon' => 'bi-person-lines-fill', 'desc' => 'Manage employee accounts, phone numbers, and unit assignments', 'department' => 'ALL'],
                    'roles' => ['name' => 'Role Assign / Permissions', 'icon' => 'bi-shield-lock', 'desc' => 'Configure role permissions, access rights, and module security', 'department' => 'ALL'],
                ]
            ],
            'system' => [
                'title'      => 'System',
                'icon'       => 'bi-sliders',
                'badge'      => 'bg-dark-subtle text-dark border-dark',
                'department' => 'ALL',
                'submodules' => [
                    'audit'    => ['name' => 'Audit Logs', 'icon' => 'bi-journal-text', 'desc' => 'System activity log, user IP address, and change timestamps', 'department' => 'ALL'],
                    'settings' => ['name' => 'Settings', 'icon' => 'bi-sliders', 'desc' => 'Company profile, invoice prefixes, tax rates, and configurations', 'department' => 'ALL'],
                    'backup'   => ['name' => 'Backup', 'icon' => 'bi-database-down', 'desc' => 'Create SQL snapshots, download backups, and restore data', 'department' => 'ALL'],
                    'master'   => ['name' => 'Master Data', 'icon' => 'bi-database', 'desc' => 'Manage shared master records', 'department' => 'ALL'],
                    'notifications' => ['name' => 'Notifications', 'icon' => 'bi-bell', 'desc' => 'View, read, and clear system notifications', 'department' => 'ALL'],
                ]
            ]
        ];

        // Attach resolved permissions to each submodule
        foreach ($modules as $modKey => &$modData) {
            foreach ($modData['submodules'] as $subKey => &$subData) {
                $subData['permissions'] = $permsMap[$subKey] ?? [];
                if (!isset($subData['department'])) {
                    $subData['department'] = $modData['department'] ?? 'ALL';
                }
            }
        }

        return $modules;
    }

    public static function getCrudPermissionsByAction(array $permissions): array {
        $columns = [
            'view' => [],
            'create' => [],
            'edit' => [],
            'delete' => [],
        ];

        foreach ($permissions as $action => $permission) {
            if (isset($columns[$action])) {
                $columns[$action][$action] = $permission;
            }
        }

        return $columns;
    }

    /**
     * Keep only valid permission IDs for CRUD actions.
     */
    public static function filterCrudPermissionIds(array $permissionIds): array {
        $permissionIds = array_values(array_unique(array_filter(
            array_map('intval', $permissionIds),
            static fn(int $id): bool => $id > 0
        )));
        if (empty($permissionIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($permissionIds), '?'));
        $rows = Database::fetchAll(
            "SELECT id FROM permissions WHERE id IN ($placeholders) AND action IN ('view', 'create', 'edit', 'delete')",
            $permissionIds
        );
        $allowedIds = array_fill_keys(array_map('intval', array_column($rows, 'id')), true);

        return array_values(array_filter(
            $permissionIds,
            static fn(int $id): bool => isset($allowedIds[$id])
        ));
    }

    /**
     * Get permission IDs for a role.
     */
    public static function getRolePermissionIds(int $roleId): array {
        $rows = Database::fetchAll(
            "SELECT permission_id FROM role_permissions WHERE role_id = ?",
            [$roleId]
        );
        return array_column($rows, 'permission_id');
    }

    /**
     * Save permissions for a role (replace all).
     */
    public static function savePermissions(int $roleId, array $permissionIds): void {
        $permissionIds = self::filterCrudPermissionIds($permissionIds);
        Database::beginTransaction();
        try {
            Database::execute("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
            foreach ($permissionIds as $permId) {
                Database::execute(
                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                    [$roleId, (int)$permId]
                );
            }
            Database::commit();
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Count users in a role.
     */
    public static function getUserCount(int $roleId): int {
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM users WHERE role_id = ?",
            [$roleId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Delete a role.
     */
    public static function delete(int $id): bool {
        Database::beginTransaction();
        try {
            Database::execute("DELETE FROM role_permissions WHERE role_id = ?", [$id]);
            Database::execute("UPDATE users SET role_id = NULL WHERE role_id = ?", [$id]);
            Database::execute("DELETE FROM roles WHERE id = ?", [$id]);
            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
}
