<?php
declare(strict_types=1);

/**
 * 共用工具：
 * - JSON 回應
 * - Session 登入檢查
 * - 審計日誌寫入
 */

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array
{
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!is_array($data)) {
        respond(400, ['success' => false, 'message' => '請提供正確的 JSON 格式']);
    }

    return $data;
}

function requireLoginUserId(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        respond(401, ['success' => false, 'message' => '尚未登入或登入已失效']);
    }

    return (int)$_SESSION['user_id'];
}

function writeAuditLog(PDO $pdo, ?int $actorUserId, string $actorRole, string $actionType, string $targetType, ?int $targetId, array $details = []): void
{
    $stmt = $pdo->prepare('
        INSERT INTO audit_logs (
            actor_user_id, actor_role, action_type, target_type, target_id, details, ip_address, user_agent
        ) VALUES (
            :actor_user_id, :actor_role, :action_type, :target_type, :target_id, :details, :ip_address, :user_agent
        )
    ');

    $stmt->bindValue(':actor_user_id', $actorUserId, $actorUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':actor_role', $actorRole, PDO::PARAM_STR);
    $stmt->bindValue(':action_type', $actionType, PDO::PARAM_STR);
    $stmt->bindValue(':target_type', $targetType, PDO::PARAM_STR);
    $stmt->bindValue(':target_id', $targetId, $targetId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':details', json_encode($details, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
    $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null, PDO::PARAM_STR);
    $stmt->execute();
}

/**
 * 車輛狀態機轉移規則
 */
function isValidVehicleStatusTransition(string $from, string $to): bool
{
    $allowed = [
        'available' => ['rented', 'maintenance', 'retired'],
        'rented' => ['available', 'maintenance'],
        'maintenance' => ['available', 'retired'],
        'retired' => [],
    ];

    return isset($allowed[$from]) && in_array($to, $allowed[$from], true);
}
