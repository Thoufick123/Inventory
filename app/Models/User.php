<?php
/**
 * User Model
 * Handles user data access, authentication, and department/unit assignments.
 */

class User {

    private static bool $schemaChecked = false;

    /**
     * Ensure database table schema has required department & unit assignment columns.
     */
    public static function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;
        try {
            $col = Database::fetchOne("SHOW COLUMNS FROM users LIKE 'department'");
            if (!$col) {
                Database::execute("ALTER TABLE users ADD COLUMN department VARCHAR(50) DEFAULT 'GENERAL' AFTER role_id");
                Database::execute("ALTER TABLE users ADD COLUMN production_unit_id INT UNSIGNED NULL AFTER department");
                Database::execute("ALTER TABLE users ADD COLUMN warehouse_id INT UNSIGNED NULL AFTER production_unit_id");
                Database::execute("ALTER TABLE users ADD COLUMN shop_id INT UNSIGNED NULL AFTER warehouse_id");
                Database::execute("ALTER TABLE users ADD COLUMN reports_access VARCHAR(50) DEFAULT 'ALL' AFTER shop_id");
            }
            $empCol = Database::fetchOne("SHOW COLUMNS FROM users LIKE 'emp_id'");
            if (!$empCol) {
                Database::execute("ALTER TABLE users ADD COLUMN emp_id VARCHAR(50) NULL AFTER username");
            }
            $chanCol = Database::fetchOne("SHOW COLUMNS FROM users LIKE 'channel_id'");
            if (!$chanCol) {
                Database::execute("ALTER TABLE users ADD COLUMN channel_id INT UNSIGNED NULL AFTER shop_id");
            }
            Database::execute("CREATE TABLE IF NOT EXISTS user_permissions (
                user_id INT UNSIGNED NOT NULL,
                permission_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (user_id, permission_id),
                INDEX idx_user_perms (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            Database::execute("CREATE TABLE IF NOT EXISTS user_permission_overrides (
                user_id INT UNSIGNED NOT NULL PRIMARY KEY
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {}
    }

    /**
     * Find user by username or email.
     */
    public static function findByUsername(string $username): ?array {
        self::ensureSchema();
        return Database::fetchOne(
            "SELECT u.*, r.name as role_name, r.slug as role_slug,
                    pu.name as production_unit_name, w.name as warehouse_name, s.name as shop_name,
                    sc.name as channel_name, sc.code as channel_code, sc.channel_type as channel_type
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             LEFT JOIN production_units pu ON u.production_unit_id = pu.id
             LEFT JOIN warehouses w ON u.warehouse_id = w.id
             LEFT JOIN shops s ON u.shop_id = s.id
             LEFT JOIN sales_channels sc ON u.channel_id = sc.id
             WHERE u.username = ? OR u.email = ?",
            [$username, $username]
        );
    }

    /**
     * Find user by ID.
     */
    public static function findById(int $id): ?array {
        self::ensureSchema();
        return Database::fetchOne(
            "SELECT u.*, r.name as role_name, r.slug as role_slug,
                    pu.name as production_unit_name, w.name as warehouse_name, s.name as shop_name,
                    sc.name as channel_name, sc.code as channel_code, sc.channel_type as channel_type
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             LEFT JOIN production_units pu ON u.production_unit_id = pu.id
             LEFT JOIN warehouses w ON u.warehouse_id = w.id
             LEFT JOIN shops s ON u.shop_id = s.id
             LEFT JOIN sales_channels sc ON u.channel_id = sc.id
             WHERE u.id = ?",
            [$id]
        );
    }

    /**
     * Get all users with their roles and unit assignments.
     */
    public static function getAll(array $filters = []): array {
        self::ensureSchema();
        $sql = "SELECT u.*, r.name as role_name, r.slug as role_slug,
                       pu.name as production_unit_name, w.name as warehouse_name, s.name as shop_name,
                       sc.name as channel_name, sc.code as channel_code, sc.channel_type as channel_type
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN production_units pu ON u.production_unit_id = pu.id
                LEFT JOIN warehouses w ON u.warehouse_id = w.id
                LEFT JOIN shops s ON u.shop_id = s.id
                LEFT JOIN sales_channels sc ON u.channel_id = sc.id
                WHERE 1=1";
        $params = [];

        // Exclude root Super Administrator from staff directory
        $includeSuperAdmin = $filters['include_superadmin'] ?? false;
        if (!$includeSuperAdmin) {
            $sql .= " AND (u.role_id != 1 AND u.username != 'admin' AND (r.slug != 'admin' OR r.slug IS NULL))";
        }

        if (!empty($filters['role_id'])) {
            $sql .= " AND u.role_id = ?";
            $params[] = $filters['role_id'];
        }
        if (!empty($filters['department'])) {
            $sql .= " AND (u.department = ? OR u.department = 'ALL')";
            $params[] = strtoupper($filters['department']);
        }
        if (!empty($filters['production_unit_id'])) {
            $sql .= " AND u.production_unit_id = ?";
            $params[] = $filters['production_unit_id'];
        }
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND u.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }
        if (!empty($filters['shop_id'])) {
            $sql .= " AND u.shop_id = ?";
            $params[] = $filters['shop_id'];
        }
        if (!empty($filters['channel_id'])) {
            $sql .= " AND u.channel_id = ?";
            $params[] = $filters['channel_id'];
        }
        if (isset($filters['is_active'])) {
            $sql .= " AND u.is_active = ?";
            $params[] = $filters['is_active'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        $sql .= " ORDER BY u.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Create a new user.
     */
    public static function create(array $data): int {
        self::ensureSchema();
        $phone = !empty(trim((string)($data['phone'] ?? ''))) ? trim((string)$data['phone']) : null;
        $email = !empty(trim((string)($data['email'] ?? ''))) ? trim((string)$data['email']) : null;
        $dept = !empty($data['department']) ? strtoupper($data['department']) : 'GENERAL';
        
        $rawChannel = $data['channel_id'] ?? ($data['whatsapp_channel_id'] ?? ($data['website_channel_id'] ?? null));
        $channelId = in_array($dept, ['WHATSAPP', 'WEBSITE']) ? (!empty($rawChannel) ? (int)$rawChannel : null) : null;
        $puId = ($dept === 'PRODUCTION') ? (!empty($data['production_unit_id']) ? (int)$data['production_unit_id'] : null) : null;
        $whId = ($dept === 'WAREHOUSE') ? (!empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null) : null;
        $shopId = ($dept === 'SHOP') ? (!empty($data['shop_id']) ? (int)$data['shop_id'] : null) : null;

        Database::execute(
            "INSERT INTO users (username, email, password_hash, full_name, phone, role_id, department, production_unit_id, warehouse_id, shop_id, channel_id, reports_access, is_active, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
            [
                $data['username'],
                $email,
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['full_name'],
                $phone,
                $data['role_id'],
                $dept,
                $puId,
                $whId,
                $shopId,
                $channelId,
                $data['reports_access'] ?? 'ALL',
            ]
        );
        $userId = (int) Database::lastInsertId();

        // Save customized permissions if provided (strictly scoped to department)
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $filteredPerms = Role::filterPermissionsForDepartment($data['permissions'], $dept);
            self::savePermissions($userId, $filteredPerms);
        }

        return $userId;
    }

    /**
     * Update user details.
     */
    public static function update(int $id, array $data): void {
        self::ensureSchema();

        $targetDept = !empty($data['department']) ? strtoupper($data['department']) : null;
        if (empty($targetDept)) {
            $existing = self::findById($id);
            $targetDept = $existing['department'] ?? 'GENERAL';
        }

        if (isset($data['whatsapp_channel_id']) || isset($data['website_channel_id'])) {
            $data['channel_id'] = $data['whatsapp_channel_id'] ?? ($data['website_channel_id'] ?? null);
        }

        // Clean up out-of-scope facility IDs if department is set
        if ($targetDept === 'PRODUCTION') {
            $data['warehouse_id'] = null;
            $data['shop_id'] = null;
            $data['channel_id'] = null;
        } elseif ($targetDept === 'WAREHOUSE') {
            $data['production_unit_id'] = null;
            $data['shop_id'] = null;
            $data['channel_id'] = null;
        } elseif (in_array($targetDept, ['WHATSAPP', 'WEBSITE'])) {
            $data['production_unit_id'] = null;
            $data['warehouse_id'] = null;
            $data['shop_id'] = null;
        } elseif ($targetDept === 'SHOP') {
            $data['production_unit_id'] = null;
            $data['warehouse_id'] = null;
            $data['channel_id'] = null;
        }

        $fields = [];
        $params = [];

        foreach (['full_name', 'email', 'phone', 'role_id', 'department', 'production_unit_id', 'warehouse_id', 'shop_id', 'channel_id', 'reports_access', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $val = $data[$field];
                if ($field === 'department' && !empty($val)) $val = strtoupper($val);
                if (in_array($field, ['email', 'phone']) && empty(trim((string)$val))) $val = null;
                if (in_array($field, ['production_unit_id', 'warehouse_id', 'shop_id', 'channel_id']) && empty($val)) $val = null;
                $params[] = $val;
            }
        }

        if (!empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $fields[] = "updated_at = NOW()";
        $params[] = $id;

        Database::execute(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );

        // Update customized permissions if provided (strictly scoped to department)
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $dept = !empty($data['department']) ? strtoupper($data['department']) : null;
            if (empty($dept)) {
                $existing = self::findById($id);
                $dept = $existing['department'] ?? 'GENERAL';
            }
            $filteredPerms = Role::filterPermissionsForDepartment($data['permissions'], $dept);
            self::savePermissions($id, $filteredPerms);
        }
    }

    /**
     * Save user-specific permissions.
     */
    public static function savePermissions(int $userId, array $permissionIds): void {
        self::ensureSchema();
        Database::execute("DELETE FROM user_permissions WHERE user_id = ?", [$userId]);
        $cleanIds = Role::filterCrudPermissionIds($permissionIds);
        foreach ($cleanIds as $pid) {
            Database::execute("INSERT IGNORE INTO user_permissions (user_id, permission_id) VALUES (?, ?)", [$userId, $pid]);
        }
        Database::execute("INSERT IGNORE INTO user_permission_overrides (user_id) VALUES (?)", [$userId]);
    }

    /**
     * Update last login timestamp.
     */
    public static function updateLastLogin(int $id): void {
        Database::execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$id]);
    }

    /**
     * Change password.
     */
    public static function changePassword(int $id, string $newPassword): void {
        Database::execute(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [password_hash($newPassword, PASSWORD_DEFAULT), $id]
        );
    }

    /**
     * Get all permission keys for a user (via custom user permissions, or fallback to role).
     */
    public static function getPermissions(int $userId): array {
        self::ensureSchema();
        // Check if user has explicit custom permissions
        $customRows = Database::fetchAll(
            "SELECT CONCAT(p.module, '.', p.action) as permission_key 
             FROM user_permissions up 
             JOIN permissions p ON up.permission_id = p.id 
             WHERE up.user_id = ?",
            [$userId]
        );
        if (!empty($customRows)) {
            return array_column($customRows, 'permission_key');
        }
        if (self::hasPermissionOverride($userId)) {
            return [];
        }

        // Fallback to role permissions
        $rows = Database::fetchAll(
            "SELECT CONCAT(p.module, '.', p.action) as permission_key 
             FROM users u 
             JOIN role_permissions rp ON u.role_id = rp.role_id 
             JOIN permissions p ON rp.permission_id = p.id 
             WHERE u.id = ?",
            [$userId]
        );
        return array_column($rows, 'permission_key');
    }

    /**
     * Get permission IDs assigned to a user (or inherited from role).
     */
    public static function getPermissionIds(int $userId): array {
        self::ensureSchema();
        $customRows = Database::fetchAll(
            "SELECT permission_id FROM user_permissions WHERE user_id = ?",
            [$userId]
        );
        if (!empty($customRows)) {
            return array_map('intval', array_column($customRows, 'permission_id'));
        }
        if (self::hasPermissionOverride($userId)) {
            return [];
        }

        $roleRows = Database::fetchAll(
            "SELECT rp.permission_id 
             FROM users u 
             JOIN role_permissions rp ON u.role_id = rp.role_id 
             WHERE u.id = ?",
            [$userId]
        );
        return array_map('intval', array_column($roleRows, 'permission_id'));
    }

    private static function hasPermissionOverride(int $userId): bool {
        return (bool)Database::fetchOne(
            "SELECT user_id FROM user_permission_overrides WHERE user_id = ?",
            [$userId]
        );
    }

    /**
     * Toggle user active status.
     */
    public static function toggleActive(int $id): void {
        Database::execute(
            "UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    /**
     * Delete user by ID.
     */
    public static function delete(int $id): void {
        Database::execute("DELETE FROM users WHERE id = ?", [$id]);
    }

    /**
     * Count users by criteria.
     */
    public static function count(array $filters = []): int {
        self::ensureSchema();
        $sql = "SELECT COUNT(*) as cnt FROM users WHERE 1=1";
        $params = [];
        if (!empty($filters['department'])) {
            $sql .= " AND (department = ? OR department = 'ALL')";
            $params[] = strtoupper($filters['department']);
        }
        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = $filters['is_active'];
        }
        return (int)(Database::fetchOne($sql, $params)['cnt'] ?? 0);
    }
}
