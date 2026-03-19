<?php
/**
 * includes/auth.php
 *
 * Authentication helpers for the admin area.
 *
 * Functions:
 *   isAdminLoggedIn() → bool   — checks session without redirecting (safe for AJAX)
 *   requireAdmin()    → void   — redirects to login if not authenticated (for page guards)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns true if an admin session is active, false otherwise.
 * Use this in AJAX/JSON endpoints where a redirect is not appropriate.
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Redirects to the admin login page if no valid session exists.
 * Use this at the top of every admin HTML page.
 */
function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . '/admin/login.php';
        header('Location: ' . $loginUrl . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}