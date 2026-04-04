<?php
/**
 * Add core users columns missing on partial/old databases (role, status, etc.).
 *
 *   php database/migrations/migrate_users_core_columns.php
 *
 * Prefer a full import of database/hostelease.sql on an empty database when possible.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

function hosteleaseStudentsTableExists(Database $db): bool
{
    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students'"
    );
    return (int) $stmt->fetch()['c'] > 0;
}

function columnExists(Database $db, string $table, string $column): bool
{
    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c",
        ['t' => $table, 'c' => $column]
    );
    return (int) $stmt->fetch()['c'] > 0;
}

try {
    $db = Database::getInstance();

    if (!hosteleaseStudentsTableExists($db)) {
        echo "Skipping: no `students` table — import database/hostelease.sql first.\n";
        exit(0);
    }

    $n = 0;

    if (!columnExists($db, 'users', 'role')) {
        $db->query(
            "ALTER TABLE users ADD COLUMN `role` ENUM('super_admin','admin','student','staff') NOT NULL DEFAULT 'student' AFTER `password_hash`"
        );
        $n++;
    }
    if (!columnExists($db, 'users', 'status')) {
        $db->query(
            "ALTER TABLE users ADD COLUMN `status` ENUM('active','suspended','inactive') NOT NULL DEFAULT 'active' AFTER `role`"
        );
        $n++;
    }
    if (!columnExists($db, 'users', 'profile_photo')) {
        $db->query('ALTER TABLE users ADD COLUMN `profile_photo` VARCHAR(255) DEFAULT NULL');
        $n++;
    }
    if (!columnExists($db, 'users', 'phone')) {
        $db->query('ALTER TABLE users ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL');
        $n++;
    }
    if (!columnExists($db, 'users', 'system_id_no')) {
        $db->query('ALTER TABLE users ADD COLUMN `system_id_no` VARCHAR(30) DEFAULT NULL');
        $n++;
    }
    if (!columnExists($db, 'users', 'created_at')) {
        $db->query('ALTER TABLE users ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $n++;
    }
    if (!columnExists($db, 'users', 'updated_at')) {
        $db->query(
            'ALTER TABLE users ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        );
        $n++;
    }

    if ($n === 0) {
        echo "users: core columns already present.\n";
        exit(0);
    }

    echo "users: applied {$n} column addition(s).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'migrate_users_core_columns failed: ' . $e->getMessage() . "\n");
    exit(1);
}
