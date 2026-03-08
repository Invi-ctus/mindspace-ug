<?php
/**
 * MindSpace — Profile Update Handler
 * =====================================
 * Handles username and password updates.
 * Validates input and updates database.
 */

session_start();
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Load database connection
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ── Update Username ───────────────────────────────────────────
if ($action === 'update_username') {
    $newUsername = trim($_POST['username'] ?? '');
    
    // Validate username
    if (empty($newUsername)) {
        echo json_encode(['success' => false, 'message' => 'Username is required']);
        exit;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $newUsername)) {
        echo json_encode(['success' => false, 'message' => 'Username must be 3–50 characters and contain only letters, numbers, or underscores']);
        exit;
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
    $stmt->execute([$newUsername, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This username is already taken']);
        exit;
    }
    
    // Update username
    $update = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
    if ($update->execute([$newUsername, $userId])) {
        $_SESSION['username'] = $newUsername;
        echo json_encode(['success' => true, 'message' => 'Username updated successfully', 'username' => $newUsername]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update username']);
    }
    exit;
}

// ── Update Password ───────────────────────────────────────────
if ($action === 'update_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required']);
        exit;
    }
    
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
        exit;
    }
    
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        exit;
    }
    
    // Get current user from database
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }
    
    // Hash new password and update
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    if ($update->execute([$hashedPassword, $userId])) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
