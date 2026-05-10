-- =========================================================
-- Production baseline seed (no demo users/vehicles)
-- =========================================================
USE um_rental_system;

SET NAMES utf8mb4;

-- Keep only baseline stations for real operations setup.
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

-- Intentionally no demo student/staff/admin account inserted here.
-- Create production accounts manually with strong credentials.
