<?php
/**
 * Create login_attempts if missing (partial schema imports often skip this table).
 *
 *   php database/migrations/migrate_login_attempts.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'login_attempts'"
    );
    if ((int) $stmt->fetch()['c'] > 0) {
        echo "login_attempts: already exists.\n";
        exit(0);
    }

    $db->query("
        CREATE TABLE `login_attempts` (
          `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `email`        VARCHAR(150) NOT NULL,
          `ip_address`   VARCHAR(45) DEFAULT NULL,
          `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_attempts_email` (`email`),
          INDEX `idx_attempts_time` (`attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "login_attempts: created successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'migrate_login_attempts failed: ' . $e->getMessage() . "\n");
    exit(1);
}
