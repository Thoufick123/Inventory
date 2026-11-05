<?php
/**
 * Settings Service — Central Configuration Repository
 * Provides unified getter/setter interface for system settings.
 */

require_once BASE_PATH . '/app/Services/AuditService.php';

class SettingsService {
    private static ?array $cache = null;

    /**
     * Load all settings into static memory cache.
     */
    private static function loadCache(): void {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                $rows = Database::fetchAll("SELECT setting_key, setting_value, setting_group FROM settings");
                foreach ($rows as $row) {
                    self::$cache[$row['setting_key']] = [
                        'value' => $row['setting_value'],
                        'group' => $row['setting_group'],
                    ];
                }
            } catch (Exception $e) {
                error_log("SettingsService::loadCache failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Get a setting value by key with optional default.
     */
    public static function get(string $key, $default = null) {
        self::loadCache();
        if (array_key_exists($key, self::$cache)) {
            $val = self::$cache[$key]['value'];
            // Auto-decode JSON if valid JSON structure
            if ($val !== null && is_string($val) && ($val === 'true' || $val === 'false')) {
                return $val === 'true';
            }
            return $val;
        }
        return $default;
    }

    /**
     * Set/update a setting value by key.
     */
    public static function set(string $key, $value, string $group = 'general'): bool {
        self::loadCache();

        $strVal = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
        $prevVal = self::$cache[$key]['value'] ?? null;

        Database::execute(
            "INSERT INTO settings (setting_key, setting_value, setting_group, created_at, updated_at) 
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = NOW()",
            [$key, $strVal, $group]
        );

        self::$cache[$key] = [
            'value' => $strVal,
            'group' => $group,
        ];

        AuditService::log('system_settings', 'UPDATE', 'setting', null, [$key => $prevVal], [$key => $strVal]);
        return true;
    }

    /**
     * Get all settings in a specific group.
     */
    public static function getGroup(string $group): array {
        self::loadCache();
        $groupData = [];
        foreach (self::$cache as $key => $data) {
            if ($data['group'] === $group) {
                $groupData[$key] = $data['value'];
            }
        }
        return $groupData;
    }

    /**
     * Update an entire group section of settings.
     */
    public static function updateGroup(string $group, array $settings): bool {
        Database::beginTransaction();
        try {
            foreach ($settings as $key => $val) {
                self::set($key, $val, $group);
            }
            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            error_log("SettingsService::updateGroup failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all settings formatted by group.
     */
    public static function getAllGrouped(): array {
        self::loadCache();
        $grouped = [
            'company'    => [],
            'switches'   => [],
            'inventory'  => [],
            'security'   => [],
            'backup'     => [],
            'general'    => [],
        ];

        foreach (self::$cache as $key => $data) {
            $grp = $data['group'] ?? 'general';
            if (!isset($grouped[$grp])) {
                $grouped[$grp] = [];
            }
            $grouped[$grp][$key] = $data['value'];
        }

        // Merge defaults for missing keys
        return array_merge(self::getDefaults(), $grouped);
    }

    /**
     * Pre-defined default settings schema.
     */
    public static function getDefaults(): array {
        return [
            'company' => [
                'company_name'       => 'GRM Textiles & Apparel Ltd',
                'company_tagline'    => 'Quality Garment Manufacturing & Retail',
                'company_email'      => 'support@grminventory.com',
                'company_phone'      => '+91 98765 43210',
                'company_gstin'      => '33AAAAA0000A1Z5',
                'company_pan'        => 'AAAAA0000A',
                'company_address'    => '123 Textile Park Road, Tirupur, Tamil Nadu 641601',
                'currency_symbol'    => '₹',
                'invoice_footer'     => 'Thank you for your business! Goods once sold can be returned within 7 days.',
            ],
            'switches' => [
                'maintenance_mode'      => 'false',
                'disable_pos_guest'     => 'false',
                'auto_approve_program'  => 'false',
                'allow_negative_stock'  => 'false',
            ],
            'inventory' => [
                'low_stock_threshold'   => '10',
                'default_warehouse_id'  => '1',
                'audit_stock_movement' => 'true',
            ],
            'security' => [
                'session_timeout_mins'  => '60',
                'max_login_attempts'   => '5',
                'lockout_duration_mins' => '15',
                'force_password_change' => 'false',
            ],
            'backup' => [
                'auto_backup_enabled'   => 'true',
                'auto_backup_frequency' => 'daily',
                'backup_retention_days' => '30',
                'alert_recipients'      => 'admin@grminventory.com',
            ],
        ];
    }
}
