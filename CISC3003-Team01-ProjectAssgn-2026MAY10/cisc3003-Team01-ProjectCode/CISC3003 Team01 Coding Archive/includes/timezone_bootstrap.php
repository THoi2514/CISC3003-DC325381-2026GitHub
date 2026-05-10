<?php
declare(strict_types=1);

/**
 * Canonical timezone helpers for UM Rental (single copy — no dependency on app_timezone.php).
 * Shared hosts may still have legacy code doing require 'app_timezone.php'; use app_timezone.php
 * as a one-line shim to this file.
 */
if (!function_exists('app_timezone_init')) {
    function app_timezone_init(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $tz = getenv('APP_TIMEZONE');
        $tz = is_string($tz) && $tz !== '' ? $tz : 'Asia/Macau';
        date_default_timezone_set($tz);
    }

    function app_timezone_configure_pdo(PDO $pdo): void
    {
        try {
            $pdo->exec("SET time_zone = '+08:00'");
        } catch (Throwable $e) {
        }
    }
}
