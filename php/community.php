<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — Community Board API
 * =====================================
 * Handles two actions:
 *   GET  ?action=fetch  → returns latest 20 posts as JSON
 *   POST action=post    → saves a new post, returns JSON
 *
 * All responses are JSON.
 */

session_start();
header('Content-Type: application/json');

// ── Auth check ─────────────────────────────────────────────────
// We still require login to prevent spam, but we never expose who posted.
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to use the community board.']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/reliability.php';

$userId = (int) $_SESSION['user_id'];
$action = '';

// Determine action from GET or POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = trim($_GET['action'] ?? '');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
}

// ═══════════════════════════════════════════════
// ACTION: fetch — return latest 20 posts
// ═══════════════════════════════════════════════
if ($action === 'fetch') {
    try {
        $stmt = $pdo->query(
            'SELECT message, created_at
             FROM community_posts
             ORDER BY created_at DESC
             LIMIT 20'
        );
        $posts = $stmt->fetchAll();

        // Sanitize message output
        foreach ($posts as &$post) {
            $post['message'] = htmlspecialchars($post['message'], ENT_QUOTES, 'UTF-8');
        }
        unset($post);

        echo json_encode(['success' => true, 'posts' => $posts]);
    } catch (PDOException $e) {
        logFailure('db_error', 'community', 'Error fetching posts');
        error_log('[MindSpace Community Fetch Error] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to fetch posts. Please try again.']);
    }
    exit;
}

// ═══════════════════════════════════════════════
// ACTION: post — save a new message
// ═══════════════════════════════════════════════
if ($action === 'post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');

    // Validate
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
        exit;
    }

    if (mb_strlen($message) > 300) {
        echo json_encode(['success' => false, 'message' => 'Message is too long (max 300 characters).']);
        exit;
    }

    // Basic profanity/spam check: disallow messages that are only symbols or spaces
    if (!preg_match('/\pL/u', $message)) {
        logFailure('validation_error', 'community', 'Spam/profanity attempt detected');
        echo json_encode(['success' => false, 'message' => 'Please write a meaningful message.']);
        exit;
    }

    // Insert
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO community_posts (user_id, message) VALUES (?, ?)'
        );
        $stmt->execute([$userId, $message]);

        echo json_encode(['success' => true, 'message' => 'Post saved.']);
    } catch (PDOException $e) {
        logFailure('db_error', 'community', 'Error saving post');
        error_log('[MindSpace Community Post Error] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error saving your post. Please try again.']);
    }
    exit;
}

// ── Unknown action ─────────────────────────────────────────────
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
