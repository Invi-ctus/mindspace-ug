<?php
require_once '../php/db.php';

// --- Query 1: LOC summary totals ---
$stmt = $pdo->query("
    SELECT
        SUM(total_loc)        AS total_loc,
        SUM(ncloc)            AS total_ncloc,
        SUM(cloc)             AS total_cloc,
        ROUND(AVG(comment_density),2) AS avg_density,
        COUNT(DISTINCT filename)      AS total_files
    FROM loc_measurements
    WHERE measured_date = (SELECT MAX(measured_date) FROM loc_measurements)
");
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Query 2: LOC per file ---
$stmt2 = $pdo->query("
    SELECT filename, total_loc, ncloc, cloc, blank_lines, comment_density, size_rating
    FROM loc_measurements
    WHERE measured_date = (SELECT MAX(measured_date) FROM loc_measurements)
    ORDER BY total_loc DESC
");
$loc_rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// --- Query 3: FP breakdown grouped by component type ---
$stmt3 = $pdo->query("
    SELECT component_type,
           COUNT(*) AS count,
           SUM(fp_points) AS subtotal
    FROM fp_measurements
    WHERE measured_date = (SELECT MAX(measured_date) FROM fp_measurements)
    GROUP BY component_type
    ORDER BY FIELD(component_type,'EI','EO','EQ','ILF','EIF')
");
$fp_groups = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// --- Query 4: FP total ---
$stmt4 = $pdo->query("SELECT SUM(fp_points) AS ufc FROM fp_measurements WHERE measured_date = (SELECT MAX(measured_date) FROM fp_measurements)");
$fp_total = $stmt4->fetch(PDO::FETCH_ASSOC);
$ufc = $fp_total['ufc'];
$vaf = 1.00;
$final_fp = $ufc * $vaf;

// --- Query 5: All FP rows for detail table ---
$stmt5 = $pdo->query("
    SELECT feature_name, component_type, description, complexity, weight, fp_points
    FROM fp_measurements
    WHERE measured_date = (SELECT MAX(measured_date) FROM fp_measurements)
    ORDER BY FIELD(component_type,'EI','EO','EQ','ILF','EIF')
");
$fp_rows = $stmt5->fetchAll(PDO::FETCH_ASSOC);

// --- Chart.js data: LOC per file ---
$chart_labels = json_encode(array_column($loc_rows, 'filename'));
$chart_ncloc  = json_encode(array_column($loc_rows, 'ncloc'));
$chart_cloc   = json_encode(array_column($loc_rows, 'cloc'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MindSpace — Software Size Metrics (Chapter 5)</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Poppins',sans-serif; background:#f5f5f5; color:#2d3436; }

    /* NAV */
    .topnav { background:#4CAF50; color:#fff; padding:14px 24px;
              display:flex; justify-content:space-between; align-items:center; }
    .topnav a { color:#fff; text-decoration:none; font-size:.9rem; }
    .topnav h1 { font-size:1.1rem; font-weight:600; }

    /* LAYOUT */
    .container { max-width:1100px; margin:24px auto; padding:0 16px; }
    h2 { color:#4CAF50; margin:24px 0 12px; font-size:1.2rem; border-left:4px solid #4CAF50; padding-left:10px; }

    /* CARDS */
    .cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
    .card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.1);
            border-top:4px solid #4CAF50; text-align:center; }
    .card .big { font-size:2rem; font-weight:700; color:#4CAF50; }
    .card .label { font-size:.85rem; color:#636e72; margin-top:4px; }
    .card .sub { font-size:.78rem; color:#636e72; }

    /* INFO BOX */
    .info-box { background:#E8F5E9; border-left:4px solid #4CAF50; padding:14px 18px;
                border-radius:8px; margin-bottom:20px; font-size:.92rem; line-height:1.7; }

    /* TABLES */
    table { width:100%; border-collapse:collapse; background:#fff;
            border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:24px; }
    th { background:#4CAF50; color:#fff; padding:12px 14px; text-align:left; font-size:.88rem; }
    td { padding:10px 14px; font-size:.87rem; border-bottom:1px solid #f0f0f0; }
    tr:last-child td { border-bottom:none; }
    tr:hover { background:#f9f9f9; }

    /* STATUS BADGES */
    .badge { padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; }
    .green  { background:#E8F5E9; color:#2E7D32; }
    .orange { background:#FFF3E0; color:#E65100; }
    .red    { background:#FFEBEE; color:#C62828; }

    /* CHART */
    .chart-box { background:#fff; border-radius:12px; padding:20px;
                 box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:24px; }

    /* FP SUMMARY BOX */
    .fp-total-box { background:#E8F5E9; border:2px solid #4CAF50; border-radius:12px;
                    padding:18px 24px; text-align:center; margin-bottom:24px; }
    .fp-total-box .fp-big { font-size:2.5rem; font-weight:700; color:#4CAF50; }
    .fp-total-box p { color:#636e72; font-size:.9rem; margin-top:6px; }

    /* GQM BOX */
    .gqm-box { background:#fff; border:1px solid #4CAF50; border-radius:10px;
               padding:18px 20px; margin-bottom:30px; }
    .gqm-box h3 { color:#4CAF50; margin-bottom:10px; }
    .gqm-box li { margin:6px 0; font-size:.9rem; }

    footer { text-align:center; color:#636e72; font-size:.8rem; padding:20px; }
  </style>
</head>
<body>

<div class="topnav">
  <h1>🌿 MindSpace — Software Size Metrics</h1>
  <a href="../admin/index.html">← Back to Admin Panel</a>
</div>

<div class="container">

  <!-- WHAT IS SOFTWARE SIZE -->
  <div class="info-box">
    <strong>📏 Chapter 5: Software Size</strong><br>
    MindSpace software size is measured in 3 ways:<br>
    <strong>1. LOC</strong> — how many lines were physically written (Length)<br>
    <strong>2. Halstead Metrics</strong> — how complex the code vocabulary is (Length)<br>
    <strong>3. Function Points</strong> — how much functionality we deliver to users (Functionality)
  </div>

  <!-- SUMMARY CARDS -->
  <h2>📊 LOC Summary</h2>
  <div class="cards">
    <div class="card">
      <div class="big"><?= number_format($summary['total_loc']) ?></div>
      <div class="label">Total Lines of Code</div>
    </div>
    <div class="card">
      <div class="big"><?= number_format($summary['total_ncloc']) ?></div>
      <div class="label">Working Code (NCLOC)</div>
      <div class="sub"><?= round($summary['total_ncloc']/$summary['total_loc']*100) ?>% of total</div>
    </div>
    <div class="card">
      <div class="big" style="color:<?= $summary['avg_density'] >= 20 ? '#4CAF50' : '#E65100' ?>">
        <?= $summary['avg_density'] ?>%
      </div>
      <div class="label">Avg Comment Density</div>
      <div class="sub">Target: ≥ 20%</div>
    </div>
    <div class="card">
      <div class="big"><?= $summary['total_files'] ?></div>
      <div class="label">Files Measured</div>
    </div>
  </div>

  <!-- LOC PER FILE TABLE -->
  <h2>📁 LOC Per File</h2>
  <table>
    <tr>
      <th>File</th><th>Total LOC</th><th>NCLOC</th><th>CLOC</th><th>Comment Density</th><th>Size</th>
    </tr>
    <?php foreach($loc_rows as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['filename']) ?></td>
      <td><?= $r['total_loc'] ?></td>
      <td><?= $r['ncloc'] ?></td>
      <td><?= $r['cloc'] ?></td>
      <td>
        <span class="badge <?= $r['comment_density'] >= 20 ? 'green' : 'orange' ?>">
          <?= $r['comment_density'] ?>%
        </span>
      </td>
      <td>
        <span class="badge <?= $r['size_rating']=='Small'?'green':($r['size_rating']=='Medium'?'orange':'red') ?>">
          <?= $r['size_rating'] ?>
        </span>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <!-- LOC BAR CHART -->
  <h2>📈 LOC Chart</h2>
  <div class="chart-box">
    <canvas id="locChart" height="120"></canvas>
  </div>

  <!-- FUNCTION POINTS -->
  <h2>🎯 Function Points (Chapter 5)</h2>
  <div class="info-box">
    <strong>What are Function Points?</strong> A language-independent measure of HOW MUCH MindSpace does for its users.
    Instead of counting lines, we count: inputs users send in, outputs we send back, and data we store.
    <br><strong>EI</strong>=data IN · <strong>EO</strong>=data OUT with calculations · <strong>EQ</strong>=data OUT without calculations · <strong>ILF</strong>=data stored inside
  </div>

  <!-- FP DETAIL TABLE -->
  <table>
    <tr><th>Feature</th><th>Type</th><th>Description</th><th>Complexity</th><th>Weight</th><th>FP Points</th></tr>
    <?php foreach($fp_rows as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['feature_name']) ?></td>
      <td><span class="badge <?= in_array($r['component_type'],['EI','EQ'])?'green':($r['component_type']=='EO'?'orange':'red') ?>">
        <?= $r['component_type'] ?></span></td>
      <td><?= htmlspecialchars($r['description']) ?></td>
      <td><?= ucfirst($r['complexity']) ?></td>
      <td><?= $r['weight'] ?></td>
      <td><strong><?= $r['fp_points'] ?></strong></td>
    </tr>
    <?php endforeach; ?>
    <tr style="background:#E8F5E9;font-weight:700;">
      <td colspan="5" style="text-align:right;">UFC (Unadjusted Function Points)</td>
      <td><?= $ufc ?></td>
    </tr>
    <tr style="background:#E8F5E9;font-weight:700;">
      <td colspan="5" style="text-align:right;">× VAF (Value Adjustment Factor)</td>
      <td><?= $vaf ?></td>
    </tr>
    <tr style="background:#4CAF50;color:#fff;font-weight:700;">
      <td colspan="5" style="text-align:right;">FINAL FUNCTION POINTS</td>
      <td><?= $final_fp ?></td>
    </tr>
  </table>

  <!-- FP TOTAL CARD -->
  <div class="fp-total-box">
    <div class="fp-big"><?= $final_fp ?> FP</div>
    <p>MindSpace = <strong>Small-to-Medium Application</strong></p>
    <p style="margin-top:8px;font-size:.85rem;">
      UFC = <?= $ufc ?> | VAF = <?= $vaf ?> (average technical complexity) | FP = <?= $ufc ?> × <?= $vaf ?> = <?= $final_fp ?>
    </p>
  </div>

  <!-- GQM CONNECTION -->
  <div class="gqm-box">
    <h3>🔗 How Size Metrics Connect to MindSpace GQM Goals</h3>
    <ul>
      <li>📊 <strong>Productivity</strong> = NCLOC / development hours → track improvement sprint by sprint</li>
      <li>🐛 <strong>Quality</strong> = defects / KLOC → target: &lt; 5 defects per 1000 lines</li>
      <li>💬 <strong>Documentation</strong> = CLOC/LOC → target: ≥ 20% (currently <?= $summary['avg_density'] ?>% — <?= $summary['avg_density'] >= 20 ? '✅ Met' : '⚠️ Needs improvement' ?>)</li>
      <li>🎯 <strong>FP = <?= $final_fp ?></strong> → allows fair comparison with other student projects regardless of language</li>
    </ul>
  </div>

</div><!-- /container -->

<footer>SWE 2204 Software Metrics — MUST BSE 2024 | Chapter 5: Software Size</footer>

<script>
// LOC Bar Chart using Chart.js
const ctx = document.getElementById('locChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= $chart_labels ?>,
    datasets: [
      {
        label: 'Working Code (NCLOC)',
        data: <?= $chart_ncloc ?>,
        backgroundColor: 'rgba(76, 175, 80, 0.7)',
        borderColor: '#4CAF50',
        borderWidth: 1
      },
      {
        label: 'Comments (CLOC)',
        data: <?= $chart_cloc ?>,
        backgroundColor: 'rgba(66, 165, 245, 0.6)',
        borderColor: '#42A5F5',
        borderWidth: 1
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'top' },
      title: { display: true, text: 'Lines of Code per File (NCLOC + CLOC)' }
    },
    scales: {
      x: { stacked: true },
      y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Lines' } }
    }
  }
});
</script>
</body>
</html>
