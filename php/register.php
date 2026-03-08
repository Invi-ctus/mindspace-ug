<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * MindSpace — Registration Handler
 * =====================================
 * Accepts a POST request from register.html.
 * Validates input, hashes the password, and inserts the user
 * into the database. Redirects back with a success or error flag.
 */

// Start session with secure settings so we can log the user in immediately after registration
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true
    ]);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.html');
    exit;
}

// ── Load database connection ───────────────────────────────────
require_once __DIR__ . '/db.php';

// ── Helper: redirect back with an error message ────────────────
function redirectError(string $message): void
{
    header('Location: ../register.html?error=' . urlencode($message));
    exit;
}

// ── 1. Collect and sanitize input ─────────────────────────────
$username         = trim($_POST['username']         ?? '');
$email            = trim($_POST['email']            ?? '');
$password         = $_POST['password']              ?? '';
$confirm_password = $_POST['confirm_password']      ?? '';

// ── 2. Validate ────────────────────────────────────────────────

// All fields are required
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    redirectError('All fields are required.');
}

// Username: 3–50 characters, letters/numbers/underscores only
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
    redirectError('Username must be 3–50 characters and contain only letters, numbers, or underscores.');
}

// Valid email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectError('Please enter a valid email address.');
}

// Password length
if (strlen($password) < 8) {
    redirectError('Password must be at least 8 characters long.');
}

// Passwords match
if ($password !== $confirm_password) {
    redirectError('Passwords do not match.');
}

// ── 3. Check for duplicate username or email ───────────────────
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$username, $email]);
$existing = $stmt->fetch();

if ($existing) {
    redirectError('That username or email is already registered. Please choose another or log in.');
}

// ── 4. Hash the password (bcrypt, cost factor 12) ─────────────
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ── 5. Insert new user into the database ──────────────────────
$insert = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
$insert->execute([$username, $email, $hashedPassword]);

$newUserId = $pdo->lastInsertId();

// ── 6. Log the user in immediately (set session variables) ─────
$_SESSION['user_id']  = $newUserId;
$_SESSION['username'] = $username;

// ── 7. Redirect to dashboard with a welcome flag ──────────────
header('Location: ../dashboard.html?welcome=1');
exit;
