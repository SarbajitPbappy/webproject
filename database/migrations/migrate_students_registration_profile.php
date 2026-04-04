<?php
/**
 * Adds students.gender and students.course for self-registration (auth/register).
 *
 *   php database/migrations/migrate_students_registration_profile.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

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

    if (!columnExists($db, 'students', 'gender')) {
        $db->query(
            "ALTER TABLE students
             ADD COLUMN gender VARCHAR(32) NULL DEFAULT NULL
             COMMENT 'Self-registration / profile'
             AFTER phone"
        );
        echo "students.gender: added.\n";
    } else {
        echo "students.gender: already exists.\n";
    }

    if (!columnExists($db, 'students', 'course')) {
        $db->query(
            "ALTER TABLE students
             ADD COLUMN course VARCHAR(200) NULL DEFAULT NULL
             COMMENT 'Course or department'
             AFTER gender"
        );
        echo "students.course: added.\n";
    } else {
        echo "students.course: already exists.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'migrate_students_registration_profile failed: ' . $e->getMessage() . "\n");
    exit(1);
}
