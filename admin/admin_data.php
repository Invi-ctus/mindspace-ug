<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — Admin Stats API
 * ================================
 * Returns JSON stats for the admin panel.
 *
 * ⚠️  SECURITY NOTE FOR PRODUCTION:
 * This demo has no admin authentication.
 * Before going live, add a proper admin login and
 * protect this endpoint (IP whitelist / admin session check).
 */

header('Content-Type: application/json');

// ── Simple IP-based dev guard (remove in production, add real auth) ──
// $allowedIPs = ['127.0.0.1', '::1'];
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Forbidden.']);
//     exit;
// }

require_once __DIR__ . '/../php/db.php';

// ── 1. Total registered users ──────────────────────────────────
$stmt       = $pdo->query('SELECT COUNT(*) AS total FROM users');
$totalUsers = (int) $stmt->fetchColumn();

// ── 2. Total mood check-ins (all time) ────────────────────────
$stmt        = $pdo->query('SELECT COUNT(*) AS total FROM moods');
$totalMoods  = (int) $stmt->fetchColumn();

// ── 3. Total community posts ───────────────────────────────────
$stmt        = $pdo->query('SELECT COUNT(*) AS total FROM community_posts');
$totalPosts  = (int) $stmt->fetchColumn();

// ── 4. Most common mood this week ─────────────────────────────
$stmt = $pdo->query(
    "SELECT mood, COUNT(*) AS cnt
     FROM moods
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY mood
     ORDER BY cnt DESC
     LIMIT 1"
);
$topMoodRow = $stmt->fetch();
$topMood    = $topMoodRow ? ucfirst($topMoodRow['mood']) . ' (' . $topMoodRow['cnt'] . ')' : 'No data';

// ── 5. New users this week ─────────────────────────────────────
$stmt        = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$newUsers    = (int) $stmt->fetchColumn();

// ── 6. Mood breakdown this week (for a table) ─────────────────
$stmt = $pdo->query(
    "SELECT mood, COUNT(*) AS cnt
     FROM moods
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY mood
     ORDER BY cnt DESC"
);
$moodBreakdown = $stmt->fetchAll();

// ── 7. Recent 10 users ────────────────────────────────────────
$stmt = $pdo->query(
    'SELECT id, username, email, created_at
     FROM users
     ORDER BY created_at DESC
     LIMIT 10'
);
$recentUsers = $stmt->fetchAll();

// Sanitize emails for display
foreach ($recentUsers as &$u) {
    $u['email']    = htmlspecialchars($u['email']);
    $u['username'] = htmlspecialchars($u['username']);
}
unset($u);

echo json_encode([
    'success'        => true,
    'totalUsers'     => $totalUsers,
    'totalMoods'     => $totalMoods,
    'totalPosts'     => $totalPosts,
    'topMood'        => $topMood,
    'newUsers'       => $newUsers,
    'moodBreakdown'  => $moodBreakdown,
    'recentUsers'    => $recentUsers,
]);
