<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — A/B Test Layout API
 * =======================================
 * Returns the assigned layout variant for the current user session.
 * Used by checkin.html to render Layout A or Layout B dynamically.
 */

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

/**
 * Get or assign A/B test variant for current session
 */
function getAbTestVariant($pdo): array
{
    // Get or create session ID
    $sessionId = $_SESSION['session_id'] ?? bin2hex(random_bytes(32));
    $_SESSION['session_id'] = $sessionId;
    
    // Check existing assignment
    $stmt = $pdo->prepare(
        'SELECT variant, assigned_at FROM ab_test_assignments 
         WHERE session_id = ? AND experiment_name = "checkin_layout_test" 
         LIMIT 1'
    );
    $stmt->execute([$sessionId]);
    $result = $stmt->fetch();
    
    if ($result) {
        return [
            'variant' => $result['variant'],
            'assigned_at' => $result['assigned_at'],
            'is_new_assignment' => false
        ];
    }
    
    // Random assignment (50/50 split)
    $variant = (rand(0, 1) === 0) ? 'A' : 'B';
    
    // Log new assignment
    $stmt = $pdo->prepare(
        'INSERT INTO ab_test_assignments (session_id, experiment_name, variant, assigned_at)
         VALUES (?, "checkin_layout_test", ?, NOW())'
    );
    $stmt->execute([$sessionId, $variant]);
    
    return [
        'variant' => $variant,
        'assigned_at' => date('Y-m-d H:i:s'),
        'is_new_assignment' => true,
        'session_id' => $sessionId
    ];
}

try {
    $assignment = getAbTestVariant($pdo);
    
    echo json_encode([
        'success' => true,
        'experiment' => 'checkin_layout_test',
        'variant' => $assignment['variant'],
        'description' => $assignment['variant'] === 'A' 
            ? 'Classic vertical mood selection with emojis' 
            : 'Compact grid layout with color-coded moods',
        'metadata' => [
            'assigned_at' => $assignment['assigned_at'],
            'is_new' => $assignment['is_new_assignment'] ?? false
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('[MindSpace A/B API Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve A/B test assignment',
        'fallback_variant' => 'A' // Default to control group on error
    ]);
}
