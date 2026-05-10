-- =========================================================
-- Reset demo state for presentation
-- Safe to re-run
-- =========================================================
USE um_rental_system;

SET NAMES utf8mb4;

START TRANSACTION;

-- Clear transactional and activity data
DELETE FROM wallet_transactions;
DELETE FROM rental_orders;
DELETE FROM forum_posts;
DELETE FROM feedback_replies;
DELETE FROM feedbacks;
DELETE FROM audit_logs;
DELETE FROM rate_limits;

-- Restore demo users to baseline state
UPDATE users
SET
  failed_login_attempts = 0,
  lock_until = NULL,
  account_status = 'active'
WHERE campus_id IN (
  's1000001','s1000002','s1000003','s1000004','s1000005',
  's1000006','s1000007','s1000008','s1000009','s1000010',
  't2000001','dc325107'
);

UPDATE users
SET balance = CASE campus_id
  WHEN 's1000001' THEN 120.00
  WHEN 's1000002' THEN 80.00
  WHEN 's1000003' THEN 64.00
  WHEN 's1000004' THEN 45.00
  WHEN 's1000005' THEN 200.00
  WHEN 's1000006' THEN 90.00
  WHEN 's1000007' THEN 34.00
  WHEN 's1000008' THEN 56.50
  WHEN 's1000009' THEN 72.00
  WHEN 's1000010' THEN 110.00
  WHEN 't2000001' THEN 150.00
  WHEN 'dc325107' THEN 9999.00
  ELSE balance
END
WHERE campus_id IN (
  's1000001','s1000002','s1000003','s1000004','s1000005',
  's1000006','s1000007','s1000008','s1000009','s1000010',
  't2000001','dc325107'
);

UPDATE users
SET role = CASE WHEN campus_id = 'dc325107' THEN 'admin' ELSE role END
WHERE campus_id = 'dc325107';

UPDATE users
SET role = 'staff'
WHERE role = 'admin' AND campus_id <> 'dc325107';

-- Restore demo vehicles to baseline station/status
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Wu Yee Sun Library (E2)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0001';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Social Sciences and Humanities Building (E21)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0002';
UPDATE vehicles SET status='rented', station_id=(SELECT id FROM rental_stations WHERE name='University Mall (S8)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0003';
UPDATE vehicles SET status='maintenance', station_id=(SELECT id FROM rental_stations WHERE name='Sports Complex (N8)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0004';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Research Building (N21)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0005';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Wu Yee Sun Library (E2)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0006';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Social Sciences and Humanities Building (E21)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0007';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='University Mall (S8)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0008';
UPDATE vehicles SET status='maintenance', station_id=(SELECT id FROM rental_stations WHERE name='Sports Complex (N8)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0009';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Research Building (N21)' LIMIT 1), battery_level=NULL WHERE serial_no='BK-0010';

UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Wu Yee Sun Library (E2)' LIMIT 1), battery_level=92 WHERE serial_no='SC-1001';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Social Sciences and Humanities Building (E21)' LIMIT 1), battery_level=78 WHERE serial_no='SC-1002';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='University Mall (S8)' LIMIT 1), battery_level=66 WHERE serial_no='SC-1003';
UPDATE vehicles SET status='rented', station_id=(SELECT id FROM rental_stations WHERE name='Sports Complex (N8)' LIMIT 1), battery_level=54 WHERE serial_no='SC-1004';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Research Building (N21)' LIMIT 1), battery_level=88 WHERE serial_no='SC-1005';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Wu Yee Sun Library (E2)' LIMIT 1), battery_level=95 WHERE serial_no='SC-1006';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Social Sciences and Humanities Building (E21)' LIMIT 1), battery_level=82 WHERE serial_no='SC-1007';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='University Mall (S8)' LIMIT 1), battery_level=76 WHERE serial_no='SC-1008';
UPDATE vehicles SET status='maintenance', station_id=(SELECT id FROM rental_stations WHERE name='Sports Complex (N8)' LIMIT 1), battery_level=61 WHERE serial_no='SC-1009';
UPDATE vehicles SET status='available', station_id=(SELECT id FROM rental_stations WHERE name='Research Building (N21)' LIMIT 1), battery_level=89 WHERE serial_no='SC-1010';

COMMIT;
