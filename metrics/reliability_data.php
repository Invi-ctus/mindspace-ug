<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — Reliability Data & Metrics
 * =========================================
 * Calculates key reliability metrics from system failures data.
 * Returns JSON with comprehensive reliability statistics.
 */

// Load database connection
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/reliability.php';

header('Content-Type: application/json');

/**
 * Calculate Mean Time To Failure (MTTF)
 * Average time between one failure and the next one
 * In seconds
 */
function calculateMTTF(): float
{
    global $pdo;
    
    try {
        $stmt = $pdo->query(
            'SELECT COUNT(*) as total_failures,
                    DATEDIFF(MAX(timestamp), MIN(timestamp)) as days_span
             FROM system_failures'
        );
        $result = $stmt->fetch();
        
        $totalFailures = (int) $result['total_failures'];
        $daysSpan = (int) $result['days_span'];
        
        // Avoid division by zero
        if ($totalFailures <= 1 || $daysSpan == 0) {
            return 0;
        }
        
        // MTTF = total time span / (number of failures - 1)
        // Convert days to seconds
        $totalSeconds = $daysSpan * 86400;
        return $totalSeconds / ($totalFailures - 1);
    } catch (PDOException $e) {
        error_log('[MindSpace MTTF Error] ' . $e->getMessage());
        return 0;
    }
}

/**
 * Calculate Mean Time To Repair (MTTR)
 * Average time to fix a failure (if resolution_time is logged)
 * In seconds
 */
function calculateMTTR(): float
{
    global $pdo;
    
    try {
        $stmt = $pdo->query(
            'SELECT AVG(resolution_time) as avg_resolution
             FROM system_failures
             WHERE resolution_time IS NOT NULL'
        );
        $result = $stmt->fetch();
        
        $avgResolution = (float) ($result['avg_resolution'] ?? 0);
        return max($avgResolution, 0);
    } catch (PDOException $e) {
        error_log('[MindSpace MTTR Error] ' . $e->getMessage());
        return 0;
    }
}

/**
 * Calculate Mean Time Between Failures (MTBF)
 * MTBF = MTTF + MTTR
 * In seconds
 */
function calculateMTBF(float $mttf, float $mttr): float
{
    return $mttf + $mttr;
}

/**
 * Calculate failure intensity
 * Failures per day
 */
function calculateFailureIntensity(): float
{
    global $pdo;
    
    try {
        $stmt = $pdo->query(
            'SELECT COUNT(*) as total_failures,
                    DATEDIFF(MAX(timestamp), MIN(timestamp)) as days_span
             FROM system_failures'
        );
        $result = $stmt->fetch();
        
        $totalFailures = (int) $result['total_failures'];
        $daysSpan = max((int) $result['days_span'], 1);
        
        return $totalFailures / $daysSpan;
    } catch (PDOException $e) {
        error_log('[MindSpace Failure Intensity Error] ' . $e->getMessage());
        return 0;
    }
}

/**
 * Calculate system availability
 * Availability = MTTF / (MTTF + MTTR)
 * Returns as a percentage (0-100)
 */
function calculateAvailability(float $mttf, float $mttr): float
{
    $mtbf = $mttf + $mttr;
    
    // Avoid division by zero
    if ($mtbf == 0) {
        return 100.0; // Perfect availability if no data
    }
    
    $availability = ($mttf / $mtbf) * 100;
    return min($availability, 100.0); // Cap at 100%
}

/**
 * Format seconds to human-readable duration
 */
function formatDuration(float $seconds): string
{
    if ($seconds < 60) {
        return round($seconds) . ' sec';
    } elseif ($seconds < 3600) {
        return round($seconds / 60, 1) . ' min';
    } elseif ($seconds < 86400) {
        return round($seconds / 3600, 1) . ' hours';
    } else {
        return round($seconds / 86400, 1) . ' days';
    }
}

// ── Get all metrics ────────────────────────────────────────────
$totalFailures = getTotalFailures();
$failuresByModule = getFailuresByModule();
$failuresByType = getFailuresByType();
$failureTrend = getFailureTrend('30 DAY');

$mttf = calculateMTTF();
$mttr = calculateMTTR();
$mtbf = calculateMTBF($mttf, $mttr);
$failureIntensity = calculateFailureIntensity();
$availability = calculateAvailability($mttf, $mttr);

// ── Prepare response ───────────────────────────────────────────
$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'summary' => [
        'total_failures' => $totalFailures,
        'failure_intensity' => round($failureIntensity, 2),
        'failure_intensity_label' => round($failureIntensity, 2) . ' failures/day'
    ],
    'metrics' => [
        'mttf' => [
            'label' => 'Mean Time To Failure',
            'value_seconds' => round($mttf, 0),
            'value_human' => formatDuration($mttf),
            'description' => 'Average time between one failure and the next'
        ],
        'mttr' => [
            'label' => 'Mean Time To Repair',
            'value_seconds' => round($mttr, 0),
            'value_human' => formatDuration($mttr),
            'description' => 'Average time to fix a failure'
        ],
        'mtbf' => [
            'label' => 'Mean Time Between Failures',
            'value_seconds' => round($mtbf, 0),
            'value_human' => formatDuration($mtbf),
            'description' => 'MTTF + MTTR'
        ],
        'availability' => [
            'label' => 'System Availability',
            'value_percent' => round($availability, 2),
            'formula' => 'MTTF / (MTTF + MTTR) × 100',
            'description' => 'Percentage of time system is operational'
        ]
    ],
    'failures_by_module' => $failuresByModule,
    'failures_by_type' => $failuresByType,
    'trend' => $failureTrend
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
