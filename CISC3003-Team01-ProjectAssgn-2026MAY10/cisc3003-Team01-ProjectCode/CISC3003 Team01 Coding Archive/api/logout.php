<?php
declare(strict_types=1);

session_start();
session_unset();
session_destroy();

$accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
$isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if ($isAjax || str_contains($accept, 'application/json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'Logged out',
        'redirect' => '../login.php',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Location: ../login.php');
exit;
