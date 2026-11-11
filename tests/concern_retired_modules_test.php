<?php

define('BASE_PATH', dirname(__DIR__));

final class Database
{
    public static function fetchAll(string $sql, array $params = []): array
    {
        return [];
    }
}

require BASE_PATH . '/app/Helpers/Response.php';
require BASE_PATH . '/app/Models/Role.php';

function assertRetiredModuleTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$hierarchy = Role::getHierarchicalPermissions();
foreach (['warehouse', 'whatsapp', 'website', 'shop'] as $group) {
    assertRetiredModuleTest(!isset($hierarchy[$group]), "Retired module group remains selectable: {$group}");
}

foreach ([
    'reports_online',
    'reports_website',
    'reports_whatsapp',
    'reports_warehouse',
    'reports_purchase',
    'reports_shop',
    'reports_inventory',
] as $module) {
    foreach ($hierarchy as $group) {
        assertRetiredModuleTest(
            !isset($group['submodules'][$module]),
            "Retired report module remains selectable: {$module}"
        );
    }
}

foreach (['warehouse', 'whatsapp_orders', 'website_orders', 'shop_stock', 'pos'] as $module) {
    assertRetiredModuleTest(!hasPermission($module), "Retired module permission remains active: {$module}");
}

echo "PASS: retired module groups, reports, and permissions are unavailable.\n";
