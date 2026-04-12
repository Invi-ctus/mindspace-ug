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

// ── Load DB and Reliability Module ────────────────────────────────
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/reliability.php';

// ── Helper: redirect with error ────────────────────────────────
function redirectError(string $msg): void
{
    header('Location: ../checkin.html?error=' . urlencode($msg));
    exit;
}

// ── A/B Test Assignment Logic ───────────────────────────────────
/**
 * Randomly assign user to Layout A or Layout B
 * 50/50 split with session persistence
 */
function assignAbTestVariant($pdo, string $sessionId): string
{
    // Check if already assigned
    $stmt = $pdo->prepare(
        'SELECT variant FROM ab_test_assignments 
         WHERE session_id = ? AND experiment_name = "checkin_layout_test" 
         LIMIT 1'
    );
    $stmt->execute([$sessionId]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['variant'];
    }
    
    // Random assignment (50/50)
    $variant = (rand(0, 1) === 0) ? 'A' : 'B';
    
    // Log assignment
    $stmt = $pdo->prepare(
        'INSERT INTO ab_test_assignments (session_id, experiment_name, variant, assigned_at)
         VALUES (?, "checkin_layout_test", ?, NOW())'
    );
    $stmt->execute([$sessionId, $variant]);
    
    return $variant;
}

// Generate/retrieve session ID for A/B test tracking
$sessionId = $_SESSION['session_id'] ?? bin2hex(random_bytes(32));
$_SESSION['session_id'] = $sessionId;

// Assign A/B test variant
$abVariant = assignAbTestVariant($pdo, $sessionId);

// ── 1. Collect and validate input ─────────────────────────────
$allowedMoods = ['happy', 'neutral', 'sad', 'frustrated', 'anxious'];
$mood         = trim($_POST['mood'] ?? '');
$note         = trim($_POST['note'] ?? '');

if (!in_array($mood, $allowedMoods, true)) {
    logFailure('validation_error', 'checkin', 'Invalid mood selection: ' . $mood);
    redirectError('Please select a valid mood before submitting.');
}

// Limit note to 500 characters (extra safety layer beyond the HTML maxlength)
if (mb_strlen($note) > 500) {
    $note = mb_substr($note, 0, 500);
}

$userId = (int) $_SESSION['user_id'];

// ── 2. Save to database with A/B test tracking ────────────────
try {
    $stmt = $pdo->prepare(
        'INSERT INTO moods (user_id, mood, note, session_id, ab_variant) 
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $mood,
        $note ?: null,
        $sessionId,
        $abVariant
    ]);
} catch (PDOException $e) {
    logFailure('db_error', 'checkin', 'Error saving mood: ' . $e->getMessage());
    error_log('[MindSpace Checkin DB Error] ' . $e->getMessage());
    redirectError('Error saving your mood. Please try again.');
}

// ── 2b. Update A/B test conversion status ───────────────────────
try {
    $stmt = $pdo->prepare(
        'UPDATE ab_test_assignments 
         SET converted = 1, 
             conversion_data = JSON_OBJECT("mood", ?, "timestamp", NOW())
         WHERE session_id = ? AND experiment_name = "checkin_layout_test"'
    );
    $stmt->execute([$mood, $sessionId]);
} catch (PDOException $e) {
    error_log('[MindSpace A/B Test Error] ' . $e->getMessage());
    // Continue even if conversion logging fails
}

// ── 3. Redirect to the check-in page with success flag and A/B variant ─────────
// The mood value and variant are passed back so the JS can display tailored content.
header('Location: ../checkin.html?saved=1&mood=' . urlencode($mood) . '&variant=' . urlencode($abVariant));
exit;
