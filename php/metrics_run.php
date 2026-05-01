<?php
/**
 * Run Metrics Analysis API
 * POST /api/metrics/run
 *
 * Triggers a new OO metrics analysis and stores the results.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/metrics/MetricsAnalyzer.php';
require_once __DIR__ . '/metrics/ReportService.php';
require_once __DIR__ . '/metrics/RiskEvaluator.php';

try {
    // Initialize analyzer
    $projectRoot = realpath(__DIR__ . '/../../');
    $analyzer = new MetricsAnalyzer($projectRoot);

    // Run analysis
    $results = $analyzer->analyze();

    // Evaluate risks
    $evaluator = new RiskEvaluator();
    foreach ($results['classes'] as &$class) {
        $evaluation = $evaluator->evaluateClass($class);
        $class['evaluation'] = $evaluation;
    }

    // Save report
    $reportService = new ReportService($pdo);
    $reportId = $reportService->saveReport($results);

    // Return success response
    echo json_encode([
        'success' => true,
        'report_id' => $reportId,
        'message' => 'Metrics analysis completed successfully',
        'summary' => $results['summary'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log('[Metrics API Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Analysis failed: ' . $e->getMessage()
    ]);
}