<?php
declare(strict_types=1);
session_start();

$role = (string)($_SESSION['role'] ?? '');
if (!isset($_SESSION['user_id']) || !in_array($role, ['staff', 'admin'], true)) {
    http_response_code(403);
    echo '403 Forbidden: staff permission required';
    exit;
}
