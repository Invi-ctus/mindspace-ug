<?php
/**
 * Get Metrics History API
 * GET /api/metrics/history
 *
 * Returns all OO metrics analysis reports.
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

try {
    $reportService = new ReportService($pdo);
    $reports = $reportService->getAllReports();

    echo json_encode([
        'success' => true,
        'data' => $reports,
        'count' => count($reports)
    ]);

} catch (Exception $e) {
    error_log('[Metrics History API Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve reports history: ' . $e->getMessage()
    ]);
}