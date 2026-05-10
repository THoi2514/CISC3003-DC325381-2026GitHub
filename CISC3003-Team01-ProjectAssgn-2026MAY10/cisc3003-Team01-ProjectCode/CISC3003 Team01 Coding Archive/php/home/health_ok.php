<?php
$projectRoot = dirname(__DIR__, 2);
/**
 * Minimal probe: no DB, no includes. If this returns plain "ok", PHP runs and .htaccess is OK.
 * Open: https://your-domain/health_ok.php
 */
header('Content-Type: text/plain; charset=utf-8');
echo 'ok';
