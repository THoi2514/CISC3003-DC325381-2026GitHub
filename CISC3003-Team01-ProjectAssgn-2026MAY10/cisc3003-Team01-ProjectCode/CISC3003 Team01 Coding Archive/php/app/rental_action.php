<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);

require_once $projectRoot . '/config.php';
require_once $projectRoot . '/includes/app_log.php';
require_once $projectRoot . '/includes/vehicle_status.php';
require_once $projectRoot . '/includes/rental_limits.php';

/**
 * 統一租賃動作 API（AJAX / Fetch）
 * 使用方式：
 * - GET  /rental_action.php?action=get_catalog
 * - POST /rental_action.php?action=start_rental
 */

function writeAudit(PDO $pdo, ?int $actorId, string $actorRole, string $actionType, string $targetType, ?int $targetId, array $details = []): void
{
    $stmt = $pdo->prepare('
        INSERT INTO audit_logs (actor_user_id, actor_role, action_type, target_type, target_id, details, ip_address, user_agent)
        VALUES (:actor_user_id, :actor_role, :action_type, :target_type, :target_id, :details, :ip_address, :user_agent)
    ');
    $stmt->bindValue(':actor_user_id', $actorId, $actorId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':actor_role', $actorRole);
    $stmt->bindValue(':action_type', $actionType);
    $stmt->bindValue(':target_type', $targetType);
    $stmt->bindValue(':target_id', $targetId, $targetId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':details', json_encode($details, JSON_UNESCAPED_UNICODE));
    $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
    $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
    $stmt->execute();
}

function validTransition(string $from, string $to): bool
{
    $rules = [
        'available' => ['rented', 'maintenance', 'retired'],
        'rented' => ['available', 'maintenance'],
        'maintenance' => ['available', 'retired'],
        'retired' => [],
    ];
    return isset($rules[$from]) && in_array($to, $rules[$from], true);
}

function forumUploadDir(): string
{
    $docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)dirname($scriptName)), DIRECTORY_SEPARATOR);

    if ($docRoot !== '') {
        $root = rtrim($docRoot, "\\/");
        if ($basePath !== '' && $basePath !== '.') {
            $root .= DIRECTORY_SEPARATOR . $basePath;
        }
        return $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'forum';
    }

    return __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'forum';
}

function forumValidateAndSaveImage(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        jsonResponse(422, ['success' => false, 'message' => 'Image upload failed']);
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        jsonResponse(422, ['success' => false, 'message' => 'Image must be within 5MB']);
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        jsonResponse(422, ['success' => false, 'message' => 'Invalid upload payload']);
    }

    $mime = (string)(mime_content_type($tmpName) ?: '');
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($allowed[$mime])) {
        jsonResponse(422, ['success' => false, 'message' => 'Only jpg/png/webp/gif are allowed']);
    }

    $dir = forumUploadDir();
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        jsonResponse(500, [
            'success' => false,
            'message' => 'Failed to prepare upload directory under htdocs',
            'debugPath' => $dir,
        ]);
    }

    $ext = $allowed[$mime];
    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $absolutePath = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmpName, $absolutePath)) {
        jsonResponse(500, ['success' => false, 'message' => 'Failed to save image']);
    }
    return 'uploads/forum/' . $filename;
}

