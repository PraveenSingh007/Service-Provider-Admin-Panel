<?php

declare(strict_types=1);

namespace App\Admin\Database;

/**
 * Lightweight Zero-Dependency Dotenv Environment Loader
 */
class Dotenv
{
    /**
     * Load environment variables from a .env file path into getenv(), $_ENV, and $_SERVER.
     *
     * @param string $filePath Absolute or relative path to .env file
     * @param bool $override Whether to overwrite existing env vars
     * @return bool True if loaded successfully
     */
    public static function load(string $filePath, bool $override = false): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '//') === 0) {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove enclosing quotes
            if (
                (strpos($value, '"') === 0 && substr($value, -1) === '"') ||
                (strpos($value, "'") === 0 && substr($value, -1) === "'")
            ) {
                $value = substr($value, 1, -1);
            }

            if ($override || getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        return true;
    }
}
