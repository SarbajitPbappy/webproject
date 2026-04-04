<?php
/**
 * Check that the connected database looks like HostelEase (not another app / empty users only).
 *
 *   php database/verify_hostelease_schema.php
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/database.php';

$requiredTables = [
    'users',
    'students',
    'rooms',
    'allocations',
    'fee_structures',
    'payments',
    'audit_logs',
];

try {
    $db = Database::getInstance();
    $missing = [];
    foreach ($requiredTables as $t) {
        $stmt = $db->query(
            "SELECT COUNT(*) AS c FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t",
            ['t' => $t]
        );
        if ((int) $stmt->fetch()['c'] === 0) {
            $missing[] = $t;
        }
    }

    if (!empty($missing)) {
        fwrite(STDERR, "❌ This database is missing HostelEase tables: " . implode(', ', $missing) . "\n\n");
        fwrite(STDERR, "Fix (pick one):\n");
        fwrite(STDERR, "  1) Create a NEW empty database on your host (recommended), point DB_NAME in .env to it,\n");
        fwrite(STDERR, "     then import the full schema:\n");
        fwrite(STDERR, "       mysql -h DB_HOST -P DB_PORT -u DB_USER -p DB_NAME < database/hostelease.sql\n\n");
        fwrite(STDERR, "  2) Then run:\n");
        fwrite(STDERR, "       php database/migrations/migrate_billing_entitlement.php\n");
        fwrite(STDERR, "       php database/migrations/migrate_login_attempts.php\n");
        fwrite(STDERR, "       php database/seeds/repair_accounts.php --with-warden\n\n");
        exit(1);
    }

    $stmt = $db->query(
        "SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'"
    );
    $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');
    foreach (['email', 'password_hash', 'role', 'status'] as $need) {
        if (!in_array($need, $cols, true)) {
            fwrite(STDERR, "❌ users table is missing column: {$need}\n");
            fwrite(STDERR, "   Re-import database/hostelease.sql into a clean database.\n");
            exit(1);
        }
    }

    echo "✅ Database \"" . DB_NAME . "\" has core HostelEase tables and users columns.\n";
    echo "   Next: php database/seeds/repair_accounts.php --with-warden\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'verify_hostelease_schema failed: ' . $e->getMessage() . "\n");
    exit(1);
}
