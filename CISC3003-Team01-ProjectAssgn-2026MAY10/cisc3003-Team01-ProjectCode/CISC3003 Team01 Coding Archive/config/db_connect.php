<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/ensure_timezone.php';
app_timezone_init();

/**
 * Database connection configuration.
 * - Uses PDO + prepared statements to prevent SQL injection.
 * - Uses UTF-8 consistently.
 * - Optional: copy `db_connect.local.example.php` to `db_connect.local.php` (gitignored) for host-specific credentials.
 */

require_once __DIR__ . '/db_env.php';
require_once __DIR__ . '/../includes/app_log.php';

$dbHost = db_env('DB_HOST', '127.0.0.1');
$dbPort = (int) db_env('DB_PORT', '3307');
$dbName = db_env('DB_NAME', 'um_rental_system');
$dbUser = db_env('DB_USER', 'um_app');
$dbPass = db_env('DB_PASS', '');
$dbSocket = db_env('DB_SOCKET', ''); // For Cloud SQL: /cloudsql/PROJECT:REGION:INSTANCE

// Keep production-safe defaults. Enable debug only via explicit local/env override.
$debugMode = false;
if (array_key_exists('DEBUG_MODE', db_env_local())) {
    $debugMode = filter_var(db_env_local()['DEBUG_MODE'], FILTER_VALIDATE_BOOLEAN);
}

// Connect to the server first, then switch database to distinguish credentials issues from missing database issues.
$dsn = $dbSocket !== ''
    ? "mysql:unix_socket={$dbSocket};charset=utf8mb4"
    : "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Explicitly check whether the target database exists.
    $dbCheckStmt = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :db_name LIMIT 1');
    $dbCheckStmt->execute([':db_name' => $dbName]);
    if (!$dbCheckStmt->fetch()) {
        throw new RuntimeException("Database `{$dbName}` does not exist. Please import database/schema.sql first.");
    }

    // Switch to the target database.
    $pdo->exec("USE `{$dbName}`");
    app_timezone_configure_pdo($pdo);
} catch (PDOException $e) {
    app_log('db_connect', 'error', 'PDO connection failed', [
        'host' => $dbHost,
        'port' => $dbPort,
        'dbname' => $dbName,
        'user' => $dbUser,
        'code' => $e->getCode(),
        'sqlstate' => $e->errorInfo[0] ?? '',
    ]);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please check your settings.',
        'debug' => $debugMode ? $e->getMessage() : null,
        'hint' => [
            'Make sure XAMPP MySQL is running.',
            'Verify DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS.',
            'Set DEBUG_MODE=true only in local troubleshooting.',
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (RuntimeException $e) {
    app_log('db_connect', 'error', 'Database initialization failed: ' . $e->getMessage(), [
        'dbname' => $dbName,
    ]);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Database initialization failed.',
        'debug' => $debugMode ? $e->getMessage() : null,
        'hint' => [
            'Import database/schema.sql using MySQL Workbench or phpMyAdmin.',
            "Make sure the database name is {$dbName}.",
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
