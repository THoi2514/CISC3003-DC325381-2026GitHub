-- Rename campus rental station labels from Chinese to English (legacy imports).
-- Also upgrades older English canonical names to the current campus list (Research / Central Teaching / University Mall).
-- Requires: database/schema.sql (table rental_stations, UNIQUE(name)).
--
USE um_rental_system;

SET NAMES utf8mb4;

UPDATE rental_stations SET name = 'Research Building (N21)' WHERE name = '大學展館 (N2)';
UPDATE rental_stations SET name = 'Sports Complex (N8)' WHERE name = '綜合體育館 (N8)';
UPDATE rental_stations SET name = 'Wu Yee Sun Library (E2)' WHERE name = '伍宜孫圖書館 (E2)';
UPDATE rental_stations SET name = 'Central Teaching Building (E6)' WHERE name = '商學院樓 (E4)';
UPDATE rental_stations SET name = 'Science and Technology Building (E11)' WHERE name = '科技學院樓 (E11)';
UPDATE rental_stations SET name = 'Social Sciences and Humanities Building (E21)' WHERE name = '人文社科樓 (E21)';
UPDATE rental_stations SET name = 'Student Activity Centre (E31)' WHERE name = '學生活動中心 (E31)';
UPDATE rental_stations SET name = 'University Mall (S8)' WHERE name = '曹光彪書院 (S8)';

-- Older seeds used these English names — migrate IDs in place (FKs preserved).
UPDATE rental_stations SET name = 'Research Building (N21)' WHERE name = 'University Gallery (N2)';
UPDATE rental_stations SET name = 'Central Teaching Building (E6)' WHERE name = 'Business Administration Building (E4)';
UPDATE rental_stations SET name = 'University Mall (S8)' WHERE name = 'Henry Fok Pearl Jubilee College (S8)';
