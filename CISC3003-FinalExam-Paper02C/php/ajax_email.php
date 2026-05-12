<?php
/**
 * C.06 Ajax email availability check (JSON).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/connect.php';

$email = isset($_GET['email']) ? trim((string) $_GET['email']) : '';
$emailOk = filter_var($email, FILTER_VALIDATE_EMAIL);

if ($emailOk === false) {
    echo json_encode(['ok' => false, 'available' => false, 'message' => 'Invalid email format.']);
    exit;
}
$email = $emailOk;

$stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();

echo json_encode([
    'ok' => true,
    'available' => !$exists,
    'message' => $exists ? 'That email is already registered.' : 'Email looks available.',
]);
