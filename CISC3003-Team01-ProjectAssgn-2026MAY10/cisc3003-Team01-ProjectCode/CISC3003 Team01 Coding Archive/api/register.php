<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_connect.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * 安全輸出 JSON 並結束程式
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

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    respond(400, ['success' => false, 'message' => '請提供正確的 JSON 格式']);
}

// 讀取並清理輸入（避免儲存惡意腳本）
$campusId = trim((string)($data['campusID'] ?? ''));
$fullName = trim((string)($data['fullName'] ?? ''));
$role = trim((string)($data['role'] ?? 'student'));
$email = trim((string)($data['email'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$password = (string)($data['password'] ?? '');
$verificationCode = trim((string)($data['verificationCode'] ?? ''));

if ($campusId === '' || $fullName === '' || $email === '' || $password === '' || $verificationCode === '') {
    respond(422, ['success' => false, 'message' => 'campusID、fullName、email、password、verificationCode 為必填']);
}

if (!in_array($role, ['student', 'teacher'], true)) {
    respond(403, ['success' => false, 'message' => '公開註冊僅允許學生或教職員（student / teacher）']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['success' => false, 'message' => 'email 格式不正確']);
}

if (!preg_match('/^\d{6}$/', $verificationCode)) {
    respond(422, ['success' => false, 'message' => '驗證碼格式不正確，請輸入 6 位數字']);
}

// 密碼至少 8 碼，且包含英文字母與數字
if (
    strlen($password) < 8
    || !preg_match('/[A-Za-z]/', $password)
    || !preg_match('/\d/', $password)
) {
    respond(422, ['success' => false, 'message' => '密碼需至少 8 碼，且包含英文字母與數字']);
}

// 避免 XSS：儲存前先做基本淨化（前端顯示時仍需 htmlspecialchars）
$safeFullName = strip_tags($fullName);
$safePhone = strip_tags($phone);
$normalizedEmail = strtolower($email);
$verificationStore = $_SESSION['register_email_verification'] ?? [];
$verificationItem = $verificationStore[$normalizedEmail] ?? null;

if (!is_array($verificationItem)) {
    respond(422, ['success' => false, 'message' => '請先發送電郵驗證碼']);
}
if ((int)($verificationItem['expires_at'] ?? 0) < time()) {
    respond(422, ['success' => false, 'message' => '驗證碼已過期，請重新發送']);
}
if (!is_string($verificationItem['code_hash'] ?? null) || !password_verify($verificationCode, $verificationItem['code_hash'])) {
    $attempts = (int)($verificationItem['attempts'] ?? 0) + 1;
    $verificationStore[$normalizedEmail]['attempts'] = $attempts;
    $_SESSION['register_email_verification'] = $verificationStore;
    if ($attempts >= 5) {
        unset($verificationStore[$normalizedEmail]);
        $_SESSION['register_email_verification'] = $verificationStore;
        respond(422, ['success' => false, 'message' => '驗證碼錯誤次數過多，請重新發送']);
    }
    respond(422, ['success' => false, 'message' => '驗證碼錯誤']);
}

try {
    // 檢查 campus_id 或 email 是否重複
    $checkStmt = $pdo->prepare('
        SELECT id
        FROM users
        WHERE campus_id = :campus_id OR email = :email
        LIMIT 1
    ');
    $checkStmt->execute([
        ':campus_id' => $campusId,
        ':email' => $email,
    ]);

    if ($checkStmt->fetch()) {
        respond(409, ['success' => false, 'message' => 'campusID 或 email 已被註冊']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare('
        INSERT INTO users (
            campus_id, full_name, role, email, phone, balance, password_hash
        ) VALUES (
            :campus_id, :full_name, :role, :email, :phone, :balance, :password_hash
        )
    ');

    $insertStmt->execute([
        ':campus_id' => $campusId,
        ':full_name' => $safeFullName,
        ':role' => $role,
        ':email' => $normalizedEmail,
        ':phone' => $safePhone !== '' ? $safePhone : null,
        ':balance' => 0.00,
        ':password_hash' => $passwordHash,
    ]);

    unset($verificationStore[$normalizedEmail]);
    $_SESSION['register_email_verification'] = $verificationStore;

    respond(201, [
        'success' => true,
        'message' => '註冊成功',
        'userID' => (int)$pdo->lastInsertId(),
    ]);
} catch (Throwable $e) {
    respond(500, ['success' => false, 'message' => '註冊失敗，請稍後再試']);
}
