<?php
declare(strict_types=1);
$layoutBase = $layoutBase ?? $publicHeaderBase ?? '';
$publicHeaderBase = $layoutBase;
$activePage = $activePage ?? '';
if ($activePage === '' && isset($authActive)) {
    if ($authActive === 'login') {
        $activePage = 'login';
    }
}
$navUserMode = 'none';
require __DIR__ . '/public_header.php';
