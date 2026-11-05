<?php
/**
 * Application Configuration
 * Loads environment variables from .env and provides app-wide settings.
 */

// Load .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

/**
 * Get environment variable with optional default.
 */
function env(string $key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null) return $default;
    
    // Cast common string values
    $lower = strtolower($value);
    if ($lower === 'true') return true;
    if ($lower === 'false') return false;
    if ($lower === 'null') return null;
    
    return $value;
}

return [
    'name'      => env('APP_NAME', 'GRM Inventory Management'),
    'env'       => env('APP_ENV', 'local'),
    'url'       => env('APP_URL', 'http://localhost/grm_inventory/grm_inventory'),
    'debug'     => env('APP_DEBUG', false),
    'timezone'  => env('APP_TIMEZONE', 'Asia/Kolkata'),
    'currency'  => 'INR',
    'currency_symbol' => '₹',
    'locale'    => 'en_IN',
];
