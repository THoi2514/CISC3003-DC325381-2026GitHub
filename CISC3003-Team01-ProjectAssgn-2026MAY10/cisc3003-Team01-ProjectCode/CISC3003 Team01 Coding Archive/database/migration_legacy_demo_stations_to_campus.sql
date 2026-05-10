-- =========================================================
-- Map legacy 5-station DEMO labels → current UM campus stops (see seed_demo_data.sql).
-- Dashboard/map read coordinates from MySQL only.
--
-- Prerequisites: rental_stations has name_zh_cn / name_zh_tw (see
-- migration_station_names_trilingual.sql if missing).
--
-- Safe to run once. Re-run: UPDATEs affect 0 rows; INSERTs use NOT EXISTS.
-- Adjust USE if your database name differs.
-- =========================================================

USE um_rental_system;

SET NAMES utf8mb4;

UPDATE rental_stations SET
  name = 'Research Building (N21)',
  name_zh_cn = '科研大楼 (N21)',
  name_zh_tw = '科研大樓 (N21)',
  latitude = 22.134812,
  longitude = 113.545431,
  capacity = 26
WHERE name = 'Main Gate Station';

UPDATE rental_stations SET
  name = 'Sports Complex (N8)',
  name_zh_cn = '综合体育馆 (N8)',
  name_zh_tw = '綜合體育館 (N8)',
  latitude = 22.133423,
  longitude = 113.543076,
  capacity = 24
WHERE name = 'Sports Complex Station';

UPDATE rental_stations SET
  name = 'Wu Yee Sun Library (E2)',
  name_zh_cn = '伍宜孙图书馆 (E2)',
  name_zh_tw = '伍宜孫圖書館 (E2)',
  latitude = 22.130586,
  longitude = 113.546032,
  capacity = 24
WHERE name = 'UM Library Station';

UPDATE rental_stations SET
  name = 'Social Sciences and Humanities Building (E21)',
  name_zh_cn = '人文社科楼 (E21)',
  name_zh_tw = '人文社科樓 (E21)',
  latitude = 22.128934,
  longitude = 113.542384,
  capacity = 20
WHERE name = 'E21 Faculty Station';

UPDATE rental_stations SET
  name = 'University Mall (S8)',
  name_zh_cn = '荟萃坊 (S8)',
  name_zh_tw = '薈萃坊 (S8)',
  latitude = 22.124521,
  longitude = 113.544074,
  capacity = 20
WHERE name = 'N6 Dormitory Station';

-- Add stops missing from the old 5-station demo
INSERT INTO rental_stations (name, name_zh_cn, name_zh_tw, latitude, longitude, capacity, status)
SELECT 'Central Teaching Building (E6)', '中央教学楼 (E6)', '中央教學樓 (E6)', 22.1283300, 113.5446480, 22, 'active'
FROM (SELECT 1 AS x) AS t
WHERE NOT EXISTS (SELECT 1 FROM rental_stations WHERE name = 'Central Teaching Building (E6)' LIMIT 1);

INSERT INTO rental_stations (name, name_zh_cn, name_zh_tw, latitude, longitude, capacity, status)
SELECT 'Science and Technology Building (E11)', '科技学院楼 (E11)', '科技學院樓 (E11)', 22.132445, 113.542634, 22, 'active'
FROM (SELECT 1 AS x) AS t
WHERE NOT EXISTS (SELECT 1 FROM rental_stations WHERE name = 'Science and Technology Building (E11)' LIMIT 1);

INSERT INTO rental_stations (name, name_zh_cn, name_zh_tw, latitude, longitude, capacity, status)
SELECT 'Student Activity Centre (E31)', '学生活动中心 (E31)', '學生活動中心 (E31)', 22.126613, 113.544071, 22, 'active'
FROM (SELECT 1 AS x) AS t
WHERE NOT EXISTS (SELECT 1 FROM rental_stations WHERE name = 'Student Activity Centre (E31)' LIMIT 1);
