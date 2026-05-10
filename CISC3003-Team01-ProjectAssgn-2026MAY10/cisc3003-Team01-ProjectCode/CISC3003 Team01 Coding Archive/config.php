<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/ensure_timezone.php';
app_timezone_init();

/**
 * 全域設定與資料庫連線
 * - 統一由此檔初始化 session 與 PDO
 * - 所有 API 皆回傳 JSON（頁面檔除外）
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    // 30 分鐘閒置逾時設定（session.gc_maxlifetime 需搭配 php.ini）
    ini_set('session.gc_maxlifetime', '1800');
    session_start();
}

require_once __DIR__ . '/config/db_env.php';
require_once __DIR__ . '/includes/app_log.php';

$dbHost = db_env('DB_HOST', '127.0.0.1');
$dbPort = (int) db_env('DB_PORT', '3307');
$dbName = db_env('DB_NAME', 'um_rental_system');
$dbUser = db_env('DB_USER', 'um_app');
$dbPass = db_env('DB_PASS', '');
$dbSocket = db_env('DB_SOCKET', '');
$allowRootFallback = filter_var(db_env('DB_ALLOW_ROOT_FALLBACK', 'false'), FILTER_VALIDATE_BOOLEAN);

$dsn = $dbSocket !== ''
    ? "mysql:unix_socket={$dbSocket};dbname={$dbName};charset=utf8mb4"
    : "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    app_log('database', 'error', 'PDO primary connection failed', [
        'host' => $dbHost,
        'port' => $dbPort,
        'dbname' => $dbName,
        'user' => $dbUser,
        'code' => $e->getCode(),
        'sqlstate' => $e->errorInfo[0] ?? '',
    ]);
    if ($dbSocket !== '' || !db_env_is_local_mysql_host($dbHost) || !$allowRootFallback) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please check database credentials and port.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    try {
        $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e2) {
        app_log('database', 'error', 'PDO root fallback connection failed', [
            'host' => $dbHost,
            'port' => $dbPort,
            'dbname' => $dbName,
            'code' => $e2->getCode(),
            'sqlstate' => $e2->errorInfo[0] ?? '',
        ]);
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please check database credentials and port.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (isset($pdo) && $pdo instanceof PDO) {
    app_timezone_configure_pdo($pdo);
}

function jsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function requireLogin(): int
{
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(401, ['success' => false, 'message' => '請先登入']);
    }
    return (int)$_SESSION['user_id'];
}

function requireAdmin(): int
{
    $userId = requireLogin();
    $role = (string)($_SESSION['role'] ?? '');
    $campusId = (string)($_SESSION['campus_id'] ?? '');
    if ($role !== 'admin' || $campusId !== 'dc325107') {
        jsonResponse(403, ['success' => false, 'message' => '無管理員權限']);
    }
    return $userId;
}

function requireStaff(): int
{
    $userId = requireLogin();
    $role = (string)($_SESSION['role'] ?? '');
    if ($role !== 'staff') {
        jsonResponse(403, ['success' => false, 'message' => 'Staff permission required']);
    }
    return $userId;
}

function requireStaffOrAdmin(): int
{
    $userId = requireLogin();
    $role = (string)($_SESSION['role'] ?? '');
    if (!in_array($role, ['staff', 'admin'], true)) {
        jsonResponse(403, ['success' => false, 'message' => 'Staff or admin permission required']);
    }
    return $userId;
}

/**
 * 可使用租借（租車／還車／車輛列表）的身分：学生、教职员 teacher、访客、职员租借自用、管理员。
 */
function roleCanRent(?string $role): bool
{
    return in_array((string)$role, ['student', 'teacher', 'visitor', 'staff', 'admin'], true);
}

/** Edit rental_stations map coordinates (staff / admin only). */
function roleCanEditRentalStationCoordinates(?string $role): bool
{
    return in_array((string)$role, ['staff', 'admin'], true);
}

/**
 * 登入且為可租借身分（與 roleCanRent 一致）。
 */
function requireRentalRole(): int
{
    $userId = requireLogin();
    if (!roleCanRent((string)($_SESSION['role'] ?? ''))) {
        jsonResponse(403, ['success' => false, 'message' => '此帳戶無法使用租借服務', 'message_key' => 'errRentalNotAllowed']);
    }
    return $userId;
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function verifyCsrfOrFail(): void
{
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }

    $headerToken = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $formToken = (string)($_POST['_csrf'] ?? '');
    $provided = $headerToken !== '' ? $headerToken : $formToken;
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    if ($provided === '' || $sessionToken === '' || !hash_equals($sessionToken, $provided)) {
        jsonResponse(419, ['success' => false, 'message' => 'CSRF validation failed']);
    }
}

function clientIp(): string
{
    return trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function rateLimit(string $scopeKey, int $limit, int $windowSeconds, int $blockSeconds = 0): void
{
    global $pdo;
    try {
        $now = new DateTimeImmutable('now');
        $windowStart = $now->sub(new DateInterval('PT' . $windowSeconds . 'S'));
        $blockedUntil = null;
        $attempts = 0;

        $stmt = $pdo->prepare('SELECT id, attempts, window_start, blocked_until FROM rate_limits WHERE scope_key = :scope_key LIMIT 1');
        $stmt->execute([':scope_key' => $scopeKey]);
        $row = $stmt->fetch();

        if ($row) {
            if (!empty($row['blocked_until']) && strtotime((string)$row['blocked_until']) > $now->getTimestamp()) {
                jsonResponse(429, ['success' => false, 'message' => 'Too many requests. Please try again later.']);
            }

            $existingWindowStart = strtotime((string)$row['window_start']);
            if ($existingWindowStart === false || $existingWindowStart < $windowStart->getTimestamp()) {
                $attempts = 1;
            } else {
                $attempts = (int)$row['attempts'] + 1;
            }

            if ($attempts > $limit) {
                $blockedUntil = $blockSeconds > 0 ? $now->add(new DateInterval('PT' . $blockSeconds . 'S'))->format('Y-m-d H:i:s') : null;
                $upd = $pdo->prepare('
                    UPDATE rate_limits
                    SET attempts = :attempts, window_start = :window_start, blocked_until = :blocked_until
                    WHERE id = :id
                ');
                $upd->bindValue(':attempts', $attempts, PDO::PARAM_INT);
                $upd->bindValue(':window_start', $now->format('Y-m-d H:i:s'));
                $upd->bindValue(':blocked_until', $blockedUntil, $blockedUntil === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $upd->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
                $upd->execute();
                jsonResponse(429, ['success' => false, 'message' => 'Too many requests. Please try again later.']);
            }

            $upd = $pdo->prepare('
                UPDATE rate_limits
                SET attempts = :attempts, window_start = :window_start, blocked_until = NULL
                WHERE id = :id
            ');
            $upd->bindValue(':attempts', $attempts, PDO::PARAM_INT);
            $upd->bindValue(':window_start', $now->format('Y-m-d H:i:s'));
            $upd->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
            $upd->execute();
            return;
        }

        $ins = $pdo->prepare('
            INSERT INTO rate_limits (scope_key, attempts, window_start, blocked_until)
            VALUES (:scope_key, 1, :window_start, NULL)
        ');
        $ins->execute([
            ':scope_key' => $scopeKey,
            ':window_start' => $now->format('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        // Fail closed: if rate-limit backend is unavailable, block sensitive requests.
        jsonResponse(429, ['success' => false, 'message' => 'Too many requests. Please try again later.']);
    }
}
