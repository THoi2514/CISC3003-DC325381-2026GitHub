<?php
declare(strict_types=1);

/**
 * Shared DB credential resolution for:
 * - config/db_connect.php (API bootstrap)
 * - includes/db.php (pages via auth.php)
 * - config.php (APIs using global $pdo)
 *
 * Priority: env-specific keys in config/db_connect.local.php → env-specific getenv() → generic keys → defaults.
 *
 * Supported key prefixes:
 * - LOCAL_DB_HOST / LOCAL_DB_PORT / ... (used in local runtime)
 * - PROD_DB_HOST / PROD_DB_PORT / ...   (used in production runtime)
 * - DB_HOST / DB_PORT / ...             (generic fallback for both)
 */

function db_env_local(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    $localPath = __DIR__ . '/db_connect.local.php';
    if (is_readable($localPath)) {
        $loaded = require $localPath;
        if (is_array($loaded)) {
            $cache = $loaded;
        }
    }
    return $cache;
}

/**
 * Resolve runtime context:
 * 1) APP_ENV override (dev/local vs prod/production)
 * 2) CLI defaults to local
 * 3) Loopback hosts are treated as local
 */
function db_runtime_env(): string
{
    $explicit = strtolower(trim((string) getenv('APP_ENV')));
    if (in_array($explicit, ['local', 'dev', 'development'], true)) {
        return 'local';
    }
    if (in_array($explicit, ['prod', 'production'], true)) {
        return 'production';
    }

    if (PHP_SAPI === 'cli') {
        return 'local';
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $addr = strtolower((string) ($_SERVER['SERVER_ADDR'] ?? ''));
    if (
        $host === 'localhost' ||
        str_starts_with($host, '127.0.0.1') ||
        str_ends_with($host, '.local') ||
        $addr === '127.0.0.1' ||
        $addr === '::1'
    ) {
        return 'local';
    }

    return 'production';
}

function db_env_candidate_keys(string $key): array
{
    if (!str_starts_with($key, 'DB_')) {
        return [$key];
    }
    $suffix = substr($key, 3);
    if ($suffix === false || $suffix === '') {
        return [$key];
    }

    $env = db_runtime_env();
    if ($env === 'local') {
        return ['LOCAL_DB_' . $suffix, $key];
    }
    return ['PROD_DB_' . $suffix, $key];
}

function db_env(string $key, string $default): string
{
    $localDb = db_env_local();
    foreach (db_env_candidate_keys($key) as $candidate) {
        if (array_key_exists($candidate, $localDb)) {
            $v = $localDb[$candidate];
            if ($v !== null && $v !== '') {
                return (string) $v;
            }
        }

        $value = getenv($candidate);
        if ($value !== false && $value !== '') {
            return (string) $value;
        }
    }
    return $default;
}

/** Root-password fallback is only for typical local XAMPP; never on remote DB hosts. */
function db_env_is_local_mysql_host(string $host): bool
{
    return in_array(strtolower($host), ['127.0.0.1', 'localhost', '::1'], true);
}
