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

$orderId = isset($data['orderID']) ? (int)$data['orderID'] : 0;
$returnStationId = isset($data['returnStationID']) ? (int)$data['returnStationID'] : 0;
$returnVehicleStatus = isset($data['returnVehicleStatus']) ? trim((string)$data['returnVehicleStatus']) : 'available';

$allowedReturnStatus = ['available', 'maintenance'];
if ($orderId <= 0 || $returnStationId <= 0) {
    respond(422, ['success' => false, 'message' => 'orderID 與 returnStationID 為必填且需大於 0']);
}
if (!in_array($returnVehicleStatus, $allowedReturnStatus, true)) {
    respond(422, ['success' => false, 'message' => 'returnVehicleStatus 只能是 available 或 maintenance']);
}

try {
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare('
        SELECT id, balance, account_status, role
        FROM users
        WHERE id = :user_id
        FOR UPDATE
    ');
    $userStmt->execute([':user_id' => $userId]);
    $user = $userStmt->fetch();

    if (!$user || (string)$user['account_status'] !== 'active') {
        $pdo->rollBack();
        respond(403, ['success' => false, 'code' => 'USER_NOT_ACTIVE', 'message' => '使用者狀態不可還車']);
    }

    if (!roleCanRent((string)($user['role'] ?? ''))) {
        $pdo->rollBack();
        respond(403, ['success' => false, 'code' => 'ROLE_NOT_ALLOWED', 'message' => '此帳戶無法使用租借服務', 'message_key' => 'errRentalNotAllowed']);
    }

    // 鎖訂單，確認歸屬與狀態
    $orderStmt = $pdo->prepare('
        SELECT id, user_id, vehicle_id, start_time, status
        FROM rental_orders
        WHERE id = :order_id
        FOR UPDATE
    ');
    $orderStmt->execute([':order_id' => $orderId]);
    $order = $orderStmt->fetch();

    if (!$order) {
        $pdo->rollBack();
        respond(404, ['success' => false, 'code' => 'ORDER_NOT_FOUND', 'message' => '找不到租賃訂單']);
    }
    if ((int)$order['user_id'] !== $userId) {
        $pdo->rollBack();
        respond(403, ['success' => false, 'code' => 'ORDER_FORBIDDEN', 'message' => '不可操作他人訂單']);
    }
    if ((string)$order['status'] !== 'active') {
        $pdo->rollBack();
        respond(409, ['success' => false, 'code' => 'ORDER_NOT_ACTIVE', 'message' => '訂單不是進行中狀態']);
    }

    // 鎖車輛，驗證狀態機 rented -> available/maintenance
    $vehicleStmt = $pdo->prepare('
        SELECT v.id, v.status, vt.price_per_30_min, b.price_multiplier
        FROM vehicles v
        INNER JOIN vehicle_types vt ON v.type_id = vt.id
        INNER JOIN brands b ON v.brand_id = b.id
        WHERE v.id = :vehicle_id
        FOR UPDATE
    ');
    $vehicleStmt->execute([':vehicle_id' => (int)$order['vehicle_id']]);
    $vehicle = $vehicleStmt->fetch();

    if (!$vehicle) {
        $pdo->rollBack();
        respond(404, ['success' => false, 'code' => 'VEHICLE_NOT_FOUND', 'message' => '找不到車輛']);
    }

    if (!isValidVehicleStatusTransition((string)$vehicle['status'], $returnVehicleStatus)) {
        $pdo->rollBack();
        respond(409, ['success' => false, 'code' => 'INVALID_VEHICLE_STATE_TRANSITION', 'message' => '車輛狀態轉移不合法']);
    }

    // 鎖站點（站點滿位檢查的第一把鎖）
    $stationStmt = $pdo->prepare('
        SELECT id, name, capacity, status
        FROM rental_stations
        WHERE id = :station_id
        FOR UPDATE
    ');
    $stationStmt->execute([':station_id' => $returnStationId]);
    $station = $stationStmt->fetch();

    if (!$station || (string)$station['status'] !== 'active') {
        $pdo->rollBack();
        respond(404, ['success' => false, 'code' => 'RETURN_STATION_NOT_AVAILABLE', 'message' => '歸還站點不存在或不可用']);
    }

    /**
     * 容量檢查（重點）
     * 1) 先用 FOR UPDATE 鎖住該站點下現有車輛列，降低並發超賣/超位機率
     * 2) 再計算目前占用數（available + maintenance 視為占用車位）
     */
    $lockRowsStmt = $pdo->prepare('
        SELECT id
        FROM vehicles
        WHERE station_id = :station_id
          AND status IN ("available", "maintenance")
        FOR UPDATE
    ');
    $lockRowsStmt->execute([':station_id' => $returnStationId]);
    $lockedRows = $lockRowsStmt->fetchAll();

    $occupiedSlots = count($lockedRows);
    $capacity = (int)$station['capacity'];

    if ($occupiedSlots >= $capacity) {
        // 推薦附近可用站點（先簡化為同表中有空位者）
        $alternativeStmt = $pdo->query('
            SELECT
                rs.id,
                rs.name,
                rs.capacity,
                SUM(CASE WHEN v.status IN ("available", "maintenance") THEN 1 ELSE 0 END) AS occupiedSlots
            FROM rental_stations rs
            LEFT JOIN vehicles v ON v.station_id = rs.id
            WHERE rs.status = "active"
            GROUP BY rs.id, rs.name, rs.capacity
            HAVING occupiedSlots < rs.capacity
            ORDER BY (rs.capacity - occupiedSlots) DESC, rs.id ASC
            LIMIT 5
        ');
        $alternatives = $alternativeStmt->fetchAll();

        $pdo->rollBack();
        respond(409, [
            'success' => false,
            'code' => 'STATION_FULL',
            'message' => '歸還站點已滿，請改選其他站點',
            'data' => [
                'returnStationID' => $returnStationId,
                'alternatives' => $alternatives,
            ],
        ]);
    }

    // 計算時長與費用（30 分鐘一計費單位，至少 1 單位）
    $startTimestamp = strtotime((string)$order['start_time']);
    $endTimestamp = time();
    $durationMinutes = (int)max(1, ceil(($endTimestamp - $startTimestamp) / 60));
    $billingUnits = (int)max(1, ceil($durationMinutes / 30));
    $fee = round(((float)$vehicle['price_per_30_min'] * (float)$vehicle['price_multiplier']) * $billingUnits, 2);

    if ((float)$user['balance'] < $fee) {
        // 餘額不足時可改為 payment_pending；此版本先拒絕完成還車
        $pdo->rollBack();
        respond(402, ['success' => false, 'code' => 'INSUFFICIENT_BALANCE', 'message' => '餘額不足，無法完成結束租賃']);
    }

    // 扣款
    $deductStmt = $pdo->prepare('
        UPDATE users
        SET balance = balance - :fee
        WHERE id = :user_id
    ');
    $deductStmt->execute([
        ':fee' => $fee,
        ':user_id' => $userId,
    ]);

    // 更新訂單
    $updateOrderStmt = $pdo->prepare('
        UPDATE rental_orders
        SET end_station_id = :end_station_id,
            end_time = NOW(),
            duration_minutes = :duration_minutes,
            fee = :fee,
            status = "completed"
        WHERE id = :order_id
    ');
    $updateOrderStmt->execute([
        ':end_station_id' => $returnStationId,
        ':duration_minutes' => $durationMinutes,
        ':fee' => $fee,
        ':order_id' => $orderId,
    ]);

    // 更新車輛狀態與停靠站
    $updateVehicleStmt = $pdo->prepare('
        UPDATE vehicles
        SET status = :status,
            station_id = :station_id
        WHERE id = :vehicle_id
    ');
    $updateVehicleStmt->execute([
        ':status' => $returnVehicleStatus,
        ':station_id' => $returnStationId,
        ':vehicle_id' => (int)$order['vehicle_id'],
    ]);

    writeAuditLog(
        $pdo,
        $userId,
        'user',
        'END_RENTAL',
        'rental_order',
        $orderId,
        [
            'vehicleID' => (int)$order['vehicle_id'],
            'returnStationID' => $returnStationId,
            'returnVehicleStatus' => $returnVehicleStatus,
            'durationMinutes' => $durationMinutes,
            'fee' => $fee,
        ]
    );

    // 查詢最新餘額作為回傳
    $newBalanceStmt = $pdo->prepare('SELECT balance FROM users WHERE id = :user_id');
    $newBalanceStmt->execute([':user_id' => $userId]);
    $newBalance = (float)($newBalanceStmt->fetch()['balance'] ?? 0);

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => '結束租賃成功',
        'data' => [
            'orderID' => $orderId,
            'vehicleID' => (int)$order['vehicle_id'],
            'endStationID' => $returnStationId,
            'durationMinutes' => $durationMinutes,
            'fee' => $fee,
            'walletBalanceAfter' => $newBalance,
            'orderStatus' => 'completed',
            'vehicleStatus' => $returnVehicleStatus,
        ],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond(500, ['success' => false, 'code' => 'END_RENTAL_FAILED', 'message' => '結束租賃失敗，請稍後再試']);
}
