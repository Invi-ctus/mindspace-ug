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

// ── 8. A/B experiment results (all known experiments) ─────────
$experimentResults = [];
try {
    $stmt = $pdo->query(
        "SELECT
            experiment_name,
            variant,
            COUNT(*)                                          AS total_assigned,
            SUM(converted)                                    AS conversions,
            ROUND(SUM(converted) * 100.0 / COUNT(*), 1)      AS conversion_rate
         FROM ab_test_assignments
         GROUP BY experiment_name, variant
         ORDER BY experiment_name, variant"
    );
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $exp = $row['experiment_name'];
        if (!isset($experimentResults[$exp])) {
            $experimentResults[$exp] = [];
        }
        $experimentResults[$exp][$row['variant']] = [
            'total_assigned'   => (int)   $row['total_assigned'],
            'conversions'      => (int)   $row['conversions'],
            'conversion_rate'  => (float) $row['conversion_rate'],
        ];
    }
} catch (PDOException $e) {
    // telemetry tables may not exist on older installs
    $experimentResults = [];
}

// ── 9. Page funnel — unique sessions per page (last 30 days) ──
$pageFunnel = [];
try {
    $stmt = $pdo->query(
        "SELECT
            page_url,
            COUNT(DISTINCT session_id) AS unique_sessions,
            COUNT(*)                   AS total_events,
            ROUND(AVG(dwell_seconds))  AS avg_dwell_seconds
         FROM telemetry_logs
         WHERE event_type = 'page_view'
           AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY page_url
         ORDER BY unique_sessions DESC
         LIMIT 10"
    );
    $pageFunnel = $stmt->fetchAll();
} catch (PDOException $e) {
    $pageFunnel = [];
}

// ── 10. Top clicked elements (last 30 days) ───────────────────
$topClicks = [];
try {
    $stmt = $pdo->query(
        "SELECT
            element_id,
            page_url,
            COUNT(*) AS click_count
         FROM telemetry_logs
         WHERE event_type = 'click'
           AND element_id IS NOT NULL
           AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY element_id, page_url
         ORDER BY click_count DESC
         LIMIT 10"
    );
    $topClicks = $stmt->fetchAll();
} catch (PDOException $e) {
    $topClicks = [];
}

// ── 11. Daily active sessions (last 14 days) ──────────────────
$dailyActivity = [];
try {
    $stmt = $pdo->query(
        "SELECT
            DATE(created_at)           AS day,
            COUNT(DISTINCT session_id) AS active_sessions
         FROM telemetry_logs
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
         GROUP BY DATE(created_at)
         ORDER BY day ASC"
    );
    $dailyActivity = $stmt->fetchAll();
} catch (PDOException $e) {
    $dailyActivity = [];
}

echo json_encode([
    'success'           => true,
    'totalUsers'        => $totalUsers,
    'totalMoods'        => $totalMoods,
    'totalPosts'        => $totalPosts,
    'topMood'           => $topMood,
    'newUsers'          => $newUsers,
    'moodBreakdown'     => $moodBreakdown,
    'recentUsers'       => $recentUsers,
    'experimentResults' => $experimentResults,
    'pageFunnel'        => $pageFunnel,
    'topClicks'         => $topClicks,
    'dailyActivity'     => $dailyActivity,
]);
