-- =====================================================================
-- HostelEase — Complete Database Schema
-- Version: 1.0.0
-- Engine: MySQL 8.x / InnoDB
-- Charset: utf8mb4
-- 
-- Import this file into MySQL Workbench or via command line:
--   mysql -u root -p < hostelease.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;



-- =====================================================================
-- TABLE: users
-- All system users (super_admin, admin, student, staff) in one table.
-- Role column differentiates access levels.
-- =====================================================================
CREATE TABLE `users` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `full_name`     VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('super_admin','admin','student','staff') NOT NULL,
  `status`        ENUM('active','suspended','inactive') NOT NULL DEFAULT 'active',
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `phone`         VARCHAR(20) DEFAULT NULL,
  `system_id_no`  VARCHAR(30) DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_users_email` (`email`),
  UNIQUE KEY `uk_users_id_no` (`system_id_no`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: students
-- Extended profile data for users with role = 'student'.
-- Linked to users table via user_id (1:1).
-- =====================================================================
CREATE TABLE `students` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT UNSIGNED NOT NULL,
  `student_id_no`   VARCHAR(30) NOT NULL,
  `phone`           VARCHAR(20) DEFAULT NULL,
  `guardian_name`   VARCHAR(100) DEFAULT NULL,
  `guardian_phone`  VARCHAR(20) DEFAULT NULL,
  `nid_or_card`     VARCHAR(255) DEFAULT NULL,
  `enrolled_date`       DATE DEFAULT NULL,
  `checkout_date`       DATE DEFAULT NULL,
  `entitled_room_type` ENUM('single','double','triple','dormitory') DEFAULT NULL
    COMMENT 'Room tier paid for; allocation must match',
  UNIQUE KEY `uk_students_user_id` (`user_id`),
  UNIQUE KEY `uk_students_id_no` (`student_id_no`),
  INDEX `idx_students_enrolled` (`enrolled_date`),
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: rooms
-- Hostel room inventory with capacity and status tracking.
-- =====================================================================
CREATE TABLE `rooms` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_number` VARCHAR(20) NOT NULL,
  `floor`       TINYINT DEFAULT NULL,
  `type`        ENUM('single','double','triple','dormitory') NOT NULL DEFAULT 'single',
  `capacity`    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `facilities`  TEXT DEFAULT NULL,
  `status`      ENUM('available','full','maintenance') NOT NULL DEFAULT 'available',
  UNIQUE KEY `uk_rooms_number` (`room_number`),
  INDEX `idx_rooms_status` (`status`),
  INDEX `idx_rooms_type` (`type`),
  INDEX `idx_rooms_floor` (`floor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: allocations
-- Tracks which student is assigned to which room, with full history.
-- =====================================================================
CREATE TABLE `allocations` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`   INT UNSIGNED NOT NULL,
  `room_id`      INT UNSIGNED NOT NULL,
  `allocated_by` INT UNSIGNED DEFAULT NULL,
  `start_date`   DATE NOT NULL,
  `end_date`     DATE DEFAULT NULL,
  `status`       ENUM('active','transferred','vacated') NOT NULL DEFAULT 'active',
  `notes`        TEXT DEFAULT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_alloc_student` (`student_id`),
  INDEX `idx_alloc_room` (`room_id`),
  INDEX `idx_alloc_status` (`status`),
  CONSTRAINT `fk_alloc_student` FOREIGN KEY (`student_id`)
    REFERENCES `students`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_alloc_room` FOREIGN KEY (`room_id`)
    REFERENCES `rooms`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_alloc_allocator` FOREIGN KEY (`allocated_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: waitlist
-- Queue for students waiting for room allocation.
-- =====================================================================
CREATE TABLE `waitlist` (
  `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`           INT UNSIGNED NOT NULL,
  `preferred_room_type`  ENUM('single','double','triple','dormitory') DEFAULT NULL,
  `requested_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`       ENUM('waiting','allocated','cancelled') NOT NULL DEFAULT 'waiting',
  INDEX `idx_waitlist_status` (`status`),
  CONSTRAINT `fk_waitlist_student` FOREIGN KEY (`student_id`)
    REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: fee_structures
-- Defines various fee types (rent, utility, laundry, etc.).
-- =====================================================================
CREATE TABLE `fee_structures` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`           VARCHAR(100) NOT NULL,
  `amount`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `frequency`      ENUM('monthly','one_time','yearly') NOT NULL DEFAULT 'monthly',
  `is_active`      BOOLEAN NOT NULL DEFAULT TRUE,
  `fee_category`   ENUM('room_rent','security_deposit','meal','utility','service','other') NOT NULL DEFAULT 'other',
  `maps_room_type` ENUM('single','double','triple','dormitory') DEFAULT NULL
    COMMENT 'For room_rent: which tier payment unlocks',
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_fees_active` (`is_active`),
  INDEX `idx_fees_category` (`fee_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: payments
-- Records individual payment transactions per student.
-- =====================================================================
CREATE TABLE `payments` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`     INT UNSIGNED NOT NULL,
  `fee_id`         INT UNSIGNED NOT NULL,
  `amount_paid`    DECIMAL(10,2) NOT NULL,
  `payment_date`   DATE NOT NULL,
  `receipt_no`     VARCHAR(50) NOT NULL,
  `payment_method` ENUM('cash','bank','online') NOT NULL DEFAULT 'cash',
  `recorded_by`    INT UNSIGNED DEFAULT NULL,
  `month_year`         VARCHAR(10) DEFAULT NULL,
  `notes`              TEXT DEFAULT NULL,
  `billing_charge_id`  INT UNSIGNED DEFAULT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_payments_receipt` (`receipt_no`),
  INDEX `idx_payments_student` (`student_id`),
  INDEX `idx_payments_billing_charge` (`billing_charge_id`),
  INDEX `idx_payments_date` (`payment_date`),
  INDEX `idx_payments_month` (`month_year`),
  CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`)
    REFERENCES `students`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_fee` FOREIGN KEY (`fee_id`)
    REFERENCES `fee_structures`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_recorder` FOREIGN KEY (`recorded_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: billing_charges
-- Warden-issued slips (monthly meal/utilities/rent, enrollment, etc.)
-- =====================================================================
CREATE TABLE `billing_charges` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`    INT UNSIGNED NOT NULL,
  `fee_id`        INT UNSIGNED NOT NULL,
  `period_month`  VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `amount_due`    DECIMAL(10,2) NOT NULL,
  `status`        ENUM('pending','paid','waived') NOT NULL DEFAULT 'pending',
  `issued_by`     INT UNSIGNED DEFAULT NULL,
  `issued_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_id`    INT UNSIGNED DEFAULT NULL,
  `notes`         TEXT DEFAULT NULL,
  UNIQUE KEY `uk_bill_student_fee_period` (`student_id`,`fee_id`,`period_month`),
  INDEX `idx_bill_student_status` (`student_id`,`status`),
  INDEX `idx_bill_period` (`period_month`),
  INDEX `idx_bill_payment` (`payment_id`),
  CONSTRAINT `fk_bill_student` FOREIGN KEY (`student_id`)
    REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bill_fee` FOREIGN KEY (`fee_id`)
    REFERENCES `fee_structures`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bill_issuer` FOREIGN KEY (`issued_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: complaints
-- Student complaint/ticket system with SLA tracking.
-- =====================================================================
CREATE TABLE `complaints` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`   INT UNSIGNED NOT NULL,
  `category`     VARCHAR(100) NOT NULL,
  `description`  TEXT NOT NULL,
  `priority`     ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `status`       ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_to`  INT UNSIGNED DEFAULT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at`  TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_complaints_student` (`student_id`),
  INDEX `idx_complaints_status` (`status`),
  INDEX `idx_complaints_priority` (`priority`),
  INDEX `idx_complaints_assigned` (`assigned_to`),
  CONSTRAINT `fk_complaints_student` FOREIGN KEY (`student_id`)
    REFERENCES `students`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_complaints_assignee` FOREIGN KEY (`assigned_to`)
    REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: notices
-- Admin announcements visible to students on dashboard.
-- =====================================================================
CREATE TABLE `notices` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`      VARCHAR(200) NOT NULL,
  `body`       TEXT NOT NULL,
  `posted_by`  INT UNSIGNED DEFAULT NULL,
  `is_pinned`  BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notices_pinned` (`is_pinned`),
  CONSTRAINT `fk_notices_poster` FOREIGN KEY (`posted_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: audit_logs
-- Immutable log of every CUD operation in the system.
-- =====================================================================
CREATE TABLE `audit_logs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `action`     VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(100) NOT NULL,
  `record_id`  INT UNSIGNED DEFAULT NULL,
  `details`    TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_user` (`user_id`),
  INDEX `idx_audit_table` (`table_name`),
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: password_resets
-- Token-based password reset tracking.
-- =====================================================================
CREATE TABLE `password_resets` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used`       BOOLEAN NOT NULL DEFAULT FALSE,
  UNIQUE KEY `uk_reset_token` (`token`),
  INDEX `idx_reset_user` (`user_id`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: login_attempts
-- Tracks failed login attempts for throttling/lockout.
-- =====================================================================
CREATE TABLE `login_attempts` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email`        VARCHAR(150) NOT NULL,
  `ip_address`   VARCHAR(45) DEFAULT NULL,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_attempts_email` (`email`),
  INDEX `idx_attempts_time` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Default Fee Structures
-- =====================================================================
INSERT INTO `fee_structures` (`name`, `amount`, `frequency`, `is_active`, `fee_category`, `maps_room_type`) VALUES
('Room Rent (Single)', 5000.00, 'monthly', TRUE, 'room_rent', 'single'),
('Room Rent (Double)', 3500.00, 'monthly', TRUE, 'room_rent', 'double'),
('Room Rent (Triple)', 3000.00, 'monthly', TRUE, 'room_rent', 'triple'),
('Room Rent (Dormitory)', 2000.00, 'monthly', TRUE, 'room_rent', 'dormitory'),
('Security Deposit', 10000.00, 'one_time', TRUE, 'security_deposit', NULL),
('Dining / Meal Plan', 4000.00, 'monthly', TRUE, 'meal', NULL),
('Utility Charge', 500.00, 'monthly', TRUE, 'utility', NULL),
('Laundry Service', 300.00, 'monthly', TRUE, 'service', NULL),
('Annual Maintenance', 2000.00, 'yearly', TRUE, 'other', NULL);

-- =====================================================================
-- TABLE: transactions
-- Unified financial ledger for income (payments) and expenses (payroll).
-- =====================================================================
CREATE TABLE `transactions` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type`             ENUM('income','expense') NOT NULL,
  `amount`           DECIMAL(10,2) NOT NULL,
  `reference_type`   VARCHAR(50) DEFAULT NULL, -- 'payment', 'payroll', etc.
  `reference_id`     INT UNSIGNED DEFAULT NULL,
  `description`      TEXT DEFAULT NULL,
  `recorded_by`      INT UNSIGNED DEFAULT NULL,
  `transaction_date` DATE NOT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_trans_type` (`type`),
  INDEX `idx_trans_date` (`transaction_date`),
  CONSTRAINT `fk_trans_recorder` FOREIGN KEY (`recorded_by`)
    REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Schema complete. Run database/seeds/admin_seed.php to create
-- the default Super Admin account.
-- =====================================================================
