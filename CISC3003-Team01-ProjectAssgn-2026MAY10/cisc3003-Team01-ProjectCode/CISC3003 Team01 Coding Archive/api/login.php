<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config.php';

/**
 * 輸出 JSON 並終止
 */
function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => '只允許 POST 請求']);
}

verifyCsrfOrFail();
rateLimit('api_login:' . clientIp(), 20, 300, 300);

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    respond(400, ['success' => false, 'message' => '請提供正確的 JSON 格式']);
}

$loginId = trim((string)($data['loginID'] ?? ($data['campusID'] ?? '')));
$password = (string)($data['password'] ?? '');

if ($loginId === '' || $password === '') {
    respond(422, ['success' => false, 'message' => 'loginID (Campus ID or Email) and password are required']);
}

try {
    $stmt = $pdo->prepare('
        SELECT id, campus_id, full_name, role, email, account_status,
               password_hash, failed_login_attempts, lock_until
        FROM users
        WHERE campus_id = :campus_id OR email = :email
        LIMIT 1
    ');
    $stmt->execute([
        ':campus_id' => $loginId,
        ':email' => $loginId,
    ]);
    $user = $stmt->fetch();

    // 不暴露帳號存在與否細節，減少帳號探測風險
    if (!$user) {
        respond(401, ['success' => false, 'message' => 'Invalid Campus ID/Email or password']);
    }

    if ($user['account_status'] !== 'active') {
        respond(403, ['success' => false, 'message' => 'Account is frozen or disabled. Please contact admin.']);
    }

    // 鎖定判斷：連錯 5 次後鎖 15 分鐘
    if (!empty($user['lock_until']) && strtotime((string)$user['lock_until']) > time()) {
        $remainingSeconds = strtotime((string)$user['lock_until']) - time();
        $remainingMinutes = (int)ceil($remainingSeconds / 60);
        respond(423, [
            'success' => false,
            'message' => "Account is temporarily locked. Try again in about {$remainingMinutes} minute(s).",
        ]);
    }

    if (!password_verify($password, (string)$user['password_hash'])) {
        $newFailedCount = ((int)$user['failed_login_attempts']) + 1;
        $lockUntil = null;

        if ($newFailedCount >= 5) {
            $newFailedCount = 5;
            $lockUntil = date('Y-m-d H:i:s', time() + 15 * 60);
        }

        $updateFailStmt = $pdo->prepare('
            UPDATE users
            SET failed_login_attempts = :failed_count,
                lock_until = :lock_until
            WHERE id = :id
        ');
        $updateFailStmt->bindValue(':failed_count', $newFailedCount, PDO::PARAM_INT);
        $updateFailStmt->bindValue(':lock_until', $lockUntil, $lockUntil === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $updateFailStmt->bindValue(':id', (int)$user['id'], PDO::PARAM_INT);
        $updateFailStmt->execute();

        if ($lockUntil !== null) {
            respond(423, ['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.']);
        }

        respond(401, ['success' => false, 'message' => 'Invalid Campus ID/Email or password']);
    }

    // 登入成功：清除失敗次數與鎖定時間
    $resetStmt = $pdo->prepare('
        UPDATE users
        SET failed_login_attempts = 0,
            lock_until = NULL
        WHERE id = :id
    ');
    $resetStmt->execute([':id' => (int)$user['id']]);

    // 建立 Session（前後端分離時可改 JWT）
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['campus_id'] = (string)$user['campus_id'];
    $_SESSION['role'] = (string)$user['role'];

    $redirect = './dashboard.php';
    if ((string)$user['role'] === 'admin') {
        $redirect = './admin/admin_dashboard.php';
    } elseif ((string)$user['role'] === 'staff') {
        $redirect = './staff/home.php';
    }

    respond(200, [
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect,
        'user' => [
            'id' => (int)$user['id'],
            'campusID' => (string)$user['campus_id'],
            'fullName' => (string)$user['full_name'],
            'role' => (string)$user['role'],
            'email' => (string)$user['email'],
        ],
    ]);
} catch (Throwable $e) {
    respond(500, ['success' => false, 'message' => 'Login failed. Please try again later.']);
}
