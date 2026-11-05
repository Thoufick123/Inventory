<?php
/**
 * Migration: Sync all missing columns from database/schema.sql to live MySQL database
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    $db = Database::getInstance();
    echo "Starting full database schema sync from schema.sql...\n\n";

    $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
    preg_match_all('/CREATE TABLE `?([a-zA-Z0-9_]+)`?\s*\((.*?)\)\s*ENGINE/s', $schema, $matches, PREG_SET_ORDER);

    $dbTables = array_column(Database::fetchAll('SHOW TABLES'), 'Tables_in_grm_inventory');

    $addedCount = 0;
    foreach ($matches as $match) {
        $tableName = $match[1];
        $body = $match[2];

        if (!in_array($tableName, $dbTables)) {
            echo "⚠️ Table `$tableName` missing from DB! Creating table...\n";
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS `$tableName` ($body) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
                echo "✓ Created table `$tableName`\n";
            } catch (\Throwable $e) {
                echo "❌ Failed to create table `$tableName`: " . $e->getMessage() . "\n";
            }
            continue;
        }

        $dbCols = array_column(Database::fetchAll("DESCRIBE `{$tableName}`"), 'Field');

        $lines = explode("\n", $body);
        foreach ($lines as $line) {
            $line = trim($line);
            // Match column definitions
            if (preg_match('/^`([a-zA-Z0-9_]+)`\s+([a-zA-Z0-9_\(\),\s\'\.]+?)(,\s*$|$)/', $line, $colMatch)) {
                $colName = $colMatch[1];
                $colDef = rtrim($colMatch[2], ',');

                if (!in_array($colName, $dbCols)) {
                    $alterSql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$colName}` {$colDef}";
                    try {
                        $db->exec($alterSql);
                        echo "✓ Added column `{$tableName}`.`{$colName}`\n";
                        $addedCount++;
                    } catch (\Throwable $e) {
                        echo "❌ Error adding `{$tableName}`.`{$colName}`: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }

    echo "\n--------------------------------------------\n";
    echo "Schema Sync Complete! Total new columns added: $addedCount\n";

} catch (\Throwable $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
