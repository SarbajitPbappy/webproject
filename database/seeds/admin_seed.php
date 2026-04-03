<?php
/**
 * HostelEase — Admin Seed Script
 * 
 * Creates the default Super Admin account.
 * Run this once after importing the database schema.
 * 
 * Usage (from project root):
 *   php database/seeds/admin_seed.php
 * 
 * Default credentials:
 *   Email:    admin@hostelease.com
 *   Password: Admin@123
 */

// Define APP_ROOT for config includes
define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/config/database.php';

try {
    $db = Database::getInstance();

    // Check if super admin already exists
    $stmt = $db->query(
        "SELECT id FROM users WHERE email = :email",
        ['email' => 'admin@hostelease.com']
    );

    if ($stmt->fetch()) {
        echo "⚠️  Super Admin already exists. Skipping seed.\n";
        exit(0);
    }

    // Create super admin
    $passwordHash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 12]);

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
    echo "   Email:    admin@hostelease.com\n";
    echo "   Password: Admin@123\n";
    echo "   ⚠️  Change these credentials after first login!\n";

} catch (Exception $e) {
    echo "❌ Seed failed: " . $e->getMessage() . "\n";
    exit(1);
}
