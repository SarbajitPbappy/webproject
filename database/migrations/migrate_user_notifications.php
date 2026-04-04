<?php
/**
 * In-app notifications (e.g. new billing slips).
 *
 *   php database/migrations/migrate_user_notifications.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications'"
    );
    if ((int) $stmt->fetch()['c'] > 0) {
        echo "user_notifications: already exists.\n";
        exit(0);
    }

    $db->query("
        CREATE TABLE `user_notifications` (
          `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id`           INT UNSIGNED NOT NULL,
          `title`             VARCHAR(200) NOT NULL,
          `body`              TEXT NOT NULL,
          `notification_type` VARCHAR(40) NOT NULL DEFAULT 'general',
          `is_read`           TINYINT(1) NOT NULL DEFAULT 0,
          `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_un_user_read` (`user_id`, `is_read`),
          INDEX `idx_un_created` (`created_at`),
          CONSTRAINT `fk_un_user` FOREIGN KEY (`user_id`)
            REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "user_notifications: created.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'migrate_user_notifications failed: ' . $e->getMessage() . "\n");
    exit(1);
}
