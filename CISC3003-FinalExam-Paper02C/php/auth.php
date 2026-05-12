<?php
declare(strict_types=1);

require_once __DIR__ . '/connect.php';

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php', true, 303);
        exit;
    }
}

/**
 * @return array<string, mixed>|null
 */
function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, full_name, email, password_hash, email_verified_at, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return is_array($row) ? $row : null;
}
