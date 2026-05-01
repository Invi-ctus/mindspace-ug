<?php
/**
 * Get Latest Metrics Report API
 * GET /api/metrics/latest
 *
 * Returns the most recent OO metrics analysis report.
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

// Load dependencies
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/metrics/ReportService.php';
require_once __DIR__ . '/metrics/RiskEvaluator.php';

try {
    $reportService = new ReportService($pdo);
    $report = $reportService->getLatestReport();

    if (!$report) {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'No metrics reports found. Run analysis first.'
        ]);
        exit;
    }

    // Add project health assessment
    $evaluator = new RiskEvaluator();
    $health = $evaluator->assessProjectHealth($report);

    $report['health_assessment'] = $health;

    echo json_encode([
        'success' => true,
        'data' => $report
    ]);

} catch (Exception $e) {
    error_log('[Metrics Latest API Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve latest report: ' . $e->getMessage()
    ]);
}