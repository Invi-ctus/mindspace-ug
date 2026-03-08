<?php
/**
 * MindSpace — Session Check API
 * ================================
 * Returns current session status and user info.
 * Used by frontend to determine login state.
 */

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true
    ]);
}

header('Content-Type: application/json');

// Prevent caching of this response
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    // Optional: Add session timeout logic here
    // For example, check if last activity was too long ago
    
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'username' => $_SESSION['username'],
        'user_id' => $_SESSION['user_id']
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => true,
        'logged_in' => false,
        'username' => null,
        'user_id' => null
    ], JSON_PRETTY_PRINT);
}
