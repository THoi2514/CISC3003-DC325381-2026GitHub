-- =========================================================
-- UM Bicycle & Scooter Rental Management System
-- MySQL 8.0+ Schema
-- =========================================================

CREATE DATABASE IF NOT EXISTS um_rental_system
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE um_rental_system;

-- 先清理舊表，避免外鍵依賴衝突
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS forum_post_replies;
DROP TABLE IF EXISTS feedback_replies;
DROP TABLE IF EXISTS feedbacks;
DROP TABLE IF EXISTS forum_posts;
DROP TABLE IF EXISTS wallet_transactions;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS rental_orders;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS rental_stations;
DROP TABLE IF EXISTS administrators;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS vehicle_types;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- Users：校園用戶主表（學生 / 教職員 / 訪客 / 管理員）
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

    -- 登入安全：連續失敗與鎖定機制
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    lock_until DATETIME NULL,
    account_status ENUM('active', 'frozen', 'disabled') NOT NULL DEFAULT 'active',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Administrators：管理員擴充資訊（關聯 users）
-- ---------------------------------------------------------
CREATE TABLE administrators (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    user_name VARCHAR(50) NOT NULL UNIQUE,
    privilege ENUM('super_admin', 'ops_admin', 'content_admin', 'finance_admin') NOT NULL DEFAULT 'ops_admin',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- VehicleTypes：車型（自行車 / 滑板車）與基礎費率
-- ---------------------------------------------------------
CREATE TABLE vehicle_types (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    price_per_30_min DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Brands：品牌與費率加權（可用於不同品牌加價）
-- ---------------------------------------------------------
CREATE TABLE brands (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    price_multiplier DECIMAL(5, 2) NOT NULL DEFAULT 1.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- RentalStations：租借站點
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
-- Vehicles：車輛資料
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
      ON DELETE RESTRICT
      ON UPDATE CASCADE,
    CONSTRAINT fk_vehicle_brand
      FOREIGN KEY (brand_id) REFERENCES brands(id)
      ON DELETE RESTRICT
      ON UPDATE CASCADE,
    CONSTRAINT fk_vehicle_station
      FOREIGN KEY (station_id) REFERENCES rental_stations(id)
      ON DELETE SET NULL
      ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_vehicles_station_status ON vehicles (station_id, status);
CREATE INDEX idx_vehicles_type_brand ON vehicles (type_id, brand_id);

-- ---------------------------------------------------------
-- RentalOrders：租賃訂單
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
      ON DELETE RESTRICT
      ON UPDATE CASCADE,
    CONSTRAINT fk_order_vehicle
      FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
      ON DELETE RESTRICT
      ON UPDATE CASCADE,
    CONSTRAINT fk_order_start_station
      FOREIGN KEY (start_station_id) REFERENCES rental_stations(id)
      ON DELETE SET NULL
      ON UPDATE CASCADE,
    CONSTRAINT fk_order_end_station
      FOREIGN KEY (end_station_id) REFERENCES rental_stations(id)
      ON DELETE SET NULL
      ON UPDATE CASCADE,
    CONSTRAINT chk_order_time CHECK (end_time IS NULL OR end_time >= start_time),
    CONSTRAINT chk_order_duration CHECK (duration_minutes IS NULL OR duration_minutes >= 0),
    CONSTRAINT chk_order_fee CHECK (fee >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_orders_user_status ON rental_orders (user_id, status);
CREATE INDEX idx_orders_vehicle_status ON rental_orders (vehicle_id, status);
CREATE INDEX idx_orders_start_time ON rental_orders (start_time);
CREATE INDEX idx_orders_status_time ON rental_orders (status, start_time, end_time);

-- ---------------------------------------------------------
-- WalletTransactions：錢包流水（儲值/扣款/退款）
-- ---------------------------------------------------------
CREATE TABLE wallet_transactions (
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

CREATE INDEX idx_wallet_user_time ON wallet_transactions (user_id, created_at);
CREATE INDEX idx_wallet_type_time ON wallet_transactions (type, created_at);

-- ---------------------------------------------------------
-- RateLimits：API / Login 限流
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
-- ForumPosts：論壇貼文
-- ---------------------------------------------------------
CREATE TABLE forum_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    category ENUM('help', 'experience', 'suggestion', 'lost_found', 'other') NOT NULL DEFAULT 'other',
    content TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    status ENUM('visible', 'locked', 'hidden', 'deleted') NOT NULL DEFAULT 'visible',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_post_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_forum_status_time ON forum_posts (status, created_at);
CREATE INDEX idx_forum_user_time ON forum_posts (user_id, created_at);

-- ---------------------------------------------------------
-- ForumPostReplies：論壇貼文回覆（類似朋友圈評論）
-- ---------------------------------------------------------
CREATE TABLE forum_post_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reply_content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_forum_reply_post
      FOREIGN KEY (post_id) REFERENCES forum_posts(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
    CONSTRAINT fk_forum_reply_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_forum_reply_post_time ON forum_post_replies (post_id, created_at);

-- ---------------------------------------------------------
-- Feedbacks：回饋工單
-- ---------------------------------------------------------
CREATE TABLE feedbacks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('bug', 'payment', 'vehicle', 'station', 'account', 'other') NOT NULL DEFAULT 'other',
    status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_feedback_status_time ON feedbacks (status, created_at);

-- ---------------------------------------------------------
-- FeedbackReplies：管理員回覆
-- ---------------------------------------------------------
CREATE TABLE feedback_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feedback_id BIGINT UNSIGNED NOT NULL,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    reply_content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reply_feedback
      FOREIGN KEY (feedback_id) REFERENCES feedbacks(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
    CONSTRAINT fk_reply_admin_user
      FOREIGN KEY (admin_user_id) REFERENCES users(id)
      ON DELETE RESTRICT
      ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- AuditLogs：審計日誌（手動結單、費用調整、封鎖內容等）
-- ---------------------------------------------------------
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    actor_role ENUM('system', 'user', 'admin') NOT NULL DEFAULT 'user',
    action_type VARCHAR(80) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_actor_user
      FOREIGN KEY (actor_user_id) REFERENCES users(id)
      ON DELETE SET NULL
      ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_audit_action_time ON audit_logs (action_type, created_at);
CREATE INDEX idx_audit_actor_time ON audit_logs (actor_user_id, created_at);

-- ---------------------------------------------------------
-- 初始主資料（可依需求調整）
-- ---------------------------------------------------------
INSERT INTO vehicle_types (name, price_per_30_min) VALUES
('bicycle', 5.00),
('scooter', 8.00);

INSERT INTO brands (name, price_multiplier) VALUES
('Giant', 1.00),
('Xiaomi', 1.10),
('Ninebot', 1.15);
