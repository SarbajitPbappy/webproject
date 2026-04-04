<?php
/**
 * HostelEase — Admin Seed Script
 * 
 * Creates the default Super Admin account.
 * Run this once after importing the database schema.
 * 
 * Usage (from the hostelease directory):
 *   php database/seeds/admin_seed.php
 *   php database/seeds/admin_seed.php --reset-password   # fix broken hash / locked account
 *
 * Prefer repair_accounts.php on servers (upsert + optional warden):
 *   php database/seeds/repair_accounts.php --with-warden
 *
 * Default credentials:
 *   Email:    admin@hostelease.com
 *   Password: Admin@123
 */

// Define APP_ROOT for config includes
define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/config/database.php';

$resetPassword = in_array('--reset-password', $_SERVER['argv'] ?? [], true);

try {
    $db = Database::getInstance();

    $stmt = $db->query(
        "SELECT id FROM users WHERE email = :email",
        ['email' => 'admin@hostelease.com']
    );
    $exists = (bool) $stmt->fetch();

    $passwordHash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 12]);

    if ($exists && !$resetPassword) {
        echo "⚠️  Super Admin already exists. Skipping seed.\n";
        echo "    Run: php database/seeds/admin_seed.php --reset-password\n";
        echo "    Or:  php database/seeds/repair_accounts.php\n";
        exit(0);
    }

    if ($exists && $resetPassword) {
        $db->query(
            "UPDATE users SET password_hash = :pass, status = 'active', role = 'super_admin',
             full_name = :name WHERE email = :email",
            [
                'pass'  => $passwordHash,
                'name'  => 'System Administrator',
                'email' => 'admin@hostelease.com',
            ]
        );
        try {
            $db->query('DELETE FROM login_attempts WHERE email = :e', ['e' => 'admin@hostelease.com']);
        } catch (Throwable $e) {
            // ignore if table missing
        }
        echo "✅ Super Admin password reset.\n";
    } else {
        $db->query(
            "INSERT INTO users (full_name, email, password_hash, role, status)
             VALUES (:name, :email, :pass, :role, :status)",
            [
                'name'   => 'System Administrator',
                'email'  => 'admin@hostelease.com',
                'pass'   => $passwordHash,
                'role'   => 'super_admin',
                'status' => 'active',
            ]
        );
        echo "✅ Super Admin created successfully!\n";
    }

    echo "   Email:    admin@hostelease.com\n";
    echo "   Password: Admin@123\n";
    echo "   ⚠️  Change these credentials after first login!\n";

} catch (Exception $e) {
    echo "❌ Seed failed: " . $e->getMessage() . "\n";
    exit(1);
}
