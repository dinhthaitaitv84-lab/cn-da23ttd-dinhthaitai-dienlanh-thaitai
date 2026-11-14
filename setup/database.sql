CREATE DATABASE IF NOT EXISTS dienlanh_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE dienlanh_db;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician', 'customer') NOT NULL DEFAULT 'customer',
    address VARCHAR(500) DEFAULT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_conversations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT UNSIGNED NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_support_conversations_customer_status (customer_id, status, id),
    KEY idx_support_conversations_status_last_message (status, last_message_at),
    CONSTRAINT fk_support_conversations_customer
        FOREIGN KEY (customer_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    message VARCHAR(2000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_support_messages_conversation (conversation_id, id),
    KEY idx_support_messages_sender_read (sender_id, read_at),
    CONSTRAINT fk_support_messages_conversation
        FOREIGN KEY (conversation_id) REFERENCES support_conversations (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_support_messages_sender
        FOREIGN KEY (sender_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS technicians (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    specialty VARCHAR(150) NOT NULL DEFAULT '',
    experience_years SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('available', 'busy', 'off') NOT NULL DEFAULT 'available',
    PRIMARY KEY (id),
    UNIQUE KEY uq_technicians_user (user_id),
    KEY idx_technicians_status (status),
    CONSTRAINT fk_technicians_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_service_categories_name (name),
    KEY idx_service_categories_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    description TEXT DEFAULT NULL,
    base_price DECIMAL(12, 0) NOT NULL DEFAULT 0,
    est_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    status TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_services_category_name (category_id, name),
    KEY idx_services_status (status),
    CONSTRAINT fk_services_category
        FOREIGN KEY (category_id) REFERENCES service_categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS devices (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_devices_customer (customer_id),
    KEY idx_devices_category (category_id),
    CONSTRAINT fk_devices_customer
        FOREIGN KEY (customer_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_devices_category
        FOREIGN KEY (category_id) REFERENCES service_categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(40) NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    device_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    address VARCHAR(500) NOT NULL,
    preferred_time DATETIME NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    status ENUM('pending', 'received', 'checking', 'waiting_parts', 'repairing', 'quoted', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    technician_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_requests_code (code),
    KEY idx_requests_customer_status (customer_id, status, created_at),
    KEY idx_requests_service (service_id),
    KEY idx_requests_device (device_id),
    KEY idx_requests_technician_status (technician_id, status),
    KEY idx_requests_status_priority (status, priority),
    CONSTRAINT fk_requests_customer
        FOREIGN KEY (customer_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_requests_service
        FOREIGN KEY (service_id) REFERENCES services (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_requests_device
        FOREIGN KEY (device_id) REFERENCES devices (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_requests_technician
        FOREIGN KEY (technician_id) REFERENCES technicians (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS request_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_request_images_request (request_id),
    CONSTRAINT fk_request_images_request
        FOREIGN KEY (request_id) REFERENCES requests (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS request_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'received', 'checking', 'waiting_parts', 'repairing', 'quoted', 'completed', 'cancelled') NOT NULL,
    note TEXT DEFAULT NULL,
    changed_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_request_logs_request_created (request_id, created_at),
    KEY idx_request_logs_changed_by (changed_by),
    CONSTRAINT fk_request_logs_request
        FOREIGN KEY (request_id) REFERENCES requests (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_request_logs_changed_by
        FOREIGN KEY (changed_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repair_reports (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id INT UNSIGNED NOT NULL,
    cause TEXT DEFAULT NULL,
    solution TEXT DEFAULT NULL,
    labor_cost DECIMAL(12, 0) NOT NULL DEFAULT 0,
    warranty_months SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_repair_reports_request (request_id),
    CONSTRAINT fk_repair_reports_request
        FOREIGN KEY (request_id) REFERENCES requests (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(180) NOT NULL,
    sku VARCHAR(80) NOT NULL,
    unit VARCHAR(40) NOT NULL,
    price DECIMAL(12, 0) NOT NULL DEFAULT 0,
    stock_qty INT UNSIGNED NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_parts_sku (sku),
    KEY idx_parts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_parts (
    report_id INT UNSIGNED NOT NULL,
    part_id INT UNSIGNED NOT NULL,
    qty DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(12, 0) NOT NULL DEFAULT 0,
    PRIMARY KEY (report_id, part_id),
    KEY idx_report_parts_part (part_id),
    CONSTRAINT fk_report_parts_report
        FOREIGN KEY (report_id) REFERENCES repair_reports (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_report_parts_part
        FOREIGN KEY (part_id) REFERENCES parts (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id INT UNSIGNED NOT NULL,
    total_amount DECIMAL(12, 0) NOT NULL DEFAULT 0,
    status ENUM('draft', 'sent', 'accepted', 'rejected') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_quotes_request_status (request_id, status),
    CONSTRAINT fk_quotes_request
        FOREIGN KEY (request_id) REFERENCES requests (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_request (request_id),
    KEY idx_reviews_customer_created (customer_id, created_at),
    CONSTRAINT fk_reviews_request
        FOREIGN KEY (request_id) REFERENCES requests (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_reviews_customer
        FOREIGN KEY (customer_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedules (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    technician_id INT UNSIGNED NOT NULL,
    request_id INT UNSIGNED DEFAULT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    note TEXT DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_schedules_technician_time (technician_id, start_time, end_time),
    KEY idx_schedules_request (request_id),
    CONSTRAINT fk_schedules_technician
        FOREIGN KEY (technician_id) REFERENCES technicians (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_schedules_request
        FOREIGN KEY (request_id) REFERENCES requests (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO users (full_name, email, phone, password, role, address, status)
VALUES (
    'Administrator',
    'admin@dienlanh.com',
    NULL,
    '$2y$10$fz06jw3oQ.3W3KWcFMVwtOofA56/F//lSYKq9yeFJMID6NTdTc6eS',
    'admin',
    NULL,
    1
);

INSERT INTO service_categories (name, description, icon, status)
VALUES
    ('Điện dân dụng', 'Dịch vụ sửa chữa và lắp đặt điện dân dụng.', 'fa-bolt', 1),
    ('Máy lạnh', 'Dịch vụ vệ sinh, bảo dưỡng và sửa chữa máy lạnh.', 'fa-snowflake', 1),
    ('Tủ lạnh', 'Dịch vụ kiểm tra, bảo dưỡng và sửa chữa tủ lạnh.', 'fa-box', 1),
    ('Máy giặt', 'Dịch vụ vệ sinh, bảo dưỡng và sửa chữa máy giặt.', 'fa-soap', 1),
    ('Máy nước nóng', 'Dịch vụ kiểm tra, lắp đặt và sửa máy nước nóng.', 'fa-temperature-high', 1),
    ('Thiết bị gia dụng khác', 'Dịch vụ sửa chữa các thiết bị gia dụng khác.', 'fa-house', 1)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    icon = VALUES(icon),
    status = VALUES(status);

INSERT INTO services (category_id, name, description, base_price, est_minutes, status)
SELECT categories.id, seeded.name, seeded.description, seeded.base_price, seeded.est_minutes, 1
FROM (
    SELECT 'Điện dân dụng' AS category_name, 'Sửa chữa điện dân dụng tại nhà' AS name, 'Kiểm tra và khắc phục sự cố điện dân dụng.' AS description, 250000 AS base_price, 60 AS est_minutes
    UNION ALL SELECT 'Điện dân dụng', 'Lắp đặt đèn và thiết bị điện', 'Lắp đặt đèn, công tắc và thiết bị điện gia dụng.', 180000, 45
    UNION ALL SELECT 'Máy lạnh', 'Vệ sinh máy lạnh', 'Vệ sinh và kiểm tra hoạt động máy lạnh.', 250000, 60
    UNION ALL SELECT 'Máy lạnh', 'Sửa chữa máy lạnh', 'Kiểm tra và sửa chữa lỗi máy lạnh.', 350000, 90
    UNION ALL SELECT 'Tủ lạnh', 'Sửa chữa tủ lạnh', 'Kiểm tra và sửa chữa lỗi tủ lạnh.', 300000, 90
    UNION ALL SELECT 'Tủ lạnh', 'Bảo dưỡng tủ lạnh', 'Kiểm tra và bảo dưỡng hoạt động tủ lạnh.', 250000, 60
    UNION ALL SELECT 'Máy giặt', 'Sửa chữa máy giặt', 'Kiểm tra và sửa chữa lỗi máy giặt.', 300000, 90
    UNION ALL SELECT 'Máy giặt', 'Vệ sinh máy giặt', 'Vệ sinh lồng giặt và kiểm tra thiết bị.', 250000, 60
    UNION ALL SELECT 'Máy nước nóng', 'Sửa chữa máy nước nóng', 'Kiểm tra và sửa chữa lỗi máy nước nóng.', 300000, 90
    UNION ALL SELECT 'Máy nước nóng', 'Lắp đặt máy nước nóng', 'Lắp đặt và kiểm tra an toàn thiết bị.', 350000, 120
    UNION ALL SELECT 'Thiết bị gia dụng khác', 'Sửa chữa thiết bị gia dụng', 'Kiểm tra và sửa chữa thiết bị gia dụng.', 250000, 60
    UNION ALL SELECT 'Thiết bị gia dụng khác', 'Kiểm tra thiết bị gia dụng', 'Kiểm tra hoạt động và xác định lỗi thiết bị.', 150000, 45
) AS seeded
INNER JOIN service_categories AS categories
    ON categories.name = seeded.category_name
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    base_price = VALUES(base_price),
    est_minutes = VALUES(est_minutes),
    status = VALUES(status);