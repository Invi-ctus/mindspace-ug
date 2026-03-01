<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — Mood Check-In Handler
 * =======================================
 * Saves the user's daily mood entry to the database.
 * Requires the user to be logged in (session must exist).
 */

session_start();

// ── Require login ──────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.html?error=' . urlencode('Please log in to save a mood check-in.'));
    exit;
}

// ── Only accept POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../checkin.html');
    exit;
}

// ── Load DB ────────────────────────────────────────────────────
require_once __DIR__ . '/db.php';

// ── Helper: redirect with error ────────────────────────────────
function redirectError(string $msg): void
{
    header('Location: ../checkin.html?error=' . urlencode($msg));
    exit;
}

// ── 1. Collect and validate input ─────────────────────────────
$allowedMoods = ['happy', 'neutral', 'sad', 'frustrated', 'anxious'];
$mood         = trim($_POST['mood'] ?? '');
$note         = trim($_POST['note'] ?? '');

if (!in_array($mood, $allowedMoods, true)) {
    redirectError('Please select a valid mood before submitting.');
}

// Limit note to 500 characters (extra safety layer beyond the HTML maxlength)
if (mb_strlen($note) > 500) {
    $note = mb_substr($note, 0, 500);
}

$userId = (int) $_SESSION['user_id'];

// ── 2. Save to database ────────────────────────────────────────
$stmt = $pdo->prepare(
    'INSERT INTO moods (user_id, mood, note) VALUES (?, ?, ?)'
);
$stmt->execute([
    $userId,
    $mood,
    $note ?: null   // store NULL if note is empty
]);

// ── 3. Redirect to the check-in page with success flag ─────────
// The mood value is passed back so the JS can display a tailored quote.
header('Location: ../checkin.html?saved=1&mood=' . urlencode($mood));
exit;
