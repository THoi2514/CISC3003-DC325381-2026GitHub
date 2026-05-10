-- In-place update of station coordinates and labels (no name changes).
-- Run after `migration_rename_stations_to_english.sql` if your table already
-- has the new English names (Research Building, Central Teaching, University Mall, …).
-- Adjust USE if your database name differs.
USE um_rental_system;

SET NAMES utf8mb4;

UPDATE rental_stations SET
  name_zh_cn = '科研大楼 (N21)', name_zh_tw = '科研大樓 (N21)',
  latitude = 22.134812, longitude = 113.545431, capacity = 26
WHERE name = 'Research Building (N21)';

UPDATE rental_stations SET
  name_zh_cn = '综合体育馆 (N8)', name_zh_tw = '綜合體育館 (N8)',
  latitude = 22.133423, longitude = 113.543076, capacity = 24
WHERE name = 'Sports Complex (N8)';

UPDATE rental_stations SET
  name_zh_cn = '伍宜孙图书馆 (E2)', name_zh_tw = '伍宜孫圖書館 (E2)',
  latitude = 22.130586, longitude = 113.546032, capacity = 24
WHERE name = 'Wu Yee Sun Library (E2)';

UPDATE rental_stations SET
  name_zh_cn = '中央教学楼 (E6)', name_zh_tw = '中央教學樓 (E6)',
  latitude = 22.1283300, longitude = 113.5446480, capacity = 22
WHERE name = 'Central Teaching Building (E6)';

UPDATE rental_stations SET
  name_zh_cn = '科技学院楼 (E11)', name_zh_tw = '科技學院樓 (E11)',
  latitude = 22.132445, longitude = 113.542634, capacity = 22
WHERE name = 'Science and Technology Building (E11)';

UPDATE rental_stations SET
  name_zh_cn = '人文社科楼 (E21)', name_zh_tw = '人文社科樓 (E21)',
  latitude = 22.128934, longitude = 113.542384, capacity = 20
WHERE name = 'Social Sciences and Humanities Building (E21)';

UPDATE rental_stations SET
  name_zh_cn = '学生活动中心 (E31)', name_zh_tw = '學生活動中心 (E31)',
  latitude = 22.126613, longitude = 113.544071, capacity = 22
WHERE name = 'Student Activity Centre (E31)';

UPDATE rental_stations SET
  name_zh_cn = '荟萃坊 (S8)', name_zh_tw = '薈萃坊 (S8)',
  latitude = 22.124521, longitude = 113.544074, capacity = 20
WHERE name = 'University Mall (S8)';
