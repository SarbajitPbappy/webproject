<?php
/**
 * HostelEase — Authentication Helper Functions
 * 
 * Provides session-based auth checks and role authorization.
 */

/**
 * Check if the current user is logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current logged-in user's data from session.
 *
 * @return array|null User data array or null if not logged in
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'full_name' => $_SESSION['user_name'] ?? '',
        'email'     => $_SESSION['user_email'] ?? '',
        'role'      => $_SESSION['user_role'] ?? '',
        'photo'     => $_SESSION['user_photo'] ?? '',
    ];
}

/**
 * Check if the current user has one of the specified roles.
 *
 * @param array $roles Allowed roles (e.g., ['admin', 'super_admin'])
 * @return bool
 */
function hasRole(array $roles): bool
{
    if (!isLoggedIn()) {
        return false;
    }
    return in_array($_SESSION['user_role'], $roles, true);
}

/**
 * Require the current user to have one of the specified roles.
 * Redirects to login if not authenticated, or 403 if unauthorized.
 *
 * @param array $roles Allowed roles
 */
function requireRole(array $roles): void
{
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header('Location: ' . BASE_URL . '?url=auth/login');
        exit;
    }

    if (!hasRole($roles)) {
        http_response_code(403);
        $_SESSION['flash_error'] = 'You do not have permission to access this page.';
        header('Location: ' . BASE_URL . '?url=dashboard/index');
        exit;
    }
}

/**
 * Require authentication (any role).
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header('Location: ' . BASE_URL . '?url=auth/login');
        exit;
    }
}

/**
 * Set flash message in session.
 *
 * @param string $type    'success', 'error', 'warning', 'info'
 * @param string $message The message to display
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear a flash message.
 *
 * @param string $type 'success', 'error', 'warning', 'info'
 * @return string|null
 */
function getFlash(string $type): ?string
{
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

/**
 * Get the client's IP address.
 */
function getClientIP(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
