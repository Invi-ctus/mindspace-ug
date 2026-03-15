<?php
require_once '../php/db.php';

// --- Cyclomatic: all functions sorted by complexity desc ---
$stmt = $pdo->query("
    SELECT filename, function_name, decision_points, cyclomatic_complexity,
           complexity_level, min_test_cases
    FROM cyclomatic_measurements
    WHERE measured_date = (SELECT MAX(measured_date) FROM cyclomatic_measurements)
    ORDER BY cyclomatic_complexity DESC
");
$cc_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Cyclomatic summary ---
$stmt2 = $pdo->query("
    SELECT MAX(cyclomatic_complexity) AS max_cc,
           ROUND(AVG(cyclomatic_complexity),1) AS avg_cc,
           SUM(CASE WHEN cyclomatic_complexity > 10 THEN 1 ELSE 0 END) AS at_risk
    FROM cyclomatic_measurements
    WHERE measured_date = (SELECT MAX(measured_date) FROM cyclomatic_measurements)
");
$cc_summary = $stmt2->fetch(PDO::FETCH_ASSOC);

// --- Module cohesion/coupling ---
$stmt3 = $pdo->query("
    SELECT module_name, internal_relations, external_relations,
           cohesion_score, coupling_score, fan_in, fan_out, ifc_score, risk_level
    FROM module_complexity
    WHERE measured_date = (SELECT MAX(measured_date) FROM module_complexity)
    ORDER BY ifc_score DESC
");
$mod_rows = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// --- System cohesion/coupling averages ---
$stmt4 = $pdo->query("
    SELECT ROUND(AVG(cohesion_score),2) AS sys_ch,
           ROUND(AVG(coupling_score),2) AS sys_cp
    FROM module_complexity
    WHERE measured_date = (SELECT MAX(measured_date) FROM module_complexity)
");
$sys = $stmt4->fetch(PDO::FETCH_ASSOC);

// --- Chart data ---
$cc_labels    = json_encode(array_map(fn($r) => $r['function_name'], $cc_rows));
$cc_values    = json_encode(array_column($cc_rows, 'cyclomatic_complexity'));
$mod_labels   = json_encode(array_map(fn($r) => basename($r['module_name']), $mod_rows));
$ch_values    = json_encode(array_column($mod_rows, 'cohesion_score'));
$cp_values    = json_encode(array_column($mod_rows, 'coupling_score'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MindSpace — Structural Complexity (Chapter 6)</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Poppins',sans-serif; background:#f5f5f5; color:#2d3436; }
    .topnav { background:#4CAF50; color:#fff; padding:14px 24px;
              display:flex; justify-content:space-between; align-items:center; }
    .topnav a { color:#fff; text-decoration:none; font-size:.9rem; }
    .topnav h1 { font-size:1.1rem; font-weight:600; }
    .container { max-width:1100px; margin:24px auto; padding:0 16px; }
    h2 { color:#4CAF50; margin:24px 0 12px; font-size:1.2rem;
         border-left:4px solid #4CAF50; padding-left:10px; }
    .cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
    .card { background:#fff; border-radius:12px; padding:20px;
            box-shadow:0 2px 8px rgba(0,0,0,.1); border-top:4px solid #4CAF50; text-align:center; }
    .card .big { font-size:2rem; font-weight:700; color:#4CAF50; }
    .card .label { font-size:.85rem; color:#636e72; margin-top:4px; }
    .card .sub { font-size:.78rem; color:#636e72; }
    .info-box { background:#E8F5E9; border-left:4px solid #4CAF50; padding:14px 18px;
                border-radius:8px; margin-bottom:20px; font-size:.92rem; line-height:1.7; }
    .warn-box { background:#FFF3E0; border-left:4px solid #FF9800; padding:14px 18px;
                border-radius:8px; margin-bottom:20px; font-size:.9rem; line-height:1.7; }
    table { width:100%; border-collapse:collapse; background:#fff;
            border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:24px; }
    th { background:#4CAF50; color:#fff; padding:12px 14px; text-align:left; font-size:.88rem; }
    td { padding:10px 14px; font-size:.87rem; border-bottom:1px solid #f0f0f0; }
    tr:last-child td { border-bottom:none; }
    tr:hover { background:#f9f9f9; }
    .badge { padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; }
    .green  { background:#E8F5E9; color:#2E7D32; }
    .orange { background:#FFF3E0; color:#E65100; }
    .red    { background:#FFEBEE; color:#C62828; }
    .chart-box { background:#fff; border-radius:12px; padding:20px;
                 box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:24px; }
    .gqm-box { background:#fff; border:1px solid #4CAF50; border-radius:10px;
               padding:18px 20px; margin-bottom:30px; }
    .gqm-box h3 { color:#4CAF50; margin-bottom:10px; }
    .gqm-box li { margin:6px 0; font-size:.9rem; }
    .summary-row { background:#E8F5E9; font-weight:700; }
    footer { text-align:center; color:#636e72; font-size:.8rem; padding:20px; }
  </style>
</head>
<body>

<div class="topnav">
  <h1>🔀 MindSpace — Structural Complexity Metrics</h1>
  <a href="../admin/index.html">← Back to Admin Panel</a>
</div>

<div class="container">

  <!-- WHAT IS STRUCTURAL COMPLEXITY -->
  <div class="info-box">
    <strong>🔀 Chapter 6: Structural Complexity</strong><br>
    MindSpace structural complexity is measured in 3 ways:<br>
    <strong>1. Cyclomatic Complexity v(G)</strong> — how many paths exist through each function (v = 1 + decision points)<br>
    <strong>2. Cohesion CH</strong> — how focused each module is on one task (higher = better)<br>
    <strong>3. Coupling CP</strong> — how dependent modules are on each other (lower = better)<br>
    <strong>Goal: LOW complexity + HIGH cohesion + LOW coupling</strong>
  </div>

  <!-- SYSTEM HEALTH CARDS -->
  <h2>🏥 System Health Summary</h2>
  <div class="cards">
    <div class="card">
      <div class="big" style="color:<?= $cc_summary['max_cc'] <= 10 ? '#4CAF50' : '#E65100' ?>">
        <?= $cc_summary['max_cc'] ?>
      </div>
      <div class="label">Highest v(G)</div>
      <div class="sub"><?= $cc_summary['max_cc'] <= 10 ? '✅ Simple range' : '⚠️ Needs review' ?></div>
    </div>
    <div class="card">
      <div class="big" style="color:<?= $sys['sys_ch'] >= 0.60 ? '#4CAF50' : '#E65100' ?>">
        <?= $sys['sys_ch'] ?>
      </div>
      <div class="label">System Cohesion CH</div>
      <div class="sub">Target: ≥ 0.60 | <?= $sys['sys_ch'] >= 0.60 ? '✅ Good' : '⚠️ Needs Work' ?></div>
    </div>
    <div class="card">
      <div class="big" style="color:<?= $sys['sys_cp'] <= 0.40 ? '#4CAF50' : '#E65100' ?>">
        <?= $sys['sys_cp'] ?>
      </div>
      <div class="label">System Coupling CP</div>
      <div class="sub">Target: ≤ 0.40 | <?= $sys['sys_cp'] <= 0.40 ? '✅ Good' : '⚠️ Needs Work' ?></div>
    </div>
    <div class="card">
      <div class="big" style="color:<?= $cc_summary['at_risk'] == 0 ? '#4CAF50' : '#C62828' ?>">
        <?= $cc_summary['at_risk'] ?>
      </div>
      <div class="label">Functions v(G) > 10</div>
      <div class="sub"><?= $cc_summary['at_risk'] == 0 ? '✅ None at risk' : '🔴 Needs refactoring' ?></div>
    </div>
  </div>

  <!-- CYCLOMATIC TABLE -->
  <h2>🔢 Cyclomatic Complexity — v(G) = 1 + d</h2>
  <div class="info-box" style="font-size:.86rem;">
    <strong>d</strong> = number of decision points (every if, elseif, while, for, foreach, case counts as 1)<br>
    <strong>v(G)</strong> also tells us the <strong>minimum number of test cases</strong> needed to test every path.<br>
    Scale: 1–10 = Simple ✅ | 11–20 = Moderate ⚠️ | 21–50 = Complex 🔴 | &gt;50 = Untestable ❌
  </div>
  <table>
    <tr>
      <th>File</th><th>Function</th><th>Decision Points (d)</th>
      <th>v(G)</th><th>Level</th><th>Min Test Cases</th>
    </tr>
    <?php foreach($cc_rows as $r):
      $bg = $r['cyclomatic_complexity'] <= 10 ? '' : ($r['cyclomatic_complexity'] <= 20 ? 'style="background:#FFF3E0"' : 'style="background:#FFEBEE"');
    ?>
    <tr <?= $bg ?>>
      <td><?= htmlspecialchars($r['filename']) ?></td>
      <td><strong><?= htmlspecialchars($r['function_name']) ?></strong></td>
      <td><?= $r['decision_points'] ?></td>
      <td><strong><?= $r['cyclomatic_complexity'] ?></strong></td>
      <td><span class="badge <?= $r['cyclomatic_complexity']<=10?'green':($r['cyclomatic_complexity']<=20?'orange':'red') ?>">
        <?= $r['complexity_level'] ?>
      </span></td>
      <td><?= $r['min_test_cases'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <div class="info-box">
    <strong>What this means for MindSpace:</strong><br>
    All functions are in the <strong>Simple range (v(G) ≤ 10)</strong> ✅<br>
    Example: handleLogin has v(G) = 4 → there are 4 independent paths through it → we need at least 4 test cases.<br>
    Simple functions = easier to test, easier to fix, fewer bugs. Good design!
  </div>

  <!-- CYCLOMATIC CHART -->
  <h2>📊 Cyclomatic Complexity Chart</h2>
  <div class="chart-box">
    <canvas id="ccChart" height="100"></canvas>
  </div>

  <!-- COHESION/COUPLING TABLE -->
  <h2>🧲 Cohesion and Coupling per Module</h2>
  <div class="info-box" style="font-size:.86rem;">
    <strong>CH = internal / (internal + external)</strong> — higher is better (module is focused)<br>
    <strong>CP = external / (internal + external)</strong> — lower is better (module is independent)<br>
    <strong>IFC = (fan_in × fan_out)²</strong> — information flow complexity<br>
    <strong>Goal: CH ≥ 0.60 and CP ≤ 0.40</strong>
  </div>
  <table>
    <tr>
      <th>Module</th><th>Internal</th><th>External</th>
      <th>Cohesion CH</th><th>Coupling CP</th>
      <th>Fan-in</th><th>Fan-out</th><th>IFC</th><th>Risk</th>
    </tr>
    <?php foreach($mod_rows as $r): ?>
    <tr>
      <td><?= htmlspecialchars(basename($r['module_name'])) ?></td>
      <td><?= $r['internal_relations'] ?></td>
      <td><?= $r['external_relations'] ?></td>
      <td><span class="badge <?= $r['cohesion_score']>=0.60?'green':($r['cohesion_score']>=0.40?'orange':'red') ?>">
        <?= $r['cohesion_score'] ?>
      </span></td>
      <td><span class="badge <?= $r['coupling_score']<=0.40?'green':($r['coupling_score']<=0.60?'orange':'red') ?>">
        <?= $r['coupling_score'] ?>
      </span></td>
      <td><?= $r['fan_in'] ?></td>
      <td><?= $r['fan_out'] ?></td>
      <td><?= $r['ifc_score'] ?></td>
      <td><span class="badge <?= $r['risk_level']=='Low'?'green':($r['risk_level']=='Medium'?'orange':'red') ?>">
        <?= $r['risk_level'] ?>
      </span></td>
    </tr>
    <?php endforeach; ?>
    <tr class="summary-row">
      <td colspan="3"><strong>System Average</strong></td>
      <td><strong><?= $sys['sys_ch'] ?></strong></td>
      <td><strong><?= $sys['sys_cp'] ?></strong></td>
      <td colspan="4" style="font-size:.82rem;color:#4CAF50;">
        CH <?= $sys['sys_ch'] >= 0.60 ? '✅' : '⚠️' ?> | CP <?= $sys['sys_cp'] <= 0.40 ? '✅' : '⚠️' ?>
      </td>
    </tr>
  </table>

  <!-- COHESION/COUPLING CHART -->
  <h2>📈 Cohesion vs Coupling Chart</h2>
  <div class="chart-box">
    <canvas id="chcpChart" height="100"></canvas>
  </div>

  <!-- IFC EXPLANATION -->
  <h2>🌊 Information Flow Complexity (Fan-in / Fan-out)</h2>
  <div class="info-box">
    <strong>Fan-in</strong> = how many other files call or depend on this module (like suppliers bringing ingredients to a restaurant)<br>
    <strong>Fan-out</strong> = how many files this module calls or depends on (like delivery services taking food out)<br>
    <strong>IFC = (fan_in × fan_out)²</strong> — squaring amplifies complexity. A module with fan_in=4 and fan_out=3 has IFC = (4×3)² = 144.<br>
    <strong>js/main.js has the highest IFC (144)</strong> — it is the frontend hub connecting everything.
    This is expected and monitored, not necessarily bad for a central JS file.
  </div>

  <!-- DESIGN QUALITY ASSESSMENT -->
  <h2>✅ Design Quality Assessment</h2>
  <table>
    <tr><th>Design Goal</th><th>Target</th><th>Current</th><th>Status</th></tr>
    <tr>
      <td>All functions v(G) ≤ 10</td><td>100%</td>
      <td><?= (10-$cc_summary['at_risk']) ?>/10 functions</td>
      <td><span class="badge <?= $cc_summary['at_risk']==0?'green':'red' ?>">
        <?= $cc_summary['at_risk']==0?'✅ Excellent':'❌ Fix needed' ?></span></td>
    </tr>
    <tr>
      <td>System Cohesion CH ≥ 0.60</td><td>≥ 0.60</td>
      <td><?= $sys['sys_ch'] ?></td>
      <td><span class="badge <?= $sys['sys_ch']>=0.60?'green':'orange' ?>">
        <?= $sys['sys_ch']>=0.60?'✅ Good':'⚠️ Monitor' ?></span></td>
    </tr>
    <tr>
      <td>System Coupling CP ≤ 0.40</td><td>≤ 0.40</td>
      <td><?= $sys['sys_cp'] ?></td>
      <td><span class="badge <?= $sys['sys_cp']<=0.40?'green':'orange' ?>">
        <?= $sys['sys_cp']<=0.40?'✅ Good':'⚠️ Monitor' ?></span></td>
    </tr>
    <tr>
      <td>Functions needing refactoring v(G)>10</td><td>0</td>
      <td><?= $cc_summary['at_risk'] ?></td>
      <td><span class="badge <?= $cc_summary['at_risk']==0?'green':'red' ?>">
        <?= $cc_summary['at_risk']==0?'✅ None':'❌ Action needed' ?></span></td>
    </tr>
  </table>

  <!-- GQM CONNECTION -->
  <div class="gqm-box">
    <h3>🔗 How Complexity Metrics Connect to MindSpace GQM Goals (Chapter 3)</h3>
    <ul>
      <li>🎯 <strong>Goal 3 (Reliability):</strong> All v(G) ≤ 10 → all functions are testable → fewer defects → reliable app for students</li>
      <li>🔄 <strong>Goal 1 (Reduce Dropout):</strong> Low coupling CP = <?= $sys['sys_cp'] ?> → modules are independent → bugs fixed quickly → less downtime</li>
      <li>🤝 <strong>Goal 2 (Community):</strong> High cohesion in community.php (CH=0.60) → post and reply features work predictably without unexpected side effects</li>
      <li>📊 <strong>Scale type:</strong> Cohesion and Coupling are Ratio scale (0 to 1, true zero exists). Cyclomatic is Absolute scale (count of paths).</li>
    </ul>
  </div>

</div><!-- /container -->

<footer>SWE 2204 Software Metrics — MUST BSE 2024 | Chapter 6: Structural Complexity</footer>

<script>
// Chart 1: Cyclomatic Complexity per function
const ctx1 = document.getElementById('ccChart').getContext('2d');
new Chart(ctx1, {
  type: 'bar',
  data: {
    labels: <?= $cc_labels ?>,
    datasets: [{
      label: 'v(G) Cyclomatic Complexity',
      data: <?= $cc_values ?>,
      backgroundColor: <?= $cc_values ?>.map(v => v <= 10 ? 'rgba(76,175,80,0.7)' : v <= 20 ? 'rgba(255,152,0,0.7)' : 'rgba(244,67,54,0.7)'),
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      title: { display: true, text: 'Cyclomatic Complexity v(G) per Function (red line = limit 10)' },
      annotation: { annotations: { line1: { type:'line', yMin:10, yMax:10, borderColor:'red', borderWidth:2, label:{ content:'Limit=10', enabled:true } } } }
    },
    scales: { y: { beginAtZero:true, max:12, title:{ display:true, text:'v(G)' } } }
  }
});

// Chart 2: Cohesion vs Coupling
const ctx2 = document.getElementById('chcpChart').getContext('2d');
new Chart(ctx2, {
  type: 'bar',
  data: {
    labels: <?= $mod_labels ?>,
    datasets: [
      { label: 'Cohesion CH (higher=better)', data: <?= $ch_values ?>, backgroundColor: 'rgba(76,175,80,0.7)', borderWidth:1 },
      { label: 'Coupling CP (lower=better)',  data: <?= $cp_values ?>, backgroundColor: 'rgba(244,67,54,0.6)',  borderWidth:1 }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      title: { display:true, text:'Cohesion vs Coupling per Module' },
      legend: { position:'top' }
    },
    scales: { y: { beginAtZero:true, max:1, title:{ display:true, text:'Score (0-1)' } } }
  }
});
</script>
</body>
</html>
