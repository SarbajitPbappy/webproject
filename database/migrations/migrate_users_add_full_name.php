<?php
/**
 * Add users.full_name when missing (older / partial schemas break seeds and login UI).
 *
 *   php database/migrations/migrate_users_add_full_name.php
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
        echo "Skipping: no `students` table — import database/hostelease.sql first (see verify_hostelease_schema.php).\n";
        exit(0);
    }

    if (columnExists($db, 'users', 'full_name')) {
        echo "users.full_name: already present.\n";
        exit(0);
    }

    if (columnExists($db, 'users', 'name')) {
        echo "users has `name` but not `full_name`. Adding full_name and copying from name...\n";
        $db->query(
            "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT '' AFTER email"
        );
        $db->query("UPDATE users SET full_name = name WHERE full_name = '' OR full_name IS NULL");
        echo "Done.\n";
        exit(0);
    }

    $db->query(
        "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT 'User' AFTER email"
    );
    echo "users.full_name: added (existing rows set to 'User' until you edit them).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'migrate_users_add_full_name failed: ' . $e->getMessage() . "\n");
    exit(1);
}
