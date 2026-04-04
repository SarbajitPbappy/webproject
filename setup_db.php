<?php
/**
 * One-time schema import: runs database/hostelease.sql against the DB from config.
 *
 * From the hostelease directory (DB_* in environment or .env):
 *   php setup_db.php
 *
 * Railway: `railway run` still runs PHP on your machine. mysql.railway.internal will not resolve.
 * Add DB_PUBLIC_HOST (+ DB_PUBLIC_PORT if not 3306) to .env — values from MySQL → Connect
 * (public TCP proxy). Then: php setup_db.php   (railway run optional if .env is loaded)
 *
 * Requires an empty database (no existing HostelEase tables), or import will fail.
 */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

require_once APP_ROOT . '/config/config.php';

$dsnParts = [
    'mysql:host=' . DB_HOST,
    'port=' . DB_PORT,
    'dbname=' . DB_NAME,
    'charset=' . DB_CHARSET,
];
if (defined('DB_SSL') && DB_SSL) {
    $dsnParts[] = 'sslmode=require';
}
$dsn = implode(';', $dsnParts);

$pdoOptions = [
    PDO::ATTR_ERRMODE                => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
];
if (defined('DB_SSL') && DB_SSL) {
    $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

$sqlPath = APP_ROOT . '/database/hostelease.sql';
$sql = file_get_contents($sqlPath);
if ($sql === false) {
    fwrite(STDERR, "Cannot read: {$sqlPath}\n");
    exit(1);
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
    $pdo->exec($sql);
    echo "Schema imported successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error importing schema: ' . $e->getMessage() . "\n");
    exit(1);
}
