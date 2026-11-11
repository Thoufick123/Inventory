<?php

define('BASE_PATH', dirname(__DIR__));

final class Database
{
    public static string $lastQuery = '';

    public static function fetchAll(string $sql, array $params = []): array
    {
        self::$lastQuery = $sql;
        return array_map(static fn(int $id): array => ['id' => $id], $params);
    }
}

require BASE_PATH . '/app/Models/Role.php';

$receivePermission = [
    'id' => 999,
    'module' => 'production_inward',
    'action' => 'receive',
    'description' => 'Receive products into concern inventory',
];
$columns = Role::getCrudPermissionsByAction([
    'view' => ['id' => 998, 'module' => 'production_inward', 'action' => 'view'],
    'receive' => $receivePermission,
]);

if (($columns['create']['receive']['id'] ?? null) !== 999) {
    throw new RuntimeException('Production Inward receive permission is not available in the admin permission matrix.');
}

$filteredIds = Role::filterCrudPermissionIds([999]);
if ($filteredIds !== [999] || !str_contains(Database::$lastQuery, "module = 'production_inward' AND action = 'receive'")) {
    throw new RuntimeException('Production Inward receive permission is removed before role or staff permissions are saved.');
}

echo "PASS: Production Inward receive permission is visible and survives permission filtering.\n";
