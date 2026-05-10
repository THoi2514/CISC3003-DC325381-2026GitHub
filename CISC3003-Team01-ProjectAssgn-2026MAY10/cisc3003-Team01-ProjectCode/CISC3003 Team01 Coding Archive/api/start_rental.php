<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => '只允許 POST 請求']);
}

$userId = requireLoginUserId();
$data = getJsonInput();

$vehicleId = isset($data['vehicleID']) ? (int)$data['vehicleID'] : 0;
$startStationId = isset($data['startStationID']) ? (int)$data['startStationID'] : 0;

if ($vehicleId <= 0 || $startStationId <= 0) {
    respond(422, ['success' => false, 'message' => 'vehicleID 與 startStationID 為必填且需大於 0']);
}

try {
    $pdo->beginTransaction();

    // 鎖使用者資料，避免同時多筆借車
    $userStmt = $pdo->prepare('
        SELECT id, balance, account_status, role
        FROM users
        WHERE id = :user_id
        FOR UPDATE
    ');
    $userStmt->execute([':user_id' => $userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        $pdo->rollBack();
        respond(404, ['success' => false, 'code' => 'USER_NOT_FOUND', 'message' => '找不到使用者']);
    }

    if ((string)$user['account_status'] !== 'active') {
        $pdo->rollBack();
        respond(403, ['success' => false, 'code' => 'ACCOUNT_NOT_ACTIVE', 'message' => '帳號狀態不可借車']);
    }

    if (!roleCanRent((string)($user['role'] ?? ''))) {
        $pdo->rollBack();
        respond(403, ['success' => false, 'code' => 'ROLE_NOT_ALLOWED', 'message' => '此帳戶無法使用租借服務', 'message_key' => 'errRentalNotAllowed']);
    }

    // 規則：同一使用者同時只能有一筆 active 訂單
    $activeOrderStmt = $pdo->prepare('
        SELECT id
        FROM rental_orders
        WHERE user_id = :user_id AND status = "active"
        LIMIT 1
        FOR UPDATE
    ');
    $activeOrderStmt->execute([':user_id' => $userId]);
    if ($activeOrderStmt->fetch()) {
        $pdo->rollBack();
        respond(409, ['success' => false, 'code' => 'ACTIVE_ORDER_EXISTS', 'message' => '已有進行中的租賃訂單']);
    }

    $pendingOrderStmt = $pdo->prepare('
        SELECT id
        FROM rental_orders
        WHERE user_id = :user_id AND status = "payment_pending"
        LIMIT 1
        FOR UPDATE
    ');
    $pendingOrderStmt->execute([':user_id' => $userId]);
    if ($pendingOrderStmt->fetch()) {
        $pdo->rollBack();
        respond(409, ['success' => false, 'code' => 'PENDING_PAYMENT_EXISTS', 'message' => '你有待付款訂單，完成付款後才能再租車']);
    }

    // 鎖站點並檢查可用狀態
    $stationStmt = $pdo->prepare('
        SELECT id, status
        FROM rental_stations
        WHERE id = :station_id
        FOR UPDATE
    ');
    $stationStmt->execute([':station_id' => $startStationId]);
    $station = $stationStmt->fetch();

    if (!$station || (string)$station['status'] !== 'active') {
        $pdo->rollBack();
        respond(404, ['success' => false, 'code' => 'STATION_NOT_AVAILABLE', 'message' => '起租站點不存在或不可用']);
    }

    // 鎖車輛，確保只有一個交易可以改變其狀態
    $vehicleStmt = $pdo->prepare('
        SELECT v.id, v.status, v.station_id, vt.name AS vehicle_type, vt.price_per_30_min, b.price_multiplier
        FROM vehicles v
        INNER JOIN vehicle_types vt ON v.type_id = vt.id
        INNER JOIN brands b ON v.brand_id = b.id
        WHERE v.id = :vehicle_id
        FOR UPDATE
    ');
    $vehicleStmt->execute([':vehicle_id' => $vehicleId]);
    $vehicle = $vehicleStmt->fetch();

    if (!$vehicle) {
        $pdo->rollBack();
        respond(404, ['success' => false, 'code' => 'VEHICLE_NOT_FOUND', 'message' => '找不到車輛']);
    }

    if ((int)$vehicle['station_id'] !== $startStationId) {
        $pdo->rollBack();
        respond(409, ['success' => false, 'code' => 'VEHICLE_STATION_MISMATCH', 'message' => '車輛不在指定起租站點']);
    }

    if (!isValidVehicleStatusTransition((string)$vehicle['status'], 'rented')) {
        $pdo->rollBack();
        respond(409, ['success' => false, 'code' => 'INVALID_VEHICLE_STATE', 'message' => '車輛狀態不可轉為 rented']);
    }

    // 可依需求改成「最低預授權金額」，目前使用 >= 單位價格檢查
    $minimumRequired = (float)$vehicle['price_per_30_min'] * (float)$vehicle['price_multiplier'];
    if ((float)$user['balance'] < $minimumRequired) {
        $pdo->rollBack();
        respond(402, ['success' => false, 'code' => 'INSUFFICIENT_BALANCE', 'message' => '錢包餘額不足，無法開始租賃']);
    }

    $insertOrderStmt = $pdo->prepare('
        INSERT INTO rental_orders (
            user_id, vehicle_id, start_station_id, start_time, fee, status
        ) VALUES (
            :user_id, :vehicle_id, :start_station_id, :start_time, 0, "active"
        )
    ');
    $insertOrderStmt->execute([
        ':user_id' => $userId,
        ':vehicle_id' => $vehicleId,
        ':start_station_id' => $startStationId,
        ':start_time' => date('Y-m-d H:i:s'),
    ]);
    $orderId = (int)$pdo->lastInsertId();

    $updateVehicleStmt = $pdo->prepare('
        UPDATE vehicles
        SET status = "rented", station_id = NULL
        WHERE id = :vehicle_id
    ');
    $updateVehicleStmt->execute([':vehicle_id' => $vehicleId]);

    writeAuditLog(
        $pdo,
        $userId,
        'user',
        'START_RENTAL',
        'rental_order',
        $orderId,
        [
            'vehicleID' => $vehicleId,
            'startStationID' => $startStationId,
        ]
    );

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => '開始租賃成功',
        'data' => [
            'orderID' => $orderId,
            'userID' => $userId,
            'vehicleID' => $vehicleId,
            'startStationID' => $startStationId,
            'orderStatus' => 'active',
            'vehicleStatus' => 'rented',
        ],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond(500, ['success' => false, 'code' => 'START_RENTAL_FAILED', 'message' => '開始租賃失敗，請稍後再試']);
}
