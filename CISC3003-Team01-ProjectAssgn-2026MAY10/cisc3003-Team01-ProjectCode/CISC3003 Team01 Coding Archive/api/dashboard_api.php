<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/vehicle_status.php';
require_once __DIR__ . '/../includes/rental_station_locale.php';
require_once __DIR__ . '/../includes/rental_limits.php';
require_once __DIR__ . '/../includes/rental_datetime.php';
require_once __DIR__ . '/../includes/app_log.php';

startAuthSession();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!roleCanRent((string)($_SESSION['role'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'This account cannot use the rental service.', 'message_key' => 'errRentalNotAllowed']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = db();
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    verifyCsrfOrFail();
    rateLimit('dashboard_api:' . $action . ':user:' . $userId . ':ip:' . clientIp(), 120, 300, 120);
}

/**
 * Reads JSON body safely for POST operations.
 */
function readBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Writes JSON response with status code.
 */
function respond(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

/** Same bounds as dashboard Leaflet UM_BOUNDS (must stay in sync). */
function umCampusCoordsOk(float $lat, float $lng): bool
{
    return $lat >= 22.1150 && $lat <= 22.1455 && $lng >= 113.5250 && $lng <= 113.5600;
}

if ($action === 'get_dashboard' && $method === 'GET') {
    try {
    $stationId = (int)($_GET['stationID'] ?? 0);
    $brandId = (int)($_GET['brandID'] ?? 0);
    $minBattery = (int)($_GET['minBattery'] ?? 0);
    $bikePage = max(1, (int)($_GET['bikePage'] ?? 1));
    $bikePerPage = max(5, min(50, (int)($_GET['bikePerPage'] ?? 10)));
    $orderPage = max(1, (int)($_GET['orderPage'] ?? 1));
    $orderPerPage = max(5, min(50, (int)($_GET['orderPerPage'] ?? 10)));

    $hasLocale = rentalStationsHasLocaleColumns($pdo);

    if ($hasLocale) {
        $stations = $pdo->query('
            SELECT
              s.id,
              s.name,
              s.name_zh_cn,
              s.name_zh_tw,
              s.capacity,
              s.latitude,
              s.longitude,
              s.status AS station_status,
              SUM(CASE WHEN v.status = "available" AND vt.name = "bicycle" THEN 1 ELSE 0 END) AS available_bicycles,
              SUM(CASE WHEN v.status = "available" AND vt.name = "scooter" THEN 1 ELSE 0 END) AS available_scooters
            FROM rental_stations s
            LEFT JOIN vehicles v ON v.station_id = s.id
            LEFT JOIN vehicle_types vt ON vt.id = v.type_id
            WHERE s.status = "active"
            GROUP BY s.id, s.name, s.name_zh_cn, s.name_zh_tw, s.capacity, s.latitude, s.longitude, s.status
            ORDER BY s.name
        ')->fetchAll();
    } else {
        $stations = $pdo->query('
            SELECT
              s.id,
              s.name,
              s.capacity,
              s.latitude,
              s.longitude,
              s.status AS station_status,
              SUM(CASE WHEN v.status = "available" AND vt.name = "bicycle" THEN 1 ELSE 0 END) AS available_bicycles,
              SUM(CASE WHEN v.status = "available" AND vt.name = "scooter" THEN 1 ELSE 0 END) AS available_scooters
            FROM rental_stations s
            LEFT JOIN vehicles v ON v.station_id = s.id
            LEFT JOIN vehicle_types vt ON vt.id = v.type_id
            WHERE s.status = "active"
            GROUP BY s.id, s.name, s.capacity, s.latitude, s.longitude, s.status
            ORDER BY s.name
        ')->fetchAll();
        foreach ($stations as &$stRow) {
            $stRow['name_zh_cn'] = '';
            $stRow['name_zh_tw'] = '';
        }
        unset($stRow);
    }

    $orderCountStmt = $pdo->prepare('
        SELECT COUNT(*) AS total
        FROM rental_orders o
        WHERE o.user_id = :uid
    ');
    $orderCountStmt->execute([':uid' => $userId]);
    $ordersTotal = (int)($orderCountStmt->fetch()['total'] ?? 0);
    $orderOffset = ($orderPage - 1) * $orderPerPage;

    $ordersStmt = $pdo->prepare('
        SELECT
          o.id,
          CONCAT("RO", LPAD(o.id, 6, "0")) AS order_no,
          o.start_time,
          o.end_time,
          o.duration_minutes,
          o.fee,
          o.status,
          vt.name AS vehicle_type,
          b.name AS brand
        FROM rental_orders o
        INNER JOIN vehicles v ON o.vehicle_id = v.id
        INNER JOIN vehicle_types vt ON vt.id = v.type_id
        INNER JOIN brands b ON b.id = v.brand_id
        WHERE o.user_id = :uid
        ORDER BY o.id DESC
        LIMIT :offset, :per_page
    ');
    $ordersStmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $ordersStmt->bindValue(':offset', $orderOffset, PDO::PARAM_INT);
    $ordersStmt->bindValue(':per_page', $orderPerPage, PDO::PARAM_INT);
    $ordersStmt->execute();
    $orders = $ordersStmt->fetchAll();

    $bikeWhere = ['v.status = "available"', '(vt.name = "bicycle" OR vt.name = "scooter")'];
    $bikeParams = [];
    if ($stationId > 0) {
        $bikeWhere[] = 'v.station_id = :station_id';
        $bikeParams[':station_id'] = $stationId;
    }
    if ($brandId > 0) {
        $bikeWhere[] = 'v.brand_id = :brand_id';
        $bikeParams[':brand_id'] = $brandId;
    }
    if ($minBattery > 0) {
        $bikeWhere[] = '(v.battery_level IS NULL OR v.battery_level >= :min_battery)';
        $bikeParams[':min_battery'] = $minBattery;
    }
    $whereSql = implode(' AND ', $bikeWhere);

    $bikeCountStmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM vehicles v
        INNER JOIN vehicle_types vt ON vt.id = v.type_id
        WHERE {$whereSql}
    ");
    foreach ($bikeParams as $k => $v) {
        $bikeCountStmt->bindValue($k, $v, PDO::PARAM_INT);
    }
    $bikeCountStmt->execute();
    $bikesTotal = (int)($bikeCountStmt->fetch()['total'] ?? 0);
    $bikeOffset = ($bikePage - 1) * $bikePerPage;

    $bicycleSql = $hasLocale
        ? "
        SELECT
          v.id,
          v.station_id,
          v.serial_no,
          v.status,
          v.battery_level,
          b.name AS brand,
          s.name AS station_name,
          s.name_zh_cn AS station_name_zh_cn,
          s.name_zh_tw AS station_name_zh_tw,
          vt.name AS vehicle_type,
          (vt.price_per_30_min * b.price_multiplier) AS price_per_30_min
        FROM vehicles v
        INNER JOIN vehicle_types vt ON vt.id = v.type_id
        INNER JOIN brands b ON b.id = v.brand_id
        LEFT JOIN rental_stations s ON s.id = v.station_id
        WHERE {$whereSql}
        ORDER BY v.id DESC
        LIMIT :offset, :per_page
    "
        : "
        SELECT
          v.id,
          v.station_id,
          v.serial_no,
          v.status,
          v.battery_level,
          b.name AS brand,
          s.name AS station_name,
          vt.name AS vehicle_type,
          (vt.price_per_30_min * b.price_multiplier) AS price_per_30_min
        FROM vehicles v
        INNER JOIN vehicle_types vt ON vt.id = v.type_id
        INNER JOIN brands b ON b.id = v.brand_id
        LEFT JOIN rental_stations s ON s.id = v.station_id
        WHERE {$whereSql}
        ORDER BY v.id DESC
        LIMIT :offset, :per_page
    ";
    $bicycleStmt = $pdo->prepare($bicycleSql);
    foreach ($bikeParams as $k => $v) {
        $bicycleStmt->bindValue($k, $v, PDO::PARAM_INT);
    }
    $bicycleStmt->bindValue(':offset', $bikeOffset, PDO::PARAM_INT);
    $bicycleStmt->bindValue(':per_page', $bikePerPage, PDO::PARAM_INT);
    $bicycleStmt->execute();
    $availableBicycles = $bicycleStmt->fetchAll();
    if (!$hasLocale) {
        foreach ($availableBicycles as &$abRow) {
            $abRow['station_name_zh_cn'] = '';
            $abRow['station_name_zh_tw'] = '';
        }
        unset($abRow);
    }
    $brands = $pdo->query('SELECT id, name FROM brands ORDER BY name')->fetchAll();

    $activeOrderSql = $hasLocale
        ? '
        SELECT
          o.id,
          CONCAT("RO", LPAD(o.id, 6, "0")) AS order_no,
          o.start_time,
          o.end_time,
          o.duration_minutes,
          o.fee,
          o.status,
          v.id AS vehicle_id,
          v.serial_no,
          vt.name AS vehicle_type,
          b.name AS brand,
          s.name AS station_name,
          s.name_zh_cn AS station_name_zh_cn,
          s.name_zh_tw AS station_name_zh_tw,
          (vt.price_per_30_min * b.price_multiplier) AS price_per_30_min
        FROM rental_orders o
        INNER JOIN vehicles v ON o.vehicle_id = v.id
        INNER JOIN vehicle_types vt ON vt.id = v.type_id
        INNER JOIN brands b ON b.id = v.brand_id
        LEFT JOIN rental_stations s ON s.id = o.start_station_id
        WHERE o.user_id = :uid AND o.status = "active"
        ORDER BY o.id DESC
        LIMIT 1
    '
        : '
        SELECT
          o.id,
          CONCAT("RO", LPAD(o.id, 6, "0")) AS order_no,
          o.start_time,
          o.end_time,
          o.duration_minutes,
          o.fee,
          o.status,
          v.id AS vehicle_id,
          v.serial_no,
          vt.name AS vehicle_type,
          b.name AS brand,
          s.name AS station_name,
          (vt.price_per_30_min * b.price_multiplier) AS price_per_30_min
        FROM rental_orders o
        INNER JOIN vehicles v ON o.vehicle_id = v.id
        INNER JOIN vehicle_types vt ON vt.id = v.type_id
        INNER JOIN brands b ON b.id = v.brand_id
        LEFT JOIN rental_stations s ON s.id = o.start_station_id
        WHERE o.user_id = :uid AND o.status = "active"
        ORDER BY o.id DESC
        LIMIT 1
    ';
    $activeOrderStmt = $pdo->prepare($activeOrderSql);
    $activeOrderStmt->execute([':uid' => $userId]);
    $activeOrderRow = $activeOrderStmt->fetch();
    $activeOrder = $activeOrderRow !== false ? $activeOrderRow : null;
    if ($activeOrder !== null && !$hasLocale) {
        $activeOrder['station_name_zh_cn'] = '';
        $activeOrder['station_name_zh_tw'] = '';
    }
    if ($activeOrder !== null && !empty($activeOrder['start_time'])) {
        $activeOrder['start_time'] = rental_start_time_for_js((string)$activeOrder['start_time']);
    }

    respond(200, [
        'success' => true,
        'data' => [
            'stations' => $stations,
            'brands' => $brands,
            'orders' => $orders,
            'availableBicycles' => $availableBicycles,
            'filters' => [
                'stationID' => $stationId,
                'brandID' => $brandId,
                'minBattery' => $minBattery,
            ],
            'pagination' => [
                'bikes' => [
                    'page' => $bikePage,
                    'perPage' => $bikePerPage,
                    'total' => $bikesTotal,
                    'totalPages' => (int)max(1, ceil($bikesTotal / $bikePerPage)),
                ],
                'orders' => [
                    'page' => $orderPage,
                    'perPage' => $orderPerPage,
                    'total' => $ordersTotal,
                    'totalPages' => (int)max(1, ceil($ordersTotal / $orderPerPage)),
                ],
            ],
            'activeOrder' => $activeOrder,
            'rentalPolicy' => [
                'maxRentalMinutes' => RENTAL_MAX_MINUTES,
                'overtimePenaltyMop' => RENTAL_OVERTIME_PENALTY_MOP,
            ],
            'mapUi' => [
                'canEditStationCoordinates' => roleCanEditRentalStationCoordinates((string)($_SESSION['role'] ?? '')),
            ],
        ],
    ]);
    } catch (Throwable $e) {
        app_log('dashboard_api', 'error', $e->getMessage(), [
            'exception' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'action' => 'get_dashboard',
            'user_id' => $userId,
        ]);
        respond(500, [
            'success' => false,
            'message' => 'Failed to load dashboard data.',
            'message_key' => 'errGetDashboard',
        ]);
    }
}

if ($action === 'update_station_location' && $method === 'POST') {
    if (!roleCanEditRentalStationCoordinates((string)($_SESSION['role'] ?? ''))) {
        respond(403, ['success' => false, 'message' => 'Staff or admin permission required', 'message_key' => 'errStationEditForbidden']);
    }
    $payload = readBody();
    $stationId = (int)($payload['station_id'] ?? 0);
    $lat = isset($payload['latitude']) ? round((float)$payload['latitude'], 7) : null;
    $lng = isset($payload['longitude']) ? round((float)$payload['longitude'], 7) : null;
    if ($stationId <= 0 || $lat === null || $lng === null) {
        respond(422, ['success' => false, 'message' => 'station_id, latitude, longitude required']);
    }
    if (!umCampusCoordsOk((float)$lat, (float)$lng)) {
        respond(422, ['success' => false, 'message' => 'Coordinates outside campus map bounds', 'message_key' => 'errMapOutOfBounds']);
    }
    try {
        $chk = $pdo->prepare('SELECT id FROM rental_stations WHERE id = :id AND status = "active" LIMIT 1');
        $chk->execute([':id' => $stationId]);
        if (!$chk->fetch()) {
            respond(404, ['success' => false, 'message' => 'Station not found or inactive']);
        }
        $upd = $pdo->prepare('UPDATE rental_stations SET latitude = :la, longitude = :ln WHERE id = :id');
        $upd->execute([':la' => $lat, ':ln' => $lng, ':id' => $stationId]);
        respond(200, [
            'success' => true,
            'message' => 'Station location updated',
            'data' => [
                'station_id' => $stationId,
                'latitude' => (float)$lat,
                'longitude' => (float)$lng,
            ],
        ]);
    } catch (Throwable $e) {
        respond(500, ['success' => false, 'message' => 'Failed to update station']);
    }
}

if ($action === 'rent' && $method === 'POST') {
    $payload = readBody();
    $vehicleId = (int)($payload['vehicle_id'] ?? 0);
    if ($vehicleId <= 0) {
        respond(422, ['success' => false, 'message' => 'vehicle_id is required']);
    }

    try {
        $pdo->beginTransaction();

        $userStmt = $pdo->prepare('SELECT id, balance FROM users WHERE id = :uid FOR UPDATE');
        $userStmt->execute([':uid' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            $pdo->rollBack();
            respond(404, ['success' => false, 'message' => 'User not found']);
        }

        $activeStmt = $pdo->prepare('SELECT id FROM rental_orders WHERE user_id = :uid AND status = "active" LIMIT 1 FOR UPDATE');
        $activeStmt->execute([':uid' => $userId]);
        if ($activeStmt->fetch()) {
            $pdo->rollBack();
            respond(409, ['success' => false, 'message' => 'User already has active order']);
        }

        $pendingStmt = $pdo->prepare('SELECT id FROM rental_orders WHERE user_id = :uid AND status = "payment_pending" LIMIT 1 FOR UPDATE');
        $pendingStmt->execute([':uid' => $userId]);
        if ($pendingStmt->fetch()) {
            $pdo->rollBack();
            respond(409, ['success' => false, 'message' => 'User has payment pending order and cannot start a new rental']);
        }

        $vehicleStmt = $pdo->prepare('
            SELECT
                v.id,
                v.station_id,
                v.status,
                (vt.price_per_30_min * b.price_multiplier) AS price_per_30_min
            FROM vehicles v
            INNER JOIN vehicle_types vt ON vt.id = v.type_id
            INNER JOIN brands b ON b.id = v.brand_id
            WHERE v.id = :vid
            FOR UPDATE
        ');
        $vehicleStmt->execute([':vid' => $vehicleId]);
        $vehicle = $vehicleStmt->fetch();
        if (!$vehicle || !vehicle_is_rentable((string)$vehicle['status'])) {
            $pdo->rollBack();
            respond(409, ['success' => false, 'message' => 'Vehicle is not available']);
        }

        $minBalance = (float)$vehicle['price_per_30_min'];
        if ((float)$user['balance'] < $minBalance) {
            $pdo->rollBack();
            respond(402, [
                'success' => false,
                'code' => 'INSUFFICIENT_BALANCE',
                'message_key' => 'errInsufficientBalance',
                'message' => 'Insufficient wallet balance',
            ]);
        }

        $insertOrder = $pdo->prepare('
            INSERT INTO rental_orders (user_id, vehicle_id, start_station_id, start_time, fee, status)
            VALUES (:uid, :vid, :sid, :start_time, 0.00, "active")
        ');
        $insertOrder->execute([
            ':uid' => $userId,
            ':vid' => $vehicleId,
            ':sid' => (int)$vehicle['station_id'],
            ':start_time' => date('Y-m-d H:i:s'),
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $userInfoStmt = $pdo->prepare('SELECT id, campus_id, full_name, phone FROM users WHERE id = :uid');
        $userInfoStmt->execute([':uid' => $userId]);
        $userInfo = $userInfoStmt->fetch();

        $updateVehicle = $pdo->prepare('UPDATE vehicles SET status = "rented", station_id = NULL WHERE id = :vid');
        $updateVehicle->execute([':vid' => $vehicleId]);

        $pdo->commit();
        $stRow = $pdo->prepare('SELECT start_time FROM rental_orders WHERE id = :id LIMIT 1');
        $stRow->execute([':id' => $orderId]);
        $stFetched = $stRow->fetch();
        $startOut = $stFetched && !empty($stFetched['start_time'])
            ? rental_start_time_for_js((string)$stFetched['start_time'])
            : date('c');
        respond(200, [
            'success' => true,
            'message' => 'Vehicle unlocked successfully',
            'data' => [
                'order_id' => $orderId,
                'vehicle_id' => $vehicleId,
                'start_time' => $startOut,
                'user_id' => (int)($userInfo['id'] ?? 0),
                'campus_id' => (string)($userInfo['campus_id'] ?? ''),
                'full_name' => (string)($userInfo['full_name'] ?? ''),
                'phone' => (string)($userInfo['phone'] ?? ''),
            ],
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        respond(500, ['success' => false, 'message' => 'Failed to start rental']);
    }
}

if ($action === 'return' && $method === 'POST') {
    $payload = readBody();
    $returnStationId = (int)($payload['return_station_id'] ?? 0);
    if ($returnStationId <= 0) {
        respond(422, ['success' => false, 'message' => 'return_station_id is required']);
    }

    try {
        $pdo->beginTransaction();

        $userStmt = $pdo->prepare('SELECT id, balance FROM users WHERE id = :uid FOR UPDATE');
        $userStmt->execute([':uid' => $userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            $pdo->rollBack();
            respond(404, ['success' => false, 'message' => 'User not found']);
        }

        $orderStmt = $pdo->prepare('
            SELECT id, vehicle_id, start_time, status
            FROM rental_orders
            WHERE user_id = :uid AND status = "active"
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ');
        $orderStmt->execute([':uid' => $userId]);
        $order = $orderStmt->fetch();
        if (!$order) {
            $pdo->rollBack();
            respond(409, ['success' => false, 'message' => 'No active order found']);
        }

        $stationStmt = $pdo->prepare('SELECT id, capacity, status FROM rental_stations WHERE id = :sid FOR UPDATE');
        $stationStmt->execute([':sid' => $returnStationId]);
        $station = $stationStmt->fetch();
        if (!$station || $station['status'] !== 'active') {
            $pdo->rollBack();
            respond(404, ['success' => false, 'message' => 'Return station is unavailable']);
        }

        $lockRowsStmt = $pdo->prepare('
            SELECT id
            FROM vehicles
            WHERE station_id = :sid
              AND status IN ("available", "maintenance")
            FOR UPDATE
        ');
        $lockRowsStmt->execute([':sid' => $returnStationId]);
        $occupied = count($lockRowsStmt->fetchAll());
        if ($occupied >= (int)$station['capacity']) {
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
            $alternatives = $alternativeStmt ? $alternativeStmt->fetchAll() : [];

            $pdo->rollBack();
            respond(409, [
                'success' => false,
                'code' => 'STATION_FULL',
                'message' => 'Station is full, please choose another station',
                'message_key' => 'errStationFull',
                'data' => [
                    'return_station_id' => $returnStationId,
                    'alternatives' => $alternatives,
                ],
            ]);
        }

        $vehicleStmt = $pdo->prepare('
            SELECT
                vehicles.id,
                (vt.price_per_30_min * b.price_multiplier) AS price_per_30_min
            FROM vehicles
            INNER JOIN vehicle_types vt ON vt.id = vehicles.type_id
            INNER JOIN brands b ON b.id = vehicles.brand_id
            WHERE vehicles.id = :vid
            FOR UPDATE
        ');
        $vehicleStmt->execute([':vid' => (int)$order['vehicle_id']]);
        $vehicle = $vehicleStmt->fetch();
        if (!$vehicle) {
            $pdo->rollBack();
            respond(404, ['success' => false, 'message' => 'Vehicle not found']);
        }

        $duration = max(1, (int)ceil((time() - strtotime((string)$order['start_time'])) / 60));
        $baseFee = (float)ceil($duration / 30) * (float)$vehicle['price_per_30_min'];
        $feeParts = rental_fee_with_overtime($baseFee, $duration);
        $fee = $feeParts['total'];

        $status = 'completed';
        if ((float)$user['balance'] >= $fee) {
            $deduct = $pdo->prepare('UPDATE users SET balance = balance - :fee WHERE id = :uid');
            $deduct->execute([':fee' => $fee, ':uid' => $userId]);
        } else {
            $status = 'payment_pending';
        }

        $updOrder = $pdo->prepare('
            UPDATE rental_orders
            SET end_station_id = :sid,
                end_time = NOW(),
                duration_minutes = :duration,
                fee = :fee,
                status = :status
            WHERE id = :oid
        ');
        $updOrder->execute([
            ':sid' => $returnStationId,
            ':duration' => $duration,
            ':fee' => $fee,
            ':status' => $status,
            ':oid' => (int)$order['id'],
        ]);

        $updVehicle = $pdo->prepare('UPDATE vehicles SET status = "available", station_id = :sid WHERE id = :vid');
        $updVehicle->execute([':sid' => $returnStationId, ':vid' => (int)$order['vehicle_id']]);

        $userInfoStmt = $pdo->prepare('SELECT id, campus_id, full_name, phone FROM users WHERE id = :uid');
        $userInfoStmt->execute([':uid' => $userId]);
        $userInfo = $userInfoStmt->fetch();
        $endedAt = date('Y-m-d H:i:s');

        $pdo->commit();
        respond(200, [
            'success' => true,
            'message' => 'Return completed',
            'receipt' => [
                'order_id' => (int)$order['id'],
                'duration_minutes' => $duration,
                'base_fee_mop' => $feeParts['base'],
                'overtime_penalty_mop' => $feeParts['penalty'],
                'fee' => round($fee, 2),
                'status' => $status,
                'end_time' => $endedAt,
                'user_id' => (int)($userInfo['id'] ?? 0),
                'campus_id' => (string)($userInfo['campus_id'] ?? ''),
                'full_name' => (string)($userInfo['full_name'] ?? ''),
                'phone' => (string)($userInfo['phone'] ?? ''),
            ],
        ]);
    } catch (Throwable $e) {
        app_log('dashboard_api', 'error', $e->getMessage(), [
            'exception' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'action' => 'complete_return',
            'user_id' => $userId,
        ]);
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        respond(500, ['success' => false, 'message' => 'Failed to complete return']);
    }
}

respond(404, ['success' => false, 'message' => 'Unsupported action']);
