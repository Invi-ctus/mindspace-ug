<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace - Database Connection
 * ====================================
 * Uses PHP's PDO (PHP Data Objects) for secure database access.
 * PDO supports prepared statements which protect against SQL injection.
 *
 * HOW TO USE in other PHP files:
 *   require_once __DIR__ . '/db.php';
 *   // $pdo is now available as a PDO connection object
 */

// ── Database credentials (edit these to match your XAMPP/WAMP setup) ──────────
define('DB_HOST',    'localhost');   // XAMPP/WAMP usually runs MySQL on localhost
define('DB_PORT',    3306);       // InfinityFree default MySQL port
define('DB_NAME',    'mindspace_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Build the Data Source Name (DSN) string ────────────────────────────────────
$dsn = 'mysql:host=' . DB_HOST
     . ';port='      . DB_PORT
     . ';dbname='    . DB_NAME
     . ';charset='   . DB_CHARSET;

// ── PDO options for safety and clarity ────────────────────────────────────────
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Return rows as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                    // Use real prepared statements
];

// ── Create the PDO connection ──────────────────────────────────────────────────
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // In production, never expose raw error messages to the user.
    // Log the real error and show a friendly message instead.
    error_log('[MindSpace DB Error] ' . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please try again later.'
    ]));
}
