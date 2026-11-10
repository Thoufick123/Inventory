<?php

class ConcernModuleRollout
{
    private static ?array $enabledModules = null;

    public static function isEnabled(string $module): bool
    {
        if (self::$enabledModules === null) {
            $configPath = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2))
                . '/config/concern_module_rollout.php';
            $config = require $configPath;
            $modules = $config['enabled_modules'] ?? null;
            if (!is_array($modules)) {
                throw new RuntimeException('Concern module rollout configuration must define an enabled_modules array.');
            }

            self::$enabledModules = array_fill_keys(
                array_values(array_filter($modules, 'is_string')),
                true
            );
        }

        return isset(self::$enabledModules[$module]);
    }
}
