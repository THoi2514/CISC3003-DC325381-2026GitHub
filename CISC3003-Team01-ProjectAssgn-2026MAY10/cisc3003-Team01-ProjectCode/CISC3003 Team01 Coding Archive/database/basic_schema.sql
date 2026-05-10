-- =========================================================
-- UM Rental System - Basic Database Structure
-- MySQL 8.0+
-- =========================================================

CREATE DATABASE IF NOT EXISTS um_rental_system
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE um_rental_system;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS wallet_transactions;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS rental_orders;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS rental_stations;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS vehicle_types;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- Users
-- ---------------------------------------------------------
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campus_id VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    role ENUM('student', 'teacher', 'staff', 'visitor', 'admin') NOT NULL DEFAULT 'student',
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    password_hash VARCHAR(255) NOT NULL,
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    lock_until DATETIME NULL,
    account_status ENUM('active', 'frozen', 'disabled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Vehicle master data
-- ---------------------------------------------------------
CREATE TABLE vehicle_types (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    price_per_30_min DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE brands (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    price_multiplier DECIMAL(5, 2) NOT NULL DEFAULT 1.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Stations
-- ---------------------------------------------------------
CREATE TABLE rental_stations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    name_zh_cn VARCHAR(120) NOT NULL DEFAULT '',
    name_zh_tw VARCHAR(120) NOT NULL DEFAULT '',
    latitude DECIMAL(10, 7) NOT NULL,
    longitude DECIMAL(10, 7) NOT NULL,
    capacity INT UNSIGNED NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_station_capacity CHECK (capacity > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Vehicles
-- ---------------------------------------------------------
CREATE TABLE vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    serial_no VARCHAR(50) NOT NULL UNIQUE,
    type_id SMALLINT UNSIGNED NOT NULL,
    brand_id SMALLINT UNSIGNED NOT NULL,
    status ENUM('available', 'rented', 'maintenance', 'retired') NOT NULL DEFAULT 'available',
    station_id BIGINT UNSIGNED NULL,
    battery_level TINYINT UNSIGNED NULL,
    issue_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicle_type
      FOREIGN KEY (type_id) REFERENCES vehicle_types(id)
      ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vehicle_brand
      FOREIGN KEY (brand_id) REFERENCES brands(id)
      ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vehicle_station
      FOREIGN KEY (station_id) REFERENCES rental_stations(id)
      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_vehicles_station_status ON vehicles (station_id, status);
CREATE INDEX idx_vehicles_type_brand ON vehicles (type_id, brand_id);

-- ---------------------------------------------------------
-- Rental orders
-- ---------------------------------------------------------
CREATE TABLE rental_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    start_station_id BIGINT UNSIGNED NULL,
    end_station_id BIGINT UNSIGNED NULL,
    start_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME NULL,
    duration_minutes INT UNSIGNED NULL,
    fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'completed', 'payment_pending', 'force_closed', 'cancelled') NOT NULL DEFAULT 'active',
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_order_vehicle
      FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
      ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_order_start_station
      FOREIGN KEY (start_station_id) REFERENCES rental_stations(id)
      ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_order_end_station
      FOREIGN KEY (end_station_id) REFERENCES rental_stations(id)
      ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_order_time CHECK (end_time IS NULL OR end_time >= start_time),
    CONSTRAINT chk_order_duration CHECK (duration_minutes IS NULL OR duration_minutes >= 0),
    CONSTRAINT chk_order_fee CHECK (fee >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_orders_user_status ON rental_orders (user_id, status);
CREATE INDEX idx_orders_vehicle_status ON rental_orders (vehicle_id, status);
CREATE INDEX idx_orders_start_time ON rental_orders (start_time);
CREATE INDEX idx_orders_status_time ON rental_orders (status, start_time, end_time);

-- ---------------------------------------------------------
-- Wallet history (used by account/top-up flow)
-- ---------------------------------------------------------
CREATE TABLE wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('topup', 'rental_charge', 'refund', 'adjustment') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    balance_after DECIMAL(10, 2) NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_wallet_amount_nonzero CHECK (amount <> 0)
) ENGINE=InnoDB;

CREATE INDEX idx_wallet_user_time ON wallet_transactions (user_id, created_at);
CREATE INDEX idx_wallet_type_time ON wallet_transactions (type, created_at);

-- ---------------------------------------------------------
-- Rate limits (used by login and API throttling)
-- ---------------------------------------------------------
CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope_key VARCHAR(190) NOT NULL UNIQUE,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    window_start DATETIME NOT NULL,
    blocked_until DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_rate_blocked_until ON rate_limits (blocked_until);
CREATE INDEX idx_vehicle_status_type ON vehicles (status, type_id);

-- ---------------------------------------------------------
-- Basic master data
-- ---------------------------------------------------------
INSERT INTO vehicle_types (name, price_per_30_min) VALUES
('bicycle', 5.00),
('scooter', 8.00);

INSERT INTO brands (name, price_multiplier) VALUES
('Giant', 1.00),
('Xiaomi', 1.10),
('Ninebot', 1.15);
