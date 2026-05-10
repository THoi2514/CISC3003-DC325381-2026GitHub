<?php
declare(strict_types=1);

/**
 * 車輛在 vehicles.status 的語意（與 database/schema.sql ENUM 一致）：
 * - available = 管理員設為可於站點租借
 * - rented / maintenance / retired = 不應出現在使用者可租清單
 */
const VEHICLE_STATUS_RENTABLE = 'available';

function vehicle_is_rentable(string $status): bool
{
    return $status === VEHICLE_STATUS_RENTABLE;
}
