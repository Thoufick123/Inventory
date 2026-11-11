<?php

define('BASE_PATH', dirname(__DIR__));

final class Database
{
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        if (str_contains($sql, 'FROM concerns')) {
            return $params === [7, 7] ? ['id' => 7] : null;
        }
        return null;
    }
}

final class Concern
{
    public static function hasFacilityModules(int $concernId, string $facilityType): bool
    {
        return false;
    }
}

function currentConcernId(): int
{
    return 7;
}

require BASE_PATH . '/app/Services/StockService.php';

if (StockService::assertLocationBelongsToActiveConcern('CONCERN', 7) !== 7) {
    throw new RuntimeException('Concern stock must be valid without any facility module enabled.');
}

try {
    StockService::assertLocationBelongsToActiveConcern('CONCERN', 8);
} catch (InvalidArgumentException $exception) {
    echo "PASS: concern stock is scoped to the active concern and does not require a facility module.\n";
    exit(0);
}

throw new RuntimeException('Concern stock outside the active concern must be rejected.');
