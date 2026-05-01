<?php
/**
 * Get Class Metrics API
 * GET /api/metrics/class/{name}
 *
 * Returns detailed metrics for a specific class.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get class name from URL
$requestUri = $_SERVER['REQUEST_URI'];
$className = null;

if (preg_match('/\/api\/metrics\/class\/([^\/]+)/', $requestUri, $matches)) {
    $className = urldecode($matches[1]);
}

if (!$className) {
    http_response_code(400);
    echo json_encode(['error' => 'Class name is required']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/metrics/ReportService.php';
require_once __DIR__ . '/metrics/RiskEvaluator.php';

try {
    $reportService = new ReportService($pdo);
    $classMetrics = $reportService->getClassMetrics($className);

    if (!$classMetrics) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => "Class '{$className}' not found in latest report"
        ]);
        exit;
    }

    // Add detailed evaluation
    $evaluator = new RiskEvaluator();
    $evaluation = $evaluator->evaluateClass($classMetrics);
    $classMetrics['evaluation'] = $evaluation;

    echo json_encode([
        'success' => true,
        'data' => $classMetrics
    ]);

} catch (Exception $e) {
    error_log('[Metrics Class API Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve class metrics: ' . $e->getMessage()
    ]);
}