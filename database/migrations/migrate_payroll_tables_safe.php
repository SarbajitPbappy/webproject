<?php
/**
 * Create payroll tables if missing and backfill staff_details for staff/admin users.
 * Safe to run multiple times (no TRUNCATE).
 *
 *   php database/migrations/migrate_payroll_tables_safe.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance();

    $db->query("
        CREATE TABLE IF NOT EXISTS `staff_details` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED NOT NULL,
          `basic_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `join_date` DATE NOT NULL,
          `last_increment_date` DATE DEFAULT NULL,
          `bank_account` VARCHAR(100) DEFAULT NULL,
          `bank_name` VARCHAR(100) DEFAULT NULL,
          UNIQUE KEY `uk_staff_user` (`user_id`),
          CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "staff_details: OK\n";

    $db->query("
        CREATE TABLE IF NOT EXISTS `pay_slips` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED NOT NULL,
          `month_year` VARCHAR(7) NOT NULL,
          `basic_salary` DECIMAL(10,2) NOT NULL,
          `performance_bonus` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `deductions` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `net_salary` DECIMAL(10,2) NOT NULL,
          `status` ENUM('applied', 'approved', 'paid', 'rejected') NOT NULL DEFAULT 'applied',
          `office_days` TINYINT UNSIGNED DEFAULT NULL,
          `approved_by` INT UNSIGNED DEFAULT NULL,
          `paid_by` INT UNSIGNED DEFAULT NULL,
          `admin_notes` TEXT,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `paid_at` TIMESTAMP NULL DEFAULT NULL,
          UNIQUE KEY `uk_payslip_user_month` (`user_id`, `month_year`),
          INDEX `idx_payslip_status` (`status`),
          CONSTRAINT `fk_payslip_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_payslip_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_payslip_payer` FOREIGN KEY (`paid_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "pay_slips: OK\n";

    $pdo = $db->getConnection();
    $ins = $pdo->prepare(
        'INSERT INTO staff_details (user_id, basic_salary, join_date)
         SELECT u.id,
                CASE WHEN u.role = \'admin\' THEN 25000.00 ELSE 12000.00 END,
                COALESCE(DATE(u.created_at), CURDATE())
         FROM users u
         WHERE u.role IN (\'staff\', \'admin\')
           AND NOT EXISTS (SELECT 1 FROM staff_details sd WHERE sd.user_id = u.id)'
    );
    $ins->execute();
    $n = $ins->rowCount();
    echo "staff_details backfill: {$n} row(s) inserted.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'migrate_payroll_tables_safe failed: ' . $e->getMessage() . "\n");
    exit(1);
}
