<?php
/**
 * Credit balance applied to the next tier-matched room-rent slip after a downgrade transfer.
 *
 *   php database/migrations/migrate_student_billing_credit.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'billing_credit_balance'"
    );
    if ((int) $stmt->fetch()['c'] > 0) {
        echo "students.billing_credit_balance: already exists.\n";
        exit(0);
    }
    $db->query(
        'ALTER TABLE students ADD COLUMN billing_credit_balance DECIMAL(10,2) NOT NULL DEFAULT 0
         COMMENT \'Applied to next room-rent slip after cheaper-room transfer\''
    );
    echo "students.billing_credit_balance: added.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'migrate_student_billing_credit failed: ' . $e->getMessage() . "\n");
    exit(1);
}
