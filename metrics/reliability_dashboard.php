<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * MindSpace — System Reliability Dashboard
 * ==========================================
 * Displays system reliability metrics and failure trends.
 * Uses Chart.js for visualization.
 */

session_start();

// Check if user is logged in (optional, can view without login for demo)
$isLoggedIn = !empty($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reliability Dashboard — MindSpace</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 15px;
        }

        .dashboard-header h1 {
            margin: 0;
            color: #333;
        }

        .dashboard-header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .metric-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .metric-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }

        .metric-unit {
            font-size: 14px;
            color: #999;
            margin-top: 5px;
        }

        .metric-description {
            font-size: 12px;
            color: #888;
            margin-top: 10px;
            line-height: 1.4;
        }

        .status-good {
            border-left: 4px solid #27ae60;
        }

        .status-warning {
            border-left: 4px solid #f39c12;
        }

        .status-critical {
            border-left: 4px solid #e74c3c;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .chart-container h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            color: #333;
        }

        .chart-wrapper {
            position: relative;
            height: 400px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-badge.ok {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.critical {
            background: #f8d7da;
            color: #721c24;
        }

        .nav-bar {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-bar a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            font-size: 14px;
        }

        .nav-bar a:hover {
            text-decoration: underline;
        }

        .empty-state {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }

        .empty-state p {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <nav class="nav-bar">
        <div>
            <a href="../dashboard.html">← Back to Dashboard</a>
        </div>
        <div>
            Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>📊 System Reliability Metrics</h1>
            <p id="lastUpdated">Loading data...</p>
        </div>

        <!-- Metrics Cards -->
        <div class="metrics-grid" id="metricsGrid">
            <div class="metric-card" style="text-align: center;">
                <p>Loading metrics...</p>
            </div>
        </div>

        <!-- Failure Trend Chart -->
        <div class="charts-grid">
            <div class="chart-container">
                <h3>Failures Over Time (Last 30 Days)</h3>
                <div class="chart-wrapper">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <h3>Failures by Module</h3>
                <div class="chart-wrapper">
                    <canvas id="moduleChart"></canvas>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-container">
                <h3>Failures by Type</h3>
                <div class="chart-wrapper">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fetch reliability metrics
        fetch('../metrics/reliability_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update last updated timestamp
                    document.getElementById('lastUpdated').textContent = 
                        'Last updated: ' + data.timestamp;

                    // Build metrics cards HTML
                    loadMetricsCards(data);

                    // Build charts
                    loadCharts(data);
                } else {
                    showError('Failed to load metrics');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error loading metrics: ' + error.message);
            });

        function getAvailabilityStatus(availability) {
            if (availability >= 99.5) return 'ok';
            if (availability >= 95) return 'warning';
            return 'critical';
        }

        function getAvailabilityBadgeClass(availability) {
            if (availability >= 99.5) return 'ok';
            if (availability >= 95) return 'warning';
            return 'critical';
        }

        function loadMetricsCards(data) {
            const grid = document.getElementById('metricsGrid');
            const metrics = data.metrics;
            const availability = metrics.availability.value_percent;

            let html = `
                <div class="metric-card status-good">
                    <h3>Total Failures</h3>
                    <div class="metric-value">${data.summary.total_failures}</div>
                    <div class="metric-unit">recorded</div>
                </div>

                <div class="metric-card status-good">
                    <h3>${metrics.mttf.label}</h3>
                    <div class="metric-value">${metrics.mttf.value_human}</div>
                    <div class="metric-unit">${metrics.mttf.value_seconds} seconds</div>
                    <div class="metric-description">${metrics.mttf.description}</div>
                </div>

                <div class="metric-card status-good">
                    <h3>${metrics.mttr.label}</h3>
                    <div class="metric-value">${metrics.mttr.value_human}</div>
                    <div class="metric-unit">${metrics.mttr.value_seconds} seconds</div>
                    <div class="metric-description">${metrics.mttr.description}</div>
                </div>

                <div class="metric-card status-good">
                    <h3>${metrics.mtbf.label}</h3>
                    <div class="metric-value">${metrics.mtbf.value_human}</div>
                    <div class="metric-unit">${metrics.mtbf.value_seconds} seconds</div>
                    <div class="metric-description">${metrics.mtbf.description}</div>
                </div>

                <div class="metric-card ${getAvailabilityStatus(availability) === 'ok' ? 'status-good' : getAvailabilityStatus(availability) === 'warning' ? 'status-warning' : 'status-critical'}">
                    <h3>${metrics.availability.label}</h3>
                    <div class="metric-value">${metrics.availability.value_percent}%</div>
                    <div class="metric-unit">uptime</div>
                    <div class="metric-description">${metrics.availability.description}</div>
                    <span class="status-badge ${getAvailabilityBadgeClass(availability)}">
                        ${availability >= 99.5 ? '✓ Excellent' : availability >= 95 ? '⚠ Good' : '✗ Needs Work'}
                    </span>
                </div>

                <div class="metric-card status-good">
                    <h3>Failure Intensity</h3>
                    <div class="metric-value">${data.summary.failure_intensity}</div>
                    <div class="metric-unit">failures per day</div>
                    <div class="metric-description">Average rate of failures</div>
                </div>
            `;

            grid.innerHTML = html;
        }

        function loadCharts(data) {
            // Failure Trend Chart
            if (data.trend && data.trend.length > 0) {
                const trendDates = data.trend.map(item => item.date);
                const trendCounts = data.trend.map(item => parseInt(item.count));

                new Chart(document.getElementById('trendChart'), {
                    type: 'line',
                    data: {
                        labels: trendDates,
                        datasets: [{
                            label: 'Failures',
                            data: trendCounts,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#3498db',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Failures by Module Chart
            if (data.failures_by_module && data.failures_by_module.length > 0) {
                const moduleNames = data.failures_by_module.map(item => item.module);
                const moduleCounts = data.failures_by_module.map(item => parseInt(item.count));

                new Chart(document.getElementById('moduleChart'), {
                    type: 'doughnut',
                    data: {
                        labels: moduleNames,
                        datasets: [{
                            data: moduleCounts,
                            backgroundColor: [
                                'rgba(231, 76, 60, 0.8)',
                                'rgba(243, 156, 18, 0.8)',
                                'rgba(46, 204, 113, 0.8)',
                                'rgba(52, 152, 219, 0.8)',
                                'rgba(155, 89, 182, 0.8)'
                            ],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
            }

            // Failures by Type Chart
            if (data.failures_by_type && data.failures_by_type.length > 0) {
                const typeNames = data.failures_by_type.map(item => item.failure_type);
                const typeCounts = data.failures_by_type.map(item => parseInt(item.count));

                new Chart(document.getElementById('typeChart'), {
                    type: 'bar',
                    data: {
                        labels: typeNames,
                        datasets: [{
                            label: 'Count',
                            data: typeCounts,
                            backgroundColor: 'rgba(52, 152, 219, 0.8)',
                            borderColor: 'rgba(52, 152, 219, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        }

        function showError(message) {
            const grid = document.getElementById('metricsGrid');
            grid.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <p><strong>⚠️ Error</strong></p>
                    <p>${message}</p>
                </div>
            `;
        }
    </script>
</body>
</html>
