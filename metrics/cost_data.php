<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

require_once __DIR__ . '/../php/db.php';

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$tableName]);
    return (bool) $stmt->fetchColumn();
}

$data = [
    'success' => true,
    'overview' => [
        'cost_tracking_ready' => false,
        'entries' => 0,
        'total_actual_cost' => 0.0,
        'total_planned_cost' => 0.0,
        'cost_variance_pct' => 0.0,
        'rework_pct' => 0.0,
        'cost_per_fp' => null,
        'fp_per_hour' => null,
    ],
    'featureBreakdown' => [],
    'sprintCostTrend' => [],
    'techniques' => [
        [
            'name' => 'Bottom-Up Estimation',
            'category' => 'Other',
            'summary' => 'Estimate each component and sum totals.',
            'advantage' => 'Detailed estimates when design is mature.',
            'drawback' => 'Can miss integration/system-level effort.'
        ],
        [
            'name' => 'Top-Down Estimation',
            'category' => 'Other',
            'summary' => 'Estimate from overall functionality and allocate down.',
            'advantage' => 'Accounts for integration and documentation.',
            'drawback' => 'Can miss low-level technical complexity.'
        ],
        [
            'name' => 'Expert Judgement',
            'category' => 'Expert',
            'summary' => 'Experienced engineers estimate and converge.',
            'advantage' => 'Fast and practical when experts are available.',
            'drawback' => 'Subjective and hard to audit.'
        ],
        [
            'name' => 'Analogy / CBR',
            'category' => 'Analogy',
            'summary' => 'Use similar historical projects as baseline.',
            'advantage' => 'Accurate with high-quality historical data.',
            'drawback' => 'Needs a maintained project history database.'
        ],
        [
            'name' => 'COCOMO / COCOMO II',
            'category' => 'Algorithmic',
            'summary' => 'Model effort from size and adjustment factors.',
            'advantage' => 'Transparent formulas and reproducible estimates.',
            'drawback' => 'Sensitive to size and rating assumptions.'
        ],
        [
            'name' => 'SLIM (Putnam)',
            'category' => 'Constraint',
            'summary' => 'Relates size, effort, and schedule under constraints.',
            'advantage' => 'Strong for large-system schedule-effort tradeoff.',
            'drawback' => 'Can overestimate effort on small/medium projects.'
        ],
        [
            'name' => 'Pricing-to-Win / Parkinson',
            'category' => 'Other',
            'summary' => 'Estimate from external budget/time constraints.',
            'advantage' => 'Simple and contract-driven.',
            'drawback' => 'Often detached from true engineering effort.'
        ],
    ],
    'cocomo' => [
        'basic_modes' => [
            'organic' => ['a' => 2.4, 'b' => 1.05],
            'semi_detached' => ['a' => 3.0, 'b' => 1.12],
            'embedded' => ['a' => 3.6, 'b' => 1.20],
        ],
        'intermediate_modes' => [
            'organic' => ['a' => 3.2, 'b' => 1.05, 'c' => 2.5, 'd' => 0.38],
            'semi_detached' => ['a' => 3.0, 'b' => 1.12, 'c' => 2.5, 'd' => 0.35],
            'embedded' => ['a' => 2.8, 'b' => 1.20, 'c' => 2.5, 'd' => 0.32],
        ],
    ],
    'slim' => [
        'default_d' => [
            'new_with_interfaces' => 12.3,
            'standalone' => 15.0,
            'reimplementation' => 27.0,
        ]
    ],
    'notes' => [
        'A model is often considered acceptable when 75% of estimates fall within 25% of actual values.',
        'All model outputs should be calibrated using local project history and consistent sizing methods.'
    ],
];

if (!tableExists($pdo, 'cost_tracking')) {
    echo json_encode($data);
    exit;
}

$data['overview']['cost_tracking_ready'] = true;

try {
    $summaryStmt = $pdo->query(
        "SELECT
            COUNT(*) AS entries,
            COALESCE(SUM(actual_hours * hourly_rate), 0) AS total_actual_cost,
            COALESCE(SUM(planned_hours * hourly_rate), 0) AS total_planned_cost,
            COALESCE(SUM(actual_hours), 0) AS total_actual_hours,
            COALESCE(SUM(rework_hours), 0) AS total_rework_hours
         FROM cost_tracking"
    );
    $summary = $summaryStmt->fetch() ?: [];

    $entries = (int) ($summary['entries'] ?? 0);
    $actualCost = (float) ($summary['total_actual_cost'] ?? 0);
    $plannedCost = (float) ($summary['total_planned_cost'] ?? 0);
    $actualHours = (float) ($summary['total_actual_hours'] ?? 0);
    $reworkHours = (float) ($summary['total_rework_hours'] ?? 0);

    $data['overview']['entries'] = $entries;
    $data['overview']['total_actual_cost'] = round($actualCost, 2);
    $data['overview']['total_planned_cost'] = round($plannedCost, 2);
    $data['overview']['cost_variance_pct'] = $plannedCost > 0
        ? round((($actualCost - $plannedCost) / $plannedCost) * 100, 2)
        : 0.0;
    $data['overview']['rework_pct'] = $actualHours > 0
        ? round(($reworkHours / $actualHours) * 100, 2)
        : 0.0;

    $featureStmt = $pdo->query(
        "SELECT
            feature_name,
            sprint_label,
            ROUND(SUM(planned_hours), 2) AS planned_hours,
            ROUND(SUM(actual_hours), 2) AS actual_hours,
            ROUND(SUM(actual_hours * hourly_rate), 2) AS actual_cost,
            ROUND(
                CASE
                    WHEN SUM(planned_hours) > 0
                    THEN ((SUM(actual_hours) - SUM(planned_hours)) / SUM(planned_hours)) * 100
                    ELSE 0
                END,
                2
            ) AS effort_variance_pct
         FROM cost_tracking
         GROUP BY feature_name, sprint_label
         ORDER BY actual_cost DESC"
    );
    $data['featureBreakdown'] = $featureStmt->fetchAll();

    $trendStmt = $pdo->query(
        "SELECT
            DATE(measured_date) AS day,
            ROUND(SUM(actual_hours * hourly_rate), 2) AS cost
         FROM cost_tracking
         GROUP BY DATE(measured_date)
         ORDER BY day ASC"
    );
    $data['sprintCostTrend'] = $trendStmt->fetchAll();

    if (tableExists($pdo, 'fp_measurements')) {
        $fpStmt = $pdo->query(
            "SELECT
                COALESCE(SUM(fp.fp_points), 0) AS total_fp,
                COALESCE(SUM(ct.actual_hours), 0) AS total_actual_hours,
                COALESCE(SUM(ct.actual_hours * ct.hourly_rate), 0) AS total_actual_cost
             FROM cost_tracking ct
             LEFT JOIN fp_measurements fp
                ON fp.feature_name = ct.feature_name"
        );
        $fp = $fpStmt->fetch() ?: [];

        $totalFp = (float) ($fp['total_fp'] ?? 0);
        $totalHours = (float) ($fp['total_actual_hours'] ?? 0);
        $totalCost = (float) ($fp['total_actual_cost'] ?? 0);

        if ($totalFp > 0) {
            $data['overview']['cost_per_fp'] = round($totalCost / $totalFp, 2);
        }
        if ($totalHours > 0) {
            $data['overview']['fp_per_hour'] = round($totalFp / $totalHours, 3);
        }
    }
} catch (PDOException $e) {
    error_log('[MindSpace Cost Metrics Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch cost metrics data.',
    ]);
    exit;
}

echo json_encode($data);
