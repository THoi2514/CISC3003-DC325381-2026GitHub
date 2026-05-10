-- Repair corrupted name_zh_cn / name_zh_tw (often appears as ?????) after bad charset import.
-- Safe to re-run: matches English `name` key only.

USE um_rental_system;

UPDATE rental_stations SET name_zh_cn = '科研大楼 (N21)', name_zh_tw = '科研大樓 (N21)' WHERE name = 'Research Building (N21)';
UPDATE rental_stations SET name_zh_cn = '综合体育馆 (N8)', name_zh_tw = '綜合體育館 (N8)' WHERE name = 'Sports Complex (N8)';
UPDATE rental_stations SET name_zh_cn = '伍宜孙图书馆 (E2)', name_zh_tw = '伍宜孫圖書館 (E2)' WHERE name = 'Wu Yee Sun Library (E2)';
UPDATE rental_stations SET name_zh_cn = '中央教学楼 (E6)', name_zh_tw = '中央教學樓 (E6)' WHERE name = 'Central Teaching Building (E6)';
UPDATE rental_stations SET name_zh_cn = '科技学院楼 (E11)', name_zh_tw = '科技學院樓 (E11)' WHERE name = 'Science and Technology Building (E11)';
UPDATE rental_stations SET name_zh_cn = '人文社科楼 (E21)', name_zh_tw = '人文社科樓 (E21)' WHERE name = 'Social Sciences and Humanities Building (E21)';
UPDATE rental_stations SET name_zh_cn = '学生活动中心 (E31)', name_zh_tw = '學生活動中心 (E31)' WHERE name = 'Student Activity Centre (E31)';
UPDATE rental_stations SET name_zh_cn = '荟萃坊 (S8)', name_zh_tw = '薈萃坊 (S8)' WHERE name = 'University Mall (S8)';
