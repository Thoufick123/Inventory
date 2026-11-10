<?php
/**
 * Create customizable staff concerns and their module assignments.
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

try {
    Database::execute(
        "CREATE TABLE IF NOT EXISTS concerns (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(500) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_concerns_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    Database::execute(
        "CREATE TABLE IF NOT EXISTS concern_modules (
            concern_id INT UNSIGNED NOT NULL,
            module_key VARCHAR(100) NOT NULL,
            PRIMARY KEY (concern_id, module_key),
            KEY idx_concern_modules_key (module_key),
            CONSTRAINT concern_modules_ibfk_1 FOREIGN KEY (concern_id) REFERENCES concerns (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    Database::execute(
        "CREATE TABLE IF NOT EXISTS user_concerns (
            user_id INT UNSIGNED NOT NULL,
            concern_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (user_id, concern_id),
            KEY idx_user_concerns_concern (concern_id),
            CONSTRAINT user_concerns_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT user_concerns_ibfk_2 FOREIGN KEY (concern_id) REFERENCES concerns (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    echo "Staff concerns are ready.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'Staff concerns migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
