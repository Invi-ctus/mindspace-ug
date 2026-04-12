<?php
require_once __DIR__ . '/../php/db.php';

$testCases = [];
$errorMessage = '';

try {
    $stmt = $pdo->query("SELECT * FROM test_cases ORDER BY created_at DESC");
    $testCases = $stmt->fetchAll();
} catch (Exception $e) {
    $errorMessage = 'Could not load test cases. Did you create the test_cases table?';
}

$total = count($testCases);
$passed = 0;
$failed = 0;
$pending = 0;

foreach ($testCases as $testCase) {
    if ($testCase['status'] === 'pass') {
        $passed++;
    } elseif ($testCase['status'] === 'fail') {
        $failed++;
    } else {
        $pending++;
    }
}

$passRate = ($total > 0) ? ($passed / $total) * 100 : 0;
$failureRate = ($total > 0) ? ($failed / $total) * 100 : 0;
$pendingRate = ($total > 0) ? ($pending / $total) * 100 : 0;

// Optional: simple feature coverage for assignment
$distinctFeatures = 0;
$distinctFeaturesArray = [];
foreach ($testCases as $testCase) {
    if (!empty($testCase['feature_name'])) {
        $distinctFeaturesArray[$testCase['feature_name']] = true;
    }
}
if (!empty($distinctFeaturesArray)) {
    $distinctFeatures = count($distinctFeaturesArray);
}

$totalFeaturesAssumed = 5;
$featureCoverageRate = ($totalFeaturesAssumed > 0) ? ($distinctFeatures / $totalFeaturesAssumed) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Metrics - MindSpace Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            background: #f8f8f8;
            color: #222;
        }

        h1, h2 {
            margin-bottom: 12px;
        }

        .metrics-box {
            background: #fff;
            border: 1px solid #ddd;
            padding: 16px;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f0f0f0;
        }

        .error {
            color: #b00020;
            margin-bottom: 12px;
        }

        .small-note {
            color: #666;
            font-size: 13px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <h1>Software Test Metrics</h1>

    <?php if (!empty($errorMessage)): ?>
        <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
    <?php endif; ?>

    <div class="metrics-box">
        <h2>Summary Counts</h2>
        <p>Total Test Cases: <strong><?php echo $total; ?></strong></p>
        <p>Passed: <strong><?php echo $passed; ?></strong></p>
        <p>Failed: <strong><?php echo $failed; ?></strong></p>
        <p>Pending: <strong><?php echo $pending; ?></strong></p>
    </div>

    <div class="metrics-box">
        <h2>Rates</h2>
        <p>Test Pass Rate: <strong><?php echo number_format($passRate, 2); ?>%</strong></p>
        <p>Test Failure Rate: <strong><?php echo number_format($failureRate, 2); ?>%</strong></p>
        <p>Test Pending Rate: <strong><?php echo number_format($pendingRate, 2); ?>%</strong></p>
    </div>

    <div class="metrics-box">
        <h2>Feature Coverage (Optional)</h2>
        <p>Distinct Features with Tests: <strong><?php echo $distinctFeatures; ?></strong></p>
        <p>Total Features (assumed): <strong><?php echo $totalFeaturesAssumed; ?></strong></p>
        <p>Feature Coverage: <strong><?php echo number_format($featureCoverageRate, 2); ?>%</strong></p>
        <p class="small-note">Coverage formula: (distinct tested features / 5) * 100</p>
    </div>

    <h2>All Test Cases</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Feature Name</th>
                <th>Test Description</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total === 0): ?>
                <tr>
                    <td colspan="5">No test cases found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($testCases as $testCase): ?>
                    <tr>
                        <td><?php echo (int)$testCase['id']; ?></td>
                        <td><?php echo htmlspecialchars($testCase['feature_name']); ?></td>
                        <td><?php echo htmlspecialchars($testCase['test_description']); ?></td>
                        <td><?php echo htmlspecialchars($testCase['status']); ?></td>
                        <td><?php echo htmlspecialchars($testCase['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
