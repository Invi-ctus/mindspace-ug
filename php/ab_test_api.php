<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — A/B Test API
 * =======================================
 * Assigns and returns experiment variants for the current session.
 * Supports multiple concurrent experiments.
 *
 * Usage:
 *   GET  php/ab_test_api.php                          → checkin_layout_test (default, backward-compat)
 *   GET  php/ab_test_api.php?experiment=<name>        → named experiment
 */

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// ── Experiment registry ────────────────────────────────────────
// Add new experiments here. Each entry defines the experiment and its variants.
const EXPERIMENTS = [
    'checkin_layout_test' => [
        'description' => 'Mood check-in layout comparison',
        'variants' => [
            'A' => 'Classic vertical mood selection with emojis',
            'B' => 'Compact grid layout with color-coded moods',
        ],
    ],
    'dashboard_nudge_test' => [
        'description' => 'Dashboard return-visit nudge comparison',
        'variants' => [
            'A' => 'Static mood summary chart (control)',
            'B' => 'Interactive chart with streak goal & nudge banner',
        ],
    ],
    'resources_layout_test' => [
        'description' => 'Resources page helpline card layout',
        'variants' => [
            'A' => 'List layout with phone links (control)',
            'B' => 'Card grid layout with call-to-action buttons',
        ],
    ],
];

/**
 * Get or assign a variant for the given experiment and session.
 */
function getOrAssignVariant(PDO $pdo, string $sessionId, string $experimentName): array
{
    // Check existing assignment
    $stmt = $pdo->prepare(
        'SELECT variant, assigned_at FROM ab_test_assignments 
         WHERE session_id = ? AND experiment_name = ? 
         LIMIT 1'
    );
    $stmt->execute([$sessionId, $experimentName]);
    $result = $stmt->fetch();

    if ($result) {
        return [
            'variant'    => $result['variant'],
            'assigned_at' => $result['assigned_at'],
            'is_new'     => false,
        ];
    }

    // Random 50/50 assignment
    $variant = (random_int(0, 1) === 0) ? 'A' : 'B';

    $stmt = $pdo->prepare(
        'INSERT INTO ab_test_assignments (session_id, experiment_name, variant, assigned_at)
         VALUES (?, ?, ?, NOW())'
    );
    $stmt->execute([$sessionId, $experimentName, $variant]);

    return [
        'variant'    => $variant,
        'assigned_at' => date('Y-m-d H:i:s'),
        'is_new'     => true,
    ];
}

try {
    // Get or create anonymous session ID
    $sessionId = $_SESSION['session_id'] ?? bin2hex(random_bytes(32));
    $_SESSION['session_id'] = $sessionId;

    // Determine which experiment is being requested
    $requestedExperiment = trim($_GET['experiment'] ?? 'checkin_layout_test');
    if (!array_key_exists($requestedExperiment, EXPERIMENTS)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Unknown experiment: ' . $requestedExperiment,
            'available' => array_keys(EXPERIMENTS),
        ]);
        exit;
    }

    $assignment = getOrAssignVariant($pdo, $sessionId, $requestedExperiment);
    $meta       = EXPERIMENTS[$requestedExperiment];

    // Log exposure event for the experiment
    $stmt = $pdo->prepare(
        'INSERT INTO telemetry_logs (session_id, event_type, page_url, element_id, metadata, created_at)
         VALUES (?, "experiment_exposure", ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $sessionId,
        $_SERVER['HTTP_REFERER'] ?? 'unknown',
        $requestedExperiment,
        json_encode(['variant' => $assignment['variant'], 'is_new' => $assignment['is_new']]),
    ]);

    echo json_encode([
        'success'     => true,
        'experiment'  => $requestedExperiment,
        'variant'     => $assignment['variant'],
        'description' => $meta['variants'][$assignment['variant']],
        'metadata'    => [
            'assigned_at' => $assignment['assigned_at'],
            'is_new'      => $assignment['is_new'],
        ],
    ]);

} catch (PDOException $e) {
    error_log('[MindSpace A/B API Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success'          => false,
        'message'          => 'Failed to retrieve A/B test assignment',
        'fallback_variant' => 'A',
    ]);
}
