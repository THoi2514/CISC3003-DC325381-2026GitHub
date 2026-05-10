<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function is_local_runtime(): bool
{
    $appEnv = strtolower(trim((string) getenv('APP_ENV')));
    if (in_array($appEnv, ['local', 'dev', 'development'], true)) {
        return true;
    }
    $serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? ''));
    $serverAddr = strtolower((string) ($_SERVER['SERVER_ADDR'] ?? ''));
    return $serverName === 'localhost'
        || str_starts_with($serverName, '127.0.0.1')
        || $serverAddr === '127.0.0.1'
        || $serverAddr === '::1';
}

function env_file_values(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    $envPath = dirname(__DIR__) . '/.env';
    if (!is_readable($envPath)) {
        return $cache;
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return $cache;
    }
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), "\"'");
        if ($key !== '' && !array_key_exists($key, $cache)) {
            $cache[$key] = $value;
        }
    }
    return $cache;
}

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && trim((string) $value) !== '') {
        return trim((string) $value);
    }
    $env = env_file_values();
    if (array_key_exists($key, $env) && trim((string) $env[$key]) !== '') {
        return trim((string) $env[$key]);
    }
    return $default;
}

function smtp_read_line($socket): string
{
    $line = '';
    while (!feof($socket)) {
        $chunk = fgets($socket, 515);
        if ($chunk === false) {
            break;
        }
        $line .= $chunk;
        if (strlen($chunk) >= 4 && $chunk[3] === ' ') {
            break;
        }
    }
    return $line;
}

function smtp_expect($socket, array $expectedCodes, string $step): void
{
    $line = smtp_read_line($socket);
    if ($line === '') {
        throw new RuntimeException("SMTP {$step} failed: empty response");
    }
    $code = (int) substr($line, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException("SMTP {$step} failed: {$line}");
    }
}

function smtp_send_cmd($socket, string $command, array $expectedCodes, string $step): void
{
    $ok = @fwrite($socket, $command . "\r\n");
    if ($ok === false) {
        throw new RuntimeException("SMTP {$step} failed: cannot write command");
    }
    smtp_expect($socket, $expectedCodes, $step);
}

function smtp_send_mail(
    string $host,
    int $port,
    string $encryption,
    string $username,
    string $password,
    string $fromEmail,
    string $fromName,
    string $toEmail,
    string $subject,
    string $body
): bool {
    $transport = strtolower(trim($encryption)) === 'ssl' ? 'ssl://' : 'tcp://';
    $socket = @stream_socket_client(
        $transport . $host . ':' . $port,
        $errno,
        $errstr,
        15,
        STREAM_CLIENT_CONNECT
    );
    if (!$socket) {
        throw new RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
    }
    stream_set_timeout($socket, 15);

    try {
        smtp_expect($socket, [220], 'greeting');
        smtp_send_cmd($socket, 'EHLO localhost', [250], 'EHLO');

        $enc = strtolower(trim($encryption));
        if ($enc === 'tls') {
            smtp_send_cmd($socket, 'STARTTLS', [220], 'STARTTLS');
            $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoEnabled !== true) {
                throw new RuntimeException('SMTP TLS handshake failed');
            }
            smtp_send_cmd($socket, 'EHLO localhost', [250], 'EHLO after STARTTLS');
        }

        smtp_send_cmd($socket, 'AUTH LOGIN', [334], 'AUTH LOGIN');
        smtp_send_cmd($socket, base64_encode($username), [334], 'SMTP username');
        smtp_send_cmd($socket, base64_encode($password), [235], 'SMTP password');

        smtp_send_cmd($socket, 'MAIL FROM:<' . $fromEmail . '>', [250], 'MAIL FROM');
        smtp_send_cmd($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251], 'RCPT TO');
        smtp_send_cmd($socket, 'DATA', [354], 'DATA');

        $safeSubject = str_replace(["\r", "\n"], '', $subject);
        $safeFromName = str_replace(["\r", "\n"], '', $fromName);
        $message = 'From: ' . $safeFromName . ' <' . $fromEmail . ">\r\n"
            . 'To: <' . $toEmail . ">\r\n"
            . 'Subject: ' . $safeSubject . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n"
            . "\r\n"
            . str_replace("\n.", "\n..", $body) . "\r\n.";

        $ok = @fwrite($socket, $message . "\r\n");
        if ($ok === false) {
            throw new RuntimeException('SMTP DATA write failed');
        }
        smtp_expect($socket, [250], 'message body');
        smtp_send_cmd($socket, 'QUIT', [221], 'QUIT');
    } finally {
        @fclose($socket);
    }

    return true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => '只允許 POST 請求']);
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    respond(400, ['success' => false, 'message' => '請提供正確的 JSON 格式']);
}

