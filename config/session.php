<?php
/**
 * HostelEase — Session Management
 * 
 * Initializes secure session with httponly cookies, SameSite policy,
 * and CSRF token generation.
 */

require_once __DIR__ . '/config.php';

// ─── Configure session parameters before starting ────────────────────
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false, // Set to true if using HTTPS
    'httponly'  => true,
    'samesite'  => 'Strict',
]);

session_name(SESSION_NAME);

// ─── Start session ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Session timeout check ──────────────────────────────────────────
if (isset($_SESSION['last_activity'])) {
    $elapsed = time() - $_SESSION['last_activity'];
    if ($elapsed > SESSION_LIFETIME) {
        // Session expired — destroy and restart
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['last_activity'] = time();

// ─── Initialize CSRF token if not set ───────────────────────────────
if (empty($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}
