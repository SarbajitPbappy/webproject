<?php
/**
 * HostelEase — CSRF Protection Helper
 * 
 * Generates and validates CSRF tokens for form submissions.
 */

/**
 * Generate and return the current CSRF token.
 * Token is stored in the session and initialized in config/session.php.
 *
 * @return string The CSRF token
 */
function generateToken(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify a submitted CSRF token against the session token.
 *
 * @param string|null $token The submitted token to verify
 * @return bool True if valid, false otherwise
 */
function verifyToken(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    }

    if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }

    $valid = hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);

    // Regenerate token after verification to prevent reuse
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));

    return $valid;
}

/**
 * Output a hidden HTML input field containing the CSRF token.
 * Use this inside <form> tags.
 *
 * @return string HTML hidden input element
 */
function csrfField(): string
{
    $token = generateToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
