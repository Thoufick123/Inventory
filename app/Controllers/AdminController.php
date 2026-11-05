<?php
/**
 * Admin Controller
 * Dashboard, staff management, and role management.
 */

require_once BASE_PATH . '/app/Models/User.php';
require_once BASE_PATH . '/app/Models/Role.php';
require_once BASE_PATH . '/app/Models/ProductionUnit.php';
require_once BASE_PATH . '/app/Models/Warehouse.php';
require_once BASE_PATH . '/app/Models/Shop.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class AdminController {

    /**
     * Admin Dashboard.
     */
    public function dashboard(): void {
        if (!hasPermission('dashboard', 'view')) {
            if (hasPermission('production', 'view') || hasPermission('cutting', 'view') || hasPermission('programs', 'view')) {
                Response::redirect(url('production/dashboard'));
                return;
            }
            if (hasPermission('warehouse', 'view') || hasPermission('inward', 'view') || hasPermission('stock', 'view')) {
                Response::redirect(url('warehouse/dashboard'));
                return;
            }
            if (hasPermission('shop', 'view') || hasPermission('pos', 'view')) {
                Response::redirect(url('shop/dashboard'));
                return;
            }
            if (hasPermission('assets', 'view')) {
                Response::redirect(url('asset/dashboard'));
                return;
            }
            if (hasPermission('reports', 'view')) {
                Response::redirect(url('reports/sales'));
                return;
            }
            if (hasPermission('staff', 'view')) {
                Response::redirect(url('admin/staff?department=all'));
                return;
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
        
        $whatsappChannels = Database::fetchAll("SELECT * FROM sales_channels WHERE channel_type = 'WHATSAPP' OR LOWER(name) LIKE '%whatsapp%' OR LOWER(code) LIKE '%wa%' ORDER BY name ASC");
        $websiteChannels  = Database::fetchAll("SELECT * FROM sales_channels WHERE channel_type = 'WEBSITE' OR LOWER(name) LIKE '%website%' OR LOWER(name) LIKE '%web%' OR LOWER(code) LIKE '%web%' ORDER BY name ASC");

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
             WHERE sb.stock_state = 'AVAILABLE'"
        );
        $data['totalStockValue'] = $result['total'] ?? 0;

        // Today's sales
        $result = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM sales WHERE DATE(created_at) = CURDATE() AND status != 'CANCELLED'"
        );
        $data['todaySales'] = $result['cnt'] ?? 0;

        // Low stock count
        $result = Database::fetchOne(
            "SELECT COUNT(DISTINCT sb.variant_id) as cnt 
             FROM stock_balances sb 
             JOIN product_variants pv ON sb.variant_id = pv.id 
             JOIN products p ON pv.product_id = p.id 
             WHERE sb.stock_state = 'AVAILABLE' AND sb.quantity <= p.min_stock AND p.min_stock > 0"
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
            "SELECT COUNT(*) as cnt FROM returns WHERE inspection_status IN ('PENDING', 'INSPECTING')"
        );
        $data['pendingReturns'] = $result['cnt'] ?? 0;

        // Recent sales
        $data['recentSales'] = Database::fetchAll(
            "SELECT s.*, c.name as customer_name 
             FROM sales s 
             LEFT JOIN customers c ON s.customer_id = c.id 
             ORDER BY s.created_at DESC LIMIT 10"
        );

        // Low stock items
        $data['lowStockItems'] = Database::fetchAll(
            "SELECT sb.quantity, pv.sku, p.name as product_name, p.min_stock
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             WHERE sb.stock_state = 'AVAILABLE' AND sb.quantity <= p.min_stock AND p.min_stock > 0
             ORDER BY sb.quantity ASC LIMIT 10"
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
        $department = $_GET['department'] ?? 'all';
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
        $filters = [
            'search'             => $_GET['search'] ?? '',
            'role_id'            => $_GET['role_id'] ?? '',
            'department'         => strtolower($department) === 'all' ? '' : $department,
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

        $activeMenu = 'staff-all';
        $deptLower = strtolower($department);
        if ($deptLower === 'all') $activeMenu = 'staff-all';
        elseif ($deptLower === 'production') $activeMenu = 'staff-production';
        elseif ($deptLower === 'warehouse') $activeMenu = 'staff-warehouse';
        elseif ($deptLower === 'whatsapp') $activeMenu = 'staff-whatsapp';
        elseif ($deptLower === 'website') $activeMenu = 'staff-website';
        elseif ($deptLower === 'shop') $activeMenu = 'staff-shop';
        elseif ($deptLower === 'reports') $activeMenu = 'staff-reports';

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
        $whatsappChannels = Database::fetchAll("SELECT * FROM sales_channels WHERE (channel_type = 'WHATSAPP' OR channel_type IS NULL OR channel_type = '' OR LOWER(name) LIKE '%whatsapp%' OR LOWER(code) LIKE '%wa%') AND (channel_type != 'WEBSITE' OR channel_type IS NULL) AND is_active = 1 ORDER BY name ASC");
        $websiteChannels  = Database::fetchAll("SELECT * FROM sales_channels WHERE (channel_type = 'WEBSITE' OR channel_type IS NULL OR channel_type = '' OR LOWER(name) LIKE '%website%' OR LOWER(name) LIKE '%web%' OR LOWER(code) LIKE '%web%' OR LOWER(code) LIKE '%we%') AND (channel_type != 'WHATSAPP' OR channel_type IS NULL) AND is_active = 1 ORDER BY name ASC");

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
        }

        $roleId     = (int)($_POST['role_id'] ?? 0);
        $department = strtoupper($_POST['department'] ?? 'PRODUCTION');

        if (!Role::isRoleCompatibleWithDepartment($roleId, $department)) {
            $role = Role::findById($roleId);
            $roleName = $role['name'] ?? 'Selected role';
            Session::set('_old_input', $_POST);
            Response::back('error', "Department Validation Error: The role '{$roleName}' does not have access permissions for the '{$department}' department. Please assign a role with {$department} permissions or select a compatible department.");
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
                $chan = Database::fetchOne("SELECT name, code FROM sales_channels WHERE id = ?", [$chanId]);
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
        $whatsappChannels = Database::fetchAll("SELECT * FROM sales_channels WHERE (channel_type = 'WHATSAPP' OR channel_type IS NULL OR channel_type = '' OR LOWER(name) LIKE '%whatsapp%' OR LOWER(code) LIKE '%wa%') AND (channel_type != 'WEBSITE' OR channel_type IS NULL) AND is_active = 1 ORDER BY name ASC");
        $websiteChannels  = Database::fetchAll("SELECT * FROM sales_channels WHERE (channel_type = 'WEBSITE' OR channel_type IS NULL OR channel_type = '' OR LOWER(name) LIKE '%website%' OR LOWER(name) LIKE '%web%' OR LOWER(code) LIKE '%web%' OR LOWER(code) LIKE '%we%') AND (channel_type != 'WHATSAPP' OR channel_type IS NULL) AND is_active = 1 ORDER BY name ASC");

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
        }

        $roleId     = (int)($_POST['role_id'] ?? 0);
        $department = strtoupper($_POST['department'] ?? ($oldStaff['department'] ?? 'PRODUCTION'));

        if (!Role::isRoleCompatibleWithDepartment($roleId, $department)) {
            $role = Role::findById($roleId);
            $roleName = $role['name'] ?? 'Selected role';
            Response::back('error', "Department Validation Error: The role '{$roleName}' does not have access permissions for the '{$department}' department. Please assign a role with {$department} permissions or select a compatible department.");
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

        $allPermissions = Database::fetchAll("SELECT id, module, action FROM permissions ORDER BY id ASC");
        $permMap = [];
        foreach ($allPermissions as $p) {
            $permMap[$p['module']][$p['action']] = (int) $p['id'];
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
            'staff'                  => null,
            'permMap'                => $permMap,
            'hierarchicalModules'    => Role::getHierarchicalPermissions(),
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
        }

        $name          = trim($_POST['name'] ?? '');
        $slugInput     = trim($_POST['slug'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $isActive      = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissionIds = array_map('intval', $_POST['permissions'] ?? []);

        if (empty($name)) {
            Response::back('error', 'Role Name is required.');
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

            // Save selected permissions
            if (!empty($permissionIds)) {
                Role::savePermissions($roleId, $permissionIds);
            }

            AuditService::log('roles', 'create', 'roles', $roleId, null, $_POST);
            Response::redirect(url('admin/roles'), 'success', "Role '{$name}' created successfully with assigned permissions.");
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
        if (!$role) Response::error(404, 'Role not found.');

        $assignedPermissionIds = Role::getRolePermissionIds((int) $id);

        $allPermissions = Database::fetchAll("SELECT id, module, action FROM permissions ORDER BY id ASC");
        $permMap = [];
        foreach ($allPermissions as $p) {
            $permMap[$p['module']][$p['action']] = (int) $p['id'];
        }

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
            'permMap'                => $permMap,
            'hierarchicalModules'    => Role::getHierarchicalPermissions(),
            'assignedPermissionIds'  => $assignedPermissionIds,
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
        }

        $roleId        = (int) $id;
        $role          = Role::findById($roleId);
        if (!$role) Response::error(404, 'Role not found.');

        $name          = trim($_POST['name'] ?? '');
        $slugInput     = trim($_POST['slug'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $isActive      = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissionIds = array_map('intval', $_POST['permissions'] ?? []);

        if (empty($name)) {
            Response::back('error', 'Role Name is required.');
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

            // Sync updated permissions
            Role::savePermissions($roleId, $permissionIds);

            AuditService::log('roles', 'update', 'roles', $roleId, $role, $_POST);
            Response::redirect(url('admin/roles'), 'success', "Role '{$name}' updated successfully.");
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

        $groupedPermissions = Role::getPermissionsGrouped();
        $hierarchicalModules = Role::getHierarchicalPermissions();
        $rolePermissionIds = Role::getRolePermissionIds((int) $id);

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
            'groupedPermissions'  => $groupedPermissions,
            'hierarchicalModules' => $hierarchicalModules,
            'rolePermissionIds'   => $rolePermissionIds,
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
        }

        $roleId = (int) $id;
        $permissionIds = $_POST['permissions'] ?? [];

        try {
            Role::savePermissions($roleId, $permissionIds);
            AuditService::log('roles', 'update_permissions', 'roles', $roleId, null, ['permissions' => $permissionIds]);
            Response::redirect(url('admin/roles'), 'success', 'Permissions updated successfully.');
        } catch (Exception $e) {
            Response::back('error', 'Failed to update permissions.');
        }
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

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production Unit Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Unit Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Contact phone must be exactly 10 digits.');
        }

        try {
            $id = ProductionUnit::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
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
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Production Unit Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Unit Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=production-units'), 'error', 'Contact phone must be exactly 10 digits.');
        }

        try {
            ProductionUnit::update($unitId, [
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ]);

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
        try {
            // Check if programs are linked
            $progCount = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM production_programs WHERE production_unit_id = ?", [$unitId])['cnt'] ?? 0);
            if ($progCount > 0) {
                Response::redirect(url('admin/dashboard?tab=production-units'), 'error', "Cannot delete unit: {$progCount} production program(s) are assigned to it. You can deactivate it instead.");
            }

            ProductionUnit::delete($unitId);
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
        ProductionUnit::toggleActive($unitId);
        AuditService::log('production_units', 'toggle_active', 'production_units', $unitId);
        Response::redirect(url('admin/dashboard?tab=production-units'), 'success', 'Production Unit status updated.');
    }

    public function ajaxAddProductionUnit(): void {
        if (!CSRF::validateRequest()) {
            Response::jsonError('Invalid security token.', [], 403);
        }

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            Response::jsonError('Production Unit Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::jsonError('Unit Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::jsonError('Contact phone must be exactly 10 digits.');
        }

        try {
            $id = ProductionUnit::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'is_active'    => 1,
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

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Contact phone must be exactly 10 digits.');
        }

        try {
            $id = Warehouse::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
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
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Warehouse Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', 'Contact phone must be exactly 10 digits.');
        }

        try {
            Warehouse::update($whId, [
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ]);

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
        try {
            // Check stock balances
            $stockCount = (float)(Database::fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM stock_balances WHERE location_type = 'WAREHOUSE' AND location_id = ?", [$whId])['total'] ?? 0);
            if ($stockCount > 0) {
                Response::redirect(url('admin/dashboard?tab=warehouses'), 'error', "Cannot delete warehouse: It holds {$stockCount} active inventory units. Please transfer stock or deactivate it.");
            }

            Warehouse::delete($whId);
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
        Warehouse::toggleActive($whId);
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

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Contact phone must be exactly 10 digits.');
        }

        try {
            $id = Shop::create([
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'gst_number'   => trim($_POST['gst_number'] ?? ''),
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
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
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $managerName = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop Name is required.');
        }
        if (!empty($managerName) && !preg_match('/^[a-zA-Z\s]+$/', $managerName)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Shop Manager name must contain only alphabets.');
        }
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            Response::redirect(url('admin/dashboard?tab=shops'), 'error', 'Contact phone must be exactly 10 digits.');
        }

        try {
            Shop::update($shopId, [
                'name'         => $name,
                'code'         => $code,
                'manager_name' => $managerName,
                'manager_id'   => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                'gst_number'   => trim($_POST['gst_number'] ?? ''),
                'address'      => trim($_POST['address'] ?? ''),
                'phone'        => $phone,
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ]);

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
        try {
            // Check stock balances
            $stockCount = (float)(Database::fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM stock_balances WHERE location_type = 'SHOP' AND location_id = ?", [$shopId])['total'] ?? 0);
            if ($stockCount > 0) {
                Response::redirect(url('admin/dashboard?tab=shops'), 'error', "Cannot delete shop: It holds {$stockCount} active stock items. Please transfer stock or deactivate it.");
            }

            Shop::delete($shopId);
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
        Shop::toggleActive($shopId);
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

        $type = $_POST['channel_type'] ?? 'OTHER';
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        if (empty($name)) {
            Response::redirect(url("admin/dashboard?tab=" . strtolower($type)), 'error', 'Channel Name is required.');
        }

        $manager_name = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        try {
            if (empty($code)) {
                $prefix = ($type === 'WHATSAPP') ? 'WA-' : 'WE-';
                $num = 1;
                do {
                    $code = $prefix . str_pad((string)$num, 2, '0', STR_PAD_LEFT);
                    $exists = Database::fetchOne("SELECT id FROM sales_channels WHERE code = ?", [$code]);
                    if ($exists) {
                        $num++;
                    } else {
                        break;
                    }
                } while (true);
            } else {
                $exists = Database::fetchOne("SELECT id FROM sales_channels WHERE code = ?", [$code]);
                if ($exists) {
                    Response::redirect(url("admin/dashboard?tab=" . strtolower($type)), 'error', 'Channel code already exists.');
                    return;
                }
            }

            Database::execute(
                "INSERT INTO sales_channels (name, code, manager_name, phone, description, channel_type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$name, $code, $manager_name, $phone, $description, $type, $is_active]
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

        $type = $_POST['channel_type'] ?? 'OTHER';
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        if (empty($name)) {
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'error', 'Channel Name is required.');
        }

        $manager_name = trim($_POST['manager_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        try {
            $exists = Database::fetchOne("SELECT id FROM sales_channels WHERE code = ? AND id != ?", [$code, $id]);
            if ($exists && !empty($code)) {
                Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($type)), 'error', 'Channel code already exists for another channel.');
                return;
            }

            Database::execute(
                "UPDATE sales_channels SET name = ?, code = ?, manager_name = ?, phone = ?, description = ?, channel_type = ?, is_active = ? WHERE id = ?",
                [$name, $code, $manager_name, $phone, $description, $type, $is_active, $id]
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
            $chan = Database::fetchOne("SELECT * FROM sales_channels WHERE id = ?", [$id]);
            if ($chan) {
                Database::execute("DELETE FROM sales_channels WHERE id = ?", [$id]);
                AuditService::log('admin', 'delete_channel', 'sales_channels', $id);
                Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($chan['channel_type'])), 'success', 'Channel deleted successfully.');
            } else {
                Response::redirect(url('admin/dashboard'), 'error', 'Channel not found.');
            }
        } catch (Exception $e) {
            // Cannot delete if in use
            Database::execute("UPDATE sales_channels SET is_active = 0 WHERE id = ?", [$id]);
            Response::redirect(url('admin/dashboard'), 'warning', 'Channel is in use and was deactivated instead of deleted.');
        }
    }

    public function toggleChannel(string $id): void {
        if (!CSRF::validateRequest()) {
            Response::redirect(url('admin/dashboard'), 'error', 'Invalid security token.');
        }

        $chan = Database::fetchOne("SELECT * FROM sales_channels WHERE id = ?", [$id]);
        if ($chan) {
            $newStatus = $chan['is_active'] ? 0 : 1;
            Database::execute("UPDATE sales_channels SET is_active = ? WHERE id = ?", [$newStatus, $id]);
            AuditService::log('admin', 'toggle_channel', 'sales_channels', $id);
            Response::redirect(url("admin/dashboard?tab=tab-" . strtolower($chan['channel_type'])), 'success', 'Channel status updated.');
        } else {
            Response::redirect(url('admin/dashboard'), 'error', 'Channel not found.');
        }
    }
}
