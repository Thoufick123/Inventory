<?php

class Concern
{
    public static function facilityModuleFamily(string $facilityType): array
    {
        return [
            'PRODUCTION' => [
                'production', 'production_inward', 'production_cash', 'sellers', 'damages', 'catalog', 'programs',
                'cutting', 'wastage', 'stitching', 'stitching_wastage', 'production_expenses', 'labels', 'qc',
                'inventory', 'batches', 'outward', 'warehouse_rework', 'reports_production',
            ],
            'WAREHOUSE' => [
                'warehouse', 'inward', 'stock', 'wh_sellers', 'wh_catalog', 'wh_labels', 'purchases',
                'purchases_returns', 'allocation', 'wh_batches', 'allocation_outward', 'damage', 'returns',
                'shop_requests', 'warehouse_expenses', 'reports_warehouse', 'reports_purchase',
                'reports_inventory',
            ],
            'SHOP' => [
                'shop', 'shop_cash', 'shop_inward', 'shop_stock', 'shop_damage', 'pos', 'sales', 'customers',
                'shop_returns', 'return_to_warehouse', 'reports_shop',
            ],
            'WHATSAPP' => [
                'whatsapp_dashboard', 'whatsapp_orders', 'whatsapp_inward', 'whatsapp_inventory',
                'whatsapp_damage', 'whatsapp_returns', 'whatsapp_return_to_wh', 'whatsapp_customers',
                'whatsapp_expenses', 'reports_whatsapp', 'reports_online',
            ],
            'WEBSITE' => [
                'website_dashboard', 'website_orders', 'website_inward', 'website_inventory',
                'website_damage', 'website_returns', 'website_return_to_wh', 'website_customers',
                'website_expenses', 'reports_website', 'reports_online',
            ],
        ][strtoupper($facilityType)] ?? [];
    }

