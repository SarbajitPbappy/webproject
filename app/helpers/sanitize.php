<?php
/**
 * HostelEase — Input Sanitization Helper
 * 
 * Functions for cleaning and escaping user input.
 */

/**
 * Sanitize a general string input.
 * Trims whitespace and removes HTML tags.
 *
 * @param string|null $input Raw input
 * @return string Sanitized string
 */
function sanitize(?string $input): string
{
    if ($input === null) {
        return '';
    }
    return trim(strip_tags($input));
}

/**
 * Sanitize and validate an email address.
 *
 * @param string|null $email Raw email input
 * @return string Sanitized email or empty string if invalid
 */
function sanitizeEmail(?string $email): string
{
    if ($email === null) {
        return '';
    }
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

/**
 * Escape output for safe HTML rendering.
 * Prevents XSS by encoding special characters.
 *
 * @param string|null $output Raw output string
 * @return string Escaped string safe for HTML output
 */
function escape(?string $output): string
{
    if ($output === null) {
        return '';
    }
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}

/**
 * Alias for escape() — shorter helper.
 *
 * @param string|null $str Raw string
 * @return string Escaped string
 */
function e(?string $str): string
{
    return escape($str);
}

/**
 * Sanitize an integer input.
 *
 * @param mixed $input Raw input
 * @return int Sanitized integer (0 if invalid)
 */
function sanitizeInt($input): int
{
    return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize a decimal/float input.
 *
 * @param mixed $input Raw input
 * @return float Sanitized float (0.0 if invalid)
 */
function sanitizeFloat($input): float
{
    return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Validate and sanitize a date string (YYYY-MM-DD format).
 *
 * @param string|null $date Raw date input
 * @return string|null Valid date string or null
 */
function sanitizeDate(?string $date): ?string
{
    if ($date === null || $date === '') {
        return null;
    }
    $date = trim($date);
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d && $d->format('Y-m-d') === $date) {
        return $date;
    }
    return null;
}

/**
 * Sanitize a phone number (keep only digits, +, -, spaces).
 *
 * @param string|null $phone Raw phone input
 * @return string Sanitized phone
 */
function sanitizePhone(?string $phone): string
{
    if ($phone === null) {
        return '';
    }
    return preg_replace('/[^0-9+\-\s]/', '', trim($phone));
}
