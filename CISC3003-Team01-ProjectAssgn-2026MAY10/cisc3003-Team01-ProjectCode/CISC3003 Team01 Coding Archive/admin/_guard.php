<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo '403 Forbidden: 管理員權限不足';
    exit;
}
