<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);

require_once $projectRoot . '/config.php';
require_once $projectRoot . '/includes/app_log.php';

/**
 * login_verify.php
 * - 模擬 SSO 的校園 ID + 密碼驗證
 * - 連續失敗 5 次鎖定 15 分鐘
 * - 驗證成功後建立 Session
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'message' => '只允許 POST 請求']);
}

verifyCsrfOrFail();
rateLimit('login_verify:' . clientIp(), 20, 300, 300);

$body = getJsonBody();
$loginID = trim((string)($body['loginID'] ?? ($body['campusID'] ?? '')));
$password = (string)($body['password'] ?? '');

if ($loginID === '' || $password === '') {
    jsonResponse(422, ['success' => false, 'message' => 'loginID (Campus ID or Email) and password are required']);
}

try {
    $stmt = $pdo->prepare('
        SELECT id, campus_id, full_name, role, email, password_hash, account_status,
               failed_login_attempts, lock_until
        FROM users
        WHERE campus_id = :campus_id OR email = :email
        LIMIT 1
    ');
    $stmt->execute([
        ':campus_id' => $loginID,
        ':email' => $loginID,
    ]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(401, ['success' => false, 'message' => 'Invalid Campus ID/Email or password']);
    }

    if ((string)$user['account_status'] !== 'active') {
        jsonResponse(403, ['success' => false, 'message' => 'Account status does not allow login']);
    }

    // 鎖定檢查
    if (!empty($user['lock_until']) && strtotime((string)$user['lock_until']) > time()) {
        $remaining = (int)ceil((strtotime((string)$user['lock_until']) - time()) / 60);
        jsonResponse(423, ['success' => false, 'message' => "Account is locked. Try again in about {$remaining} minute(s)."]);
    }

    if (!password_verify($password, (string)$user['password_hash'])) {
        $failed = (int)$user['failed_login_attempts'] + 1;
        $lockUntil = null;
        if ($failed >= 5) {
            $failed = 5;
            $lockUntil = date('Y-m-d H:i:s', time() + (15 * 60));
        }

        $upd = $pdo->prepare('
            UPDATE users
            SET failed_login_attempts = :failed,
                lock_until = :lock_until
            WHERE id = :id
        ');
        $upd->bindValue(':failed', $failed, PDO::PARAM_INT);
        $upd->bindValue(':lock_until', $lockUntil, $lockUntil === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $upd->bindValue(':id', (int)$user['id'], PDO::PARAM_INT);
        $upd->execute();

        if ($lockUntil !== null) {
            app_log('login_verify', 'warning', 'Account locked after failed attempts', [
                'user_id' => (int)$user['id'],
                'failed_attempts' => $failed,
            ]);
            jsonResponse(423, ['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.']);
        }
        jsonResponse(401, ['success' => false, 'message' => 'Invalid Campus ID/Email or password']);
    }

    // 登入成功：重置失敗計數並建立 Session
    $reset = $pdo->prepare('
        UPDATE users
        SET failed_login_attempts = 0,
            lock_until = NULL
        WHERE id = :id
    ');
    $reset->execute([':id' => (int)$user['id']]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['campus_id'] = (string)$user['campus_id'];
    $_SESSION['full_name'] = (string)$user['full_name'];
    $_SESSION['role'] = (string)$user['role'];
    $_SESSION['email'] = (string)$user['email'];
    $_SESSION['last_activity'] = time();

    $redirect = './dashboard.php';
    if ((string)$user['role'] === 'admin') {
        $redirect = './admin/admin_dashboard.php';
    } elseif ((string)$user['role'] === 'staff') {
        $redirect = './staff/home.php';
    }

    jsonResponse(200, [
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect,
    ]);
} catch (Throwable $e) {
    jsonResponse(500, ['success' => false, 'message' => 'Login verification failed. Please try again later.']);
}
