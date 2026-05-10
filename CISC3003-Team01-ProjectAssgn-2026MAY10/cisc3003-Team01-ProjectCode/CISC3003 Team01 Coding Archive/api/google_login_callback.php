<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/oauth_config.php';

function failToLogin(string $message): void
{
    $msg = urlencode($message);
    header("Location: ../login.php?oauth_error={$msg}");
    exit;
}

function httpPostForm(string $url, array $formData): array
{
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($formData, '', '&', PHP_QUERY_RFC3986),
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ];
    $response = @file_get_contents($url, false, stream_context_create($options));
    if ($response === false) {
        return [];
    }
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : [];
}

function httpGetJson(string $url, string $accessToken): array
{
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$accessToken}\r\n",
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ];
    $response = @file_get_contents($url, false, stream_context_create($options));
    if ($response === false) {
        return [];
    }
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : [];
}

function generateCampusId(PDO $pdo, string $email): string
{
    $seed = preg_replace('/[^a-z0-9]/i', '', strstr($email, '@', true) ?: 'googleuser');
    $seed = strtolower(substr($seed, 0, 12));
    if ($seed === '') {
        $seed = 'googleuser';
    }

    for ($i = 0; $i < 10; $i++) {
        $candidate = sprintf('g_%s_%03d', $seed, random_int(0, 999));
        $stmt = $pdo->prepare('SELECT id FROM users WHERE campus_id = :campus_id LIMIT 1');
        $stmt->execute([':campus_id' => $candidate]);
        if (!$stmt->fetch()) {
            return $candidate;
        }
    }

    return 'g_' . bin2hex(random_bytes(6));
}

$oauthConfig = loadGoogleOAuthConfig();
$clientId = $oauthConfig['client_id'];
$clientSecret = $oauthConfig['client_secret'];
$redirectUri = resolveGoogleRedirectUri($oauthConfig);

if ($clientId === '' || $clientSecret === '') {
    failToLogin('Google login is not configured yet.');
}

$state = (string)($_GET['state'] ?? '');
$code = (string)($_GET['code'] ?? '');
$sessionState = (string)($_SESSION['google_oauth_state'] ?? '');
unset($_SESSION['google_oauth_state']);

if ($code === '' || $state === '' || $sessionState === '' || !hash_equals($sessionState, $state)) {
    failToLogin('Invalid Google OAuth state.');
}

$tokenData = httpPostForm('https://oauth2.googleapis.com/token', [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code',
]);

$accessToken = (string)($tokenData['access_token'] ?? '');
if ($accessToken === '') {
    failToLogin('Unable to get Google access token.');
}

$googleUser = httpGetJson('https://www.googleapis.com/oauth2/v2/userinfo', $accessToken);
$email = trim((string)($googleUser['email'] ?? ''));
$name = trim((string)($googleUser['name'] ?? 'Google User'));

if ($email === '') {
    failToLogin('Google account email is unavailable.');
}

try {
    $stmt = $pdo->prepare('
        SELECT id, campus_id, full_name, role, email, account_status
        FROM users
        WHERE email = :email
        LIMIT 1
    ');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        $campusId = generateCampusId($pdo, $email);
        $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $insert = $pdo->prepare('
            INSERT INTO users (campus_id, full_name, role, email, phone, balance, password_hash, account_status)
            VALUES (:campus_id, :full_name, :role, :email, :phone, :balance, :password_hash, :account_status)
        ');
        $insert->execute([
            ':campus_id' => $campusId,
            ':full_name' => $name !== '' ? $name : 'Google User',
            ':role' => 'visitor',
            ':email' => $email,
            ':phone' => null,
            ':balance' => 0.00,
            ':password_hash' => $passwordHash,
            ':account_status' => 'active',
        ]);

        $userId = (int)$pdo->lastInsertId();
        $user = [
            'id' => $userId,
            'campus_id' => $campusId,
            'full_name' => $name,
            'role' => 'visitor',
            'email' => $email,
            'account_status' => 'active',
        ];
    }

    if ((string)$user['account_status'] !== 'active') {
        failToLogin('Account is frozen or disabled. Please contact admin.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['campus_id'] = (string)$user['campus_id'];
    $_SESSION['full_name'] = (string)$user['full_name'];
    $_SESSION['role'] = (string)$user['role'];
    $_SESSION['email'] = (string)$user['email'];
    $_SESSION['last_activity'] = time();

    if ((string)$user['role'] === 'admin') {
        header('Location: ../admin/admin_dashboard.php');
        exit;
    }

    if ((string)$user['role'] === 'staff') {
        header('Location: ../staff/home.php');
        exit;
    }

    header('Location: ../dashboard.php');
    exit;
} catch (Throwable $e) {
    failToLogin('Google login failed. Please try again later.');
}
