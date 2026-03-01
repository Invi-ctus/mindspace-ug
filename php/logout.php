<?php
/**
 * MindSpace — Logout Handler
 * ================================
 * Destroys the PHP session and redirects to the login page.
 */

session_start();

// Clear all session variables
$_SESSION = [];

// Invalidate the session cookie in the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroy the session on the server
session_destroy();

// Redirect to login with a confirmation message
header('Location: ../login.html?logged_out=1');
exit;
