<?php
/**
 * HostelEase — Repair / ensure working login accounts (server or local).
 *
 * Use when:
 *   - Schema was imported but passwords don’t work
 *   - admin_seed skipped because the row already existed with a bad hash
 *   - You need a known-good super admin + warden after a messy DB
 *
 * From the hostelease directory:
 *   php database/seeds/repair_accounts.php
 *   php database/seeds/repair_accounts.php --with-warden
 *
 * Does NOT drop tables. For a full empty database, import database/hostelease.sql first,
 * then run migrate_* scripts if needed, then this file.
 */

define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/config/database.php';

$argv = $_SERVER['argv'] ?? [];
$withWarden = in_array('--with-warden', $argv, true);

/**
 * @return string[] column names on users
 */
function usersTableColumns(Database $db): array
{
    $stmt = $db->query('SHOW COLUMNS FROM users');
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
}

/**
 * Upsert a user row compatible with current users table shape.
 */
function upsertUser(Database $db, array $cols, string $displayName, string $email, string $hash, string $role): void
{
    $have = array_flip($cols);
    $fields = ['email', 'password_hash', 'role', 'status'];
    $params = [
        'email'         => $email,
        'password_hash' => $hash,
        'role'          => $role,
        'status'        => 'active',
    ];

    if (isset($have['full_name'])) {
        $fields[] = 'full_name';
        $params['full_name'] = $displayName;
    } elseif (isset($have['name'])) {
        $fields[] = 'name';
        $params['name'] = $displayName;
    }

    $placeholders = array_map(fn ($f) => ':' . $f, $fields);
    $insertSql = 'INSERT INTO users (`' . implode('`,`', $fields) . '`) VALUES (' . implode(',', $placeholders) . ')';

    $updates = ['password_hash = VALUES(password_hash)', 'status = VALUES(status)', 'role = VALUES(role)'];
    if (isset($have['full_name'])) {
        $updates[] = 'full_name = VALUES(full_name)';
    } elseif (isset($have['name'])) {
        $updates[] = 'name = VALUES(name)';
    }

    $sql = $insertSql . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    $db->query($sql, $params);
}

try {
    $db = Database::getInstance();

    $chk = $db->query(
        "SELECT COUNT(*) AS c FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students'"
    );
    if ((int) $chk->fetch()['c'] === 0) {
        throw new RuntimeException(
            'This database is not a HostelEase schema (missing `students`). Run: php database/verify_hostelease_schema.php'
        );
    }

    $cols = usersTableColumns($db);
    if (!in_array('email', $cols, true) || !in_array('password_hash', $cols, true)) {
        throw new RuntimeException(
            'users table is missing email or password_hash. Import database/hostelease.sql into this database.'
        );
    }
    if (!in_array('full_name', $cols, true) && !in_array('name', $cols, true)) {
        throw new RuntimeException(
            'users table needs full_name or name column. Import database/hostelease.sql to match HostelEase.'
        );
    }

    $cost = ['cost' => 12];

    $superHash = password_hash('Admin@123', PASSWORD_BCRYPT, $cost);
    upsertUser($db, $cols, 'System Administrator', 'admin@hostelease.com', $superHash, 'super_admin');
    echo "✅ Super Admin ready: admin@hostelease.com / Admin@123\n";

    if ($withWarden) {
        $wHash = password_hash('Warden@123', PASSWORD_BCRYPT, $cost);
        upsertUser($db, $cols, 'Hall Warden', 'warden@hostelease.com', $wHash, 'admin');
        echo "✅ Warden ready: warden@hostelease.com / Warden@123\n";
    }

    // Clear lockouts for these emails
    foreach (['admin@hostelease.com', 'warden@hostelease.com'] as $em) {
        try {
            $db->query('DELETE FROM login_attempts WHERE email = :e', ['e' => $em]);
        } catch (Throwable $e) {
            // table may be missing; ignore
        }
    }

    echo "\nLog in using the correct portal:\n";
    echo "  • Super Admin → choose Super Admin login, email admin@hostelease.com\n";
    echo "  • Warden      → choose Admin/Warden login, email warden@hostelease.com (if --with-warden)\n";
    echo "\nOther emails (e.g. warden@gmail.com) are separate accounts — they must exist in `users` with a valid hash.\n";
} catch (Throwable $e) {
    fwrite(STDERR, '❌ repair_accounts failed: ' . $e->getMessage() . "\n");
    exit(1);
}
