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

// ─── Application Settings ───────────────────────────────────────────
define('APP_NAME', 'HostelEase');
define('APP_VERSION', '1.0.0');
// BASE_URL is used for generating links. In production (Render), the host is dynamic,
// so we compute it from environment/request when not explicitly provided.
$baseUrlEnv = getenv('BASE_URL') ?: '';
if (!empty($baseUrlEnv)) {
    $computedBaseUrl = rtrim($baseUrlEnv, '/') . '/';
} else {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $computedBaseUrl = $scheme . '://' . $host . '/';
}
define('BASE_URL', $computedBaseUrl);

// ─── Database Configuration ─────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'hostelease-hostelease.a.aivencloud.com');
define('DB_PORT', getenv('DB_PORT') ?: '19887');
define('DB_NAME', getenv('DB_NAME') ?: 'defaultdb');
define('DB_USER', getenv('DB_USER') ?: 'avnadmin');
// IMPORTANT: do not commit real DB passwords into git.
// In Render, set DB_PASS in Environment variables.
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
define('DB_SSL', filter_var(getenv('DB_SSL') ?: 'true', FILTER_VALIDATE_BOOLEAN));

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