    public static function hasFacilityModules(int $concernId, string $facilityType): bool
    {
        $modules = self::facilityModuleFamily($facilityType);
        if ($concernId <= 0 || !$modules) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($modules), '?'));
        return (bool)Database::fetchOne(
            "SELECT 1 FROM concern_modules
             WHERE concern_id = ? AND module_key IN ({$placeholders})
             LIMIT 1",
            array_merge([$concernId], $modules)
        );
    }

    public static function getAll(bool $activeOnly = false): array
    {
        $sql = "SELECT c.*,
                       GROUP_CONCAT(DISTINCT cm.module_key ORDER BY cm.module_key SEPARATOR ',') AS module_keys
                FROM concerns c
                LEFT JOIN concern_modules cm ON cm.concern_id = c.id";
        if ($activeOnly) {
            $sql .= " WHERE c.is_active = 1";
        }
        $sql .= " GROUP BY c.id ORDER BY c.name";

        $concerns = Database::fetchAll($sql);
        foreach ($concerns as &$concern) {
            $concern['module_keys'] = !empty($concern['module_keys'])
                ? explode(',', $concern['module_keys'])
                : [];
        }
        unset($concern);

        return $concerns;
    }

    public static function find(int $id): ?array
    {
        $concern = Database::fetchOne(
            "SELECT id, name, description, is_active FROM concerns WHERE id = ?",
            [$id]
        );
        if (!$concern) {
            return null;
        }

        $concern['module_keys'] = self::getModuleKeys($id);
        return $concern;
    }

    public static function getModuleKeys(int $id): array
    {
        $rows = Database::fetchAll(
            "SELECT module_key FROM concern_modules WHERE concern_id = ? ORDER BY module_key",
            [$id]
        );

        return array_column($rows, 'module_key');
    }

    public static function getUserConcernIds(int $userId): array
    {
        $rows = Database::fetchAll(
            "SELECT concern_id FROM user_concerns WHERE user_id = ? ORDER BY concern_id",
            [$userId]
        );

        return array_map('intval', array_column($rows, 'concern_id'));
    }

    public static function validateFacilityAssignments(array $data, array $concernIds): void
    {
        $concernIds = array_values(array_unique(array_filter(
            array_map('intval', $concernIds),
            static fn(int $id): bool => $id > 0
        )));
        $department = strtoupper((string)($data['department'] ?? 'GENERAL'));
        $facilityFields = [
            'PRODUCTION' => ['production_unit_id', 'production_units', 'PRODUCTION'],
            'WAREHOUSE' => ['warehouse_id', 'warehouses', 'WAREHOUSE'],
            'SHOP' => ['shop_id', 'shops', 'SHOP'],
            'WHATSAPP' => ['channel_id', 'sales_channels', 'WHATSAPP'],
            'WEBSITE' => ['channel_id', 'sales_channels', 'WEBSITE'],
        ];
        if (!isset($facilityFields[$department])) {
            return;
        }

        [$field, $table, $facilityType] = $facilityFields[$department];
        $facilityId = (int)($data[$field] ?? 0);
        if ($facilityId <= 0) {
            return;
        }
        if (!$concernIds) {
            throw new InvalidArgumentException('A facility can only be assigned to staff who have access to its concern.');
        }

        $placeholders = implode(',', array_fill(0, count($concernIds), '?'));
        $facility = Database::fetchOne(
            "SELECT id, concern_id" . ($table === 'sales_channels' ? ", channel_type, name, code" : "") . "
             FROM {$table}
             WHERE id = ? AND is_active = 1 AND concern_id IN ({$placeholders})",
            array_merge([$facilityId], $concernIds)
        );
        if (!$facility) {
            throw new InvalidArgumentException('The assigned facility must be active and belong to one of this staff member’s concerns.');
        }

        $supportedModules = self::facilityModuleFamily($facilityType);
        $modulePlaceholders = implode(',', array_fill(0, count($supportedModules), '?'));
        $hasCompatibleModule = Database::fetchOne(
            "SELECT 1 FROM concern_modules
             WHERE concern_id = ? AND module_key IN ({$modulePlaceholders})
             LIMIT 1",
            array_merge([(int)$facility['concern_id']], $supportedModules)
        );
        if (!$hasCompatibleModule) {
            throw new InvalidArgumentException('The assigned facility’s concern does not have a compatible module enabled.');
        }

        if ($facilityType === 'WHATSAPP' || $facilityType === 'WEBSITE') {
            $channelType = strtoupper((string)($facility['channel_type'] ?? ''));
            $legacyChannelMatches = $channelType === ''
                && ($facilityType === 'WHATSAPP'
                    ? (stripos((string)$facility['name'], 'whatsapp') !== false || stripos((string)$facility['code'], 'wa') === 0)
                    : (stripos((string)$facility['name'], 'website') !== false
                        || stripos((string)$facility['name'], 'web') !== false
                        || stripos((string)$facility['code'], 'web') === 0
                        || stripos((string)$facility['code'], 'we') === 0));
            if ($channelType !== $facilityType && !$legacyChannelMatches) {
                throw new InvalidArgumentException('Choose a sales channel that matches the staff member’s channel type.');
            }
        }
    }

    public static function getAccessibleToUser(int $userId, bool $includeAll = false): array
    {
        $sql = "SELECT c.*,
                       GROUP_CONCAT(DISTINCT cm.module_key ORDER BY cm.module_key SEPARATOR ',') AS module_keys
                FROM concerns c
                LEFT JOIN concern_modules cm ON cm.concern_id = c.id";
        $params = [];
        if (!$includeAll) {
            $sql .= " JOIN user_concerns uc ON uc.concern_id = c.id AND uc.user_id = ?";
            $params[] = $userId;
        }
        $sql .= " WHERE c.is_active = 1
                  GROUP BY c.id
                  ORDER BY c.name";

        $concerns = Database::fetchAll($sql, $params);
        foreach ($concerns as &$concern) {
            $concern['module_keys'] = !empty($concern['module_keys'])
                ? explode(',', $concern['module_keys'])
                : [];
        }
        unset($concern);

        return $concerns;
    }

    public static function getActiveForCurrentUser(): ?array
    {
        $user = function_exists('currentUser') ? currentUser() : Session::user();
        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $concerns = self::getAccessibleToUser($userId, function_exists('isSuperAdmin') && isSuperAdmin());
        $activeId = (int)Session::get('active_concern_id', 0);
        foreach ($concerns as $concern) {
            if ((int)$concern['id'] === $activeId) {
                return $concern;
            }
        }

        Session::remove('active_concern_id');
        if (!(function_exists('isSuperAdmin') && isSuperAdmin()) && count($concerns) === 1) {
            Session::set('active_concern_id', (int)$concerns[0]['id']);
            return $concerns[0];
        }

        return null;
    }

    public static function activateForUser(int $userId, int $concernId, bool $includeAll = false): bool
    {
        $concerns = self::getAccessibleToUser($userId, $includeAll);
        foreach ($concerns as $concern) {
            if ((int)$concern['id'] === $concernId) {
                Session::set('active_concern_id', $concernId);
                return true;
            }
        }

        return false;
    }

    public static function hasModule(int $concernId, string $moduleKey): bool
    {
        return (bool)Database::fetchOne(
            "SELECT 1 FROM concern_modules WHERE concern_id = ? AND module_key = ?",
            [$concernId, $moduleKey]
        );
    }

    public static function getModuleKeysForIds(array $concernIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $concernIds),
            static fn(int $id): bool => $id > 0
        )));
        if (!$ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            "SELECT DISTINCT cm.module_key
             FROM concern_modules cm
             JOIN concerns c ON c.id = cm.concern_id AND c.is_active = 1
             WHERE cm.concern_id IN ($placeholders)",
            $ids
        );

        return array_column($rows, 'module_key');
    }

    public static function filterPermissionIds(array $permissionIds, array $concernIds): array
    {
        $moduleKeys = self::getModuleKeysForIds($concernIds);
        $permissionIds = Role::filterCrudPermissionIds($permissionIds);
        if (!$moduleKeys || !$permissionIds) {
            return [];
        }

        $idPlaceholders = implode(',', array_fill(0, count($permissionIds), '?'));
        $modulePlaceholders = implode(',', array_fill(0, count($moduleKeys), '?'));
        $rows = Database::fetchAll(
            "SELECT id FROM permissions
             WHERE id IN ($idPlaceholders) AND module IN ($modulePlaceholders)",
            array_merge($permissionIds, $moduleKeys)
        );

        return array_map('intval', array_column($rows, 'id'));
    }

    public static function save(?int $id, string $name, string $description, array $moduleKeys): int
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 100) {
            throw new InvalidArgumentException('Enter a concern name up to 100 characters.');
        }
        if (mb_strlen($description) > 500) {
            throw new InvalidArgumentException('Concern description must be 500 characters or fewer.');
        }

        $availableModules = [];
        foreach (Role::getHierarchicalPermissions() as $module) {
            foreach ($module['submodules'] ?? [] as $moduleKey => $submodule) {
                if (!empty($submodule['permissions'])) {
                    $availableModules[$moduleKey] = true;
                }
            }
        }
        $moduleKeys = array_values(array_unique(array_filter(
            array_map(static fn($key): string => trim((string)$key), $moduleKeys),
            static fn(string $key): bool => $key !== '' && isset($availableModules[$key])
        )));
        if (!$moduleKeys) {
            throw new InvalidArgumentException('Select at least one module for this concern.');
        }

        Database::beginTransaction();
        try {
            if ($id === null) {
                Database::execute(
                    "INSERT INTO concerns (name, description, is_active, created_at, updated_at)
                     VALUES (?, ?, 1, NOW(), NOW())",
                    [$name, $description !== '' ? $description : null]
                );
                $id = (int)Database::lastInsertId();
            } else {
                Database::execute(
                    "UPDATE concerns SET name = ?, description = ?, updated_at = NOW() WHERE id = ?",
                    [$name, $description !== '' ? $description : null, $id]
                );
                if (!Database::fetchOne("SELECT id FROM concerns WHERE id = ?", [$id])) {
                    throw new InvalidArgumentException('Concern was not found.');
                }
                Database::execute("DELETE FROM concern_modules WHERE concern_id = ?", [$id]);
            }

            foreach ($moduleKeys as $moduleKey) {
                Database::execute(
                    "INSERT INTO concern_modules (concern_id, module_key) VALUES (?, ?)",
                    [$id, $moduleKey]
                );
            }
            Database::commit();
            return $id;
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            throw $e;
        }
    }

    public static function syncUserConcerns(int $userId, array $concernIds): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $concernIds),
            static fn(int $id): bool => $id > 0
        )));
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $found = Database::fetchAll(
                "SELECT id FROM concerns WHERE is_active = 1 AND id IN ($placeholders)",
                $ids
            );
            if (count($found) !== count($ids)) {
                throw new InvalidArgumentException('One or more selected concerns are unavailable.');
            }
        }

        Database::beginTransaction();
        try {
            Database::execute("DELETE FROM user_concerns WHERE user_id = ?", [$userId]);
            foreach ($ids as $concernId) {
                Database::execute(
                    "INSERT INTO user_concerns (user_id, concern_id) VALUES (?, ?)",
                    [$userId, $concernId]
                );
            }
            Database::commit();
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            throw $e;
        }
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM concerns WHERE id = ?", [$id]);
    }
}
