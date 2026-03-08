<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — Empirical Framework Installation Checker
 * =======================================================
 * Verifies that all telemetry and A/B testing components
 * are properly installed and configured.
 * 
 * USAGE: Visit http://localhost/mindspace-ug/install_check.php
 */

require_once __DIR__ . '/php/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindSpace - Installation Check</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; background: #f5f7fa; }
        h1 { color: #4CAF50; border-bottom: 3px solid #4CAF50; padding-bottom: 0.5rem; }
        h2 { color: #2d3436; margin-top: 2rem; }
        .check { background: white; padding: 1rem; margin: 1rem 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .success { border-left: 5px solid #4CAF50; }
        .error { border-left: 5px solid #ef5350; }
        .warning { border-left: 5px solid #FFD54F; }
        .status { font-weight: bold; padding: 0.3rem 0.8rem; border-radius: 4px; display: inline-block; margin-bottom: 0.5rem; }
        .status.ok { background: #E8F5E9; color: #2E7D32; }
        .status.fail { background: #FFEBEE; color: #C62828; }
        .status.warn { background: #FFF8E1; color: #F57F17; }
        code { background: #f0f0f0; padding: 0.2rem 0.5rem; border-radius: 4px; font-family: 'Courier New', monospace; }
        pre { background: #2d3436; color: #dfe6e9; padding: 1rem; border-radius: 6px; overflow-x: auto; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <h1>🔍 MindSpace Empirical Framework - Installation Check</h1>
    <p>This page verifies that telemetry and A/B testing components are properly installed.</p>

<?php

$checks = [];
$allPassed = true;

// ── CHECK 1: Database Connection ───────────────────────────────
try {
    $stmt = $pdo->query('SELECT 1');
    $checks[] = [
        'title' => 'Database Connection',
        'status' => 'ok',
        'message' => 'Successfully connected to mindspace_db',
        'details' => 'PDO connection working correctly'
    ];
} catch (PDOException $e) {
    $checks[] = [
        'title' => 'Database Connection',
        'status' => 'fail',
        'message' => 'Cannot connect to database',
        'details' => $e->getMessage()
    ];
    $allPassed = false;
}

// ── CHECK 2: Telemetry Logs Table ──────────────────────────────
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'telemetry_logs'");
    if ($stmt->rowCount() > 0) {
        // Verify structure
        $stmt = $pdo->query("DESCRIBE telemetry_logs");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['id', 'session_id', 'event_type', 'page_url', 'created_at'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            $checks[] = [
                'title' => 'Telemetry Logs Table',
                'status' => 'ok',
                'message' => 'Table exists with correct structure',
                'details' => 'Columns: ' . implode(', ', $columns)
            ];
        } else {
            $checks[] = [
                'title' => 'Telemetry Logs Table',
                'status' => 'warn',
                'message' => 'Table exists but missing columns',
                'details' => 'Missing: ' . implode(', ', $missingColumns)
            ];
            $allPassed = false;
        }
    } else {
        $checks[] = [
            'title' => 'Telemetry Logs Table',
            'status' => 'fail',
            'message' => 'Table does not exist',
            'details' => 'Run database/migration_telemetry.sql to create it'
        ];
        $allPassed = false;
    }
} catch (PDOException $e) {
    $checks[] = [
        'title' => 'Telemetry Logs Table',
        'status' => 'fail',
        'message' => 'Error checking table',
        'details' => $e->getMessage()
    ];
    $allPassed = false;
}

// ── CHECK 3: A/B Test Assignments Table ────────────────────────
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'ab_test_assignments'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("DESCRIBE ab_test_assignments");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['id', 'session_id', 'experiment_name', 'variant', 'converted'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            $checks[] = [
                'title' => 'A/B Test Assignments Table',
                'status' => 'ok',
                'message' => 'Table exists with correct structure',
                'details' => 'Columns: ' . implode(', ', $columns)
            ];
        } else {
            $checks[] = [
                'title' => 'A/B Test Assignments Table',
                'status' => 'warn',
                'message' => 'Table exists but missing columns',
                'details' => 'Missing: ' . implode(', ', $missingColumns)
            ];
            $allPassed = false;
        }
    } else {
        $checks[] = [
            'title' => 'A/B Test Assignments Table',
            'status' => 'fail',
            'message' => 'Table does not exist',
            'details' => 'Run database/migration_telemetry.sql to create it'
        ];
        $allPassed = false;
    }
} catch (PDOException $e) {
    $checks[] = [
        'title' => 'A/B Test Assignments Table',
        'status' => 'fail',
        'message' => 'Error checking table',
        'details' => $e->getMessage()
    ];
    $allPassed = false;
}

// ── CHECK 4: Moods Table Extensions ────────────────────────────
try {
    $stmt = $pdo->query("DESCRIBE moods");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredExtensions = ['session_id', 'ab_variant'];
    $missingExtensions = array_diff($requiredExtensions, $columns);
    
    if (empty($missingExtensions)) {
        $checks[] = [
            'title' => 'Moods Table Extensions',
            'status' => 'ok',
            'message' => 'Extended columns present',
            'details' => 'Added: session_id, ab_variant'
        ];
    } else {
        $checks[] = [
            'title' => 'Moods Table Extensions',
            'status' => 'warn',
            'message' => 'Missing extension columns',
            'details' => 'Missing: ' . implode(', ', $missingExtensions)
        ];
        $allPassed = false;
    }
} catch (PDOException $e) {
    $checks[] = [
        'title' => 'Moods Table Extensions',
        'status' => 'fail',
        'message' => 'Error checking moods table',
        'details' => $e->getMessage()
    ];
    $allPassed = false;
}

// ── CHECK 5: Ab_test_metrics View ──────────────────────────────
try {
    $stmt = $pdo->query("SHOW FULL TABLES WHERE table_type = 'VIEW'");
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('ab_test_metrics', $views)) {
        $checks[] = [
            'title' => 'Analytics View',
            'status' => 'ok',
            'message' => 'ab_test_metrics view exists',
            'details' => 'Ready for metrics queries'
        ];
    } else {
        $checks[] = [
            'title' => 'Analytics View',
            'status' => 'warn',
            'message' => 'View not found',
            'details' => 'Optional: Run migration script to create'
        ];
    }
} catch (PDOException $e) {
    $checks[] = [
        'title' => 'Analytics View',
        'status' => 'fail',
        'message' => 'Error checking views',
        'details' => $e->getMessage()
    ];
}

// ── CHECK 6: PHP Files Exist ───────────────────────────────────
$requiredFiles = [
    'php/telemetry.php',
    'php/ab_test_api.php',
    'php/checkin.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $checks[] = [
            'title' => "File: {$file}",
            'status' => 'ok',
            'message' => 'File exists',
            'details' => 'Located at: ' . realpath(__DIR__ . '/' . $file)
        ];
    } else {
        $checks[] = [
            'title' => "File: {$file}",
            'status' => 'fail',
            'message' => 'File not found',
            'details' => 'Expected at: ' . __DIR__ . '/' . $file
        ];
        $allPassed = false;
    }
}

// ── CHECK 7: Test Telemetry Insert ─────────────────────────────
try {
    $testSessionId = 'install_check_' . bin2hex(random_bytes(8));
    $stmt = $pdo->prepare(
        'INSERT INTO telemetry_logs (session_id, event_type, page_url, created_at) 
         VALUES (?, "page_view", "/install_check.php", NOW())'
    );
    $stmt->execute([$testSessionId]);
    
    // Clean up test record
    $stmt = $pdo->prepare('DELETE FROM telemetry_logs WHERE session_id = ?');
    $stmt->execute([$testSessionId]);
    
    $checks[] = [
        'title' => 'Telemetry Write Test',
        'status' => 'ok',
        'message' => 'Can insert and delete telemetry records',
        'details' => 'Database permissions OK'
    ];
} catch (PDOException $e) {
    $checks[] = [
        'title' => 'Telemetry Write Test',
        'status' => 'fail',
        'message' => 'Cannot write to telemetry_logs',
        'details' => $e->getMessage()
    ];
    $allPassed = false;
}

// ── Display Results ────────────────────────────────────────────
echo "<h2>📋 Check Results</h2>";

foreach ($checks as $check) {
    $statusClass = $check['status'];
    $statusLabel = strtoupper($check['status']);
    
    echo "<div class='check {$statusClass}'>";
    echo "<span class='status {$check['status']}'>{$statusLabel}</span>";
    echo "<h3>{$check['title']}</h3>";
    echo "<p><strong>✓</strong> {$check['message']}</p>";
    if (!empty($check['details'])) {
        echo "<p><code>{$check['details']}</code></p>";
    }
    echo "</div>";
}

// ── Summary ────────────────────────────────────────────────────
echo "<h2>📊 Summary</h2>";
echo "<div class='check " . ($allPassed ? 'success' : 'error') . "'>";

if ($allPassed) {
    echo "<h3>✅ All Checks Passed!</h3>";
    echo "<p>Your MindSpace installation is ready for empirical investigation.</p>";
    echo "<ul>";
    echo "<li><strong>Next:</strong> Visit <a href='checkin.html'>Check-in Page</a> to test A/B testing</li>";
    echo "<li><strong>Documentation:</strong> Read <a href='METRICS.md'>METRICS.md</a></li>";
    echo "<li><strong>Quick Start:</strong> See <a href='AB_TESTING_GUIDE.md'>AB_TESTING_GUIDE.md</a></li>";
    echo "</ul>";
} else {
    echo "<h3>⚠️ Some Checks Failed</h3>";
    echo "<p>Please address the errors above before proceeding.</p>";
    echo "<p><strong>Recommended Action:</strong> Run <code>database/migration_telemetry.sql</code> in phpMyAdmin</p>";
}

echo "</div>";

// ── Quick Stats (if installation is complete) ──────────────────
if ($allPassed) {
    echo "<h2>📈 Current Metrics</h2>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM telemetry_logs");
        $telemetryCount = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM ab_test_assignments WHERE experiment_name = 'checkin_layout_test'");
        $abTestCount = $stmt->fetchColumn();
        
        echo "<div class='check success'>";
        echo "<ul>";
        echo "<li><strong>Telemetry Events Logged:</strong> {$telemetryCount}</li>";
        echo "<li><strong>A/B Test Participants:</strong> {$abTestCount}</li>";
        echo "</ul>";
        echo "</div>";
    } catch (PDOException $e) {
        // Ignore stats errors
    }
}

?>

    <hr style="margin: 3rem 0; border: none; border-top: 1px solid #ddd;">
    <p style="color: #636e72; font-size: 0.9rem;">
        <strong>Note:</strong> This installation checker is for development purposes only. 
        Remove or protect this file in production environments.
    </p>
</body>
</html>
