<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/rental_station_locale.php';

/**
 * 站點與可用車輛查詢 API
 * 支援篩選：
 * - stationID
 * - vehicleType (bicycle / scooter)
 * - brand
 */

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, ['success' => false, 'message' => '只允許 GET 請求']);
}

$stationID = isset($_GET['stationID']) ? (int)$_GET['stationID'] : null;
$vehicleType = isset($_GET['vehicleType']) ? trim((string)$_GET['vehicleType']) : null;
$brand = isset($_GET['brand']) ? trim((string)$_GET['brand']) : null;

$allowedTypes = ['bicycle', 'scooter'];
if ($vehicleType !== null && $vehicleType !== '' && !in_array($vehicleType, $allowedTypes, true)) {
    respond(422, ['success' => false, 'message' => 'vehicleType 只能是 bicycle 或 scooter']);
}

try {
    $hasLocale = rentalStationsHasLocaleColumns($pdo);

    $sql = $hasLocale
        ? '
        SELECT
            rs.id,
            rs.name,
            rs.name_zh_cn,
            rs.name_zh_tw,
            rs.latitude,
            rs.longitude,
            rs.capacity,
            SUM(CASE WHEN v.status = "available" THEN 1 ELSE 0 END) AS availableVehicles,
            COUNT(v.id) AS totalVehicles
        FROM rental_stations rs
        LEFT JOIN vehicles v ON v.station_id = rs.id
        LEFT JOIN vehicle_types vt ON v.type_id = vt.id
        LEFT JOIN brands b ON v.brand_id = b.id
        WHERE rs.status = "active"
    '
        : '
        SELECT
            rs.id,
            rs.name,
            rs.latitude,
            rs.longitude,
            rs.capacity,
            SUM(CASE WHEN v.status = "available" THEN 1 ELSE 0 END) AS availableVehicles,
            COUNT(v.id) AS totalVehicles
        FROM rental_stations rs
        LEFT JOIN vehicles v ON v.station_id = rs.id
        LEFT JOIN vehicle_types vt ON v.type_id = vt.id
        LEFT JOIN brands b ON v.brand_id = b.id
        WHERE rs.status = "active"
    ';

    $params = [];

    if ($stationID !== null && $stationID > 0) {
        $sql .= ' AND rs.id = :stationID ';
        $params[':stationID'] = $stationID;
    }

    if ($vehicleType !== null && $vehicleType !== '') {
        $sql .= ' AND vt.name = :vehicleType ';
        $params[':vehicleType'] = $vehicleType;
    }

    if ($brand !== null && $brand !== '') {
        $sql .= ' AND b.name = :brand ';
        $params[':brand'] = $brand;
    }

    $sql .= $hasLocale
        ? '
        GROUP BY rs.id, rs.name, rs.name_zh_cn, rs.name_zh_tw, rs.latitude, rs.longitude, rs.capacity
        ORDER BY rs.name ASC
    '
        : '
        GROUP BY rs.id, rs.name, rs.latitude, rs.longitude, rs.capacity
        ORDER BY rs.name ASC
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    if (!$hasLocale) {
        foreach ($rows as &$r) {
            $r['name_zh_cn'] = '';
            $r['name_zh_tw'] = '';
        }
        unset($r);
    }

    respond(200, [
        'success' => true,
        'count' => count($rows),
        'data' => $rows,
    ]);
} catch (Throwable $e) {
    respond(500, ['success' => false, 'message' => '讀取站點資料失敗']);
}