$email = strtolower(trim((string)($data['email'] ?? '')));
if ($email === '') {
    respond(422, ['success' => false, 'message' => 'email 為必填']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['success' => false, 'message' => 'email 格式不正確']);
}

$store = $_SESSION['register_email_verification'] ?? [];
$entry = $store[$email] ?? null;
$now = time();
$cooldownSec = 60;
if (is_array($entry)) {
    $lastSent = (int)($entry['last_sent_at'] ?? 0);
    if (($now - $lastSent) < $cooldownSec) {
        $remain = $cooldownSec - ($now - $lastSent);
        respond(429, [
            'success' => false,
            'message' => "請於 {$remain} 秒後再試",
            'cooldownSec' => $remain,
        ]);
    }
}

$code = (string) random_int(100000, 999999);
$appName = env_value('APP_NAME', 'UM Rental');
$fromEmail = env_value('MAIL_FROM', 'no-reply@umrental.local');
$subject = $appName . ' registration verification code';
$body = "Your verification code is {$code}.\r\n"
    . "This code is valid for 10 minutes.\r\n"
    . "If you did not request this, please ignore this email.\r\n";

$isLocalRuntime = is_local_runtime();
$sent = false;

if ($isLocalRuntime) {
    // Local/dev policy: never send real emails, always use debug flow.
    @file_put_contents(
        __DIR__ . '/../.register_mail_debug.log',
        date('c') . " email={$email} code={$code}\n",
        FILE_APPEND | LOCK_EX
    );
} else {
    $mailHost = env_value('MAIL_HOST', '');
    $mailPort = (int) env_value('MAIL_PORT', '587');
    $mailEncryption = strtolower(env_value('MAIL_ENCRYPTION', 'tls'));
    $mailUsername = env_value('MAIL_USERNAME', '');
    $mailPassword = env_value('MAIL_PASSWORD', '');
    $smtpConfigured = $mailHost !== '' && $mailPort > 0 && $mailUsername !== '' && $mailPassword !== '' && $fromEmail !== '';
    if (!$smtpConfigured) {
        respond(500, ['success' => false, 'message' => '郵件設定未完成，請聯絡系統管理員']);
    }
    try {
        $sent = smtp_send_mail(
            $mailHost,
            $mailPort,
            $mailEncryption,
            $mailUsername,
            $mailPassword,
            $fromEmail,
            $appName,
            $email,
            $subject,
            $body
        );
    } catch (Throwable $e) {
        @file_put_contents(
            __DIR__ . '/../.register_mail_error.log',
            date('c') . ' email=' . $email . ' error=' . $e->getMessage() . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
    if (!$sent) {
        respond(500, ['success' => false, 'message' => '驗證碼寄送失敗，請稍後再試']);
    }
}

$store[$email] = [
    'code_hash' => password_hash($code, PASSWORD_DEFAULT),
    'expires_at' => $now + 600,
    'last_sent_at' => $now,
    'attempts' => 0,
];
$_SESSION['register_email_verification'] = $store;

respond(200, [
    'success' => true,
    'message' => $sent ? '驗證碼已寄出，請檢查你的電郵' : '本機開發模式：已產生測試驗證碼',
    'cooldownSec' => $cooldownSec,
    'debugCode' => $isLocalRuntime ? $code : null,
]);