function settlePendingPaymentsForUser(PDO $pdo, int $userId, string $actorRole = 'user'): array
{
    $actorRole = in_array($actorRole, ['admin', 'user', 'system'], true) ? $actorRole : 'user';
    $balanceStmt = $pdo->prepare('SELECT balance FROM users WHERE id = :uid FOR UPDATE');
    $balanceStmt->execute([':uid' => $userId]);
    $user = $balanceStmt->fetch();
    if (!$user) {
        return ['settled' => 0, 'remainingPending' => 0, 'balance' => 0.0];
    }

    $balance = round((float)$user['balance'], 2);
    $pendingStmt = $pdo->prepare('
        SELECT id, fee
        FROM rental_orders
        WHERE user_id = :uid
          AND status = "payment_pending"
          AND fee > 0
        ORDER BY id ASC
        FOR UPDATE
    ');
    $pendingStmt->execute([':uid' => $userId]);
    $pendingOrders = $pendingStmt->fetchAll();

    $settled = 0;
    foreach ($pendingOrders as $order) {
        $fee = round((float)($order['fee'] ?? 0), 2);
        if ($fee <= 0 || $balance < $fee) {
            continue;
        }
        $balance = round($balance - $fee, 2);

        $updUser = $pdo->prepare('UPDATE users SET balance = :balance WHERE id = :uid');
        $updUser->execute([':balance' => $balance, ':uid' => $userId]);

        $updOrder = $pdo->prepare('UPDATE rental_orders SET status = "completed", updated_at = NOW() WHERE id = :oid');
        $updOrder->execute([':oid' => (int)$order['id']]);

        $walletStmt = $pdo->prepare('
            INSERT INTO wallet_transactions (user_id, type, amount, balance_after, reference_type, reference_id, note)
            VALUES (:user_id, "rental_charge", :amount, :balance_after, "rental_order", :reference_id, :note)
        ');
        $walletStmt->execute([
            ':user_id' => $userId,
            ':amount' => -1 * $fee,
            ':balance_after' => $balance,
            ':reference_id' => (int)$order['id'],
            ':note' => 'Auto settlement for pending rental payment',
        ]);

        writeAudit($pdo, $userId, $actorRole, 'ACCOUNT_AUTO_SETTLE_PENDING_PAYMENT', 'rental_order', (int)$order['id'], [
            'fee' => $fee,
            'balanceAfter' => $balance,
        ]);
        $settled++;
    }

    $remainingStmt = $pdo->prepare('SELECT COUNT(*) FROM rental_orders WHERE user_id = :uid AND status = "payment_pending"');
    $remainingStmt->execute([':uid' => $userId]);
    $remaining = (int)$remainingStmt->fetchColumn();

    return ['settled' => $settled, 'remainingPending' => $remaining, 'balance' => $balance];
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = getJsonBody();
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    verifyCsrfOrFail();
    $uid = (string)($_SESSION['user_id'] ?? 'guest');
    rateLimit('action:' . $action . ':user:' . $uid . ':ip:' . clientIp(), 120, 300, 120);
}

try {
    if ($action === 'get_catalog' && $method === 'GET') {
        requireRentalRole();

        $type = trim((string)($_GET['vehicleType'] ?? ''));
        $stationId = (int)($_GET['stationID'] ?? 0);

        $stationSql = '
            SELECT
                rs.id, rs.name, rs.latitude, rs.longitude, rs.capacity,
                SUM(CASE WHEN v.status = "available" THEN 1 ELSE 0 END) AS availableVehicles
            FROM rental_stations rs
            LEFT JOIN vehicles v ON v.station_id = rs.id
            WHERE rs.status = "active"
            GROUP BY rs.id, rs.name, rs.latitude, rs.longitude, rs.capacity
            ORDER BY rs.name
        ';
        $stations = $pdo->query($stationSql)->fetchAll();

        $vehicleSql = '
            SELECT
                v.id, v.serial_no, v.status, v.battery_level,
                v.station_id, rs.name AS station_name,
                vt.name AS vehicle_type, vt.price_per_30_min,
                b.name AS brand
            FROM vehicles v
            INNER JOIN vehicle_types vt ON v.type_id = vt.id
            INNER JOIN brands b ON v.brand_id = b.id
            LEFT JOIN rental_stations rs ON v.station_id = rs.id
            WHERE v.status = "available"
        ';
        $params = [];
        if ($type !== '') {
            $vehicleSql .= ' AND vt.name = :vehicle_type';
            $params[':vehicle_type'] = $type;
        }
        if ($stationId > 0) {
            $vehicleSql .= ' AND v.station_id = :station_id';
            $params[':station_id'] = $stationId;
        }
        $vehicleSql .= ' ORDER BY v.id DESC';
        $stmt = $pdo->prepare($vehicleSql);
        $stmt->execute($params);
        $vehicles = $stmt->fetchAll();

        jsonResponse(200, ['success' => true, 'data' => ['stations' => $stations, 'vehicles' => $vehicles]]);
    }

    if ($action === 'start_rental' && $method === 'POST') {
        $userId = requireRentalRole();
        $vehicleId = (int)($body['vehicleID'] ?? 0);
        if ($vehicleId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'vehicleID 必填']);
        }

        $pdo->beginTransaction();
        $uStmt = $pdo->prepare('SELECT id, balance, account_status FROM users WHERE id = :id FOR UPDATE');
        $uStmt->execute([':id' => $userId]);
        $user = $uStmt->fetch();
        if (!$user || $user['account_status'] !== 'active') {
            $pdo->rollBack();
            jsonResponse(403, ['success' => false, 'message' => '使用者狀態不可借車']);
        }

        $activeStmt = $pdo->prepare('SELECT id FROM rental_orders WHERE user_id = :uid AND status = "active" LIMIT 1 FOR UPDATE');
        $activeStmt->execute([':uid' => $userId]);
        if ($activeStmt->fetch()) {
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => '已有進行中的租賃', 'message_key' => 'errUserHasActiveOrder']);
        }

        $pendingStmt = $pdo->prepare('SELECT id FROM rental_orders WHERE user_id = :uid AND status = "payment_pending" LIMIT 1 FOR UPDATE');
        $pendingStmt->execute([':uid' => $userId]);
        if ($pendingStmt->fetch()) {
            $pdo->rollBack();
            jsonResponse(409, [
                'success' => false,
                'message' => '你有待付款訂單，完成付款後才能再租車',
                'message_key' => 'errUserHasPendingPayment',
            ]);
        }

        $vStmt = $pdo->prepare('
            SELECT v.id, v.status, v.station_id, vt.price_per_30_min, b.price_multiplier
            FROM vehicles v
            INNER JOIN vehicle_types vt ON vt.id = v.type_id
            INNER JOIN brands b ON b.id = v.brand_id
            WHERE v.id = :vid FOR UPDATE
        ');
        $vStmt->execute([':vid' => $vehicleId]);
        $vehicle = $vStmt->fetch();
        if (!$vehicle) {
            $pdo->rollBack();
            jsonResponse(404, ['success' => false, 'message' => '車輛不存在']);
        }
        if (!validTransition((string)$vehicle['status'], 'rented')) {
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => '車輛目前不可租借', 'message_key' => 'errVehicleNotAvailable']);
        }

        $required = (float)$vehicle['price_per_30_min'] * (float)$vehicle['price_multiplier'];
        if ((float)$user['balance'] < $required) {
            $pdo->rollBack();
            jsonResponse(402, ['success' => false, 'message' => '餘額不足']);
        }

        $orderStmt = $pdo->prepare('
            INSERT INTO rental_orders (user_id, vehicle_id, start_station_id, start_time, status, fee)
            VALUES (:uid, :vid, :sid, :start_time, "active", 0)
        ');
        $orderStmt->execute([
            ':uid' => $userId,
            ':vid' => $vehicleId,
            ':sid' => (int)$vehicle['station_id'],
            ':start_time' => date('Y-m-d H:i:s'),
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $upd = $pdo->prepare('UPDATE vehicles SET status = "rented", station_id = NULL WHERE id = :vid');
        $upd->execute([':vid' => $vehicleId]);

        writeAudit($pdo, $userId, 'user', 'START_RENTAL', 'rental_order', $orderId, ['vehicleID' => $vehicleId]);
        $pdo->commit();

        jsonResponse(200, ['success' => true, 'message' => '租借成功', 'data' => ['orderID' => $orderId, 'startTime' => date('c')]]);
    }

    if ($action === 'end_rental' && $method === 'POST') {
        $userId = requireRentalRole();
        $orderId = (int)($body['orderID'] ?? 0);
        $returnStationId = (int)($body['returnStationID'] ?? 0);
        if ($orderId <= 0 || $returnStationId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'orderID 與 returnStationID 必填']);
        }

        $pdo->beginTransaction();
        $uStmt = $pdo->prepare('SELECT id, balance FROM users WHERE id = :id FOR UPDATE');
        $uStmt->execute([':id' => $userId]);
        $user = $uStmt->fetch();
        if (!$user) {
            $pdo->rollBack();
            jsonResponse(404, ['success' => false, 'message' => '使用者不存在']);
        }

        $oStmt = $pdo->prepare('
            SELECT id, user_id, vehicle_id, start_time, status
            FROM rental_orders WHERE id = :oid FOR UPDATE
        ');
        $oStmt->execute([':oid' => $orderId]);
        $order = $oStmt->fetch();
        if (!$order || (int)$order['user_id'] !== $userId || $order['status'] !== 'active') {
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => '找不到進行中的訂單', 'message_key' => 'errNoActiveOrder']);
        }

        $sStmt = $pdo->prepare('SELECT id, name, capacity, status FROM rental_stations WHERE id = :sid FOR UPDATE');
        $sStmt->execute([':sid' => $returnStationId]);
        $station = $sStmt->fetch();
        if (!$station || $station['status'] !== 'active') {
            $pdo->rollBack();
            jsonResponse(404, ['success' => false, 'message' => '歸還站點不可用', 'message_key' => 'errReturnStationUnavailable']);
        }

        $lockRows = $pdo->prepare('
            SELECT id FROM vehicles WHERE station_id = :sid AND status IN ("available","maintenance") FOR UPDATE
        ');
        $lockRows->execute([':sid' => $returnStationId]);
        $occupied = count($lockRows->fetchAll());
        if ($occupied >= (int)$station['capacity']) {
            $alt = $pdo->query('
                SELECT rs.id, rs.name, rs.capacity,
                       SUM(CASE WHEN v.status IN ("available","maintenance") THEN 1 ELSE 0 END) AS occupiedSlots
                FROM rental_stations rs
                LEFT JOIN vehicles v ON v.station_id = rs.id
                WHERE rs.status = "active"
                GROUP BY rs.id, rs.name, rs.capacity
                HAVING occupiedSlots < rs.capacity
                ORDER BY (rs.capacity - occupiedSlots) DESC
                LIMIT 5
            ')->fetchAll();
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => '站點已滿，請選擇其他位置', 'message_key' => 'errStationFull', 'alternatives' => $alt]);
        }

        $vStmt = $pdo->prepare('
            SELECT v.id, v.status, vt.price_per_30_min, b.price_multiplier
            FROM vehicles v
            INNER JOIN vehicle_types vt ON vt.id = v.type_id
            INNER JOIN brands b ON b.id = v.brand_id
            WHERE v.id = :vid FOR UPDATE
        ');
        $vStmt->execute([':vid' => (int)$order['vehicle_id']]);
        $vehicle = $vStmt->fetch();
        if (!$vehicle || !validTransition((string)$vehicle['status'], 'available')) {
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => '車輛狀態異常，無法還車']);
        }

        $duration = max(1, (int)ceil((time() - strtotime((string)$order['start_time'])) / 60));
        $basePrice = (float)$vehicle['price_per_30_min'] * (float)$vehicle['price_multiplier'];
        $baseFee = (float)(ceil($duration / 30) * $basePrice);
        $feeParts = rental_fee_with_overtime($baseFee, $duration);
        $fee = $feeParts['total'];

        $orderStatus = 'completed';
        if ((float)$user['balance'] >= $fee) {
            $deductStmt = $pdo->prepare('UPDATE users SET balance = balance - :fee WHERE id = :uid');
            $deductStmt->execute([':fee' => $fee, ':uid' => $userId]);
            $newBalanceTmp = round((float)$user['balance'] - $fee, 2);
            $walletNote = $feeParts['penalty'] > 0
                ? ('Auto charge on return (includes MOP ' . $feeParts['penalty'] . ' overtime penalty)')
                : 'Auto charge on return';
            $walletStmt = $pdo->prepare('
                INSERT INTO wallet_transactions (user_id, type, amount, balance_after, reference_type, reference_id, note)
                VALUES (:user_id, "rental_charge", :amount, :balance_after, "rental_order", :reference_id, :note)
            ');
            $walletStmt->execute([
                ':user_id' => $userId,
                ':amount' => -1 * $fee,
                ':balance_after' => $newBalanceTmp,
                ':reference_id' => $orderId,
                ':note' => $walletNote,
            ]);
        } else {
            $orderStatus = 'payment_pending';
        }

        $updOrder = $pdo->prepare('
            UPDATE rental_orders
            SET end_station_id = :sid, end_time = NOW(), duration_minutes = :duration, fee = :fee, status = :status
            WHERE id = :oid
        ');
        $updOrder->execute([
            ':sid' => $returnStationId,
            ':duration' => $duration,
            ':fee' => $fee,
            ':status' => $orderStatus,
            ':oid' => $orderId,
        ]);

        $updVehicle = $pdo->prepare('UPDATE vehicles SET status = "available", station_id = :sid WHERE id = :vid');
        $updVehicle->execute([':sid' => $returnStationId, ':vid' => (int)$order['vehicle_id']]);

        writeAudit($pdo, $userId, 'user', 'END_RENTAL', 'rental_order', $orderId, [
            'duration' => $duration,
            'base_fee' => $feeParts['base'],
            'overtime_penalty' => $feeParts['penalty'],
            'fee' => $fee,
            'status' => $orderStatus,
        ]);

        $balStmt = $pdo->prepare('SELECT balance FROM users WHERE id = :uid');
        $balStmt->execute([':uid' => $userId]);
        $newBal = (float)($balStmt->fetch()['balance'] ?? 0);

        $pdo->commit();
        jsonResponse(200, [
            'success' => true,
            'message' => '還車完成',
            'data' => [
                'orderID' => $orderId,
                'durationMinutes' => $duration,
                'baseFeeMop' => $feeParts['base'],
                'overtimePenaltyMop' => $feeParts['penalty'],
                'fee' => round($fee, 2),
                'status' => $orderStatus,
                'walletBalanceAfter' => $newBal,
            ],
        ]);
    }

    if ($action === 'my_orders' && $method === 'GET') {
        $userId = requireRentalRole();
        $pdo->beginTransaction();
        settlePendingPaymentsForUser($pdo, $userId, (string)($_SESSION['role'] ?? 'user') === 'admin' ? 'admin' : 'user');
        $pdo->commit();
        $stmt = $pdo->prepare('
            SELECT ro.id, ro.start_time, ro.end_time, ro.duration_minutes, ro.fee, ro.status,
                   v.serial_no, vt.name AS vehicle_type, b.name AS brand
            FROM rental_orders ro
            INNER JOIN vehicles v ON ro.vehicle_id = v.id
            INNER JOIN vehicle_types vt ON v.type_id = vt.id
            INNER JOIN brands b ON v.brand_id = b.id
            WHERE ro.user_id = :uid
            ORDER BY ro.id DESC
        ');
        $stmt->execute([':uid' => $userId]);
        jsonResponse(200, ['success' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'my_stats' && $method === 'GET') {
        $userId = requireRentalRole();
        $stmt = $pdo->prepare('
            SELECT
              COUNT(*) AS total_orders,
              COALESCE(SUM(duration_minutes), 0) AS total_minutes,
              COALESCE(SUM(fee), 0) AS total_fee
            FROM rental_orders
            WHERE user_id = :uid
              AND status IN ("completed", "payment_pending")
        ');
        $stmt->execute([':uid' => $userId]);
        jsonResponse(200, ['success' => true, 'data' => $stmt->fetch()]);
    }

    if ($action === 'account_profile' && $method === 'GET') {
        $userId = requireLogin();
        $pdo->beginTransaction();
        settlePendingPaymentsForUser($pdo, $userId, (string)($_SESSION['role'] ?? 'user') === 'admin' ? 'admin' : 'user');
        $pdo->commit();
        $stmt = $pdo->prepare('SELECT id, campus_id, full_name, email, phone, role, balance, account_status, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        jsonResponse(200, ['success' => true, 'data' => $stmt->fetch()]);
    }

    if ($action === 'account_update_profile' && $method === 'POST') {
        $userId = requireLogin();
        $fullName = trim((string)($body['fullName'] ?? ''));
        $phone = trim((string)($body['phone'] ?? ''));
        if ($fullName === '') {
            jsonResponse(422, ['success' => false, 'message' => 'fullName is required']);
        }
        $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, phone = :phone WHERE id = :id');
        $stmt->execute([':full_name' => strip_tags($fullName), ':phone' => $phone !== '' ? strip_tags($phone) : null, ':id' => $userId]);
        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'user'), 'ACCOUNT_UPDATE_PROFILE', 'user', $userId);
        jsonResponse(200, ['success' => true, 'message' => 'Profile updated']);
    }

    if ($action === 'account_wallet_history' && $method === 'GET') {
        $userId = requireLogin();
        $stmt = $pdo->prepare('
            SELECT id, type, amount, balance_after, reference_type, reference_id, note, created_at
            FROM wallet_transactions
            WHERE user_id = :uid
            ORDER BY id DESC
            LIMIT 100
        ');
        $stmt->execute([':uid' => $userId]);
        jsonResponse(200, ['success' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'account_topup' && $method === 'POST') {
        $userId = requireLogin();
        $amount = (float)($body['amount'] ?? 0);
        $paymentMethod = trim((string)($body['payment_method'] ?? 'manual'));
        $cardLast4 = preg_replace('/\D+/', '', (string)($body['card_last4'] ?? ''));
        if ($amount <= 0 || $amount > 5000) {
            jsonResponse(422, ['success' => false, 'message' => 'Top-up amount must be between 0 and 5000']);
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            $pdo->rollBack();
            jsonResponse(404, ['success' => false, 'message' => 'User not found']);
        }
        $newBalance = round((float)$user['balance'] + $amount, 2);
        $upd = $pdo->prepare('UPDATE users SET balance = :balance WHERE id = :id');
        $upd->execute([':balance' => $newBalance, ':id' => $userId]);
        $wt = $pdo->prepare('
            INSERT INTO wallet_transactions (user_id, type, amount, balance_after, reference_type, reference_id, note)
            VALUES (:user_id, "topup", :amount, :balance_after, :reference_type, NULL, :note)
        ');
        $referenceType = $paymentMethod === 'credit_card' ? 'card_topup' : 'manual_topup';
        $note = $paymentMethod === 'credit_card'
            ? ('Top-up by credit card ending ' . ($cardLast4 !== '' ? substr($cardLast4, -4) : '****'))
            : 'Top-up from account page';
        $wt->execute([
            ':user_id' => $userId,
            ':amount' => $amount,
            ':balance_after' => $newBalance,
            ':reference_type' => $referenceType,
            ':note' => $note,
        ]);
        $settleResult = settlePendingPaymentsForUser($pdo, $userId, (string)($_SESSION['role'] ?? 'user') === 'admin' ? 'admin' : 'user');
        $newBalance = (float)$settleResult['balance'];
        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'user'), 'ACCOUNT_TOPUP', 'user', $userId, ['amount' => $amount, 'payment_method' => $paymentMethod]);
        $pdo->commit();
        jsonResponse(200, [
            'success' => true,
            'message' => 'Top-up completed',
            'data' => [
                'balance' => $newBalance,
                'autoSettledOrders' => (int)$settleResult['settled'],
                'remainingPendingOrders' => (int)$settleResult['remainingPending'],
            ],
        ]);
    }

    if ($action === 'account_admin_test_topup' && $method === 'POST') {
        requireAdmin();
        $userId = requireLogin();
        $amount = (float)($body['amount'] ?? 0);
        if ($amount <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'Amount must be greater than 0']);
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            $pdo->rollBack();
            jsonResponse(404, ['success' => false, 'message' => 'User not found']);
        }
        $newBalance = round((float)$user['balance'] + $amount, 2);
        $upd = $pdo->prepare('UPDATE users SET balance = :balance WHERE id = :id');
        $upd->execute([':balance' => $newBalance, ':id' => $userId]);
        $wt = $pdo->prepare('
            INSERT INTO wallet_transactions (user_id, type, amount, balance_after, reference_type, reference_id, note)
            VALUES (:user_id, "topup", :amount, :balance_after, "admin_test_topup", NULL, "Admin test balance top-up")
        ');
        $wt->execute([':user_id' => $userId, ':amount' => $amount, ':balance_after' => $newBalance]);
        $settleResult = settlePendingPaymentsForUser($pdo, $userId, 'admin');
        $newBalance = (float)$settleResult['balance'];
        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'admin'), 'ACCOUNT_ADMIN_TEST_TOPUP', 'user', $userId, ['amount' => $amount]);
        $pdo->commit();
        jsonResponse(200, [
            'success' => true,
            'message' => 'Test balance top-up completed',
            'data' => [
                'balance' => $newBalance,
                'autoSettledOrders' => (int)$settleResult['settled'],
                'remainingPendingOrders' => (int)$settleResult['remainingPending'],
            ],
        ]);
    }

    if ($action === 'account_feedback_submit' && $method === 'POST') {
        $userId = requireLogin();
        $title = trim((string)($body['title'] ?? ''));
        $description = trim((string)($body['description'] ?? ''));
        $category = trim((string)($body['category'] ?? 'other'));
        $allowedCategories = ['bug', 'payment', 'vehicle', 'station', 'account', 'other'];

        if ($title === '' || $description === '') {
            jsonResponse(422, ['success' => false, 'message' => 'title and description are required']);
        }
        if (!in_array($category, $allowedCategories, true)) {
            jsonResponse(422, ['success' => false, 'message' => 'invalid feedback category']);
        }
        if (mb_strlen($title) > 180) {
            jsonResponse(422, ['success' => false, 'message' => 'title cannot exceed 180 characters']);
        }

        $insert = $pdo->prepare('
            INSERT INTO feedbacks (user_id, title, description, category, status)
            VALUES (:user_id, :title, :description, :category, "open")
        ');
        $insert->execute([
            ':user_id' => $userId,
            ':title' => strip_tags($title),
            ':description' => strip_tags($description),
            ':category' => $category,
        ]);
        $feedbackId = (int)$pdo->lastInsertId();

        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'user'), 'ACCOUNT_SUBMIT_FEEDBACK', 'feedback', $feedbackId, [
            'category' => $category,
        ]);
        jsonResponse(200, [
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'data' => ['feedbackID' => $feedbackId],
        ]);
    }

    if ($action === 'account_feedback_list' && $method === 'GET') {
        $userId = requireLogin();
        $stmt = $pdo->prepare('
            SELECT id, title, description, category, status, created_at, updated_at
            FROM feedbacks
            WHERE user_id = :uid
            ORDER BY id DESC
            LIMIT 100
        ');
        $stmt->execute([':uid' => $userId]);
        $feedbacks = $stmt->fetchAll();

        $feedbackIds = array_map(static fn($row) => (int)$row['id'], $feedbacks);
        $repliesByFeedback = [];
        if ($feedbackIds !== []) {
            $placeholders = implode(',', array_fill(0, count($feedbackIds), '?'));
            $replyStmt = $pdo->prepare("
                SELECT fr.id, fr.feedback_id, fr.reply_content, fr.created_at, u.full_name AS admin_name
                FROM feedback_replies fr
                INNER JOIN users u ON u.id = fr.admin_user_id
                WHERE fr.feedback_id IN ({$placeholders})
                ORDER BY fr.id ASC
            ");
            $replyStmt->execute($feedbackIds);
            foreach ($replyStmt->fetchAll() as $reply) {
                $fid = (int)$reply['feedback_id'];
                if (!isset($repliesByFeedback[$fid])) {
                    $repliesByFeedback[$fid] = [];
                }
                $repliesByFeedback[$fid][] = $reply;
            }
        }

        foreach ($feedbacks as &$row) {
            $fid = (int)$row['id'];
            $row['replies'] = $repliesByFeedback[$fid] ?? [];
        }
        unset($row);

        jsonResponse(200, ['success' => true, 'data' => $feedbacks]);
    }

    if ($action === 'account_feedback_update' && $method === 'POST') {
        $userId = requireLogin();
        $feedbackId = (int)($body['feedbackID'] ?? 0);
        $title = trim((string)($body['title'] ?? ''));
        $description = trim((string)($body['description'] ?? ''));
        $category = trim((string)($body['category'] ?? 'other'));
        $allowedCategories = ['bug', 'payment', 'vehicle', 'station', 'account', 'other'];

        if ($feedbackId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'feedbackID is required']);
        }
        if ($title === '' || $description === '') {
            jsonResponse(422, ['success' => false, 'message' => 'title and description are required']);
        }
        if (!in_array($category, $allowedCategories, true)) {
            jsonResponse(422, ['success' => false, 'message' => 'invalid feedback category']);
        }
        if (mb_strlen($title) > 180) {
            jsonResponse(422, ['success' => false, 'message' => 'title cannot exceed 180 characters']);
        }

        $lockStmt = $pdo->prepare('
            SELECT id, status
            FROM feedbacks
            WHERE id = :id AND user_id = :uid
            LIMIT 1
            FOR UPDATE
        ');
        $lockStmt->execute([':id' => $feedbackId, ':uid' => $userId]);
        $feedback = $lockStmt->fetch();
        if (!$feedback) {
            jsonResponse(404, ['success' => false, 'message' => 'Feedback not found']);
        }
        if (in_array((string)$feedback['status'], ['resolved', 'closed'], true)) {
            jsonResponse(409, ['success' => false, 'message' => 'Resolved/closed feedback cannot be edited']);
        }

        $upd = $pdo->prepare('
            UPDATE feedbacks
            SET title = :title, description = :description, category = :category, updated_at = NOW()
            WHERE id = :id AND user_id = :uid
        ');
        $upd->execute([
            ':title' => strip_tags($title),
            ':description' => strip_tags($description),
            ':category' => $category,
            ':id' => $feedbackId,
            ':uid' => $userId,
        ]);

        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'user'), 'ACCOUNT_EDIT_FEEDBACK', 'feedback', $feedbackId, [
            'category' => $category,
        ]);
        jsonResponse(200, ['success' => true, 'message' => 'Feedback updated']);
    }

    if ($action === 'account_feedback_delete' && $method === 'POST') {
        $userId = requireLogin();
        $feedbackId = (int)($body['feedbackID'] ?? 0);
        if ($feedbackId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'feedbackID is required']);
        }

        $pdo->beginTransaction();
        $lockStmt = $pdo->prepare('
            SELECT id, status
            FROM feedbacks
            WHERE id = :id AND user_id = :uid
            LIMIT 1
            FOR UPDATE
        ');
        $lockStmt->execute([':id' => $feedbackId, ':uid' => $userId]);
        $feedback = $lockStmt->fetch();
        if (!$feedback) {
            $pdo->rollBack();
            jsonResponse(404, ['success' => false, 'message' => 'Feedback not found']);
        }
        if (in_array((string)$feedback['status'], ['resolved', 'closed'], true)) {
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => 'Resolved/closed feedback cannot be deleted']);
        }

        $replyCountStmt = $pdo->prepare('SELECT COUNT(*) AS c FROM feedback_replies WHERE feedback_id = :id');
        $replyCountStmt->execute([':id' => $feedbackId]);
        $replyCount = (int)($replyCountStmt->fetch()['c'] ?? 0);
        if ($replyCount > 0) {
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => 'Feedback already replied by admin cannot be deleted']);
        }

        $delStmt = $pdo->prepare('DELETE FROM feedbacks WHERE id = :id AND user_id = :uid');
        $delStmt->execute([':id' => $feedbackId, ':uid' => $userId]);
        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'user'), 'ACCOUNT_DELETE_FEEDBACK', 'feedback', $feedbackId);
        $pdo->commit();
        jsonResponse(200, ['success' => true, 'message' => 'Feedback deleted']);
    }

    if ($action === 'account_forum_submit' && $method === 'POST') {
        $userId = requireLogin();
        $payload = $body;
        if ($payload === [] && !empty($_POST)) {
            $payload = $_POST;
        }
        $title = trim((string)($payload['title'] ?? ''));
        $content = trim((string)($payload['content'] ?? ''));
        $category = trim((string)($payload['category'] ?? 'other'));
        $allowedCategories = ['help', 'experience', 'suggestion', 'lost_found', 'other'];
        $imagePath = '';

        if ($title === '' || $content === '') {
            jsonResponse(422, ['success' => false, 'message' => 'title and content are required']);
        }
        if (!in_array($category, $allowedCategories, true)) {
            jsonResponse(422, ['success' => false, 'message' => 'invalid forum category']);
        }
        if (mb_strlen($title) > 180) {
            jsonResponse(422, ['success' => false, 'message' => 'title cannot exceed 180 characters']);
        }
        if (isset($_FILES['postImage'])) {
            $imagePath = forumValidateAndSaveImage($_FILES['postImage']);
        }

        $insert = $pdo->prepare('
            INSERT INTO forum_posts (user_id, title, category, content, image_path, status)
            VALUES (:user_id, :title, :category, :content, :image_path, "visible")
        ');
        $insert->execute([
            ':user_id' => $userId,
            ':title' => strip_tags($title),
            ':category' => $category,
            ':content' => strip_tags($content),
            ':image_path' => $imagePath !== '' ? $imagePath : null,
        ]);
        $postId = (int)$pdo->lastInsertId();

        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'user'), 'ACCOUNT_CREATE_FORUM_POST', 'forum_post', $postId, [
            'category' => $category,
            'hasImage' => $imagePath !== '',
        ]);
        jsonResponse(200, [
            'success' => true,
            'message' => 'Forum post submitted',
            'data' => [
                'postID' => $postId,
                'imagePath' => $imagePath !== '' ? $imagePath : null,
            ],
        ]);
    }

    if ($action === 'account_forum_my_posts' && $method === 'GET') {
        $userId = requireLogin();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['pageSize'] ?? 6);
        if ($pageSize < 1) {
            $pageSize = 6;
        }
        if ($pageSize > 20) {
            $pageSize = 20;
        }
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM forum_posts WHERE user_id = :uid');
        $countStmt->execute([':uid' => $userId]);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = (int)max(1, ceil($total / max(1, $pageSize)));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $pageSize;

        $stmt = $pdo->prepare('
            SELECT id, title, category, content, image_path, status, created_at, updated_at
            FROM forum_posts
            WHERE user_id = :uid
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        jsonResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll(),
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    if ($action === 'account_forum_feed' && $method === 'GET') {
        requireLogin();
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $category = trim((string)($_GET['category'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = (int)($_GET['pageSize'] ?? 6);
        if ($pageSize < 1) {
            $pageSize = 6;
        }
        if ($pageSize > 20) {
            $pageSize = 20;
        }
        $allowedCategories = ['help', 'experience', 'suggestion', 'lost_found', 'other'];
        $params = [];
        $whereSql = ' WHERE fp.status = "visible"';
        if ($category !== '' && in_array($category, $allowedCategories, true)) {
            $whereSql .= ' AND fp.category = :category';
            $params[':category'] = $category;
        }
        if ($keyword !== '') {
            $whereSql .= ' AND (fp.title LIKE :kw OR fp.content LIKE :kw OR u.full_name LIKE :kw)';
            $params[':kw'] = '%' . $keyword . '%';
        }

        $countSql = '
            SELECT COUNT(*)
            FROM forum_posts fp
            INNER JOIN users u ON u.id = fp.user_id
        ' . $whereSql;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = (int)max(1, ceil($total / max(1, $pageSize)));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $pageSize;

        $sql = '
            SELECT fp.id, fp.user_id, fp.title, fp.category, fp.content, fp.image_path, fp.status, fp.created_at, fp.updated_at,
                   u.campus_id, u.full_name
            FROM forum_posts fp
            INNER JOIN users u ON u.id = fp.user_id
        ' . $whereSql . '
            ORDER BY fp.updated_at DESC, fp.id DESC
            LIMIT :limit OFFSET :offset
        ';
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        jsonResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll(),
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    if ($action === 'forum_post_replies' && $method === 'GET') {
        requireLogin();
        $postId = (int)($_GET['postID'] ?? 0);
        if ($postId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'postID is required']);
        }

        $postStmt = $pdo->prepare('SELECT id, status FROM forum_posts WHERE id = :id LIMIT 1');
        $postStmt->execute([':id' => $postId]);
        $post = $postStmt->fetch();
        if (!$post || (string)$post['status'] !== 'visible') {
            jsonResponse(404, ['success' => false, 'message' => 'Forum post not found']);
        }

        $replyStmt = $pdo->prepare('
            SELECT r.id, r.post_id, r.user_id, r.reply_content, r.created_at, r.updated_at,
                   u.full_name, u.campus_id
            FROM forum_post_replies r
            INNER JOIN users u ON u.id = r.user_id
            WHERE r.post_id = :post_id
            ORDER BY r.id ASC
            LIMIT 200
        ');
        $replyStmt->execute([':post_id' => $postId]);
        jsonResponse(200, ['success' => true, 'data' => $replyStmt->fetchAll()]);
    }

    if ($action === 'forum_post_reply_submit' && $method === 'POST') {
        $userId = requireLogin();
        $postId = (int)($body['postID'] ?? 0);
        $replyContent = trim((string)($body['replyContent'] ?? ''));
        if ($postId <= 0 || $replyContent === '') {
            jsonResponse(422, ['success' => false, 'message' => 'postID and replyContent are required']);
        }
        if (mb_strlen($replyContent) > 500) {
            jsonResponse(422, ['success' => false, 'message' => 'Reply cannot exceed 500 characters']);
        }

        $postStmt = $pdo->prepare('SELECT id, status FROM forum_posts WHERE id = :id LIMIT 1');
        $postStmt->execute([':id' => $postId]);
        $post = $postStmt->fetch();
        if (!$post) {
            jsonResponse(404, ['success' => false, 'message' => 'Forum post not found']);
        }
        if ((string)$post['status'] !== 'visible') {
            jsonResponse(409, ['success' => false, 'message' => 'This post is not open for replies']);
        }

        $ins = $pdo->prepare('
            INSERT INTO forum_post_replies (post_id, user_id, reply_content)
            VALUES (:post_id, :user_id, :reply_content)
        ');
        $ins->execute([
            ':post_id' => $postId,
            ':user_id' => $userId,
            ':reply_content' => strip_tags($replyContent),
        ]);
        $replyId = (int)$pdo->lastInsertId();

        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'user'), 'ACCOUNT_REPLY_FORUM_POST', 'forum_post', $postId, [
            'replyID' => $replyId,
        ]);
        jsonResponse(200, ['success' => true, 'message' => 'Reply posted', 'data' => ['replyID' => $replyId]]);
    }

    if ($action === 'account_forum_update' && $method === 'POST') {
        $userId = requireLogin();
        $postId = (int)($body['postID'] ?? 0);
        $title = trim((string)($body['title'] ?? ''));
        $content = trim((string)($body['content'] ?? ''));
        $category = trim((string)($body['category'] ?? 'other'));
        $allowedCategories = ['help', 'experience', 'suggestion', 'lost_found', 'other'];

        if ($postId <= 0 || $title === '' || $content === '') {
            jsonResponse(422, ['success' => false, 'message' => 'postID, title and content are required']);
        }
        if (!in_array($category, $allowedCategories, true)) {
            jsonResponse(422, ['success' => false, 'message' => 'invalid forum category']);
        }
        if (mb_strlen($title) > 180) {
            jsonResponse(422, ['success' => false, 'message' => 'title cannot exceed 180 characters']);
        }

        $lockStmt = $pdo->prepare('
            SELECT id, status
            FROM forum_posts
            WHERE id = :id AND user_id = :uid
            LIMIT 1
            FOR UPDATE
        ');
        $lockStmt->execute([':id' => $postId, ':uid' => $userId]);
        $post = $lockStmt->fetch();
        if (!$post) {
            jsonResponse(404, ['success' => false, 'message' => 'Forum post not found']);
        }
        if (in_array((string)$post['status'], ['locked', 'hidden', 'deleted'], true)) {
            jsonResponse(409, ['success' => false, 'message' => 'This post cannot be edited in current status']);
        }

        $upd = $pdo->prepare('
            UPDATE forum_posts
            SET title = :title, category = :category, content = :content, updated_at = NOW()
            WHERE id = :id AND user_id = :uid
        ');
        $upd->execute([
            ':title' => strip_tags($title),
            ':category' => $category,
            ':content' => strip_tags($content),
            ':id' => $postId,
            ':uid' => $userId,
        ]);

        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'user'), 'ACCOUNT_EDIT_FORUM_POST', 'forum_post', $postId, [
            'category' => $category,
        ]);
        jsonResponse(200, ['success' => true, 'message' => 'Forum post updated']);
    }

    if ($action === 'account_forum_delete' && $method === 'POST') {
        $userId = requireLogin();
        $postId = (int)($body['postID'] ?? 0);
        if ($postId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'postID is required']);
        }

        $lockStmt = $pdo->prepare('
            SELECT id, status
            FROM forum_posts
            WHERE id = :id AND user_id = :uid
            LIMIT 1
            FOR UPDATE
        ');
        $lockStmt->execute([':id' => $postId, ':uid' => $userId]);
        $post = $lockStmt->fetch();
        if (!$post) {
            jsonResponse(404, ['success' => false, 'message' => 'Forum post not found']);
        }
        if ((string)$post['status'] === 'locked') {
            jsonResponse(409, ['success' => false, 'message' => 'Locked post cannot be deleted by user']);
        }

        $upd = $pdo->prepare('
            UPDATE forum_posts
            SET status = "deleted", content = "[removed by user]", updated_at = NOW()
            WHERE id = :id AND user_id = :uid
        ');
        $upd->execute([':id' => $postId, ':uid' => $userId]);

        writeAudit($pdo, $userId, (string)($_SESSION['role'] ?? 'user'), 'ACCOUNT_DELETE_FORUM_POST', 'forum_post', $postId);
        jsonResponse(200, ['success' => true, 'message' => 'Forum post deleted']);
    }

    if ($action === 'admin_overview' && $method === 'GET') {
        requireAdmin();
        $kpi = [];
        $kpi['vehicle'] = $pdo->query('
            SELECT status, COUNT(*) AS total FROM vehicles GROUP BY status
        ')->fetchAll();
        $kpi['stationUsage'] = $pdo->query('
            SELECT rs.id, rs.name, rs.capacity,
                   SUM(CASE WHEN v.status IN ("available","maintenance") THEN 1 ELSE 0 END) AS occupied
            FROM rental_stations rs
            LEFT JOIN vehicles v ON v.station_id = rs.id
            GROUP BY rs.id, rs.name, rs.capacity
            ORDER BY rs.name
        ')->fetchAll();
        jsonResponse(200, ['success' => true, 'data' => $kpi]);
    }

    if ($action === 'admin_vehicles' && $method === 'GET') {
        requireAdmin();
        $rows = $pdo->query('
            SELECT v.id, v.serial_no, v.status, v.station_id, rs.name AS station_name,
                   vt.name AS vehicle_type, b.name AS brand, v.battery_level
            FROM vehicles v
            INNER JOIN vehicle_types vt ON vt.id = v.type_id
            INNER JOIN brands b ON b.id = v.brand_id
            LEFT JOIN rental_stations rs ON rs.id = v.station_id
            ORDER BY v.id DESC
        ')->fetchAll();
        jsonResponse(200, ['success' => true, 'data' => $rows]);
    }

    if ($action === 'admin_update_vehicle' && $method === 'POST') {
        $adminId = requireAdmin();
        $vehicleId = (int)($body['vehicleID'] ?? 0);
        $newStatus = trim((string)($body['newStatus'] ?? ''));
        if ($newStatus === 'active') {
            $newStatus = 'available';
        }
        if ($vehicleId <= 0 || !in_array($newStatus, ['available', 'maintenance', 'retired'], true)) {
            jsonResponse(422, ['success' => false, 'message' => '參數不合法']);
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id, status FROM vehicles WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $vehicleId]);
        $vehicle = $stmt->fetch();
        if (!$vehicle) {
            $pdo->rollBack();
            jsonResponse(404, ['success' => false, 'message' => '車輛不存在']);
        }
        if (!validTransition((string)$vehicle['status'], $newStatus) && (string)$vehicle['status'] !== $newStatus) {
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => '狀態轉移不合法']);
        }

        $upd = $pdo->prepare('UPDATE vehicles SET status = :st WHERE id = :id');
        $upd->execute([':st' => $newStatus, ':id' => $vehicleId]);
        writeAudit($pdo, $adminId, 'admin', 'ADMIN_UPDATE_VEHICLE_STATUS', 'vehicle', $vehicleId, ['newStatus' => $newStatus]);
        $pdo->commit();
        jsonResponse(200, ['success' => true, 'message' => '車輛狀態已更新']);
    }

    if ($action === 'admin_assign_station' && $method === 'POST') {
        $adminId = requireAdmin();
        $vehicleId = (int)($body['vehicleID'] ?? 0);
        $stationId = (int)($body['stationID'] ?? 0);
        if ($vehicleId <= 0 || $stationId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'vehicleID 與 stationID 必填']);
        }
        $stmt = $pdo->prepare('UPDATE vehicles SET station_id = :sid WHERE id = :vid');
        $stmt->execute([':sid' => $stationId, ':vid' => $vehicleId]);
        writeAudit($pdo, $adminId, 'admin', 'ADMIN_ASSIGN_STATION', 'vehicle', $vehicleId, ['stationID' => $stationId]);
        jsonResponse(200, ['success' => true, 'message' => '站點指派成功']);
    }

    if ($action === 'admin_abnormal_orders' && $method === 'GET') {
        requireAdmin();
        $rows = $pdo->query('
            SELECT ro.id, ro.user_id, ro.vehicle_id, ro.start_time, ro.status,
                   TIMESTAMPDIFF(HOUR, ro.start_time, NOW()) AS elapsed_hours
            FROM rental_orders ro
            WHERE ro.status = "active"
              AND TIMESTAMPDIFF(HOUR, ro.start_time, NOW()) >= 24
            ORDER BY ro.start_time ASC
        ')->fetchAll();
        jsonResponse(200, ['success' => true, 'data' => $rows]);
    }

    if ($action === 'admin_payment_pending_orders' && $method === 'GET') {
        requireAdmin();
        $rows = $pdo->query('
            SELECT ro.id, ro.user_id, ro.vehicle_id, ro.start_time, ro.end_time, ro.duration_minutes, ro.fee, ro.status,
                   u.campus_id, u.full_name, u.email, u.balance,
                   v.serial_no
            FROM rental_orders ro
            INNER JOIN users u ON u.id = ro.user_id
            INNER JOIN vehicles v ON v.id = ro.vehicle_id
            WHERE ro.status = "payment_pending"
            ORDER BY ro.id DESC
            LIMIT 300
        ')->fetchAll();
        jsonResponse(200, ['success' => true, 'data' => $rows]);
    }

    if ($action === 'admin_force_end' && $method === 'POST') {
        $adminId = requireAdmin();
        $orderId = (int)($body['orderID'] ?? 0);
        $reason = trim((string)($body['reason'] ?? ''));
        $adjustFee = (float)($body['adjustFee'] ?? 0);
        if ($orderId <= 0 || $reason === '') {
            jsonResponse(422, ['success' => false, 'message' => 'orderID 與 reason 必填']);
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id, user_id, vehicle_id, status FROM rental_orders WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch();
        if (!$order || $order['status'] !== 'active') {
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => '訂單不是進行中']);
        }

        $fee = max(0, $adjustFee);
        $updOrder = $pdo->prepare('
            UPDATE rental_orders
            SET end_time = NOW(), duration_minutes = TIMESTAMPDIFF(MINUTE, start_time, NOW()),
                fee = :fee, status = "force_closed", note = :note
            WHERE id = :id
        ');
        $updOrder->execute([':fee' => $fee, ':note' => $reason, ':id' => $orderId]);

        $updVehicle = $pdo->prepare('UPDATE vehicles SET status = "maintenance" WHERE id = :vid');
        $updVehicle->execute([':vid' => (int)$order['vehicle_id']]);

        writeAudit($pdo, $adminId, 'admin', 'ADMIN_FORCE_END', 'rental_order', $orderId, [
            'reason' => $reason,
            'adjustFee' => $fee,
        ]);
        $pdo->commit();
        jsonResponse(200, ['success' => true, 'message' => '手動結單完成']);
    }

    if ($action === 'admin_staff_accounts' && $method === 'GET') {
        requireAdmin();
        $rows = $pdo->query('
            SELECT id, campus_id, full_name, email, phone, account_status, created_at
            FROM users
            WHERE role = "staff"
            ORDER BY id DESC
        ')->fetchAll();
        jsonResponse(200, ['success' => true, 'data' => $rows]);
    }

    if ($action === 'admin_add_staff' && $method === 'POST') {
        $adminId = requireAdmin();
        $campusId = trim((string)($body['campusID'] ?? ''));
        $fullName = trim((string)($body['fullName'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $phone = trim((string)($body['phone'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if ($campusId === '' || $fullName === '' || $email === '' || $password === '') {
            jsonResponse(422, ['success' => false, 'message' => 'campusID, fullName, email, password are required']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(422, ['success' => false, 'message' => 'Invalid email format']);
        }
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            jsonResponse(422, ['success' => false, 'message' => 'Password must be at least 8 chars and include letters and numbers']);
        }

        $dup = $pdo->prepare('SELECT id FROM users WHERE campus_id = :campus_id OR email = :email LIMIT 1');
        $dup->execute([':campus_id' => $campusId, ':email' => $email]);
        if ($dup->fetch()) {
            jsonResponse(409, ['success' => false, 'message' => 'Campus ID or email already exists']);
        }

        $stmt = $pdo->prepare('
            INSERT INTO users (
                campus_id, full_name, role, email, phone, balance, password_hash, account_status
            ) VALUES (
                :campus_id, :full_name, "staff", :email, :phone, 0, :password_hash, "active"
            )
        ');
        $stmt->execute([
            ':campus_id' => $campusId,
            ':full_name' => strip_tags($fullName),
            ':email' => $email,
            ':phone' => $phone !== '' ? strip_tags($phone) : null,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $newUserId = (int)$pdo->lastInsertId();
        writeAudit($pdo, $adminId, 'admin', 'ADMIN_ADD_STAFF', 'user', $newUserId, [
            'campusID' => $campusId,
            'email' => $email,
        ]);
        jsonResponse(200, ['success' => true, 'message' => 'Staff account added']);
    }

    if ($action === 'admin_update_staff_status' && $method === 'POST') {
        $adminId = requireAdmin();
        $userId = (int)($body['userID'] ?? 0);
        $accountStatus = trim((string)($body['accountStatus'] ?? ''));
        if ($userId <= 0 || !in_array($accountStatus, ['active', 'frozen', 'disabled'], true)) {
            jsonResponse(422, ['success' => false, 'message' => 'Invalid parameters']);
        }

        $stmt = $pdo->prepare('UPDATE users SET account_status = :status WHERE id = :id AND role = "staff"');
        $stmt->execute([':status' => $accountStatus, ':id' => $userId]);
        if ($stmt->rowCount() === 0) {
            jsonResponse(404, ['success' => false, 'message' => 'Staff not found']);
        }

        writeAudit($pdo, $adminId, 'admin', 'ADMIN_UPDATE_STAFF_STATUS', 'user', $userId, ['accountStatus' => $accountStatus]);
        jsonResponse(200, ['success' => true, 'message' => 'Staff account status updated']);
    }

    if ($action === 'admin_remove_staff' && $method === 'POST') {
        $adminId = requireAdmin();
        $userId = (int)($body['userID'] ?? 0);
        if ($userId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'userID is required']);
        }

        if ((int)($_SESSION['user_id'] ?? 0) === $userId) {
            jsonResponse(409, ['success' => false, 'message' => 'Cannot remove current login admin']);
        }

        $checkUser = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = "staff" LIMIT 1');
        $checkUser->execute([':id' => $userId]);
        if (!$checkUser->fetch()) {
            jsonResponse(404, ['success' => false, 'message' => 'Staff not found']);
        }

        $hasOrders = $pdo->prepare('SELECT id FROM rental_orders WHERE user_id = :id LIMIT 1');
        $hasOrders->execute([':id' => $userId]);
        $hasPosts = $pdo->prepare('SELECT id FROM forum_posts WHERE user_id = :id LIMIT 1');
        $hasPosts->execute([':id' => $userId]);
        $hasFeedback = $pdo->prepare('SELECT id FROM feedbacks WHERE user_id = :id LIMIT 1');
        $hasFeedback->execute([':id' => $userId]);

        if ($hasOrders->fetch() || $hasPosts->fetch() || $hasFeedback->fetch()) {
            jsonResponse(409, ['success' => false, 'message' => 'Cannot remove: staff has related records (orders/posts/feedback).']);
        }

        $del = $pdo->prepare('DELETE FROM users WHERE id = :id AND role = "staff"');
        $del->execute([':id' => $userId]);

        writeAudit($pdo, $adminId, 'admin', 'ADMIN_REMOVE_STAFF', 'user', $userId, ['mode' => 'hard_delete']);
        jsonResponse(200, ['success' => true, 'message' => 'Staff account removed']);
    }

    if ($action === 'staff_feedback_list' && $method === 'GET') {
        requireStaffOrAdmin();
        $status = trim((string)($_GET['status'] ?? ''));
        $allowedStatus = ['open', 'in_progress', 'resolved', 'closed'];
        $params = [];
        $sql = '
            SELECT f.id, f.user_id, f.title, f.description, f.category, f.status, f.created_at, f.updated_at,
                   u.campus_id, u.full_name, u.email
            FROM feedbacks f
            INNER JOIN users u ON u.id = f.user_id
        ';
        if ($status !== '' && in_array($status, $allowedStatus, true)) {
            $sql .= ' WHERE f.status = :status';
            $params[':status'] = $status;
        }
        $sql .= ' ORDER BY FIELD(f.status, "open","in_progress","resolved","closed"), f.updated_at DESC, f.id DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $feedbacks = $stmt->fetchAll();

        $feedbackIds = array_map(static fn($row) => (int)$row['id'], $feedbacks);
        $repliesByFeedback = [];
        if ($feedbackIds !== []) {
            $placeholders = implode(',', array_fill(0, count($feedbackIds), '?'));
            $replyStmt = $pdo->prepare("
                SELECT fr.id, fr.feedback_id, fr.reply_content, fr.created_at, u.full_name AS admin_name
                FROM feedback_replies fr
                INNER JOIN users u ON u.id = fr.admin_user_id
                WHERE fr.feedback_id IN ({$placeholders})
                ORDER BY fr.id ASC
            ");
            $replyStmt->execute($feedbackIds);
            foreach ($replyStmt->fetchAll() as $reply) {
                $fid = (int)$reply['feedback_id'];
                if (!isset($repliesByFeedback[$fid])) {
                    $repliesByFeedback[$fid] = [];
                }
                $repliesByFeedback[$fid][] = $reply;
            }
        }
        foreach ($feedbacks as &$row) {
            $fid = (int)$row['id'];
            $row['replies'] = $repliesByFeedback[$fid] ?? [];
        }
        unset($row);

        jsonResponse(200, ['success' => true, 'data' => $feedbacks]);
    }

    if ($action === 'staff_feedback_reply' && $method === 'POST') {
        $staffId = requireStaffOrAdmin();
        $feedbackId = (int)($body['feedbackID'] ?? 0);
        $replyContent = trim((string)($body['replyContent'] ?? ''));
        $newStatus = trim((string)($body['status'] ?? ''));
        $allowedStatus = ['open', 'in_progress', 'resolved', 'closed'];

        if ($feedbackId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'feedbackID is required']);
        }
        if ($replyContent === '' && $newStatus === '') {
            jsonResponse(422, ['success' => false, 'message' => 'replyContent or status is required']);
        }
        if ($newStatus !== '' && !in_array($newStatus, $allowedStatus, true)) {
            jsonResponse(422, ['success' => false, 'message' => 'Invalid status']);
        }

        $pdo->beginTransaction();
        $lockStmt = $pdo->prepare('SELECT id, status FROM feedbacks WHERE id = :id FOR UPDATE');
        $lockStmt->execute([':id' => $feedbackId]);
        $feedback = $lockStmt->fetch();
        if (!$feedback) {
            $pdo->rollBack();
            jsonResponse(404, ['success' => false, 'message' => 'Feedback not found']);
        }

        $finalStatus = $newStatus;
        if ($finalStatus === '' && $replyContent !== '' && (string)$feedback['status'] === 'open') {
            $finalStatus = 'in_progress';
        }

        if ($replyContent !== '') {
            $replyStmt = $pdo->prepare('
                INSERT INTO feedback_replies (feedback_id, admin_user_id, reply_content)
                VALUES (:feedback_id, :admin_user_id, :reply_content)
            ');
            $replyStmt->execute([
                ':feedback_id' => $feedbackId,
                ':admin_user_id' => $staffId,
                ':reply_content' => $replyContent,
            ]);
        }

        if ($finalStatus !== '' && $finalStatus !== (string)$feedback['status']) {
            $upd = $pdo->prepare('UPDATE feedbacks SET status = :status WHERE id = :id');
            $upd->execute([':status' => $finalStatus, ':id' => $feedbackId]);
        }

        writeAudit($pdo, $staffId, (string)($_SESSION['role'] ?? 'staff'), 'STAFF_REPLY_FEEDBACK', 'feedback', $feedbackId, [
            'status' => $finalStatus !== '' ? $finalStatus : (string)$feedback['status'],
            'hasReply' => $replyContent !== '',
        ]);

        $pdo->commit();
        jsonResponse(200, ['success' => true, 'message' => 'Feedback updated']);
    }

    if ($action === 'staff_forum_list' && $method === 'GET') {
        requireStaffOrAdmin();
        $status = trim((string)($_GET['status'] ?? ''));
        $allowedStatus = ['visible', 'locked', 'hidden', 'deleted'];
        $params = [];
        $sql = '
            SELECT fp.id, fp.user_id, fp.title, fp.category, fp.content, fp.status, fp.created_at, fp.updated_at,
                   u.campus_id, u.full_name, u.email
            FROM forum_posts fp
            INNER JOIN users u ON u.id = fp.user_id
        ';
        if ($status !== '' && in_array($status, $allowedStatus, true)) {
            $sql .= ' WHERE fp.status = :status';
            $params[':status'] = $status;
        }
        $sql .= ' ORDER BY FIELD(fp.status, "visible","locked","hidden","deleted"), fp.updated_at DESC, fp.id DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(200, ['success' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'staff_forum_update_status' && $method === 'POST') {
        $staffId = requireStaffOrAdmin();
        $postId = (int)($body['postID'] ?? 0);
        $status = trim((string)($body['status'] ?? ''));
        $allowedStatus = ['visible', 'locked', 'hidden', 'deleted'];
        if ($postId <= 0 || !in_array($status, $allowedStatus, true)) {
            jsonResponse(422, ['success' => false, 'message' => 'Invalid parameters']);
        }

        $upd = $pdo->prepare('UPDATE forum_posts SET status = :status, updated_at = NOW() WHERE id = :id');
        $upd->execute([':status' => $status, ':id' => $postId]);
        if ($upd->rowCount() === 0) {
            jsonResponse(404, ['success' => false, 'message' => 'Forum post not found']);
        }

        writeAudit($pdo, $staffId, (string)($_SESSION['role'] ?? 'staff'), 'STAFF_UPDATE_FORUM_POST_STATUS', 'forum_post', $postId, [
            'status' => $status,
        ]);
        jsonResponse(200, ['success' => true, 'message' => 'Forum post status updated']);
    }

    if ($action === 'report_kpi' && $method === 'GET') {
        requireStaffOrAdmin();
        $from = trim((string)($_GET['from'] ?? date('Y-m-01')));
        $to = trim((string)($_GET['to'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            jsonResponse(422, ['success' => false, 'message' => 'Invalid date range']);
        }

        $summaryStmt = $pdo->prepare('
            SELECT
              COUNT(*) AS total_orders,
              COALESCE(SUM(CASE WHEN status IN ("completed","payment_pending","force_closed") THEN fee ELSE 0 END), 0) AS total_revenue,
              COALESCE(AVG(CASE WHEN duration_minutes IS NOT NULL THEN duration_minutes ELSE NULL END), 0) AS avg_minutes
            FROM rental_orders
            WHERE DATE(start_time) BETWEEN :from AND :to
        ');
        $summaryStmt->execute([':from' => $from, ':to' => $to]);
        $summary = $summaryStmt->fetch();

        $stationStmt = $pdo->prepare('
            SELECT rs.id, rs.name, COUNT(ro.id) AS total_orders, COALESCE(SUM(ro.fee), 0) AS revenue
            FROM rental_stations rs
            LEFT JOIN rental_orders ro
              ON ro.start_station_id = rs.id
             AND DATE(ro.start_time) BETWEEN :from AND :to
            GROUP BY rs.id, rs.name
            ORDER BY total_orders DESC, rs.name
            LIMIT 20
        ');
        $stationStmt->execute([':from' => $from, ':to' => $to]);

        $statusStmt = $pdo->prepare('
            SELECT status, COUNT(*) AS total
            FROM rental_orders
            WHERE DATE(start_time) BETWEEN :from AND :to
            GROUP BY status
        ');
        $statusStmt->execute([':from' => $from, ':to' => $to]);

        jsonResponse(200, [
            'success' => true,
            'data' => [
                'range' => ['from' => $from, 'to' => $to],
                'summary' => $summary,
                'byStation' => $stationStmt->fetchAll(),
                'byStatus' => $statusStmt->fetchAll(),
            ],
        ]);
    }

    if ($action === 'staff_bicycles' && $method === 'GET') {
        requireStaffOrAdmin();

        $bicycles = $pdo->query('
            SELECT
                v.id,
                v.serial_no,
                v.status,
                v.station_id,
                v.issue_note,
                rs.name AS station_name,
                b.name AS brand,
                au.id AS renter_user_id,
                au.campus_id AS renter_campus_id,
                au.full_name AS renter_name,
                au.phone AS renter_phone,
                aro.start_time AS rent_start_time,
                lu.full_name AS last_renter_name,
                lro.end_time AS last_end_time
            FROM vehicles v
            INNER JOIN vehicle_types vt ON vt.id = v.type_id
            INNER JOIN brands b ON b.id = v.brand_id
            LEFT JOIN rental_stations rs ON rs.id = v.station_id
            LEFT JOIN rental_orders aro ON aro.vehicle_id = v.id AND aro.status = "active"
            LEFT JOIN users au ON au.id = aro.user_id
            LEFT JOIN rental_orders lro ON lro.id = (
                SELECT ro2.id
                FROM rental_orders ro2
                WHERE ro2.vehicle_id = v.id
                  AND ro2.end_time IS NOT NULL
                ORDER BY ro2.end_time DESC
                LIMIT 1
            )
            LEFT JOIN users lu ON lu.id = lro.user_id
            WHERE vt.name = "bicycle"
            ORDER BY v.id DESC
        ')->fetchAll();

        $brands = $pdo->query('SELECT id, name FROM brands ORDER BY name')->fetchAll();
        $stations = $pdo->query('SELECT id, name FROM rental_stations WHERE status = "active" ORDER BY name')->fetchAll();

        jsonResponse(200, [
            'success' => true,
            'data' => [
                'bicycles' => $bicycles,
                'brands' => $brands,
                'stations' => $stations,
            ],
        ]);
    }

    if ($action === 'staff_add_bicycle' && $method === 'POST') {
        $adminId = requireAdmin();
        $serialNo = trim((string)($body['serialNo'] ?? ''));
        $brandId = (int)($body['brandID'] ?? 0);
        $stationId = (int)($body['stationID'] ?? 0);
        $batteryLevel = $body['batteryLevel'] ?? null;

        if ($serialNo === '' || $brandId <= 0 || $stationId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'serialNo, brandID, stationID are required']);
        }

        $battery = null;
        if ($batteryLevel !== null && $batteryLevel !== '') {
            $battery = (int)$batteryLevel;
            if ($battery < 0 || $battery > 100) {
                jsonResponse(422, ['success' => false, 'message' => 'batteryLevel must be between 0 and 100']);
            }
        }

        $typeStmt = $pdo->prepare('SELECT id FROM vehicle_types WHERE name = "bicycle" LIMIT 1');
        $typeStmt->execute();
        $bicycleType = $typeStmt->fetch();
        if (!$bicycleType) {
            jsonResponse(500, ['success' => false, 'message' => 'Bicycle type is missing in master data']);
        }

        $brandStmt = $pdo->prepare('SELECT id FROM brands WHERE id = :id LIMIT 1');
        $brandStmt->execute([':id' => $brandId]);
        if (!$brandStmt->fetch()) {
            jsonResponse(404, ['success' => false, 'message' => 'Brand not found']);
        }

        $stationStmt = $pdo->prepare('SELECT id FROM rental_stations WHERE id = :id AND status = "active" LIMIT 1');
        $stationStmt->execute([':id' => $stationId]);
        if (!$stationStmt->fetch()) {
            jsonResponse(404, ['success' => false, 'message' => 'Active station not found']);
        }

        $insert = $pdo->prepare('
            INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
            VALUES (:serial_no, :type_id, :brand_id, "available", :station_id, :battery_level)
        ');
        try {
            $insert->bindValue(':serial_no', $serialNo);
            $insert->bindValue(':type_id', (int)$bicycleType['id'], PDO::PARAM_INT);
            $insert->bindValue(':brand_id', $brandId, PDO::PARAM_INT);
            $insert->bindValue(':station_id', $stationId, PDO::PARAM_INT);
            $insert->bindValue(':battery_level', $battery, $battery === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $insert->execute();
        } catch (Throwable $e) {
            jsonResponse(409, ['success' => false, 'message' => 'Failed to add bicycle. Serial number may already exist.']);
        }

        $vehicleId = (int)$pdo->lastInsertId();
        writeAudit($pdo, $adminId, 'admin', 'ADMIN_ADD_BICYCLE', 'vehicle', $vehicleId, [
            'serialNo' => $serialNo,
            'brandID' => $brandId,
            'stationID' => $stationId,
            'batteryLevel' => $battery,
        ]);

        jsonResponse(200, ['success' => true, 'message' => 'Bicycle added']);
    }

    if ($action === 'staff_update_vehicle_issue' && $method === 'POST') {
        $actorId = requireStaffOrAdmin();
        $actorRole = (string)($_SESSION['role'] ?? 'staff');
        $vehicleId = (int)($body['vehicleID'] ?? 0);
        $issueNote = trim((string)($body['issueNote'] ?? ''));
        if ($vehicleId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'vehicleID is required']);
        }
        if (strlen($issueNote) > 500) {
            jsonResponse(422, ['success' => false, 'message' => 'issueNote must be at most 500 characters']);
        }

        $check = $pdo->prepare('
            SELECT v.id
            FROM vehicles v
            INNER JOIN vehicle_types vt ON vt.id = v.type_id
            WHERE v.id = :id AND vt.name = "bicycle"
            LIMIT 1
        ');
        $check->execute([':id' => $vehicleId]);
        if (!$check->fetch()) {
            jsonResponse(404, ['success' => false, 'message' => 'Bicycle not found']);
        }

        $upd = $pdo->prepare('UPDATE vehicles SET issue_note = :n WHERE id = :id');
        $upd->execute([':n' => $issueNote === '' ? null : $issueNote, ':id' => $vehicleId]);
        $auditRole = $actorRole === 'admin' ? 'admin' : 'user';
        writeAudit($pdo, $actorId, $auditRole, 'STAFF_UPDATE_VEHICLE_ISSUE', 'vehicle', $vehicleId, ['issueNote' => $issueNote]);
        jsonResponse(200, ['success' => true, 'message' => 'Issue note updated']);
    }

    if ($action === 'staff_remove_bicycle' && $method === 'POST') {
        $adminId = requireAdmin();
        $vehicleId = (int)($body['vehicleID'] ?? 0);
        if ($vehicleId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'vehicleID is required']);
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('
            SELECT v.id, v.serial_no, v.status
            FROM vehicles v
            INNER JOIN vehicle_types vt ON vt.id = v.type_id
            WHERE v.id = :id AND vt.name = "bicycle"
            FOR UPDATE
        ');
        $stmt->execute([':id' => $vehicleId]);
        $bike = $stmt->fetch();
        if (!$bike) {
            $pdo->rollBack();
            jsonResponse(404, ['success' => false, 'message' => 'Bicycle not found']);
        }
        if ((string)$bike['status'] === 'rented') {
            $pdo->rollBack();
            jsonResponse(409, ['success' => false, 'message' => 'Cannot remove a rented bicycle']);
        }

        $del = $pdo->prepare('DELETE FROM vehicles WHERE id = :id');
        $del->execute([':id' => $vehicleId]);
        writeAudit($pdo, $adminId, 'admin', 'ADMIN_REMOVE_BICYCLE', 'vehicle', $vehicleId, ['serialNo' => $bike['serial_no']]);
        $pdo->commit();

        jsonResponse(200, ['success' => true, 'message' => 'Bicycle removed']);
    }

    if ($action === 'staff_students' && $method === 'GET') {
        requireStaffOrAdmin();
        $rows = $pdo->query('
            SELECT id, campus_id, full_name, role, email, account_status, created_at
            FROM users
            WHERE role IN ("student", "teacher", "visitor")
            ORDER BY id DESC
        ')->fetchAll();
        jsonResponse(200, ['success' => true, 'data' => $rows]);
    }

    if ($action === 'staff_add_student' && $method === 'POST') {
        $staffId = requireStaffOrAdmin();
        $campusId = trim((string)($body['campusID'] ?? ''));
        $fullName = trim((string)($body['fullName'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $phone = trim((string)($body['phone'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if ($campusId === '' || $fullName === '' || $email === '' || $password === '') {
            jsonResponse(422, ['success' => false, 'message' => 'campusID, fullName, email, password are required']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(422, ['success' => false, 'message' => 'Invalid email format']);
        }
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            jsonResponse(422, ['success' => false, 'message' => 'Password must be at least 8 chars and include letters and numbers']);
        }

        $dup = $pdo->prepare('SELECT id FROM users WHERE campus_id = :campus_id OR email = :email LIMIT 1');
        $dup->execute([':campus_id' => $campusId, ':email' => $email]);
        if ($dup->fetch()) {
            jsonResponse(409, ['success' => false, 'message' => 'Campus ID or email already exists']);
        }

        $stmt = $pdo->prepare('
            INSERT INTO users (
                campus_id, full_name, role, email, phone, balance, password_hash, account_status
            ) VALUES (
                :campus_id, :full_name, "student", :email, :phone, 0, :password_hash, "active"
            )
        ');
        $stmt->execute([
            ':campus_id' => $campusId,
            ':full_name' => strip_tags($fullName),
            ':email' => $email,
            ':phone' => $phone !== '' ? strip_tags($phone) : null,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $newUserId = (int)$pdo->lastInsertId();
        writeAudit($pdo, $staffId, 'staff', 'STAFF_ADD_STUDENT', 'user', $newUserId, [
            'campusID' => $campusId,
            'email' => $email,
        ]);

        jsonResponse(200, ['success' => true, 'message' => 'Student account added']);
    }

    if ($action === 'staff_update_student_status' && $method === 'POST') {
        $staffId = requireStaffOrAdmin();
        $userId = (int)($body['userID'] ?? 0);
        $accountStatus = trim((string)($body['accountStatus'] ?? ''));
        if ($userId <= 0 || !in_array($accountStatus, ['active', 'frozen', 'disabled'], true)) {
            jsonResponse(422, ['success' => false, 'message' => 'Invalid parameters']);
        }

        $stmt = $pdo->prepare('UPDATE users SET account_status = :status WHERE id = :id AND role IN ("student","teacher","visitor")');
        $stmt->execute([':status' => $accountStatus, ':id' => $userId]);
        if ($stmt->rowCount() === 0) {
            jsonResponse(404, ['success' => false, 'message' => 'Managed user not found']);
        }

        writeAudit($pdo, $staffId, 'staff', 'STAFF_UPDATE_STUDENT_STATUS', 'user', $userId, ['accountStatus' => $accountStatus]);
        jsonResponse(200, ['success' => true, 'message' => 'Student account status updated']);
    }

    if ($action === 'staff_remove_student' && $method === 'POST') {
        $staffId = requireStaffOrAdmin();
        $userId = (int)($body['userID'] ?? 0);
        if ($userId <= 0) {
            jsonResponse(422, ['success' => false, 'message' => 'userID is required']);
        }

        $checkUser = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role IN ("student","teacher","visitor") LIMIT 1');
        $checkUser->execute([':id' => $userId]);
        if (!$checkUser->fetch()) {
            jsonResponse(404, ['success' => false, 'message' => 'Managed user not found']);
        }

        $hasOrders = $pdo->prepare('SELECT id FROM rental_orders WHERE user_id = :id LIMIT 1');
        $hasOrders->execute([':id' => $userId]);
        $hasPosts = $pdo->prepare('SELECT id FROM forum_posts WHERE user_id = :id LIMIT 1');
        $hasPosts->execute([':id' => $userId]);
        $hasFeedback = $pdo->prepare('SELECT id FROM feedbacks WHERE user_id = :id LIMIT 1');
        $hasFeedback->execute([':id' => $userId]);

        if ($hasOrders->fetch() || $hasPosts->fetch() || $hasFeedback->fetch()) {
            jsonResponse(409, ['success' => false, 'message' => 'Cannot remove: student has related records (orders/posts/feedback).']);
        }

        $del = $pdo->prepare('DELETE FROM users WHERE id = :id AND role IN ("student","teacher","visitor")');
        $del->execute([':id' => $userId]);

        writeAudit($pdo, $staffId, 'staff', 'STAFF_REMOVE_STUDENT', 'user', $userId, ['mode' => 'hard_delete']);
        jsonResponse(200, ['success' => true, 'message' => 'Student account removed']);
    }

    if ($action === 'staff_export_students' && $method === 'GET') {
        requireStaffOrAdmin();
        $rows = $pdo->query('SELECT campus_id, full_name, role, email, phone, account_status FROM users WHERE role IN ("student","teacher","visitor") ORDER BY id DESC')->fetchAll();
        $lines = ['campus_id,full_name,role,email,phone,account_status'];
        foreach ($rows as $r) {
            $lines[] = sprintf(
                '"%s","%s","%s","%s","%s","%s"',
                str_replace('"', '""', (string)$r['campus_id']),
                str_replace('"', '""', (string)$r['full_name']),
                str_replace('"', '""', (string)$r['role']),
                str_replace('"', '""', (string)$r['email']),
                str_replace('"', '""', (string)($r['phone'] ?? '')),
                str_replace('"', '""', (string)$r['account_status'])
            );
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="students_export.csv"');
        echo implode("\n", $lines);
        exit;
    }

    if ($action === 'staff_export_bicycles' && $method === 'GET') {
        requireStaffOrAdmin();
        $rows = $pdo->query('
            SELECT v.serial_no, b.name AS brand, rs.name AS station_name, v.status, v.battery_level
            FROM vehicles v
            INNER JOIN vehicle_types vt ON vt.id = v.type_id
            INNER JOIN brands b ON b.id = v.brand_id
            LEFT JOIN rental_stations rs ON rs.id = v.station_id
            WHERE vt.name = "bicycle"
            ORDER BY v.id DESC
        ')->fetchAll();
        $lines = ['serial_no,brand,station,status,battery_level'];
        foreach ($rows as $r) {
            $lines[] = sprintf(
                '"%s","%s","%s","%s","%s"',
                str_replace('"', '""', (string)$r['serial_no']),
                str_replace('"', '""', (string)$r['brand']),
                str_replace('"', '""', (string)($r['station_name'] ?? '')),
                str_replace('"', '""', (string)$r['status']),
                str_replace('"', '""', (string)($r['battery_level'] ?? ''))
            );
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bicycles_export.csv"');
        echo implode("\n", $lines);
        exit;
    }

    if ($action === 'staff_import_bicycles_csv' && $method === 'POST') {
        $adminId = requireAdmin();
        $csvText = trim((string)($body['csvText'] ?? ''));
        if ($csvText === '') {
            jsonResponse(422, ['success' => false, 'message' => 'csvText is required']);
        }
        $rows = preg_split('/\r\n|\r|\n/', $csvText) ?: [];
        if (count($rows) < 2) {
            jsonResponse(422, ['success' => false, 'message' => 'CSV must include header and at least one row']);
        }

        $typeRows = $pdo->query('SELECT id, name FROM vehicle_types WHERE name IN ("bicycle", "scooter")')->fetchAll();
        $typeByName = [];
        foreach ($typeRows as $tr) {
            $tname = strtolower(trim((string)($tr['name'] ?? '')));
            $tid = (int)($tr['id'] ?? 0);
            if ($tname !== '' && $tid > 0) {
                $typeByName[$tname] = $tid;
            }
        }
        if (!isset($typeByName['bicycle'])) {
            jsonResponse(500, ['success' => false, 'message' => 'Bicycle type is missing in master data']);
        }

        $brandRows = $pdo->query('SELECT id, name FROM brands')->fetchAll();
        $brandByName = [];
        $brandById = [];
        foreach ($brandRows as $br) {
            $bid = (int)($br['id'] ?? 0);
            $bname = trim((string)($br['name'] ?? ''));
            if ($bid > 0) {
                $brandById[$bid] = $bid;
            }
            if ($bname !== '') {
                $brandByName[strtolower($bname)] = $bid;
            }
        }

        $stationRows = $pdo->query('SELECT id, name FROM rental_stations WHERE status = "active"')->fetchAll();
        $stationByName = [];
        $stationById = [];
        foreach ($stationRows as $sr) {
            $sid = (int)($sr['id'] ?? 0);
            $sname = trim((string)($sr['name'] ?? ''));
            if ($sid > 0) {
                $stationById[$sid] = $sid;
            }
            if ($sname !== '') {
                $stationByName[strtolower($sname)] = $sid;
            }
        }

        $insert = $pdo->prepare('
            INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
            VALUES (:serial_no, :type_id, :brand_id, :status, :station_id, :battery_level)
        ');
        $statusAllowed = ['available', 'maintenance', 'retired'];
        $created = 0;
        $errors = [];

        for ($i = 1; $i < count($rows); $i++) {
            if (trim($rows[$i]) === '') {
                continue;
            }
            $cols = str_getcsv($rows[$i]);
            if (count($cols) < 3) {
                $errors[] = 'Row ' . ($i + 1) . ': need at least serial_no, brand, station';
                continue;
            }

            $serialNo = trim((string)$cols[0]);
            $hasVehicleTypeCol = count($cols) >= 6;
            $vehicleTypeRef = strtolower(trim((string)($hasVehicleTypeCol ? $cols[1] : 'bicycle')));
            $brandRef = trim((string)($hasVehicleTypeCol ? $cols[2] : $cols[1]));
            $stationRef = trim((string)($hasVehicleTypeCol ? $cols[3] : $cols[2]));
            $batteryRef = trim((string)($hasVehicleTypeCol ? ($cols[4] ?? '') : ($cols[3] ?? '')));
            $statusRef = strtolower(trim((string)($hasVehicleTypeCol ? ($cols[5] ?? 'available') : ($cols[4] ?? 'available'))));

            if ($serialNo === '' || $brandRef === '' || $stationRef === '') {
                $errors[] = 'Row ' . ($i + 1) . ': serial_no, brand, station are required';
                continue;
            }
            if (!isset($typeByName[$vehicleTypeRef])) {
                $errors[] = 'Row ' . ($i + 1) . ': vehicle_type must be bicycle or scooter';
                continue;
            }
            if (!in_array($statusRef, $statusAllowed, true)) {
                $errors[] = 'Row ' . ($i + 1) . ': status must be available/maintenance/retired';
                continue;
            }

            $brandId = null;
            if (ctype_digit($brandRef) && isset($brandById[(int)$brandRef])) {
                $brandId = (int)$brandRef;
            } else {
                $brandId = $brandByName[strtolower($brandRef)] ?? null;
            }
            if (!$brandId) {
                $errors[] = 'Row ' . ($i + 1) . ': brand not found';
                continue;
            }

            $stationId = null;
            if (ctype_digit($stationRef) && isset($stationById[(int)$stationRef])) {
                $stationId = (int)$stationRef;
            } else {
                $stationId = $stationByName[strtolower($stationRef)] ?? null;
            }
            if (!$stationId) {
                $errors[] = 'Row ' . ($i + 1) . ': active station not found';
                continue;
            }

            $battery = null;
            if ($batteryRef !== '') {
                if (!is_numeric($batteryRef)) {
                    $errors[] = 'Row ' . ($i + 1) . ': battery_level must be numeric';
                    continue;
                }
                $battery = (int)$batteryRef;
                if ($battery < 0 || $battery > 100) {
                    $errors[] = 'Row ' . ($i + 1) . ': battery_level must be between 0 and 100';
                    continue;
                }
            }

            $dup = $pdo->prepare('SELECT id FROM vehicles WHERE serial_no = :serial_no LIMIT 1');
            $dup->execute([':serial_no' => $serialNo]);
            if ($dup->fetch()) {
                $errors[] = 'Row ' . ($i + 1) . ': serial_no already exists';
                continue;
            }

            try {
                $insert->bindValue(':serial_no', $serialNo);
                $insert->bindValue(':type_id', (int)$typeByName[$vehicleTypeRef], PDO::PARAM_INT);
                $insert->bindValue(':brand_id', (int)$brandId, PDO::PARAM_INT);
                $insert->bindValue(':status', $statusRef);
                $insert->bindValue(':station_id', (int)$stationId, PDO::PARAM_INT);
                $insert->bindValue(':battery_level', $battery, $battery === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $insert->execute();
                $created++;
            } catch (Throwable $e) {
                $errors[] = 'Row ' . ($i + 1) . ': insert failed';
            }
        }

        writeAudit($pdo, $adminId, 'admin', 'ADMIN_IMPORT_VEHICLES_CSV', 'vehicle', null, ['created' => $created, 'errors' => $errors]);
        jsonResponse(200, ['success' => true, 'message' => 'Vehicle CSV import completed', 'data' => ['created' => $created, 'errors' => $errors]]);
    }

    if ($action === 'staff_import_students_csv' && $method === 'POST') {
        $staffId = requireStaffOrAdmin();
        $csvText = trim((string)($body['csvText'] ?? ''));
        if ($csvText === '') {
            jsonResponse(422, ['success' => false, 'message' => 'csvText is required']);
        }
        $rows = preg_split('/\r\n|\r|\n/', $csvText) ?: [];
        if (count($rows) < 2) {
            jsonResponse(422, ['success' => false, 'message' => 'CSV must include header and at least one row']);
        }
        $created = 0;
        $errors = [];
        for ($i = 1; $i < count($rows); $i++) {
            if (trim($rows[$i]) === '') {
                continue;
            }
            $cols = str_getcsv($rows[$i]);
            if (count($cols) < 5) {
                $errors[] = 'Row ' . ($i + 1) . ': invalid columns';
                continue;
            }
            [$campusId, $fullName, $email, $phone, $password] = $cols;
            $campusId = trim($campusId);
            $fullName = trim($fullName);
            $email = trim($email);
            $password = trim($password);
            if ($campusId === '' || $fullName === '' || $email === '' || $password === '') {
                $errors[] = 'Row ' . ($i + 1) . ': missing required values';
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Row ' . ($i + 1) . ': invalid email';
                continue;
            }
            $dup = $pdo->prepare('SELECT id FROM users WHERE campus_id = :campus_id OR email = :email LIMIT 1');
            $dup->execute([':campus_id' => $campusId, ':email' => $email]);
            if ($dup->fetch()) {
                $errors[] = 'Row ' . ($i + 1) . ': duplicated campus/email';
                continue;
            }
            $ins = $pdo->prepare('
                INSERT INTO users (campus_id, full_name, role, email, phone, balance, password_hash, account_status)
                VALUES (:campus_id, :full_name, "student", :email, :phone, 0, :password_hash, "active")
            ');
            $ins->execute([
                ':campus_id' => $campusId,
                ':full_name' => strip_tags($fullName),
                ':email' => $email,
                ':phone' => trim($phone) !== '' ? trim($phone) : null,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $created++;
        }
        writeAudit($pdo, $staffId, 'staff', 'STAFF_IMPORT_STUDENTS_CSV', 'user', null, ['created' => $created, 'errors' => $errors]);
        jsonResponse(200, ['success' => true, 'message' => 'CSV import completed', 'data' => ['created' => $created, 'errors' => $errors]]);
    }

    if ($action === 'staff_batch_student_status' && $method === 'POST') {
        $staffId = requireStaffOrAdmin();
        $ids = $body['userIDs'] ?? [];
        $accountStatus = trim((string)($body['accountStatus'] ?? ''));
        if (!is_array($ids) || $ids === [] || !in_array($accountStatus, ['active', 'frozen', 'disabled'], true)) {
            jsonResponse(422, ['success' => false, 'message' => 'Invalid batch payload']);
        }
        $idInts = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if ($idInts === []) {
            jsonResponse(422, ['success' => false, 'message' => 'No valid student IDs']);
        }
        $placeholders = implode(',', array_fill(0, count($idInts), '?'));
        $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE role = 'student' AND id IN ({$placeholders})");
        $stmt->execute(array_merge([$accountStatus], $idInts));
        writeAudit($pdo, $staffId, 'staff', 'STAFF_BATCH_STUDENT_STATUS', 'user', null, ['count' => $stmt->rowCount(), 'status' => $accountStatus]);
        jsonResponse(200, ['success' => true, 'message' => 'Batch status updated', 'data' => ['updated' => $stmt->rowCount()]]);
    }

    jsonResponse(404, ['success' => false, 'message' => '找不到對應的 API action']);
} catch (Throwable $e) {
    app_log('rental_action', 'error', $e->getMessage(), [
        'exception' => $e::class,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'action' => $action,
        'method' => $method,
    ]);
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(500, ['success' => false, 'message' => '伺服器錯誤，請稍後再試']);
}
