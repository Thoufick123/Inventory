<?php
/**
 * Admin Controller
 * Dashboard, staff management, and role management.
 */

require_once BASE_PATH . '/app/Models/User.php';
require_once BASE_PATH . '/app/Models/Role.php';
require_once BASE_PATH . '/app/Models/Concern.php';
require_once BASE_PATH . '/app/Models/ProductionUnit.php';
require_once BASE_PATH . '/app/Models/Warehouse.php';
require_once BASE_PATH . '/app/Models/Shop.php';
require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Services/LegacyConcernDataService.php';

class AdminController {

    private function isValidGstNumber(string $gstNumber): bool {
        return $gstNumber === '' || preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstNumber) === 1;
    }

    /**
     * Admin Dashboard.
     */
    public function dashboard(): void {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        $stockConcernId = $concernId ?? 0;
        if (!hasPermission('dashboard', 'view')) {
            $landingPages = [
                'production' => 'production/dashboard',
                'sellers' => 'production/sellers',
                'catalog' => 'production/catalog',
                'programs' => 'production/programs',
                'cutting' => 'production/cutting',
                'stitching' => 'production/stitching',
                'labels' => 'production/labels',
                'qc' => 'production/qc',
                'inventory' => 'production/inventory',
                'batches' => 'production/batches',
                'outward' => 'production/outward',
                'warehouse_rework' => 'production/warehouse-rework',
                'production_billing' => 'production/billing',
                'production_cash' => 'production/cash',
                'production_expenses' => 'production/expenses',
                'warehouse' => 'warehouse/dashboard',
                'inward' => 'warehouse/inward',
                'stock' => 'warehouse/stock',
                'purchases' => 'warehouse/purchases',
                'allocation' => 'warehouse/allocation',
                'returns' => 'warehouse/returns',
                'website_dashboard' => 'warehouse/website-orders',
                'website_orders' => 'warehouse/website-orders',
                'whatsapp_dashboard' => 'warehouse/whatsapp-orders',
                'whatsapp_orders' => 'warehouse/whatsapp-orders',
                'shop' => 'shop/dashboard',
                'pos' => 'shop/pos',
                'assets' => 'asset/dashboard',
                'reports' => 'reports/sales',
                'staff' => 'admin/staff?department=all',
                'roles' => 'admin/roles',
            ];
            foreach ($landingPages as $module => $page) {
                if (hasPermission($module, 'view')) {
                    Response::redirect(url($page));
                    return;
                }
            }
            Response::error(403, 'Access denied. You do not have permission to view the executive dashboard.');
            return;
        }

        // Aggregate KPIs
        $data = [
            'pageTitle'    => 'Dashboard',
            'activeMenu'   => 'dashboard',
            'breadcrumb'   => [['label' => 'Dashboard']],
        ];

        // Organization Units with rich metrics
        $productionUnits = ProductionUnit::getWithMetrics();
        $warehouses      = Warehouse::getWithMetrics();
        $shops           = Shop::getWithMetrics();
        $managersList    = User::getAll(['is_active' => 1]);
        
        $whatsappChannels = getScopedSalesChannels('WHATSAPP', false);
        $websiteChannels  = getScopedSalesChannels('WEBSITE', false);

        $data['productionUnits']      = $productionUnits;
        $data['warehouses']           = $warehouses;
        $data['shops']                = $shops;
        $data['managersList']         = $managersList;
        $data['whatsappChannels']     = $whatsappChannels;
        $data['websiteChannels']      = $websiteChannels;
        $data['totalProductionUnits'] = count($productionUnits);
        $data['totalWarehouses']      = count($warehouses);
        $data['totalShops']           = count($shops);
        $data['totalWhatsapp']        = count($whatsappChannels);
        $data['totalWebsite']         = count($websiteChannels);

        // Total products
        $result = Database::fetchOne("SELECT COUNT(*) as cnt FROM products WHERE is_active = 1");
        $data['totalProducts'] = $result['cnt'] ?? 0;

        // Total stock value (across all warehouses and shops)
        $result = Database::fetchOne(
            "SELECT COALESCE(SUM(sb.quantity * pv.selling_price), 0) as total 
             FROM stock_balances sb 
             JOIN product_variants pv ON sb.variant_id = pv.id 
             WHERE sb.stock_state = 'AVAILABLE' AND sb.concern_id = ?",
            [$stockConcernId]
        );
        $data['totalStockValue'] = $result['total'] ?? 0;

        // Today's sales
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM sales
             WHERE concern_id = ? AND DATE(created_at) = CURDATE() AND status != 'CANCELLED'",
            [$stockConcernId]
        );
        $data['todaySales'] = $result['cnt'] ?? 0;

        // Low stock count
        $result = Database::fetchOne(
            "SELECT COUNT(DISTINCT sb.variant_id) as cnt 
             FROM stock_balances sb 
             JOIN product_variants pv ON sb.variant_id = pv.id 
             JOIN products p ON pv.product_id = p.id 
             WHERE sb.stock_state = 'AVAILABLE' AND sb.concern_id = ?
               AND sb.quantity <= p.min_stock AND p.min_stock > 0",
            [$stockConcernId]
        );
        $data['lowStockCount'] = $result['cnt'] ?? 0;

        // Pending production programs
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM production_programs WHERE status IN ('DRAFT', 'APPROVED', 'SCHEDULED', 'IN_PROGRESS')"
        );
        $data['pendingProduction'] = $result['cnt'] ?? 0;

        // Pending QC
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM qc_batches WHERE status = 'PENDING'"
        );
        $data['pendingQC'] = $result['cnt'] ?? 0;

        // Pending allocation
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM warehouse_allocations WHERE status IN ('REQUESTED', 'STOCK_CHECK', 'RESERVED', 'APPROVED')"
        );
        $data['pendingAllocation'] = $result['cnt'] ?? 0;

        // Pending returns
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM returns
             WHERE concern_id = ? AND inspection_status IN ('PENDING', 'INSPECTING')",
            [$stockConcernId]
        );
        $data['pendingReturns'] = $result['cnt'] ?? 0;

        // Recent sales
        $data['recentSales'] = Database::fetchAll(
            "SELECT s.*, c.name as customer_name 
             FROM sales s 
             LEFT JOIN customers c ON s.customer_id = c.id AND c.concern_id = s.concern_id
             WHERE s.concern_id = ?
             ORDER BY s.created_at DESC LIMIT 10",
            [$stockConcernId]
        );

        // Low stock items
        $data['lowStockItems'] = Database::fetchAll(
            "SELECT sb.quantity, pv.sku, p.name as product_name, p.min_stock
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             WHERE sb.stock_state = 'AVAILABLE' AND sb.concern_id = ?
               AND sb.quantity <= p.min_stock AND p.min_stock > 0
             ORDER BY sb.quantity ASC LIMIT 10",
            [$stockConcernId]
        );

        // Recent Audit Activity Logs
        $data['recentAuditLogs'] = AuditService::getLogs([], 6, 0);
        $data['auditStats'] = AuditService::getStats();

        Response::view('admin.dashboard', $data);
    }

    /**
     * Staff List.
     */
    public function staffList(): void {
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
        $filters = [
            'search'             => $_GET['search'] ?? '',
            'role_id'            => $_GET['role_id'] ?? '',
            'department'         => '',
            'concern_id'         => $_GET['concern_id'] ?? '',
            'production_unit_id' => $_GET['production_unit_id'] ?? '',
            'warehouse_id'       => $_GET['warehouse_id'] ?? '',
            'shop_id'            => $_GET['shop_id'] ?? '',
        ];
        if ($status !== null) {
            $filters['is_active'] = $status;
        }

        $staff           = User::getAll($filters);
        $roles           = Role::getActive();
        $productionUnits = ProductionUnit::getWithMetrics();
        $warehouses      = Warehouse::getWithMetrics();
        $shops           = Shop::getWithMetrics();
        $concerns        = Concern::getAll(true);

        $activeMenu = 'staff-all';

        Response::view('admin.staff_list', [
            'pageTitle'       => 'Staff Management',
            'activeModule'    => 'staff',
            'activeMenu'      => $activeMenu,
            'breadcrumb'      => [['label' => 'Staff']],
            'staff'           => $staff,
            'roles'           => $roles,
            'productionUnits' => $productionUnits,
            'warehouses'      => $warehouses,
            'shops'           => $shops,
            'concerns'        => $concerns,
            'filters'         => $filters,
        ]);
    }

    public function viewStaff(string $id): void {
        $staff = User::findById((int)$id);
        if (!$staff || (int)$staff['role_id'] === 1 || $staff['username'] === 'admin' || ($staff['role_slug'] ?? '') === 'admin') {
            Response::error(404, 'Staff member not found.');
            return;
        }

        $assignments = array_filter([
            'Production Unit' => $staff['production_unit_name'] ?? null,
            'Warehouse'       => $staff['warehouse_name'] ?? null,
            'Shop'            => $staff['shop_name'] ?? null,
            'Sales Channel'   => $staff['channel_name'] ?? null,
        ]);
        $concernNames = [];
        foreach (Concern::getUserConcernIds((int)$staff['id']) as $concernId) {
            $concern = Concern::find($concernId);
            if ($concern) {
                $concernNames[] = $concern['name'];
            }
        }

        Response::view('admin.staff_view', [
            'pageTitle'    => 'Staff Details',
            'activeModule' => 'staff',
            'activeMenu'   => 'staff-all',
            'breadcrumb'   => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Details'],
            ],
            'staff'        => $staff,
            'assignments'  => $assignments,
            'concernNames' => $concernNames,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'staffPermissions'    => User::getPermissionIds((int)$staff['id']),
        ]);
    }

    /**
     * Show create staff form.
     */
    public function createStaff(): void {
        if (!hasPermission('staff', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create staff members.');
            return;
        }

        $roles            = Role::getActive();
        $productionUnits  = ProductionUnit::getWithMetrics();
        $warehouses       = Warehouse::getWithMetrics();
        $shops            = Shop::getWithMetrics();
        $whatsappChannels = getScopedSalesChannels('WHATSAPP');
        $websiteChannels  = getScopedSalesChannels('WEBSITE');

        Response::view('admin.staff_form', [
            'pageTitle'           => 'Create Staff',
            'activeModule'        => 'staff',
            'activeMenu'          => 'staff-create',
            'breadcrumb'          => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Create'],
            ],
            'roles'               => $roles,
            'productionUnits'     => $productionUnits,
            'warehouses'          => $warehouses,
            'whatsappChannels'    => $whatsappChannels,
            'websiteChannels'     => $websiteChannels,
            'shops'               => $shops,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'rolePermissionsMap'  => Role::getAllRolePermissionsMap(),
            'staffPermissions'    => [],
            'concerns'            => Concern::getAll(true),
            'staffConcernIds'     => [],
            'staff'               => null,
        ]);
    }

    /**
     * Store new staff.
     */
    public function storeStaff(): void {
        if (!hasPermission('staff', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create staff members.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
            return;
        }

        $validator = Validator::make($_POST)
            ->required('username')
            ->required('full_name')
            ->required('password')
            ->required('role_id')
            ->minLength('password', 6)
            ->unique('username', 'users');

        if (!empty($_POST['email'])) {
            $validator->email('email');
        }

        if (!empty($_POST['phone'])) {
            $cleanPhone = preg_replace('/^(\+91|91)\s*/', '', trim((string)$_POST['phone']));
            $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $cleanPhone);
            $_POST['phone'] = $cleanPhone;

            $validator->phone('phone', 'Phone number')
                      ->unique('phone', 'users', 'phone', null, 'Phone number');
        }

        if ($validator->fails()) {
            Session::set('_old_input', $_POST);
            Response::back('error', implode(' ', $validator->allErrors()));
            return;
        }
        $selectedConcernIds = array_values(array_filter(
            array_map('intval', is_array($_POST['concern_ids'] ?? null) ? $_POST['concern_ids'] : []),
            static fn(int $concernId): bool => $concernId > 0
        ));
        if (!$selectedConcernIds) {
            Response::back('error', 'Assign at least one concern to this staff member.');
            return;
        }

        try {
            $id = User::create($_POST);
            $newSnapshot = $this->prepareStaffSnapshot($_POST);
            AuditService::log('staff', 'create', 'users', $id, null, $newSnapshot);
            Response::redirect(url('admin/staff'), 'success', 'Staff member created successfully.');
        } catch (Exception $e) {
            error_log("Error creating staff: " . $e->getMessage());
            Response::back('error', 'Failed to create staff member: ' . $e->getMessage());
        }
    }

    /**
     * Helper to prepare human-readable staff attribute snapshots for audit logging.
     */
    private function prepareStaffSnapshot(array $data, ?array $userRecord = null): array {
        $roleName = $userRecord['role_name'] ?? null;
        if (!empty($data['role_id'])) {
            $role = Role::findById((int)$data['role_id']);
            $roleName = $role['name'] ?? $roleName;
        }

        $puName = $userRecord['production_unit_name'] ?? null;
        if (array_key_exists('production_unit_id', $data)) {
            $puId = (int)$data['production_unit_id'];
            $pu = $puId ? ProductionUnit::find($puId) : null;
            $puName = $pu['name'] ?? ($puId ? 'Unit #' . $puId : 'None (Unassigned)');
        }

        $whName = $userRecord['warehouse_name'] ?? null;
        if (array_key_exists('warehouse_id', $data)) {
            $whId = (int)$data['warehouse_id'];
            $wh = $whId ? Warehouse::find($whId) : null;
            $whName = $wh['name'] ?? ($whId ? 'Warehouse #' . $whId : 'None (Unassigned)');
        }

        $shopName = $userRecord['shop_name'] ?? null;
        if (array_key_exists('shop_id', $data)) {
            $shopId = (int)$data['shop_id'];
            $shop = $shopId ? Shop::find($shopId) : null;
            $shopName = $shop['name'] ?? ($shopId ? 'Shop #' . $shopId : 'None (Unassigned)');
        }

        $chanName = $userRecord['channel_name'] ?? null;
        if (array_key_exists('channel_id', $data) || array_key_exists('whatsapp_channel_id', $data) || array_key_exists('website_channel_id', $data)) {
            $chanId = (int)($data['channel_id'] ?? $data['whatsapp_channel_id'] ?? $data['website_channel_id'] ?? 0);
            if ($chanId) {
                $concernId = currentConcernId();
                $chan = $concernId === null ? null : Database::fetchOne(
                    "SELECT name, code FROM sales_channels WHERE id = ? AND concern_id = ?",
                    [$chanId, $concernId]
                );
                $chanName = $chan ? $chan['name'] . ' (' . $chan['code'] . ')' : 'Channel #' . $chanId;
            } else {
                $chanName = 'None (Unassigned)';
            }
        }

        $snapshot = [
            'Staff Name'        => $data['full_name'] ?? ($userRecord['full_name'] ?? ''),
            'Username'          => $data['username'] ?? ($userRecord['username'] ?? ''),
            'Role'              => $roleName ?: 'Staff',
            'Department'        => $data['department'] ?? ($userRecord['department'] ?? 'PRODUCTION'),
            'Email'             => !empty($data['email']) ? $data['email'] : ($userRecord['email'] ?? '-'),
            'Phone'             => !empty($data['phone']) ? $data['phone'] : ($userRecord['phone'] ?? '-'),
            'Production Unit'   => $puName ?: 'None',
            'Warehouse'         => $whName ?: 'None',
            'Shop'              => $shopName ?: 'None',
            'Sales Channel'     => $chanName ?: 'None',
            'Concerns'          => implode(', ', array_column(
                array_filter(array_map(
                    static fn($concernId) => Concern::find((int)$concernId),
                    is_array($data['concern_ids'] ?? null) ? $data['concern_ids'] : ($userRecord ? Concern::getUserConcernIds((int)$userRecord['id']) : [])
                )),
                'name'
            )) ?: 'None',
            'Reports Access'    => $data['reports_access'] ?? ($userRecord['reports_access'] ?? 'ALL'),
            'Account Status'    => isset($data['is_active']) ? ((int)$data['is_active'] ? 'Active' : 'Deactivated') : ($userRecord ? ((int)($userRecord['is_active'] ?? 1) ? 'Active' : 'Deactivated') : 'Active'),
        ];

        if (!empty($data['password'])) {
            $snapshot['Password'] = '[UPDATED / ENCRYPTED]';
        }

        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $snapshot['Custom Permissions'] = count($data['permissions']) . ' specific permissions assigned';
        }

        return $snapshot;
    }

    /**
     * Show edit staff form.
     */
    public function editStaff(string $id): void {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit staff members.');
            return;
        }

        $staffId = (int) $id;
        $staff = User::findById($staffId);
        if (!$staff) Response::error(404, 'Staff not found.');

        if ((int)$staff['role_id'] === 1 || $staff['username'] === 'admin' || ($staff['role_slug'] ?? '') === 'admin') {
            Response::redirect(url('admin/staff'), 'warning', 'Super Administrator account is managed under System Settings.');
        }

        $roles            = Role::getActive();
        $productionUnits  = ProductionUnit::getWithMetrics();
        $warehouses       = Warehouse::getWithMetrics();
        $shops            = Shop::getWithMetrics();
        $whatsappChannels = getScopedSalesChannels('WHATSAPP');
        $websiteChannels  = getScopedSalesChannels('WEBSITE');

        Response::view('admin.staff_form', [
            'pageTitle'           => 'Edit Staff',
            'activeModule'        => 'staff',
            'activeMenu'          => 'staff',
            'breadcrumb'          => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Edit'],
            ],
            'roles'               => $roles,
            'productionUnits'     => $productionUnits,
            'warehouses'          => $warehouses,
            'whatsappChannels'    => $whatsappChannels,
            'websiteChannels'     => $websiteChannels,
            'shops'               => $shops,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'rolePermissionsMap'  => Role::getAllRolePermissionsMap(),
            'staffPermissions'    => User::getPermissionIds($staffId),
            'concerns'            => Concern::getAll(true),
            'staffConcernIds'     => Concern::getUserConcernIds($staffId),
            'staff'               => $staff,
        ]);
    }

    /**
     * Update staff.
     */
    public function updateStaff(string $id): void {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit staff members.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $staffId = (int) $id;
        $oldStaff = User::findById($staffId);
        if (!$oldStaff) Response::error(404, 'Staff not found.');

        $validator = Validator::make($_POST)
            ->required('full_name')
            ->required('role_id');

        if (!empty($_POST['email'])) {
            $validator->email('email');
        }

        if (!empty($_POST['phone'])) {
            $cleanPhone = preg_replace('/^(\+91|91)\s*/', '', trim((string)$_POST['phone']));
            $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $cleanPhone);
            $_POST['phone'] = $cleanPhone;

            $validator->phone('phone', 'Phone number')
                      ->unique('phone', 'users', 'phone', $staffId, 'Phone number');
        }

        if ($validator->fails()) {
            Response::back('error', implode(' ', $validator->allErrors()));
            return;
        }
        $selectedConcernIds = array_values(array_filter(
            array_map('intval', is_array($_POST['concern_ids'] ?? null) ? $_POST['concern_ids'] : []),
            static fn(int $concernId): bool => $concernId > 0
        ));
        if (!$selectedConcernIds) {
            Response::back('error', 'Assign at least one concern to this staff member.');
            return;
        }

        try {
            $oldSnapshot = $this->prepareStaffSnapshot([], $oldStaff);
            $newSnapshot = $this->prepareStaffSnapshot($_POST, $oldStaff);
            User::update($staffId, $_POST);
            AuditService::log('staff', 'update', 'users', $staffId, $oldSnapshot, $newSnapshot);
            Response::redirect(url('admin/staff'), 'success', 'Staff member updated successfully.');
        } catch (Exception $e) {
            error_log("Error updating staff: " . $e->getMessage());
            Response::back('error', 'Failed to update staff member: ' . $e->getMessage());
        }
    }

    /**
     * AJAX check phone format & uniqueness.
     */
    public function checkPhoneAvailability(): void {
        $phone = trim((string)($_GET['phone'] ?? ''));
        $exceptId = !empty($_GET['except_id']) ? (int)$_GET['except_id'] : null;

        if ($phone === '') {
            Response::json(['valid' => true, 'available' => true, 'message' => '']);
            return;
        }

        // Clean phone
        $cleanPhone = preg_replace('/^(\+91|91)\s*/', '', $phone);
        $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $cleanPhone);

        // Check if non-digits are present
        if (!ctype_digit($cleanPhone)) {
            Response::json([
                'valid' => false,
                'available' => false,
                'message' => 'Phone number must only contain numeric digits (0-9).'
            ]);
            return;
        }

        // Check length
        if (strlen($cleanPhone) !== 10) {
            Response::json([
                'valid' => false,
                'available' => false,
                'message' => 'Phone number must be exactly 10 digits.'
            ]);
            return;
        }

        // Check Indian starting digit (6-9)
        if (!in_array($cleanPhone[0], ['6', '7', '8', '9'])) {
            Response::json([
                'valid' => false,
                'available' => false,
                'message' => 'Indian mobile numbers must start with 6, 7, 8, or 9.'
            ]);
            return;
        }

        // Check duplicate in database
        $sql = "SELECT id, full_name, username FROM users WHERE phone = ?";
        $params = [$cleanPhone];
        if ($exceptId !== null) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }

        $existing = Database::fetchOne($sql, $params);
        if ($existing) {
            Response::json([
                'valid' => true,
                'available' => false,
                'message' => "Phone number is already registered to '{$existing['full_name']}' (@{$existing['username']})."
            ]);
            return;
        }

        Response::json([
            'valid' => true,
            'available' => true,
            'message' => 'Phone number is valid and available.'
        ]);
    }

    /**
     * Toggle staff active status.
     */
    public function toggleStaff(string $id): void {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to update staff status.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $staffId = (int) $id;
        $staff = User::findById($staffId);
        if (!$staff) Response::error(404, 'Staff not found.');

        if ((int)$staff['role_id'] === 1 || $staff['username'] === 'admin' || ($staff['role_slug'] ?? '') === 'admin') {
            Response::back('error', 'Super Administrator account status cannot be modified here.');
        }

        User::toggleActive($staffId);
        $action = $staff['is_active'] ? 'deactivated' : 'activated';
        AuditService::log(
            'staff',
            'status_change',
            'users',
            $staffId,
            [
                'Staff Name'     => $staff['full_name'],
                'Username'       => $staff['username'],
                'Account Status' => $staff['is_active'] ? 'Active' : 'Deactivated'
            ],
            [
                'Staff Name'     => $staff['full_name'],
                'Username'       => $staff['username'],
                'Account Status' => !$staff['is_active'] ? 'Active' : 'Deactivated'
            ]
        );
        Response::redirect(url('admin/staff'), 'success', "Staff member $action successfully.");
    }

    /**
     * Delete staff member.
     */
    public function deleteStaff(string $id): void {
        if (!hasPermission('staff', 'delete')) {
            Response::error(403, 'Access denied: You do not have permission to delete staff members.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
        }

        $staffId = (int) $id;
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($staffId === $currentUserId) {
            Response::back('error', 'You cannot delete your own account.');
        }

        $staff = User::findById($staffId);
        if (!$staff) Response::error(404, 'Staff not found.');

        if ((int)$staff['role_id'] === 1 || $staff['username'] === 'admin' || ($staff['role_slug'] ?? '') === 'admin') {
            Response::back('error', 'Super Administrator account cannot be deleted.');
        }

        try {
            $delSnapshot = $this->prepareStaffSnapshot([], $staff);
            User::delete($staffId);
            AuditService::log('staff', 'delete', 'users', $staffId, $delSnapshot, null);
            Response::redirect(url('admin/staff'), 'success', "Staff member '{$staff['full_name']}' deleted successfully.");
        } catch (Exception $e) {
            error_log("Error deleting staff: " . $e->getMessage());
            Response::back('error', 'Failed to delete staff member. Make sure no dependent records exist.');
        }
    }

    /**
     * Roles List.
     */
    public function roles(): void {
        if (!hasPermission('roles', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view roles.');
            return;
        }

        $roles = Role::getAll();
        $roles = Database::fetchAll("SELECT * FROM roles WHERE id != 1 ORDER BY name ASC");
        // Count users per role
        foreach ($roles as &$role) {
            $role['user_count'] = Role::getUserCount($role['id']);
        }

        Response::view('admin.roles_list', [
            'pageTitle'    => 'Roles & Permissions',
            'activeModule' => 'staff',
            'activeMenu'   => 'roles',
            'breadcrumb'   => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles & Permissions'],
            ],
            'roles'        => $roles,
        ]);
    }

    /**
     * Create role form.
     */
    public function createRole(): void {
        if (!hasPermission('roles', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create roles.');
            return;
        }

        $concerns = Concern::getAll(true);
        $selectedConcernId = (int)($_GET['concern_id'] ?? 0);
        $selectedConcern = null;
        foreach ($concerns as $concern) {
            if ((int)$concern['id'] === $selectedConcernId) {
                $selectedConcern = $concern;
                break;
            }
        }

        Response::view('admin.role_form', [
            'pageTitle'              => 'Add New Role',
            'activeModule'           => 'staff',
            'activeMenu'             => 'roles',
            'breadcrumb'             => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles', 'url' => url('admin/roles')],
                ['label' => 'Create'],
            ],
            'role'                   => null,
            'concerns'               => $concerns,
            'selectedConcernId'      => $selectedConcernId,
            'selectedConcern'        => $selectedConcern,
            'hierarchicalModules'    => Role::filterHierarchicalPermissions(
                Role::getHierarchicalPermissions(),
                $selectedConcern['module_keys'] ?? []
            ),
            'assignedPermissionIds'  => [],
        ]);
    }

    /**
     * Store new role.
     */
    public function storeRole(): void {
        if (!hasPermission('roles', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create roles.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
            return;
        }

        $name          = trim($_POST['name'] ?? '');
        $slugInput     = trim($_POST['slug'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $isActive      = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissionIds = $_POST['permissions'] ?? [];
        $concern = Concern::find((int)($_POST['concern_id'] ?? 0));

        if (empty($name)) {
            Response::back('error', 'Role Name is required.');
            return;
        }
        if (!$concern || empty($concern['is_active'])) {
            Response::back('error', 'Select a valid concern before saving role permissions.');
            return;
        }

        // Prepare unique role slug
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slugInput ?: $name), '-'));
        $slug = $baseSlug ?: 'role-' . time();
        $count = 1;
        while (Database::fetchOne("SELECT id FROM roles WHERE slug = ?", [$slug])) {
            $slug = $baseSlug . '-' . (++$count);
        }

        try {
            $roleId = Role::create([
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'is_active'   => $isActive
            ]);

            Role::savePermissionsForModules($roleId, is_array($permissionIds) ? $permissionIds : [], $concern['module_keys']);

            AuditService::log('roles', 'create', 'roles', $roleId, null, $_POST);
            Response::redirect(url('admin/roles'), 'success', "Role '{$name}' created with permissions for {$concern['name']}.");
        } catch (Exception $e) {
            error_log("Error creating role: " . $e->getMessage());
            Response::back('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Edit role form.
     */
    public function editRole(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit roles.');
            return;
        }

        $role = Role::findById((int) $id);
        if (!$role) {
            Response::error(404, 'Role not found.');
            return;
        }

        $concerns = Concern::getAll(true);
        $selectedConcernId = (int)($_GET['concern_id'] ?? 0);
        $selectedConcern = null;
        foreach ($concerns as $concern) {
            if ((int)$concern['id'] === $selectedConcernId) {
                $selectedConcern = $concern;
                break;
            }
        }
        $moduleKeys = $selectedConcern['module_keys'] ?? [];

        Response::view('admin.role_form', [
            'pageTitle'              => 'Edit Role: ' . ($role['name'] ?? ''),
            'activeModule'           => 'staff',
            'activeMenu'             => 'roles',
            'breadcrumb'             => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles', 'url' => url('admin/roles')],
                ['label' => 'Edit'],
            ],
            'role'                   => $role,
            'concerns'               => $concerns,
            'selectedConcernId'      => $selectedConcernId,
            'selectedConcern'        => $selectedConcern,
            'hierarchicalModules'    => Role::filterHierarchicalPermissions(
                Role::getHierarchicalPermissions(),
                $moduleKeys
            ),
            'assignedPermissionIds'  => Role::getRolePermissionIdsForModules((int)$id, $moduleKeys),
        ]);
    }

    /**
     * Update role.
     */
    public function updateRole(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit roles.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
            return;
        }

        $roleId        = (int) $id;
        $role          = Role::findById($roleId);
        if (!$role) {
            Response::error(404, 'Role not found.');
            return;
        }

        $name          = trim($_POST['name'] ?? '');
        $slugInput     = trim($_POST['slug'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $isActive      = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissionIds = $_POST['permissions'] ?? [];
        $concern = Concern::find((int)($_POST['concern_id'] ?? 0));

        if (empty($name)) {
            Response::back('error', 'Role Name is required.');
            return;
        }
        if (!$concern || empty($concern['is_active'])) {
            Response::back('error', 'Select a valid concern before saving role permissions.');
            return;
        }

        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slugInput ?: $name), '-'));
        $slug = $baseSlug ?: 'role-' . $roleId;
        $count = 1;
        while ($existing = Database::fetchOne("SELECT id FROM roles WHERE slug = ? AND id != ?", [$slug, $roleId])) {
            $slug = $baseSlug . '-' . (++$count);
        }

        try {
            Role::update($roleId, [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'is_active'   => $isActive
            ]);

            Role::savePermissionsForModules($roleId, is_array($permissionIds) ? $permissionIds : [], $concern['module_keys']);

            AuditService::log('roles', 'update', 'roles', $roleId, $role, $_POST);
            Response::redirect(url('admin/roles'), 'success', "Role '{$name}' updated; permissions for {$concern['name']} were saved.");
        } catch (Exception $e) {
            error_log("Error updating role: " . $e->getMessage());
            Response::back('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Manage role permissions.
     */
    public function managePermissions(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to manage permissions.');
            return;
        }

        $role = Role::findById((int) $id);
        if (!$role) Response::error(404, 'Role not found.');

        $concerns = Concern::getAll(true);
        $selectedConcernId = (int)($_GET['concern_id'] ?? 0);
        $selectedConcern = null;
        foreach ($concerns as $concern) {
            if ((int)$concern['id'] === $selectedConcernId) {
                $selectedConcern = $concern;
                break;
            }
        }
        $moduleKeys = $selectedConcern['module_keys'] ?? [];
        $hierarchicalModules = Role::filterHierarchicalPermissions(
            Role::getHierarchicalPermissions(),
            $moduleKeys
        );
        $rolePermissionIds = Role::getRolePermissionIdsForModules((int)$id, $moduleKeys);

        Response::view('admin.permissions', [
            'pageTitle'           => 'Manage Permissions — ' . $role['name'],
            'activeModule'        => 'staff',
            'activeMenu'          => 'roles',
            'breadcrumb'          => [
                ['label' => 'Staff', 'url' => url('admin/staff?department=all')],
                ['label' => 'Roles', 'url' => url('admin/roles')],
                ['label' => 'Permissions'],
            ],
            'role'                => $role,
            'hierarchicalModules' => $hierarchicalModules,
            'rolePermissionIds'   => $rolePermissionIds,
            'concerns'           => $concerns,
            'selectedConcernId'  => $selectedConcernId,
            'selectedConcern'    => $selectedConcern,
        ]);
    }

    /**
     * Save role permissions.
     */
    public function savePermissions(string $id): void {
        if (!hasPermission('roles', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to save permissions.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
            return;
        }

        $roleId = (int) $id;
        $permissionIds = $_POST['permissions'] ?? [];
        $concernId = (int)($_POST['concern_id'] ?? 0);
        $concern = Concern::find($concernId);
        if (!$concern || empty($concern['is_active'])) {
            Response::back('error', 'Select a valid concern before saving role permissions.');
            return;
        }

        try {
            Role::savePermissionsForModules($roleId, is_array($permissionIds) ? $permissionIds : [], $concern['module_keys']);
            AuditService::log('roles', 'update_permissions', 'roles', $roleId, null, [
                'concern_id' => $concernId,
                'permissions' => $permissionIds,
            ]);
            Response::redirect(
                url('admin/roles/permissions/' . $roleId . '?concern_id=' . $concernId),
                'success',
                'Permissions updated for ' . $concern['name'] . '.'
            );
        } catch (Exception $e) {
            error_log('Role permissions update failed: ' . $e->getMessage());
            Response::back('error', 'Failed to update permissions: ' . $e->getMessage());
        }
    }

    public function concerns(): void
    {
        if (!hasPermission('staff', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view staff concerns.');
            return;
        }

        $concernId = (int)($_GET['concern_id'] ?? 0);
        $concern = $concernId ? Concern::find($concernId) : null;
        if ($concernId && !$concern) {
            Response::redirect(url('admin/staff/concerns'), 'error', 'Concern was not found.');
            return;
        }

        Response::view('admin.concerns', [
            'pageTitle' => 'Staff Concerns & Modules',
            'activeModule' => 'staff',
            'activeMenu' => 'staff-concerns',
            'concerns' => Concern::getAll(),
            'selectedConcern' => $concern,
            'hierarchicalModules' => Role::getHierarchicalPermissions(),
            'breadcrumb' => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Concerns & Modules'],
            ],
        ]);
    }

    public function legacyConcernData(): void
    {
        if (!isSuperAdmin()) {
            Response::error(403, 'Only Super Admin can review or assign unassigned legacy records.');
            return;
        }

        $table = (string)($_GET['table'] ?? '');
        $tables = LegacyConcernDataService::getTables();
        if ($table !== '' && !array_key_exists($table, $tables)) {
            Response::redirect(url('admin/legacy-data'), 'error', 'Unknown legacy data table.');
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $data = [
            'pageTitle' => 'Legacy Concern Data',
            'activeModule' => 'staff',
            'activeMenu' => 'legacy-data',
            'tables' => LegacyConcernDataService::getSummary(),
            'concerns' => Concern::getAll(true),
            'selectedTable' => $table,
            'canAssign' => $table === '' || LegacyConcernDataService::isAssignable($table),
            'records' => [],
            'recordCount' => 0,
            'page' => $page,
            'pageSize' => 50,
            'breadcrumb' => [
                ['label' => 'Staff', 'url' => url('admin/staff')],
                ['label' => 'Legacy Concern Data'],
            ],
        ];

        if ($table !== '') {
            $result = LegacyConcernDataService::getUnassignedRecords($table, 50, ($page - 1) * 50);
            $data['records'] = $result['records'];
            $data['recordCount'] = $result['total'];
        }

        Response::view('admin.legacy_data', $data);
    }

    public function assignLegacyConcernData(): void
    {
        if (!isSuperAdmin()) {
            Response::error(403, 'Only Super Admin can assign unassigned legacy records.');
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
            return;
        }

        $table = (string)($_POST['table'] ?? '');
        $concernId = (int)($_POST['concern_id'] ?? 0);
        $recordIds = is_array($_POST['record_ids'] ?? null) ? $_POST['record_ids'] : [];
        $concern = Concern::find($concernId);
        if (!$concern || (int)$concern['is_active'] !== 1) {
            Response::redirect(url('admin/legacy-data'), 'error', 'Choose an active concern.');
            return;
        }

        try {
            $assignedRecordIds = array_values(array_unique(array_filter(
                array_map('intval', $recordIds),
                static fn(int $id): bool => $id > 0
            )));
            $assignedCount = LegacyConcernDataService::assignUnassignedRecords($table, $assignedRecordIds, $concernId);
            AuditService::log('staff', 'assign_legacy_concern_data', $table, null, [
                'concern_id' => null,
                'record_ids' => $assignedRecordIds,
            ], [
                'concern_id' => $concernId,
                'record_ids' => $assignedRecordIds,
            ]);
            Response::redirect(
                url('admin/legacy-data?table=' . rawurlencode($table)),
                'success',
                $assignedCount . ' legacy record(s) assigned to ' . $concern['name'] . '.'
            );
        } catch (Throwable $e) {
            Response::redirect(
                url('admin/legacy-data?table=' . rawurlencode($table)),
                'error',
                'Unable to assign legacy records: ' . $e->getMessage()
            );
        }
    }

    public function storeConcern(): void
    {
        if (!hasPermission('staff', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create concerns.');
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
            return;
        }

        try {
            $concernId = Concern::save(
                null,
                (string)($_POST['name'] ?? ''),
                (string)($_POST['description'] ?? ''),
                is_array($_POST['module_keys'] ?? null) ? $_POST['module_keys'] : []
            );
            AuditService::log('staff', 'create_concern', 'concerns', $concernId, null, $_POST);
            Response::redirect(url('admin/staff/concerns?concern_id=' . $concernId), 'success', 'Concern created.');
        } catch (Exception $e) {
            Response::back('error', 'Unable to save concern: ' . $e->getMessage());
        }
    }

    public function updateConcern(string $id): void
    {
        if (!hasPermission('staff', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit concerns.');
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
            return;
        }

        try {
            Concern::save(
                (int)$id,
                (string)($_POST['name'] ?? ''),
                (string)($_POST['description'] ?? ''),
                is_array($_POST['module_keys'] ?? null) ? $_POST['module_keys'] : []
            );
            AuditService::log('staff', 'update_concern', 'concerns', (int)$id, null, $_POST);
            Response::redirect(url('admin/staff/concerns?concern_id=' . (int)$id), 'success', 'Concern updated.');
        } catch (Exception $e) {
            Response::back('error', 'Unable to update concern: ' . $e->getMessage());
        }
    }

    public function deleteConcern(string $id): void
    {
        if (!hasPermission('staff', 'delete')) {
            Response::error(403, 'Access denied: You do not have permission to delete concerns.');
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid form submission.');
            return;
        }

        $concern = Concern::find((int)$id);
        if (!$concern) {
            Response::redirect(url('admin/staff/concerns'), 'error', 'Concern was not found.');
            return;
        }
        Concern::delete((int)$id);
        AuditService::log('staff', 'delete_concern', 'concerns', (int)$id, $concern, null);
        Response::redirect(url('admin/staff/concerns'), 'success', 'Concern deleted.');
    }

    /**
     * Delete role.
     */
    public function deleteRole(string $id): void {
        if (!hasPermission('roles', 'delete')) {
            Response::error(403, 'Access denied: You do not have permission to delete roles.');
            return;
        }

        if (!CSRF::validateRequest()) {
            Response::back('error', 'Invalid security token.');
        }

        $roleId = (int)$id;
        if ($roleId === 1) {
            Response::back('error', 'Super Admin role cannot be deleted.');
        }

        try {
            Role::delete($roleId);
            AuditService::log('roles', 'delete', 'roles', $roleId);
            Response::redirect(url('admin/roles'), 'success', 'Role deleted successfully.');
        } catch (Exception $e) {
            Response::back('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // PRODUCTION UNITS MANAGEMENT
    // =========================================================================

    public function storeProductionUnit(): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Invalid security token.');
        }
        $activeConcernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$activeConcernId) {
            Response::redirect(url('admin/concern/select'), 'error', 'Select a concern before creating a production unit.');
        }

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production Unit Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Unit Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Enter a valid 15-character GSTIN.');
        }

        try {
            $id = ProductionUnit::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'gst_number'   => $gstNumber,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
                'concern_id'   => $activeConcernId,
            ]);

            AuditService::log('production_units', 'create', 'production_units', $id, null, $_POST);
            Response::redirect(url('admin/dashboard?tab=production-units'), 'success', "Production Unit '{$name}' created successfully.");
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Error creating unit: ' . $e->getMessage());
        }
    }

    public function updateProductionUnit(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Invalid security token.');
        }

        $unitId = (int)$id;
        if (!ProductionUnit::find($unitId)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production unit not found in the active concern.');
        }
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production Unit Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Unit Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Enter a valid 15-character GSTIN.');
        }

        try {
            if (!ProductionUnit::update($unitId, [
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'gst_number'   => $gstNumber,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ])) {
                Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production unit could not be updated in the active concern.');
            }

            AuditService::log('production_units', 'update', 'production_units', $unitId, null, $_POST);
            Response::redirect(url('admin/dashboard?tab=production-units'), 'success', "Production Unit '{$name}' updated successfully.");
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Error updating unit: ' . $e->getMessage());
        }
    }

    public function deleteProductionUnit(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Invalid security token.');
        }

        $unitId = (int)$id;
        if (!ProductionUnit::find($unitId)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production unit not found in the active concern.');
        }
        try {
            // Check if programs are linked
            $progCount = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM production_programs WHERE production_unit_id = ?", [$unitId])['cnt'] ?? 0);
            if ($progCount > 0) {
                Response::redirect(url('admin/dashboard?tab=production-units'), 'error', "Cannot delete unit: {$progCount} production program(s) are assigned to it. You can deactivate it instead.");
            }

            if (!ProductionUnit::delete($unitId)) {
                Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production unit could not be deleted.');
            }
            AuditService::log('production_units', 'delete', 'production_units', $unitId);
            Response::redirect(url('admin/dashboard?tab=production-units'), 'success', 'Production Unit deleted successfully.');
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Error deleting unit: ' . $e->getMessage());
        }
    }

    public function toggleProductionUnit(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Invalid security token.');
        }

        $unitId = (int)$id;
        if (!ProductionUnit::toggleActive($unitId)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production unit not found in the active concern.');
        }
        AuditService::log('production_units', 'toggle_active', 'production_units', $unitId);
        Response::redirect(url('admin/dashboard?tab=production-units'), 'success', 'Production Unit status updated.');
    }

    public function ajaxAddProductionUnit(): void {
        if (!CSRF::validateRequest()) {
            Response::jsonError('Invalid security token.', [], 403);
        }
        $activeConcernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$activeConcernId) {
            Response::jsonError('Select a concern before creating a production unit.', [], 409);
        }

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::jsonError('Production Unit Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::jsonError('Unit Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::jsonError('Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::jsonError('Enter a valid 15-character GSTIN.');
        }

        try {
            $id = ProductionUnit::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'gst_number'   => $gstNumber,
                'is_active'    => 1,
                'concern_id'   => $activeConcernId,
            ]);

            $unit = ProductionUnit::find($id);
            AuditService::log('production_units', 'create_ajax', 'production_units', $id, null, $_POST);
            Response::jsonSuccess('Production Unit added successfully!', ['unit' => $unit]);
        } catch (Exception $e) {
            Response::jsonError('Database error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // WAREHOUSE MANAGEMENT
    // =========================================================================

    public function storeWarehouse(): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Invalid security token.');
        }
        $activeConcernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$activeConcernId) {
            Response::redirect(url('admin/concern/select'), 'error', 'Select a concern before creating a warehouse.');
        }

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Enter a valid 15-character GSTIN.');
        }

        try {
            $id = Warehouse::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'gst_number'   => $gstNumber,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
                'concern_id'   => $activeConcernId,
            ]);

            AuditService::log('warehouses', 'create', 'warehouses', $id, null, $_POST);
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'success', "Warehouse '{$name}' created successfully.");
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Error creating warehouse: ' . $e->getMessage());
        }
    }

    public function updateWarehouse(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Invalid security token.');
        }

        $whId = (int)$id;
        if (!Warehouse::find($whId)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse not found in the active concern.');
        }
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Enter a valid 15-character GSTIN.');
        }

        try {
            if (!Warehouse::update($whId, [
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'gst_number'   => $gstNumber,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ])) {
                Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse could not be updated in the active concern.');
            }

            AuditService::log('warehouses', 'update', 'warehouses', $whId, null, $_POST);
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'success', "Warehouse '{$name}' updated successfully.");
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Error updating warehouse: ' . $e->getMessage());
        }
    }

    public function deleteWarehouse(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Invalid security token.');
        }

        $whId = (int)$id;
        if (!Warehouse::find($whId)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse not found in the active concern.');
        }
        try {
            // Check stock balances
            $stockCount = (float)(Database::fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM stock_balances WHERE location_type = 'WAREHOUSE' AND location_id = ? AND concern_id = ?", [$whId, (int)currentConcernId()])['total'] ?? 0);
            if ($stockCount > 0) {
                Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', "Cannot delete warehouse: It holds {$stockCount} active inventory units. Please transfer stock or deactivate it.");
            }

            if (!Warehouse::delete($whId)) {
                Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse could not be deleted.');
            }
            AuditService::log('warehouses', 'delete', 'warehouses', $whId);
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'success', 'Warehouse deleted successfully.');
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Error deleting warehouse: ' . $e->getMessage());
        }
    }

    public function toggleWarehouse(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Invalid security token.');
        }

        $whId = (int)$id;
        if (!Warehouse::toggleActive($whId)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse not found in the active concern.');
        }
        AuditService::log('warehouses', 'toggle_active', 'warehouses', $whId);
        Response::redirect(url('admin/dashboard?tab=warehouses'), 'success', 'Warehouse status updated.');
    }

    // =========================================================================
    // SHOP MANAGEMENT
    // =========================================================================

    public function storeShop(): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Invalid security token.');
        }
        $activeConcernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$activeConcernId) {
            Response::redirect(url('admin/concern/select'), 'error', 'Select a concern before creating a shop.');
        }

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Enter a valid 15-character GSTIN.');
        }

        try {
            $id = Shop::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'gst_number'   => $gstNumber,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
                'concern_id'   => $activeConcernId,
            ]);

            AuditService::log('shops', 'create', 'shops', $id, null, $_POST);
            Response::redirect(url('admin/dashboard?tab=shops'), 'success', "Retail Shop '{$name}' created successfully.");
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Error creating shop: ' . $e->getMessage());
        }
    }

    public function updateShop(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Invalid security token.');
        }

        $shopId = (int)$id;
        if (!Shop::find($shopId)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop not found in the active concern.');
        }
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Contact phone must be exactly 10 digits.');
        }
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Enter a valid 15-character GSTIN.');
        }

        try {
            if (!Shop::update($shopId, [
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'gst_number'   => $gstNumber,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ])) {
                Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop could not be updated in the active concern.');
            }

            AuditService::log('shops', 'update', 'shops', $shopId, null, $_POST);
            Response::redirect(url('admin/dashboard?tab=shops'), 'success', "Retail Shop '{$name}' updated successfully.");
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Error updating shop: ' . $e->getMessage());
        }
    }

    public function deleteShop(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Invalid security token.');
        }

        $shopId = (int)$id;
        if (!Shop::find($shopId)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop not found in the active concern.');
        }
        try {
            // Check stock balances
            $stockCount = (float)(Database::fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM stock_balances WHERE location_type = 'SHOP' AND location_id = ? AND concern_id = ?", [$shopId, (int)currentConcernId()])['total'] ?? 0);
            if ($stockCount > 0) {
                Response::redirect(url('admin/dashboard?tab=shops'), 'error', "Cannot delete shop: It holds {$stockCount} active stock items. Please transfer stock or deactivate it.");
            }

            if (!Shop::delete($shopId)) {
                Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop could not be deleted.');
            }
            AuditService::log('shops', 'delete', 'shops', $shopId);
            Response::redirect(url('admin/dashboard?tab=shops'), 'success', 'Retail Shop deleted successfully.');
        } catch (Exception $e) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Error deleting shop: ' . $e->getMessage());
        }
    }

    public function toggleShop(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Invalid security token.');
        }

        $shopId = (int)$id;
        if (!Shop::toggleActive($shopId)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop not found in the active concern.');
        }
        AuditService::log('shops', 'toggle_active', 'shops', $shopId);
        Response::redirect(url('admin/dashboard?tab=shops'), 'success', 'Shop status updated.');
    }

    // =====================================================================
    // SALES CHANNELS (WHATSAPP, WEBSITE)
    // =====================================================================

    public function storeChannel(): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard'), 'error', 'Invalid security token.');
        }
        $activeConcernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if (!$activeConcernId) {
            Response::redirect(url('admin/concern/select'), 'error', 'Select a concern before creating a sales channel.');
        }

        $type = strtoupper($_POST['channel_type'] ?? '');
        if (!in_array($type, ['WHATSAPP', 'WEBSITE'], true)) {
            Response::redirect(url('admin/dashboard'), 'error', 'Select a supported sales channel type.');
        }
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        if (empty($name)) {
            Response::redirect(url("admin/dashboard?tab=" . strtolower($type)), 'error', 'Channel Name is required.');
        }

        $manager_name = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'error', 'Enter a valid 15-character GSTIN.');
        }
        if (!Concern::hasFacilityModules((int)$activeConcernId, $type)) {
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'error', 'Enable a module for this channel type in the active concern first.');
        }

        try {
            if (empty($code)) {
                $prefix = ($type === 'WHATSAPP' ? 'WA' : 'WE') . '-C' . $activeConcernId . '-';
                $num = 1;
                do {
                    $code = $prefix . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
                    $exists = Database::fetchOne("SELECT id FROM sales_channels WHERE code = ? AND concern_id = ?", [$code, $activeConcernId]);
                    if ($exists) {
                        $num++;
                    } else {
                        break;
                    }
                } while (true);
            } else {
                $exists = Database::fetchOne("SELECT id FROM sales_channels WHERE code = ? AND concern_id = ?", [$code, $activeConcernId]);
                if ($exists) {
                    Response::redirect(url("admin/dashboard?tab=" . strtolower($type)), 'error', 'Channel code already exists.');
                    return;
                }
            }

            Database::execute(
                "INSERT INTO sales_channels (name, code, manager_name, phone, gst_number, description, channel_type, is_active, concern_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$name, $code, $manager_name, $phone, $gstNumber ?: null, $description, $type, $is_active, $activeConcernId]
            );
            $id = Database::lastInsertId();
            AuditService::log('admin', 'create_channel', 'sales_channels', $id, null, $_POST);
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'success', "Channel '{$name}' created successfully.");
        } catch (Exception $e) {
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'error', 'Error creating channel: ' . $e->getMessage());
        }
    }

    public function updateChannel(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard'), 'error', 'Invalid security token.');
        }

        $activeConcernId = currentConcernId();
        $assignedChannelId = function_exists('currentChannelId') ? currentChannelId() : null;
        $channel = $activeConcernId === null ? null : Database::fetchOne(
            "SELECT * FROM sales_channels WHERE id = ? AND concern_id = ?",
            [(int)$id, $activeConcernId]
        );
        if (!$channel || $assignedChannelId === 0 || ($assignedChannelId !== null && $assignedChannelId !== (int)$id)) {
            Response::redirect(url('admin/dashboard'), 'error', 'Sales channel not found in the active concern.');
        }
        $type = strtoupper($_POST['channel_type'] ?? '');
        if (!in_array($type, ['WHATSAPP', 'WEBSITE'], true) || !Concern::hasFacilityModules((int)$activeConcernId, $type)) {
            Response::redirect(url('admin/dashboard'), 'error', 'Choose a supported channel type enabled for the active concern.');
        }
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        if (empty($name)) {
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'error', 'Channel Name is required.');
        }

        $manager_name = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        if (!$this->isValidGstNumber($gstNumber)) {
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'error', 'Enter a valid 15-character GSTIN.');
        }

        try {
            $exists = Database::fetchOne("SELECT id FROM sales_channels WHERE code = ? AND id != ? AND concern_id = ?", [$code, $id, $activeConcernId]);
            if ($exists && !empty($code)) {
                Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'error', 'Channel code already exists for another channel.');
                return;
            }

            Database::execute(
                "UPDATE sales_channels SET name = ?, code = ?, manager_name = ?, phone = ?, gst_number = ?, description = ?, channel_type = ?, is_active = ? WHERE id = ? AND concern_id = ?",
                [$name, $code, $manager_name, $phone, $gstNumber ?: null, $description, $type, $is_active, $id, $activeConcernId]
            );
            AuditService::log('admin', 'update_channel', 'sales_channels', $id, null, $_POST);
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'success', "Channel '{$name}' updated successfully.");
        } catch (Exception $e) {
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'error', 'Error updating channel: ' . $e->getMessage());
        }
    }

    public function deleteChannel(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard'), 'error', 'Invalid security token.');
        }

        try {
            $concernId = currentConcernId();
            $assignedChannelId = function_exists('currentChannelId') ? currentChannelId() : null;
            $chan = $concernId === null || $assignedChannelId === 0 || ($assignedChannelId !== null && $assignedChannelId !== (int)$id)
                ? null
                : Database::fetchOne(
                "SELECT * FROM sales_channels WHERE id = ? AND concern_id = ?",
                [$id, $concernId]
            );
            if ($chan) {
                Database::execute("DELETE FROM sales_channels WHERE id = ? AND concern_id = ?", [$id, $concernId]);
                AuditService::log('admin', 'delete_channel', 'sales_channels', $id);
                Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($chan['channel_type'])), 'success', 'Channel deleted successfully.');
            } else {
                Response::redirect(url('admin/dashboard'), 'error', 'Channel not found.');
            }
        } catch (Exception $e) {
            // Cannot delete if in use
            $concernId = currentConcernId();
            if ($concernId !== null) {
                Database::execute("UPDATE sales_channels SET is_active = 0 WHERE id = ? AND concern_id = ?", [$id, $concernId]);
            }
            Response::redirect(url('admin/dashboard'), 'warning', 'Channel is in use and was deactivated instead of deleted.');
        }
    }

    public function toggleChannel(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard'), 'error', 'Invalid security token.');
        }

        $concernId = currentConcernId();
        $assignedChannelId = function_exists('currentChannelId') ? currentChannelId() : null;
        $chan = $concernId === null || $assignedChannelId === 0 || ($assignedChannelId !== null && $assignedChannelId !== (int)$id)
            ? null
            : Database::fetchOne(
            "SELECT * FROM sales_channels WHERE id = ? AND concern_id = ?",
            [$id, $concernId]
        );
        if ($chan) {
            $newStatus = $chan['is_active'] ? 0 : 1;
            Database::execute("UPDATE sales_channels SET is_active = ? WHERE id = ? AND concern_id = ?", [$newStatus, $id, $concernId]);
            AuditService::log('admin', 'toggle_channel', 'sales_channels', $id);
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($chan['channel_type'])), 'success', 'Channel status updated.');
        } else {
            Response::redirect(url('admin/dashboard'), 'error', 'Channel not found.');
        }
    }
}
