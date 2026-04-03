<?php
/**
 * HostelEase — Payroll & Finances Migration Script
 * Run this from CLI: php database/migrations/migrate_payroll.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance();
    
    echo "Starting Payroll & Finances Migration...\n";

    // 1. Transactions Ledger
    $db->query("
        CREATE TABLE IF NOT EXISTS `transactions` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `type` ENUM('income', 'expense') NOT NULL,
          `amount` DECIMAL(12,2) NOT NULL,
          `reference_type`   VARCHAR(50) DEFAULT NULL, -- 'payment', 'payroll', etc.
          `reference_id`     INT UNSIGNED DEFAULT NULL,
          `description` TEXT,
          `recorded_by` INT UNSIGNED DEFAULT NULL,
          `transaction_date` DATE NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_trans_type` (`type`),
          INDEX `idx_trans_date` (`transaction_date`),
          CONSTRAINT `fk_trans_recorder` FOREIGN KEY (`recorded_by`)
            REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ Table 'transactions' created.\n";

    // 2. Staff Details
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
          CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ Table 'staff_details' created.\n";

    // 3. Pay Slips
    $db->query("
        CREATE TABLE IF NOT EXISTS `pay_slips` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED NOT NULL,
          `month_year` VARCHAR(7) NOT NULL, -- Format: YYYY-MM
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
          CONSTRAINT `fk_payslip_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_payslip_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
          CONSTRAINT `fk_payslip_payer` FOREIGN KEY (`paid_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ Table 'pay_slips' created.\n";

    // 4. Seed initial funds and dummy data
    // Add existing payments as Income transactions
    $payments = $db->query("SELECT id, amount_paid, payment_date, recorded_by FROM payments")->fetchAll();
    
    // First truncate transactions to prevent duplicates if ran twice
    $db->query("TRUNCATE TABLE transactions");
    
    $stmt = $db->getConnection()->prepare("
        INSERT INTO transactions (type, reference_type, amount, description, reference_id, recorded_by, transaction_date)
        VALUES ('income', 'payment', :amount, 'Student Fee Payment', :ref, :usr, :pdate)
    ");
    
    $count = 0;
    foreach ($payments as $p) {
        $stmt->execute([
            ':amount' => $p['amount_paid'],
            ':ref'    => $p['id'],
            ':usr'    => $p['recorded_by'],
            ':pdate'  => $p['payment_date']
        ]);
        $count++;
    }
    echo "✅ Seeded {$count} historical payments into transactions ledger.\n";

    // 5. Initialize Staff Details for all Staff/Warden users
    $staffUsers = $db->query("SELECT id, role, created_at FROM users WHERE role IN ('staff', 'admin')")->fetchAll();
    
    // TRUNCATE STAFF DETAILS SAFELY
    $db->query("SET FOREIGN_KEY_CHECKS = 0;");
    $db->query("TRUNCATE TABLE staff_details;");
    $db->query("SET FOREIGN_KEY_CHECKS = 1;");
    
    $stmtStaff = $db->getConnection()->prepare("
        INSERT INTO staff_details (user_id, basic_salary, join_date)
        VALUES (:uid, :salary, :joindate)
    ");
    
    $sCount = 0;
    foreach ($staffUsers as $u) {
        $salary = ($u['role'] === 'admin') ? 25000.00 : 12000.00;
        // set join date dynamically somewhere in in past 2-3 years to demonstrate +1.5% increment
        $joinTime = strtotime("-".rand(10, 800)." days"); 
        
        $stmtStaff->execute([
            ':uid' => $u['id'],
            ':salary' => $salary,
            ':joindate' => date('Y-m-d', $joinTime)
        ]);
        $sCount++;
    }
    echo "✅ Seeded {$sCount} staff details records.\n";

    echo "\n🎉 MIGRATION SUCCESSFUL!\n";

} catch (Exception $e) {
    echo "\n❌ MIGRATION FAILED: " . $e->getMessage() . "\n";
}
