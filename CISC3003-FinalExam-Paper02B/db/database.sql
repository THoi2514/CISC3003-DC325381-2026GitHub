CREATE DATABASE IF NOT EXISTS cisc3003_paper02b
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cisc3003_paper02b;

DROP TABLE IF EXISTS contact_log;

CREATE TABLE contact_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sender_name VARCHAR(120) NOT NULL,
  sender_email VARCHAR(190) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  outcome ENUM('sent','failed') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO contact_log (sender_name, sender_email, subject, outcome)
VALUES ('Sample', 'sample@example.com', 'Hello', 'sent');
