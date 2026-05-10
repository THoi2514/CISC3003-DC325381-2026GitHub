-- =========================================================
-- Management procedures for UM rental system
-- Covers:
--   - add/remove bicycle
--   - add/remove student
--   - add/remove staff
--   - start/end rental
-- =========================================================

USE um_rental_system;

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_add_bicycle $$
CREATE PROCEDURE sp_add_bicycle(
    IN p_serial_no VARCHAR(50),
    IN p_brand_id SMALLINT UNSIGNED,
    IN p_station_id BIGINT UNSIGNED,
    IN p_battery_level TINYINT UNSIGNED
)
BEGIN
    DECLARE v_type_id SMALLINT UNSIGNED;

    SELECT id INTO v_type_id
    FROM vehicle_types
    WHERE name = 'bicycle'
    LIMIT 1;

    IF v_type_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bicycle vehicle type not found';
    END IF;

    INSERT INTO vehicles (
        serial_no, type_id, brand_id, status, station_id, battery_level
    ) VALUES (
        p_serial_no, v_type_id, p_brand_id, 'available', p_station_id, p_battery_level
    );
END $$

DROP PROCEDURE IF EXISTS sp_remove_bicycle $$
CREATE PROCEDURE sp_remove_bicycle(IN p_vehicle_id BIGINT UNSIGNED)
BEGIN
    DECLARE v_status VARCHAR(30);
    DECLARE v_type_name VARCHAR(50);

    SELECT v.status, vt.name
    INTO v_status, v_type_name
    FROM vehicles v
    INNER JOIN vehicle_types vt ON vt.id = v.type_id
    WHERE v.id = p_vehicle_id
    LIMIT 1;

    IF v_type_name IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vehicle not found';
    END IF;

    IF v_type_name <> 'bicycle' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Target vehicle is not a bicycle';
    END IF;

    IF v_status = 'rented' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot remove a rented bicycle';
    END IF;

    DELETE FROM vehicles WHERE id = p_vehicle_id;
END $$

DROP PROCEDURE IF EXISTS sp_add_student $$
CREATE PROCEDURE sp_add_student(
    IN p_campus_id VARCHAR(30),
    IN p_full_name VARCHAR(120),
    IN p_email VARCHAR(120),
    IN p_phone VARCHAR(30),
    IN p_password_hash VARCHAR(255)
)
BEGIN
    INSERT INTO users (
        campus_id, full_name, role, email, phone, balance, password_hash, account_status
    ) VALUES (
        p_campus_id, p_full_name, 'student', p_email, p_phone, 0.00, p_password_hash, 'active'
    );
END $$

DROP PROCEDURE IF EXISTS sp_remove_student $$
CREATE PROCEDURE sp_remove_student(IN p_user_id BIGINT UNSIGNED)
BEGIN
    DECLARE v_role VARCHAR(20);
    DECLARE v_has_orders INT DEFAULT 0;
    DECLARE v_has_posts INT DEFAULT 0;
    DECLARE v_has_feedback INT DEFAULT 0;

    SELECT role INTO v_role
    FROM users
    WHERE id = p_user_id
    LIMIT 1;

    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User not found';
    END IF;

    IF v_role <> 'student' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Target user is not a student';
    END IF;

    SELECT COUNT(*) INTO v_has_orders FROM rental_orders WHERE user_id = p_user_id;
    SELECT COUNT(*) INTO v_has_posts FROM forum_posts WHERE user_id = p_user_id;
    SELECT COUNT(*) INTO v_has_feedback FROM feedbacks WHERE user_id = p_user_id;

    IF v_has_orders > 0 OR v_has_posts > 0 OR v_has_feedback > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot remove student with related records';
    END IF;

    DELETE FROM users WHERE id = p_user_id;
END $$

DROP PROCEDURE IF EXISTS sp_add_staff $$
CREATE PROCEDURE sp_add_staff(
    IN p_campus_id VARCHAR(30),
    IN p_full_name VARCHAR(120),
    IN p_email VARCHAR(120),
    IN p_phone VARCHAR(30),
    IN p_password_hash VARCHAR(255)
)
BEGIN
    INSERT INTO users (
        campus_id, full_name, role, email, phone, balance, password_hash, account_status
    ) VALUES (
        p_campus_id, p_full_name, 'staff', p_email, p_phone, 0.00, p_password_hash, 'active'
    );
END $$

DROP PROCEDURE IF EXISTS sp_remove_staff $$
CREATE PROCEDURE sp_remove_staff(IN p_user_id BIGINT UNSIGNED)
BEGIN
    DECLARE v_role VARCHAR(20);

    SELECT role INTO v_role
    FROM users
    WHERE id = p_user_id
    LIMIT 1;

    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User not found';
    END IF;

    IF v_role <> 'staff' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Target user is not a staff account';
    END IF;

    UPDATE users
    SET account_status = 'disabled'
    WHERE id = p_user_id;
END $$

