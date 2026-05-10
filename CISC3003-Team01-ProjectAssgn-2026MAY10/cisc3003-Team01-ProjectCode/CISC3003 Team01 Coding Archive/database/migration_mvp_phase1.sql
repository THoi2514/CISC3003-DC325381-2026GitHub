-- =========================================================
-- MVP Phase-1 migration
-- =========================================================
-- NOTE:
--   This migration is kept for backward compatibility.
--   New environments should import database/schema.sql directly,
--   which already includes these tables and indexes.
USE um_rental_system;

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('topup', 'rental_charge', 'adjustment', 'refund') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
    CONSTRAINT chk_wallet_amount_nonzero CHECK (amount <> 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope_key VARCHAR(190) NOT NULL UNIQUE,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    window_start DATETIME NOT NULL,
    blocked_until DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Indexes are defined in schema.sql for new installations.
