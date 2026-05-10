<?php
declare(strict_types=1);

/**
 * Structured application logging via PHP error_log (Apache/PHP error log).
 * Does not log secrets; redacts known sensitive keys in context arrays.
 */
function app_log(string $channel, string $level, string $message, array $context = []): void
{
    $redactKeys = [
        'password', 'password_hash', 'DB_PASS', 'client_secret', 'GOOGLE_CLIENT_SECRET',
        'tmp_name', 'HTTP_COOKIE',
    ];
    $safe = $context;
    foreach ($redactKeys as $key) {
        if (array_key_exists($key, $safe)) {
            $safe[$key] = '[redacted]';
        }
    }

    $suffix = $safe !== []
        ? ' ' . json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
        : '';

    error_log(sprintf('[UM_Rental][%s][%s] %s%s', $channel, strtoupper($level), $message, $suffix));
}
