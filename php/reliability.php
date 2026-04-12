<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — System Reliability Module
 * ========================================
 * Tracks system failures for reliability metrics.
 * 
 * HOW TO USE:
 *   require_once __DIR__ . '/reliability.php';
 *   logFailure('login_error', 'auth', 'Invalid credentials attempt');
 */

// Load database connection
require_once __DIR__ . '/db.php';

/**
 * Log a system failure to the database
 *
 * @param string $failureType The type of failure (e.g., 'login_error', 'db_error', 'api_error')
 * @param string $module      The module where the failure occurred (e.g., 'auth', 'dashboard', 'checkin')
 * @param string $description Optional description of the failure
 * @return bool               True if logged successfully, false otherwise
 */
function logFailure(string $failureType, string $module, string $description = ''): bool
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO system_failures (failure_type, module, description) 
             VALUES (?, ?, ?)'
        );
        
        return $stmt->execute([
            $failureType,
            $module,
            $description ?: null
        ]);
    } catch (PDOException $e) {
        // Even if logging fails, don't break the application
        error_log('[MindSpace Reliability] Failed to log failure: ' . $e->getMessage());
        return false;
    }
}

/**
 * Mark a failure as resolved by setting its resolution_time
 *
 * @param int $failureId      The ID of the failure to resolve
 * @return bool               True if marked successfully
 */
function markFailureResolved(int $failureId): bool
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare(
            'UPDATE system_failures 
             SET resolution_time = TIMESTAMPDIFF(SECOND, timestamp, NOW())
             WHERE id = ? AND resolution_time IS NULL'
        );
        
        return $stmt->execute([$failureId]);
    } catch (PDOException $e) {
        error_log('[MindSpace Reliability] Failed to mark failure resolved: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get total number of failures
 *
 * @return int The total count of failures
 */
function getTotalFailures(): int
{
    global $pdo;
    
    try {
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM system_failures');
        $result = $stmt->fetch();
        return (int) $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log('[MindSpace Reliability] Failed to get total failures: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get failures grouped by module
 *
 * @return array Array of modules with failure counts
 */
function getFailuresByModule(): array
{
    global $pdo;
    
    try {
        $stmt = $pdo->query(
            'SELECT module, COUNT(*) as count 
             FROM system_failures 
             GROUP BY module 
             ORDER BY count DESC'
        );
        return $stmt->fetchAll() ?? [];
    } catch (PDOException $e) {
        error_log('[MindSpace Reliability] Failed to get failures by module: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get failures grouped by type
 *
 * @return array Array of failure types with counts
 */
function getFailuresByType(): array
{
    global $pdo;
    
    try {
        $stmt = $pdo->query(
            'SELECT failure_type, COUNT(*) as count 
             FROM system_failures 
             GROUP BY failure_type 
             ORDER BY count DESC'
        );
        return $stmt->fetchAll() ?? [];
    } catch (PDOException $e) {
        error_log('[MindSpace Reliability] Failed to get failures by type: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get failures over a time period for trending
 *
 * @param string $interval MySQL INTERVAL string (e.g., '7 DAY', '1 MONTH')
 * @return array Array of dates with failure counts
 */
function getFailureTrend(string $interval = '7 DAY'): array
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare(
            'SELECT DATE(timestamp) as date, COUNT(*) as count 
             FROM system_failures 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ' . $interval . ')
             GROUP BY DATE(timestamp) 
             ORDER BY date ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll() ?? [];
    } catch (PDOException $e) {
        error_log('[MindSpace Reliability] Failed to get failure trend: ' . $e->getMessage());
        return [];
    }
}
