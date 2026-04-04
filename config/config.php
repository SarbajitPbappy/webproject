<?php
/**
 * HostelEase — Application Configuration
 * 
 * Central configuration file defining all application constants.
 * Modify these values according to your environment.
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Environment (development/production)
define('APP_ENV', getenv('APP_ENV') ?: 'development');

/**
 * Load key/value pairs from a local .env file (no external dependency).
 * Lines format: KEY=value
 * - Ignores empty lines and comments (# ...).
 * - Does not override values already present in the environment.
 */
function loadEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '') {
            continue;
        }

        $current = getenv($key);
        if ($current !== false && $current !== '') {
            continue; // don't override real env vars
        }

        $value = trim($value);
        // Strip optional surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

loadEnvFile(APP_ROOT . '/.env');

/**
 * Parse a mysql:// or mysql2:// URL (e.g. Railway DATABASE_URL / Connect string).
 *
 * @return array{host:string,port:?string,user:?string,pass:?string,database:?string}|null
 */
function hostelease_parse_mysql_connection_url(string $raw): ?array
{
    $raw = trim($raw);
    if ($raw === '' || !preg_match('#^mysql2?://#i', $raw)) {
        return null;
    }

    $parts = parse_url($raw);
    if ($parts === false || empty($parts['host'])) {
        return null;
    }

    $path = $parts['path'] ?? '';
    $database = null;
    if ($path !== '' && $path !== '/') {
        $database = rawurldecode(ltrim($path, '/'));
    }

    return [
        'host'     => $parts['host'],
        'port'     => isset($parts['port']) ? (string) (int) $parts['port'] : null,
        'user'     => isset($parts['user']) ? rawurldecode($parts['user']) : null,
        'pass'     => array_key_exists('pass', $parts) && $parts['pass'] !== null ? rawurldecode((string) $parts['pass']) : null,
        'database' => $database,
    ];
}

// ─── Application Settings ───────────────────────────────────────────
define('APP_NAME', 'HostelEase');
define('APP_VERSION', '1.0.0');
// BASE_URL is used for generating links. In production (Render), the host is dynamic,
// so we compute it from environment/request when not explicitly provided.
$baseUrlEnv = getenv('BASE_URL') ?: '';
// Safety: don't allow localhost BASE_URL in production.
$isLocalhostBase =
    str_contains($baseUrlEnv, 'localhost') ||
    str_contains($baseUrlEnv, '127.0.0.1') ||
    str_contains($baseUrlEnv, '0.0.0.0');

if (!empty($baseUrlEnv) && !(APP_ENV !== 'development' && $isLocalhostBase)) {
    $b = trim($baseUrlEnv);
    // Host-only values (e.g. app.up.railway.app) are treated as *relative paths* in HTML,
    // which stacks on every click (…/host/host/…). Always store an absolute URL.
    if (!preg_match('#^https?://#i', $b)) {
        $b = ($isLocalhostBase ? 'http://' : 'https://') . ltrim($b, '/');
    }
    $computedBaseUrl = rtrim($b, '/') . '/';
} else {
    $forwardedProto = strtolower(trim($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $computedBaseUrl = $scheme . '://' . $host . '/';
}
define('BASE_URL', $computedBaseUrl);

// ─── Database Configuration ─────────────────────────────────────────
// CLI on your laptop: internal Railway hostnames do not resolve. Use either:
// - DB_PUBLIC_HOST=junction.proxy.rlwy.net (+ DB_PUBLIC_PORT), or
// - Paste the full mysql://… URL from Railway → MySQL → Connect into DB_PUBLIC_HOST, or
// - Rely on DATABASE_URL / MYSQL_URL when `railway run` injects them.
// Web (Apache) is not CLI, so it keeps using DB_HOST / DB_PORT / DB_* from env.
$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';

$dbHost = getenv('DB_HOST') ?: 'mysql.railway.internal';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'railway';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

if ($isCli) {
    $parsed = null;
    $urlCandidates = [
        getenv('DB_PUBLIC_HOST'),
        getenv('MYSQL_PUBLIC_URL'),
        getenv('DATABASE_PUBLIC_URL'),
        getenv('DATABASE_URL'),
        getenv('MYSQL_URL'),
    ];
    foreach ($urlCandidates as $candidate) {
        if ($candidate === false || $candidate === '') {
            continue;
        }
        $p = hostelease_parse_mysql_connection_url($candidate);
        if ($p === null) {
            continue;
        }
        // `railway run` may inject internal URLs; those do not resolve on your laptop.
        if (str_ends_with($p['host'], '.railway.internal')) {
            continue;
        }
        $parsed = $p;
        break;
    }

    if ($parsed !== null) {
        $dbHost = $parsed['host'];
        if ($parsed['port'] !== null) {
            $dbPort = $parsed['port'];
        }
        if ($parsed['database'] !== null) {
            $dbName = $parsed['database'];
        }
        if ($parsed['user'] !== null) {
            $dbUser = $parsed['user'];
        }
        if ($parsed['pass'] !== null) {
            $dbPass = $parsed['pass'];
        }
    } else {
        $publicHost = getenv('DB_PUBLIC_HOST');
        if ($publicHost !== false && $publicHost !== '') {
            $dbHost = $publicHost;
            $publicPort = getenv('DB_PUBLIC_PORT');
            if ($publicPort !== false && $publicPort !== '') {
                $dbPort = $publicPort;
            }
        }
    }
}

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', 'utf8mb4');
// Default to false for local dev; set true in Render if your DB requires SSL.
define('DB_SSL', filter_var(getenv('DB_SSL') ?: 'false', FILTER_VALIDATE_BOOLEAN));

// ─── File Upload Settings ───────────────────────────────────────────
define('UPLOAD_PATH', APP_ROOT . '/public/uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);
define('ALLOWED_DOC_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

// ─── Session Settings ───────────────────────────────────────────────
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('SESSION_NAME', 'HOSTELEASE_SID');

// ─── Security Settings ──────────────────────────────────────────────
define('SUPER_ADMIN_EMAIL', 'admin@hostelease.com');
// Optional super-admin "virtual login" fallback.
// Prefer real DB seeded super_admin user instead of hardcoded password hashes.
define('SUPER_ADMIN_PASS_HASH', getenv('SUPER_ADMIN_PASS_HASH') ?: '');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds
define('CSRF_TOKEN_NAME', '_csrf_token');
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour

// ─── Pagination ─────────────────────────────────────────────────────
define('RECORDS_PER_PAGE', 15);

// ─── SLA Settings (Complaints) ──────────────────────────────────────
define('SLA_HIGH_PRIORITY_HOURS', 24);
define('SLA_MEDIUM_PRIORITY_HOURS', 72);

// ─── Receipt Settings ───────────────────────────────────────────────
define('RECEIPT_PREFIX', 'RCP');

// ─── Error Reporting (Production) ───────────────────────────────────
// In production: display_errors = Off, log_errors = On
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/logs/error.log');
error_reporting(E_ALL);

// ─── Timezone ───────────────────────────────────────────────────────
date_default_timezone_set('Asia/Dhaka');
