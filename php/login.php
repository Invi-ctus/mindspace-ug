<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — Login Handler
 * ================================
 * Validates credentials, starts a PHP session,
 * and redirects to the dashboard on success.
 */

session_start();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.html');
    exit;
}

// ── Load database connection ───────────────────────────────────
require_once __DIR__ . '/db.php';

// ── Helper: redirect back with error ──────────────────────────
function redirectError(string $message): void
{
    header('Location: ../login.html?error=' . urlencode($message));
    exit;
}

// ── 1. Collect input ──────────────────────────────────────────
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

// ── 2. Basic validation ────────────────────────────────────────
if (empty($email) || empty($password)) {
    redirectError('Please enter your email and password.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectError('Please enter a valid email address.');
}

// ── 3. Look up the user by email ──────────────────────────────
$stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

// ── 4. Verify the password hash ───────────────────────────────
// Using password_verify() is the safe way — never compare hashes directly.
if (!$user || !password_verify($password, $user['password'])) {
    // Generic message: don't specify whether email or password was wrong
    redirectError('Invalid email or password. Please try again.');
}

// ── 5. Regenerate session ID to prevent session fixation ──────
session_regenerate_id(true);

// ── 6. Store user info in session ─────────────────────────────
$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];

// ── 7. Redirect to dashboard ──────────────────────────────────
header('Location: ../dashboard.html');
exit;
