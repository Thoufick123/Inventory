<?php
/**
 * Database Helper — PDO Singleton
 * Provides a single database connection with transaction support.
 */

class Database {
    private static ?PDO $instance = null;

    /**
     * Error codes indicating MySQL connection was lost or timed out.
     */
    private const GONE_AWAY_ERRORS = [2006, 2013, 2002, 2003, 1053];

    /**
     * Get the PDO connection instance (singleton).
     */
    public static function getInstance(bool $forceReconnect = false): PDO {
        if (self::$instance === null || $forceReconnect) {
            self::$instance = null;
            $config = require __DIR__ . '/../../config/database.php';
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                if (function_exists('env') && env('APP_DEBUG', false)) {
                    throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
                }
                throw new PDOException("Database connection could not be established. Please ensure MySQL service is running in XAMPP.", 2002, $e);
            }
        }
        return self::$instance;
    }

    /**
     * Explicitly reconnect to the database.
     */
    public static function reconnect(): PDO {
        return self::getInstance(true);
    }

    /**
     * Ping the database connection to check if active.
     */
    public static function ping(): bool {
        try {
            self::getInstance()->query('SELECT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static int $transactionDepth = 0;

    /**
     * Check if currently in a transaction.
     */
    public static function inTransaction(): bool {
        try {
            return self::getInstance()->inTransaction();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Begin a database transaction (supports nested calls).
     */
    public static function beginTransaction(): bool {
        if (self::$transactionDepth === 0 || !self::inTransaction()) {
            self::$transactionDepth = 1;
            return self::getInstance()->beginTransaction();
        }
        self::$transactionDepth++;
        return true;
    }

    /**
     * Commit the current transaction (commits on outermost layer).
     */
    public static function commit(): bool {
        if (self::$transactionDepth > 1) {
            self::$transactionDepth--;
            return true;
        }
        self::$transactionDepth = 0;
        if (self::inTransaction()) {
            return self::getInstance()->commit();
        }
        return true;
    }

    /**
     * Roll back the current transaction.
     */
    public static function rollback(): bool {
        if (self::$transactionDepth > 1) {
            self::$transactionDepth--;
            return true;
        }
        self::$transactionDepth = 0;
        if (self::inTransaction()) {
            return self::getInstance()->rollBack();
        }
        return true;
    }

    /**
     * Check if an exception represents a disconnected or gone-away MySQL server.
     */
    private static function isConnectionLost(PDOException $e): bool {
        $msg = strtolower($e->getMessage());
        $code = (int)($e->errorInfo[1] ?? $e->getCode());

        if (in_array($code, self::GONE_AWAY_ERRORS, true)) {
            return true;
        }

        return str_contains($msg, 'server has gone away')
            || str_contains($msg, 'lost connection')
            || str_contains($msg, 'failed to connect')
            || str_contains($msg, 'error while sending')
            || str_contains($msg, 'packets out of order');
    }

    /**
     * Execute a query with prepared statement and return the statement.
     * Auto-reconnects and retries once if connection went away (and not in transaction).
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage() . " | SQL: " . $sql);
            // If connection was lost and we are not in a transaction, retry once after reconnecting
            if (self::$transactionDepth === 0 && self::isConnectionLost($e)) {
                error_log("Database connection lost. Reconnecting and retrying query: " . substr($sql, 0, 80));
                self::reconnect();
                $stmt = self::getInstance()->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }
            throw $e;
        }
    }

    /**
     * Fetch a single row.
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows.
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Execute an insert/update/delete and return affected rows.
     */
    public static function execute(string $sql, array $params = []): int {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Get the last inserted ID.
     */
    public static function lastInsertId(): string {
        return self::getInstance()->lastInsertId();
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}
}
