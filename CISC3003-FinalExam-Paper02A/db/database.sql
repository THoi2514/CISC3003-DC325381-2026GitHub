-- Scenario A — create database / table (A.09) and sample INSERT (A.10)
CREATE DATABASE IF NOT EXISTS cisc3003_paper02a
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cisc3003_paper02a;

DROP TABLE IF EXISTS workshop_applications;

CREATE TABLE workshop_applications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  country VARCHAR(80) NOT NULL,
  experience_level ENUM('beginner','intermediate','advanced') NOT NULL,
  topics VARCHAR(255) NOT NULL,
  comments TEXT NOT NULL,
  wants_newsletter TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A.10: explicit INSERT INTO example (also used to verify phpMyAdmin import)
INSERT INTO workshop_applications
  (full_name, email, phone, country, experience_level, topics, comments, wants_newsletter)
VALUES
  ('Sample Applicant', 'sample.applicant@example.com', '+85300000000', 'Macau',
   'beginner', 'PHP,HTML', 'Imported row to verify SQL INSERT.', 1);
