<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_env.php';
require_once __DIR__ . '/ensure_timezone.php';
require_once __DIR__ . '/app_log.php';

/**
 * Shared database bootstrap for public pages and APIs.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    app_timezone_init();

    $host = db_env('DB_HOST', '127.0.0.1');
    $port = (int) db_env('DB_PORT', '3307');
    $name = db_env('DB_NAME', 'um_rental_system');
    $user = db_env('DB_USER', 'um_app');
    $pass = db_env('DB_PASS', '');
    $socket = db_env('DB_SOCKET', '');
    $allowRootFallback = filter_var(db_env('DB_ALLOW_ROOT_FALLBACK', 'false'), FILTER_VALIDATE_BOOLEAN);

    $dsn = $socket !== ''
        ? "mysql:unix_socket={$socket};dbname={$name};charset=utf8mb4"
        : "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        if ($socket !== '' || !db_env_is_local_mysql_host($host) || !$allowRootFallback) {
            app_log('database', 'error', 'db() PDO connection failed', [
                'host' => $host,
                'port' => $port,
                'dbname' => $name,
                'user' => $user,
                'code' => $e->getCode(),
                'sqlstate' => $e->errorInfo[0] ?? '',
            ]);
            throw $e;
        }
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e2) {
            app_log('database', 'error', 'db() root fallback PDO failed', [
                'host' => $host,
                'port' => $port,
                'dbname' => $name,
                'code' => $e2->getCode(),
                'sqlstate' => $e2->errorInfo[0] ?? '',
            ]);
            throw $e2;
        }
    }

    app_timezone_configure_pdo($pdo);

    return $pdo;
}
