-- 執行前請備份。新增教師角色、單車現場備註欄位（職員可填寫小問題說明）。
USE um_rental_system;

ALTER TABLE users
  MODIFY COLUMN role ENUM('student', 'teacher', 'staff', 'visitor', 'admin') NOT NULL DEFAULT 'student';

ALTER TABLE vehicles
  ADD COLUMN issue_note VARCHAR(500) NULL DEFAULT NULL AFTER battery_level;
