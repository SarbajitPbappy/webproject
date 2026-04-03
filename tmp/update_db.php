<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    echo "Updating users table...\n";
    $conn->exec("ALTER TABLE users ADD COLUMN system_id_no VARCHAR(30) DEFAULT NULL AFTER profile_photo, ADD UNIQUE KEY uk_users_id_no (system_id_no)");
    
    echo "Updating transactions table...\n";
    // Drop existing if it doesn't match closely, or just alter. 
    // Let's drop and recreate to be sure it matches hostelease.sql exactly
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    $conn->exec("DROP TABLE IF EXISTS transactions");
    $conn->exec("CREATE TABLE `transactions` (
      `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `type`             ENUM('income','expense') NOT NULL,
      `amount`           DECIMAL(10,2) NOT NULL,
      `reference_type`   VARCHAR(50) DEFAULT NULL,
      `reference_id`     INT UNSIGNED DEFAULT NULL,
      `description`      TEXT DEFAULT NULL,
      `recorded_by`      INT UNSIGNED DEFAULT NULL,
      `transaction_date` DATE NOT NULL,
      `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_trans_type` (`type`),
      INDEX `idx_trans_date` (`transaction_date`),
      CONSTRAINT `fk_trans_recorder` FOREIGN KEY (`recorded_by`)
        REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Database updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
