-- =========================================================
-- Demo seed data for UM rental system
-- Usage:
--   1) Import database/schema.sql first
--   2) Import this file
-- Demo login password for all demo accounts: Password123
-- =========================================================

USE um_rental_system;

SET NAMES utf8mb4;

-- ---------------------------------------------------------
-- Users
-- ---------------------------------------------------------
INSERT INTO users (
    campus_id, full_name, role, email, phone, balance, password_hash,
    failed_login_attempts, lock_until, account_status
) VALUES
('s1000001', 'Alice Chan', 'student', 'alice.chan@um.edu.mo', '+853-6200-0001', 120.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('s1000002', 'Brian Leong', 'student', 'brian.leong@um.edu.mo', '+853-6200-0002', 80.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('s1000003', 'Cindy Ho', 'student', 'cindy.ho@um.edu.mo', '+853-6200-0003', 64.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('s1000004', 'Daniel Kuok', 'student', 'daniel.kuok@um.edu.mo', '+853-6200-0004', 45.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('s1000005', 'Evelyn Cheang', 'student', 'evelyn.cheang@um.edu.mo', '+853-6200-0005', 200.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('s1000006', 'Frankie Lei', 'student', 'frankie.lei@um.edu.mo', '+853-6200-0006', 90.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('s1000007', 'Grace Tam', 'student', 'grace.tam@um.edu.mo', '+853-6200-0007', 34.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('s1000008', 'Henry Vong', 'student', 'henry.vong@um.edu.mo', '+853-6200-0008', 56.50, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('s1000009', 'Iris Choi', 'student', 'iris.choi@um.edu.mo', '+853-6200-0009', 72.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('s1000010', 'Jason Ng', 'student', 'jason.ng@um.edu.mo', '+853-6200-0010', 110.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('t2000001', 'Dr. Maria Wong', 'staff', 'maria.wong@um.edu.mo', '+853-6200-1001', 150.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('v3000001', 'David Visitor', 'visitor', 'david.visitor@gmail.com', '+853-6200-2001', 60.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('dc325107', 'Ho Weng Hong', 'admin', 'dc325107@um.edu.mo', '+853-6200-9107', 9999.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    role = VALUES(role),
    phone = VALUES(phone),
    balance = VALUES(balance),
    account_status = VALUES(account_status);

INSERT INTO administrators (user_id, user_name, privilege)
SELECT u.id, 'um_super_admin', 'super_admin'
FROM users u
WHERE u.campus_id = 'dc325107'
ON DUPLICATE KEY UPDATE
    privilege = VALUES(privilege);

UPDATE users
SET role = 'staff'
WHERE role = 'admin' AND campus_id <> 'dc325107';

-- ---------------------------------------------------------
-- Stations (do not DROP — vehicles/orders reference rental_stations)
-- ---------------------------------------------------------
INSERT INTO rental_stations (name, name_zh_cn, name_zh_tw, latitude, longitude, capacity, status) VALUES
('Research Building (N21)', '科研大楼 (N21)', '科研大樓 (N21)', 22.134812, 113.545431, 26, 'active'),
('Sports Complex (N8)', '综合体育馆 (N8)', '綜合體育館 (N8)', 22.133423, 113.543076, 24, 'active'),
('Wu Yee Sun Library (E2)', '伍宜孙图书馆 (E2)', '伍宜孫圖書館 (E2)', 22.130586, 113.546032, 24, 'active'),
('Central Teaching Building (E6)', '中央教学楼 (E6)', '中央教學樓 (E6)', 22.1283300, 113.5446480, 22, 'active'),
('Science and Technology Building (E11)', '科技学院楼 (E11)', '科技學院樓 (E11)', 22.132445, 113.542634, 22, 'active'),
('Social Sciences and Humanities Building (E21)', '人文社科楼 (E21)', '人文社科樓 (E21)', 22.128934, 113.542384, 20, 'active'),
('Student Activity Centre (E31)', '学生活动中心 (E31)', '學生活動中心 (E31)', 22.126613, 113.544071, 22, 'active'),
('University Mall (S8)', '荟萃坊 (S8)', '薈萃坊 (S8)', 22.124521, 113.544074, 20, 'active')
ON DUPLICATE KEY UPDATE
    name_zh_cn = VALUES(name_zh_cn),
    name_zh_tw = VALUES(name_zh_tw),
    latitude = VALUES(latitude),
    longitude = VALUES(longitude),
    capacity = VALUES(capacity),
    status = VALUES(status);

-- ---------------------------------------------------------
-- Vehicles (bicycles + scooters)
-- ---------------------------------------------------------
INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0001', vt.id, b.id, 'available', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Giant' AND rs.name = 'Wu Yee Sun Library (E2)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0002', vt.id, b.id, 'available', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Xiaomi' AND rs.name = 'Social Sciences and Humanities Building (E21)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0003', vt.id, b.id, 'rented', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Ninebot' AND rs.name = 'University Mall (S8)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0004', vt.id, b.id, 'maintenance', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Xiaomi' AND rs.name = 'Sports Complex (N8)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0005', vt.id, b.id, 'available', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Ninebot' AND rs.name = 'Research Building (N21)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0006', vt.id, b.id, 'available', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Giant' AND rs.name = 'Wu Yee Sun Library (E2)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0007', vt.id, b.id, 'available', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Ninebot' AND rs.name = 'Social Sciences and Humanities Building (E21)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0008', vt.id, b.id, 'available', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Xiaomi' AND rs.name = 'University Mall (S8)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0009', vt.id, b.id, 'maintenance', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Giant' AND rs.name = 'Sports Complex (N8)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'BK-0010', vt.id, b.id, 'available', rs.id, NULL
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'bicycle' AND b.name = 'Xiaomi' AND rs.name = 'Research Building (N21)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1001', vt.id, b.id, 'available', rs.id, 92
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Xiaomi' AND rs.name = 'Wu Yee Sun Library (E2)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1002', vt.id, b.id, 'available', rs.id, 78
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Ninebot' AND rs.name = 'Social Sciences and Humanities Building (E21)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1003', vt.id, b.id, 'available', rs.id, 66
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Xiaomi' AND rs.name = 'University Mall (S8)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1004', vt.id, b.id, 'rented', rs.id, 54
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Ninebot' AND rs.name = 'Sports Complex (N8)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1005', vt.id, b.id, 'available', rs.id, 88
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Xiaomi' AND rs.name = 'Research Building (N21)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1006', vt.id, b.id, 'available', rs.id, 95
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Ninebot' AND rs.name = 'Wu Yee Sun Library (E2)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1007', vt.id, b.id, 'available', rs.id, 82
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Xiaomi' AND rs.name = 'Social Sciences and Humanities Building (E21)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1008', vt.id, b.id, 'available', rs.id, 76
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Ninebot' AND rs.name = 'University Mall (S8)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1009', vt.id, b.id, 'maintenance', rs.id, 61
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Xiaomi' AND rs.name = 'Sports Complex (N8)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1010', vt.id, b.id, 'available', rs.id, 89
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Ninebot' AND rs.name = 'Research Building (N21)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

-- Ensure existing bicycle records also refresh to mixed brands when re-seeding.
UPDATE vehicles v
JOIN brands b ON b.name = CASE v.serial_no
    WHEN 'BK-0002' THEN 'Xiaomi'
    WHEN 'BK-0003' THEN 'Ninebot'
    WHEN 'BK-0004' THEN 'Xiaomi'
    WHEN 'BK-0005' THEN 'Ninebot'
    WHEN 'BK-0007' THEN 'Ninebot'
    WHEN 'BK-0008' THEN 'Xiaomi'
    WHEN 'BK-0010' THEN 'Xiaomi'
    ELSE 'Giant'
END
SET v.brand_id = b.id
WHERE v.serial_no IN (
    'BK-0001','BK-0002','BK-0003','BK-0004','BK-0005',
    'BK-0006','BK-0007','BK-0008','BK-0009','BK-0010'
);

-- ---------------------------------------------------------
-- Demo orders (2 completed, 1 active)
-- ---------------------------------------------------------
INSERT INTO rental_orders (
    user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time,
    duration_minutes, fee, status, note
)
SELECT
    u.id,
    v.id,
    rs_start.id,
    rs_end.id,
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 35 MINUTE,
    35,
    8.50,
    'completed',
    'Demo completed order'
FROM users u
JOIN vehicles v ON v.serial_no = 'BK-0001'
JOIN rental_stations rs_start ON rs_start.name = 'Wu Yee Sun Library (E2)'
JOIN rental_stations rs_end ON rs_end.name = 'Research Building (N21)'
WHERE u.campus_id = 's1000001'
AND NOT EXISTS (
    SELECT 1 FROM rental_orders ro
    WHERE ro.user_id = u.id AND ro.vehicle_id = v.id AND ro.start_time = DATE_SUB(NOW(), INTERVAL 3 DAY)
);

INSERT INTO rental_orders (
    user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time,
    duration_minutes, fee, status, note
)
SELECT
    u.id,
    v.id,
    rs_start.id,
    rs_end.id,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 52 MINUTE,
    52,
    16.20,
    'completed',
    'Demo completed order'
FROM users u
JOIN vehicles v ON v.serial_no = 'SC-1002'
JOIN rental_stations rs_start ON rs_start.name = 'Social Sciences and Humanities Building (E21)'
JOIN rental_stations rs_end ON rs_end.name = 'Sports Complex (N8)'
WHERE u.campus_id = 't2000001'
AND NOT EXISTS (
    SELECT 1 FROM rental_orders ro
    WHERE ro.user_id = u.id AND ro.vehicle_id = v.id AND ro.start_time = DATE_SUB(NOW(), INTERVAL 1 DAY)
);

INSERT INTO rental_orders (
    user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time,
    duration_minutes, fee, status, note
)
SELECT
    u.id,
    v.id,
    rs_start.id,
    NULL,
    DATE_SUB(NOW(), INTERVAL 20 MINUTE),
    NULL,
    NULL,
    0.00,
    'active',
    'Demo active order'
FROM users u
JOIN vehicles v ON v.serial_no = 'SC-1004'
JOIN rental_stations rs_start ON rs_start.name = 'Sports Complex (N8)'
WHERE u.campus_id = 's1000002'
AND NOT EXISTS (
    SELECT 1 FROM rental_orders ro
    WHERE ro.user_id = u.id AND ro.status = 'active'
);

-- ---------------------------------------------------------
-- Extra KPI demo orders (more stations + recent days; idempotent by note)
-- Report KPI filters by DATE(start_time) and groups by start_station_id.
-- ---------------------------------------------------------
INSERT INTO rental_orders (
    user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time,
    duration_minutes, fee, status, note
)
SELECT u.id, v.id, rs_start.id, rs_end.id,
    DATE_SUB(NOW(), INTERVAL 4 DAY),
    DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 42 MINUTE,
    42, 11.00, 'completed', 'KPI demo spread E6'
FROM users u
JOIN vehicles v ON v.serial_no = 'BK-0005'
JOIN rental_stations rs_start ON rs_start.name = 'Central Teaching Building (E6)'
JOIN rental_stations rs_end ON rs_end.name = 'Student Activity Centre (E31)'
WHERE u.campus_id = 's1000003'
AND NOT EXISTS (SELECT 1 FROM rental_orders ro WHERE ro.note = 'KPI demo spread E6');

INSERT INTO rental_orders (
    user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time,
    duration_minutes, fee, status, note
)
SELECT u.id, v.id, rs_start.id, rs_end.id,
    DATE_SUB(NOW(), INTERVAL 5 DAY),
    DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 38 MINUTE,
    38, 9.50, 'completed', 'KPI demo spread E11'
FROM users u
JOIN vehicles v ON v.serial_no = 'BK-0006'
JOIN rental_stations rs_start ON rs_start.name = 'Science and Technology Building (E11)'
JOIN rental_stations rs_end ON rs_end.name = 'Central Teaching Building (E6)'
WHERE u.campus_id = 's1000004'
AND NOT EXISTS (SELECT 1 FROM rental_orders ro WHERE ro.note = 'KPI demo spread E11');

INSERT INTO rental_orders (
    user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time,
    duration_minutes, fee, status, note
)
SELECT u.id, v.id, rs_start.id, rs_end.id,
    DATE_SUB(NOW(), INTERVAL 6 DAY),
    DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 55 MINUTE,
    55, 14.25, 'completed', 'KPI demo spread E31'
FROM users u
JOIN vehicles v ON v.serial_no = 'SC-1005'
JOIN rental_stations rs_start ON rs_start.name = 'Student Activity Centre (E31)'
JOIN rental_stations rs_end ON rs_end.name = 'University Mall (S8)'
WHERE u.campus_id = 's1000005'
AND NOT EXISTS (SELECT 1 FROM rental_orders ro WHERE ro.note = 'KPI demo spread E31');

INSERT INTO rental_orders (
    user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time,
    duration_minutes, fee, status, note
)
SELECT u.id, v.id, rs_start.id, rs_end.id,
    DATE_SUB(NOW(), INTERVAL 8 DAY),
    DATE_SUB(NOW(), INTERVAL 8 DAY) + INTERVAL 29 MINUTE,
    29, 7.75, 'completed', 'KPI demo spread S8'
FROM users u
JOIN vehicles v ON v.serial_no = 'SC-1006'
JOIN rental_stations rs_start ON rs_start.name = 'University Mall (S8)'
JOIN rental_stations rs_end ON rs_end.name = 'Research Building (N21)'
WHERE u.campus_id = 's1000006'
AND NOT EXISTS (SELECT 1 FROM rental_orders ro WHERE ro.note = 'KPI demo spread S8');

INSERT INTO rental_orders (
    user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time,
    duration_minutes, fee, status, note
)
SELECT u.id, v.id, rs_start.id, rs_end.id,
    DATE_SUB(NOW(), INTERVAL 10 DAY),
    DATE_SUB(NOW(), INTERVAL 10 DAY) + INTERVAL 61 MINUTE,
    61, 17.40, 'completed', 'KPI demo spread N21'
FROM users u
JOIN vehicles v ON v.serial_no = 'BK-0007'
JOIN rental_stations rs_start ON rs_start.name = 'Research Building (N21)'
JOIN rental_stations rs_end ON rs_end.name = 'Wu Yee Sun Library (E2)'
WHERE u.campus_id = 's1000007'
AND NOT EXISTS (SELECT 1 FROM rental_orders ro WHERE ro.note = 'KPI demo spread N21');

INSERT INTO rental_orders (
    user_id, vehicle_id, start_station_id, end_station_id, start_time, end_time,
    duration_minutes, fee, status, note
)
SELECT u.id, v.id, rs_start.id, rs_end.id,
    DATE_SUB(NOW(), INTERVAL 11 DAY),
    DATE_SUB(NOW(), INTERVAL 11 DAY) + INTERVAL 48 MINUTE,
    48, 13.10, 'completed', 'KPI demo spread E2 extra'
FROM users u
JOIN vehicles v ON v.serial_no = 'BK-0008'
JOIN rental_stations rs_start ON rs_start.name = 'Wu Yee Sun Library (E2)'
JOIN rental_stations rs_end ON rs_end.name = 'Science and Technology Building (E11)'
WHERE u.campus_id = 's1000008'
AND NOT EXISTS (SELECT 1 FROM rental_orders ro WHERE ro.note = 'KPI demo spread E2 extra');

-- ---------------------------------------------------------
-- Optional sample forum/feedback data
-- ---------------------------------------------------------
INSERT INTO forum_posts (user_id, title, category, content, status)
SELECT u.id, 'Best route from library to dorm?', 'experience', 'Any shortcut recommendation for evening rides?', 'visible'
FROM users u
WHERE u.campus_id = 's1000001'
AND NOT EXISTS (
    SELECT 1 FROM forum_posts fp WHERE fp.user_id = u.id AND fp.title = 'Best route from library to dorm?'
);

INSERT INTO feedbacks (user_id, title, description, category, status)
SELECT u.id, 'Scooter brake feels loose', 'Vehicle SC-1004 had weak brake response near Sports Complex (N8).', 'vehicle', 'open'
FROM users u
WHERE u.campus_id = 's1000002'
AND NOT EXISTS (
    SELECT 1 FROM feedbacks f WHERE f.user_id = u.id AND f.title = 'Scooter brake feels loose'
);

-- ---------------------------------------------------------
-- Wallet transaction demo data (requires migration_mvp_phase1.sql)
-- ---------------------------------------------------------
INSERT INTO wallet_transactions (user_id, type, amount, balance_after, reference_type, reference_id, note)
SELECT u.id, 'topup', 150.00, u.balance, NULL, NULL, 'Initial top-up for demo'
FROM users u
WHERE u.campus_id = 's1000001'
AND NOT EXISTS (
    SELECT 1 FROM wallet_transactions wt
    WHERE wt.user_id = u.id AND wt.type = 'topup' AND wt.note = 'Initial top-up for demo'
);

INSERT INTO wallet_transactions (user_id, type, amount, balance_after, reference_type, reference_id, note)
SELECT u.id, 'rental_charge', -16.20, u.balance, 'rental_order', ro.id, 'Demo rental charge'
FROM users u
INNER JOIN rental_orders ro ON ro.user_id = u.id AND ro.status = 'completed'
WHERE u.campus_id = 't2000001'
AND NOT EXISTS (
    SELECT 1 FROM wallet_transactions wt
    WHERE wt.user_id = u.id AND wt.reference_type = 'rental_order' AND wt.reference_id = ro.id
);
