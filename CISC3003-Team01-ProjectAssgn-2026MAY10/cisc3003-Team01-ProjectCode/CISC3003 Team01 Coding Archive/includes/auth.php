<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Starts secure session for authentication features.
 */
function startAuthSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Redirects to login page when user session is missing.
 */
function requireUserSession(): int
{
    startAuthSession();
    if (!isset($_SESSION['user_id'])) {
        header('Location: ./login.php');
        exit;
    }
    return (int)$_SESSION['user_id'];
}

/**
 * Attempts login with lockout strategy.
 */
function attemptLogin(string $loginId, string $password): array
{
    $pdo = db();
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $scopeKey = 'web_login:' . $ip . ':' . strtolower($loginId);

    $limitStmt = $pdo->prepare('SELECT id, attempts, window_start, blocked_until FROM rate_limits WHERE scope_key = :scope_key LIMIT 1');
    $limitStmt->execute([':scope_key' => $scopeKey]);
    $limitRow = $limitStmt->fetch();
    $nowTs = time();
    if ($limitRow && !empty($limitRow['blocked_until']) && strtotime((string)$limitRow['blocked_until']) > $nowTs) {
        return ['ok' => false, 'message' => 'Too many login attempts. Please try again later.'];
    }

    $stmt = $pdo->prepare('
        SELECT id, campus_id, full_name, role, email, password_hash, failed_login_attempts, lock_until, account_status
        FROM users
        WHERE campus_id = :campus_id OR email = :email
        LIMIT 1
    ');
    $stmt->execute([
        ':campus_id' => $loginId,
        ':email' => $loginId,
    ]);
    $user = $stmt->fetch();

    if (!$user) {
        $now = date('Y-m-d H:i:s');
        if ($limitRow) {
            $attempts = ((int)$limitRow['attempts']) + 1;
            $blockedUntil = $attempts > 10 ? date('Y-m-d H:i:s', $nowTs + 600) : null;
            $upd = $pdo->prepare('UPDATE rate_limits SET attempts = :attempts, window_start = :window_start, blocked_until = :blocked_until WHERE id = :id');
            $upd->bindValue(':attempts', $attempts, PDO::PARAM_INT);
            $upd->bindValue(':window_start', $now);
            $upd->bindValue(':blocked_until', $blockedUntil, $blockedUntil === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $upd->bindValue(':id', (int)$limitRow['id'], PDO::PARAM_INT);
            $upd->execute();
        } else {
            $ins = $pdo->prepare('INSERT INTO rate_limits (scope_key, attempts, window_start, blocked_until) VALUES (:scope_key, 1, :window_start, NULL)');
            $ins->execute([':scope_key' => $scopeKey, ':window_start' => $now]);
        }
        return ['ok' => false, 'message' => 'Invalid Campus ID/Email or password.'];
    }

    if ($user['account_status'] !== 'active') {
        return ['ok' => false, 'message' => 'This account is not active.'];
    }

    if (!empty($user['lock_until']) && strtotime((string)$user['lock_until']) > time()) {
        $minutes = (int)ceil((strtotime((string)$user['lock_until']) - time()) / 60);
        return ['ok' => false, 'message' => "Account locked. Try again in {$minutes} minute(s)."];
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
            SET failed_login_attempts = :failed, lock_until = :lock_until
            WHERE id = :id
        ');
        $upd->bindValue(':failed', $failed, PDO::PARAM_INT);
        $upd->bindValue(':lock_until', $lockUntil, $lockUntil === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $upd->bindValue(':id', (int)$user['id'], PDO::PARAM_INT);
        $upd->execute();

        if ($lockUntil !== null) {
            return ['ok' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.'];
        }
        return ['ok' => false, 'message' => 'Invalid Campus ID/Email or password.'];
    }

    $reset = $pdo->prepare('
        UPDATE users
        SET failed_login_attempts = 0, lock_until = NULL
        WHERE id = :id
    ');
    $reset->execute([':id' => (int)$user['id']]);

    startAuthSession();
    session_regenerate_id(true);
    if ($limitRow) {
        $resetLimit = $pdo->prepare('UPDATE rate_limits SET attempts = 0, blocked_until = NULL, window_start = :window_start WHERE id = :id');
        $resetLimit->execute([':window_start' => date('Y-m-d H:i:s'), ':id' => (int)$limitRow['id']]);
    }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['campus_id'] = (string)$user['campus_id'];
    $_SESSION['full_name'] = (string)$user['full_name'];
    $_SESSION['role'] = (string)$user['role'];
    $_SESSION['email'] = (string)$user['email'];

    return ['ok' => true];
}