DROP PROCEDURE IF EXISTS sp_start_rental $$
CREATE PROCEDURE sp_start_rental(
    IN p_user_id BIGINT UNSIGNED,
    IN p_vehicle_id BIGINT UNSIGNED
)
BEGIN
    DECLARE v_user_balance DECIMAL(10,2);
    DECLARE v_user_status VARCHAR(20);
    DECLARE v_active_count INT DEFAULT 0;
    DECLARE v_vehicle_status VARCHAR(30);
    DECLARE v_start_station BIGINT UNSIGNED;
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_multiplier DECIMAL(5,2);
    DECLARE v_required DECIMAL(10,2);

    START TRANSACTION;

    SELECT balance, account_status
    INTO v_user_balance, v_user_status
    FROM users
    WHERE id = p_user_id
    FOR UPDATE;

    IF v_user_status IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User not found';
    END IF;

    IF v_user_status <> 'active' THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User account is not active';
    END IF;

    SELECT COUNT(*) INTO v_active_count
    FROM rental_orders
    WHERE user_id = p_user_id AND status = 'active'
    FOR UPDATE;

    IF v_active_count > 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User already has active rental';
    END IF;

    SELECT v.status, v.station_id, vt.price_per_30_min, b.price_multiplier
    INTO v_vehicle_status, v_start_station, v_price, v_multiplier
    FROM vehicles v
    INNER JOIN vehicle_types vt ON vt.id = v.type_id
    INNER JOIN brands b ON b.id = v.brand_id
    WHERE v.id = p_vehicle_id
    FOR UPDATE;

    IF v_vehicle_status IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vehicle not found';
    END IF;

    IF v_vehicle_status <> 'available' THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vehicle is not available';
    END IF;

    SET v_required = v_price * v_multiplier;
    IF v_user_balance < v_required THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient balance';
    END IF;

    INSERT INTO rental_orders (
        user_id, vehicle_id, start_station_id, start_time, status, fee
    ) VALUES (
        p_user_id, p_vehicle_id, v_start_station, NOW(), 'active', 0.00
    );

    UPDATE vehicles
    SET status = 'rented', station_id = NULL
    WHERE id = p_vehicle_id;

    COMMIT;
END $$

DROP PROCEDURE IF EXISTS sp_end_rental $$
CREATE PROCEDURE sp_end_rental(
    IN p_user_id BIGINT UNSIGNED,
    IN p_return_station_id BIGINT UNSIGNED
)
BEGIN
    DECLARE v_order_id BIGINT UNSIGNED;
    DECLARE v_vehicle_id BIGINT UNSIGNED;
    DECLARE v_start_time DATETIME;
    DECLARE v_station_capacity INT UNSIGNED;
    DECLARE v_station_status VARCHAR(20);
    DECLARE v_occupied INT DEFAULT 0;
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_multiplier DECIMAL(5,2);
    DECLARE v_fee DECIMAL(10,2);
    DECLARE v_duration INT;
    DECLARE v_balance DECIMAL(10,2);
    DECLARE v_order_status VARCHAR(30);

    START TRANSACTION;

    SELECT id, vehicle_id, start_time
    INTO v_order_id, v_vehicle_id, v_start_time
    FROM rental_orders
    WHERE user_id = p_user_id AND status = 'active'
    ORDER BY id DESC
    LIMIT 1
    FOR UPDATE;

    IF v_order_id IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No active order found';
    END IF;

    SELECT capacity, status
    INTO v_station_capacity, v_station_status
    FROM rental_stations
    WHERE id = p_return_station_id
    FOR UPDATE;

    IF v_station_status IS NULL OR v_station_status <> 'active' THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Return station unavailable';
    END IF;

    SELECT COUNT(*) INTO v_occupied
    FROM vehicles
    WHERE station_id = p_return_station_id
      AND status IN ('available', 'maintenance')
    FOR UPDATE;

    IF v_occupied >= v_station_capacity THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Return station is full';
    END IF;

    SELECT vt.price_per_30_min, b.price_multiplier
    INTO v_price, v_multiplier
    FROM vehicles v
    INNER JOIN vehicle_types vt ON vt.id = v.type_id
    INNER JOIN brands b ON b.id = v.brand_id
    WHERE v.id = v_vehicle_id
    FOR UPDATE;

    SET v_duration = GREATEST(1, CEIL(TIMESTAMPDIFF(SECOND, v_start_time, NOW()) / 60));
    SET v_fee = CEIL(v_duration / 30) * (v_price * v_multiplier);

    SELECT balance INTO v_balance
    FROM users
    WHERE id = p_user_id
    FOR UPDATE;

    SET v_order_status = 'completed';
    IF v_balance >= v_fee THEN
        UPDATE users SET balance = balance - v_fee WHERE id = p_user_id;
    ELSE
        SET v_order_status = 'payment_pending';
    END IF;

    UPDATE rental_orders
    SET end_station_id = p_return_station_id,
        end_time = NOW(),
        duration_minutes = v_duration,
        fee = v_fee,
        status = v_order_status
    WHERE id = v_order_id;

    UPDATE vehicles
    SET status = 'available', station_id = p_return_station_id
    WHERE id = v_vehicle_id;

    COMMIT;
END $$

DELIMITER ;
