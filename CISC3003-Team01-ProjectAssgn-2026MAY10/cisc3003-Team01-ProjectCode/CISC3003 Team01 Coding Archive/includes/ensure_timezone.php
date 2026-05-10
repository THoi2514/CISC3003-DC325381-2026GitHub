<?php
declare(strict_types=1);

/**
 * Always loadable: uses timezone_bootstrap.php when present, otherwise defines the same
 * functions inline. Prevents HTTP 500 when FTP only uploaded part of /includes.
 */
if (!function_exists('app_timezone_init')) {
    if (is_readable(__DIR__ . '/timezone_bootstrap.php')) {
        require_once __DIR__ . '/timezone_bootstrap.php';
    } else {
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
}
