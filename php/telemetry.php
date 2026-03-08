<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — Telemetry Data Handler
 * =======================================
 * Securely receives and logs anonymous user interaction data.
 * Implements 2nd-degree data collection with privacy safeguards.
 * 
 * ETHICAL COMPLIANCE:
 * - No personally identifiable information (PII) collected
 * - Session IDs are anonymous and temporary
 * - Data used solely for improving user experience
 * - Users can opt-out via browser settings
 */

session_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/db.php';

// Set JSON response headers
header('Content-Type: application/json');

/**
 * Generate or retrieve anonymous session ID
 * Uses existing PHP session if available, otherwise creates anonymous ID
 */
function getSessionId(): string {
    if (!empty($_SESSION['session_id'])) {
        return $_SESSION['session_id'];
    }
    
    // Create anonymous session ID if none exists
    $sessionId = bin2hex(random_bytes(32));
    $_SESSION['session_id'] = $sessionId;
    return $sessionId;
}

/**
 * Sanitize and validate telemetry data
 * Prevents injection attacks and ensures data integrity
 */
function sanitizeTelemetryData(array $data): array {
    $sanitized = [
        'event_type'    => null,
        'page_url'      => null,
        'element_id'    => null,
        'element_class' => null,
        'dwell_seconds' => null,
        'metadata'      => null
    ];
    
    // Validate event type against whitelist
    $allowedEvents = ['page_view', 'click', 'dwell_time', 'form_interaction', 'resource_access'];
    $eventType = trim($data['event_type'] ?? '');
    $sanitized['event_type'] = in_array($eventType, $allowedEvents, true) ? $eventType : 'page_view';
    
    // Sanitize URL (max 255 chars, remove dangerous characters)
    $pageUrl = filter_var(trim($data['page_url'] ?? ''), FILTER_SANITIZE_URL);
    $sanitized['page_url'] = mb_substr($pageUrl, 0, 255);
    
    // Sanitize element identifiers
    if (!empty($data['element_id'])) {
        $sanitized['element_id'] = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($data['element_id']));
    }
    
    if (!empty($data['element_class'])) {
        $sanitized['element_class'] = preg_replace('/[^a-zA-Z0-9 _-]/', '', trim($data['element_class']));
    }
    
    // Validate dwell time (must be positive integer, max 1 hour)
    if (isset($data['dwell_seconds']) && is_numeric($data['dwell_seconds'])) {
        $dwellTime = (int) $data['dwell_seconds'];
        $sanitized['dwell_seconds'] = min(max($dwellTime, 0), 3600); // Cap at 1 hour
    }
    
    // Sanitize metadata (JSON object with limited size)
    if (!empty($data['metadata']) && is_array($data['metadata'])) {
        $metadataJson = json_encode($data['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($metadataJson && mb_strlen($metadataJson) <= 2048) { // Max 2KB
            $sanitized['metadata'] = $metadataJson;
        }
    }
    
    return $sanitized;
}

// ── 1. Collect and validate input ─────────────────────────────
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$sanitizedData = sanitizeTelemetryData($data);

// Validate required fields
if (empty($sanitizedData['page_url'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Page URL is required']);
    exit;
}

$sessionId = getSessionId();

// ── 2. Log to database using prepared statements ───────────────
try {
    $stmt = $pdo->prepare(
        'INSERT INTO telemetry_logs 
         (session_id, event_type, page_url, element_id, element_class, dwell_seconds, metadata, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    
    $success = $stmt->execute([
        $sessionId,
        $sanitizedData['event_type'],
        $sanitizedData['page_url'],
        $sanitizedData['element_id'],
        $sanitizedData['element_class'],
        $sanitizedData['dwell_seconds'],
        $sanitizedData['metadata']
    ]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Telemetry logged successfully',
            'log_id' => $pdo->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to insert telemetry data');
    }
    
} catch (PDOException $e) {
    error_log('[MindSpace Telemetry Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to log telemetry data'
    ]);
} catch (Exception $e) {
    error_log('[MindSpace Telemetry Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
