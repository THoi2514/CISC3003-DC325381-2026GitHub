<?php
/**
 * Minimal auth + HTML — if this works but dashboard.php is 500, the failure is not session/auth bootstrap.
 * DELETE after debugging.
 */
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);
header('Content-Type: text/html; charset=utf-8');
require_once $projectRoot . '/includes/auth.php';
requireUserSession();
startAuthSession();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Dashboard tiny probe</title></head>
<body><p>If you see this, auth/session and a short response work.</p></body>
</html>
