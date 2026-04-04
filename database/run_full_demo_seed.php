<?php
/**
 * Fill an empty HostelEase database: super admin, safe migrations, then full Bangladesh demo
 * (wardens, staff, 100 students @hallportal.demo.bd).
 *
 * From the hostelease/ directory, with DB_* (or DB_PUBLIC_* for local CLI) configured:
 *   php database/run_full_demo_seed.php
 *   php database/run_full_demo_seed.php --force   # re-run demo seed if rows already exist
 *
 * Requires database/hostelease.sql already imported (tables exist).
 *
 * Optional next step:
 *   php database/seeds/demo_hostel_building_allocate.php --force --with-billing
 */

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$php = PHP_BINARY !== '' ? PHP_BINARY : 'php';

$steps = [
    'database/seeds/admin_seed.php',
    'database/migrations/migrate_payroll_tables_safe.php',
    'database/migrations/migrate_room_requests_transfer_fee.php',
    'database/migrations/migrate_student_billing_credit.php',
    'database/migrations/migrate_user_notifications.php',
    'database/migrations/migrate_students_registration_profile.php',
    'database/migrations/migrate_login_attempts.php',
];

foreach ($steps as $rel) {
    $path = $root . '/' . $rel;
    if (!is_file($path)) {
        fwrite(STDERR, "Missing file: {$rel}\n");
        exit(1);
    }
    echo "\n>>> {$php} {$rel}\n";
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($path);
    passthru($cmd, $code);
    if ($code !== 0) {
        fwrite(STDERR, "Stopped after failure (exit {$code}): {$rel}\n");
        exit($code);
    }
}

$demoArgs = array_slice($_SERVER['argv'] ?? [], 1);
$demoScript = $root . '/database/seeds/bangladesh_demo_seed.php';
$demoCmd = escapeshellarg($php) . ' ' . escapeshellarg($demoScript);
foreach ($demoArgs as $arg) {
    $demoCmd .= ' ' . escapeshellarg($arg);
}

echo "\n>>> bangladesh_demo_seed.php\n";
passthru($demoCmd, $code);
if ($code !== 0) {
    exit($code);
}

echo <<<'TXT'


Done.
  Super admin: admin@hostelease.com / Admin@123 (change after login)
  Demo users:  see database/seeds/bangladesh_demo_seed.php (Warden@123, Staff@123, Student@123)

Optional — DEMO tower + room allocations + sample bills:
  php database/seeds/demo_hostel_building_allocate.php --force --with-billing

Alternative synthetic dataset (truncates many tables; needs admin_seed first):
  php database/seeds/production_seed.php

TXT;
