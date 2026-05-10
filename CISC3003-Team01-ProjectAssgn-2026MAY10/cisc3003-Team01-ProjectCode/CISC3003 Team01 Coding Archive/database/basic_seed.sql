-- =========================================================
-- UM Rental System - Basic Seed Data
-- Requires: database/basic_schema.sql
-- Demo password for all users: Password123
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
('t2000001', 'Dr. Maria Wong', 'staff', 'maria.wong@um.edu.mo', '+853-6200-1001', 150.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active'),
('dc325107', 'Ho Weng Hong', 'admin', 'dc325107@um.edu.mo', '+853-6200-9107', 9999.00, '$2y$10$1RYWJalKlvaplh3MjYTm4.v71hoItMxq4LvZtpW3paMGnufCDmvF2', 0, NULL, 'active')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    role = VALUES(role),
    phone = VALUES(phone),
    balance = VALUES(balance),
    account_status = VALUES(account_status);

-- ---------------------------------------------------------
-- Stations
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
-- Vehicles (minimum runnable set)
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
SELECT 'SC-1001', vt.id, b.id, 'available', rs.id, 92
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Xiaomi' AND rs.name = 'Wu Yee Sun Library (E2)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

INSERT INTO vehicles (serial_no, type_id, brand_id, status, station_id, battery_level)
SELECT 'SC-1002', vt.id, b.id, 'available', rs.id, 78
FROM vehicle_types vt, brands b, rental_stations rs
WHERE vt.name = 'scooter' AND b.name = 'Ninebot' AND rs.name = 'University Mall (S8)'
ON DUPLICATE KEY UPDATE status = VALUES(status), station_id = VALUES(station_id), battery_level = VALUES(battery_level);

-- Refresh mixed bicycle brand for re-seeding on existing records.
UPDATE vehicles v
JOIN brands b ON b.name = CASE v.serial_no
    WHEN 'BK-0002' THEN 'Xiaomi'
    ELSE 'Giant'
END
SET v.brand_id = b.id
WHERE v.serial_no IN ('BK-0001', 'BK-0002');

-- ---------------------------------------------------------
-- Optional wallet history baseline
-- ---------------------------------------------------------
INSERT INTO wallet_transactions (user_id, type, amount, balance_after, reference_type, reference_id, note)
SELECT u.id, 'topup', 120.00, u.balance, NULL, NULL, 'Initial top-up for basic seed'
FROM users u
WHERE u.campus_id = 's1000001'
AND NOT EXISTS (
    SELECT 1 FROM wallet_transactions wt
    WHERE wt.user_id = u.id AND wt.type = 'topup' AND wt.note = 'Initial top-up for basic seed'
);
