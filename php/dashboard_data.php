<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — Dashboard Data API
 * =====================================
 * Returns JSON with mood history and stats for the logged-in user.
 * Called by dashboard.html via fetch().
 *
 * Response shape:
 * {
 *   "success": true,
 *   "username": "...",
 *   "stats": { "total": 0, "streak": 0, "topMood": "..." },
 *   "recent": [ { "mood":"...", "note":"...", "created_at":"..." }, ... ],
 *   "moodCounts": { "happy":0, "neutral":0, ... }
 * }
 */

session_start();
header('Content-Type: application/json');

// ── Auth check ─────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'redirect' => '../login.html']);
    exit;
}

require_once __DIR__ . '/db.php';

$userId = (int) $_SESSION['user_id'];

// ── 1. Get username ────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$userRow = $stmt->fetch();
$username = $userRow ? $userRow['username'] : 'Friend';

// ── 2. Last 7 mood entries ─────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT mood, note, created_at
     FROM moods
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 7'
);
$stmt->execute([$userId]);
$recent = $stmt->fetchAll();

// ── 3. Total check-ins ─────────────────────────────────────────
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM moods WHERE user_id = ?');
$stmt->execute([$userId]);
$totalRow = $stmt->fetch();
$total = (int)($totalRow['total'] ?? 0);

// ── 4. Mood frequency this week (for bar chart) ────────────────
$stmt = $pdo->prepare(
    "SELECT mood, COUNT(*) AS cnt
     FROM moods
     WHERE user_id = ?
       AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY mood"
);
$stmt->execute([$userId]);
$moodRows = $stmt->fetchAll();

// Build a full map with 0s for moods that have no entries
$moodCounts = ['happy' => 0, 'neutral' => 0, 'sad' => 0, 'frustrated' => 0, 'anxious' => 0];
foreach ($moodRows as $row) {
    $moodCounts[$row['mood']] = (int) $row['cnt'];
}

// ── 5. Most common mood this week ─────────────────────────────
arsort($moodCounts);
$topMood = array_key_first($moodCounts) ?: 'N/A';

// ── 6. Check-in streak (consecutive days up to today) ─────────
$stmt = $pdo->prepare(
    "SELECT DISTINCT DATE(created_at) AS day
     FROM moods
     WHERE user_id = ?
     ORDER BY day DESC
     LIMIT 30"
);
$stmt->execute([$userId]);
$days    = $stmt->fetchAll(PDO::FETCH_COLUMN);
$streak  = 0;
$today   = new DateTime('today');

foreach ($days as $day) {
    $diff = (int) $today->diff(new DateTime($day))->days;
    if ($diff === $streak) {
        $streak++;
    } else {
        break;
    }
}

// ── 7. Return JSON ─────────────────────────────────────────────
echo json_encode([
    'success'    => true,
    'username'   => htmlspecialchars($username),
    'stats'      => [
        'total'   => $total,
        'streak'  => $streak,
        'topMood' => $topMood
    ],
    'recent'     => $recent,
    'moodCounts' => $moodCounts
]);
