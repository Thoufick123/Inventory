<?php
/**
 * Migration: Remove the unused customer loyalty-points field.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$columns = Database::fetchAll("SHOW COLUMNS FROM `customers` LIKE 'loyalty_points'");
if (empty($columns)) {
    echo "The customers.loyalty_points column is already absent.\n";
    exit(0);
}

Database::getInstance()->exec("ALTER TABLE `customers` DROP COLUMN `loyalty_points`");
echo "Removed customers.loyalty_points.\n";
