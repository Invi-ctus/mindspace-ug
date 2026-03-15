<?php
require_once '../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MindSpace — Software Metrics Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
    .info-box { background:#E8F5E9; border-left:4px solid #4CAF50; padding:14px 18px;
                border-radius:8px; margin-bottom:24px; font-size:.92rem; line-height:1.7; }

    /* METRIC CARDS GRID */
    .metric-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; margin-bottom:30px; }
    .metric-card { background:#fff; border-radius:14px; padding:24px; text-decoration:none; color:#2d3436;
                   box-shadow:0 2px 8px rgba(0,0,0,.1); border-top:4px solid #4CAF50;
                   transition:transform .2s, box-shadow .2s; display:flex; flex-direction:column; }
    .metric-card:hover { transform:translateY(-4px); box-shadow:0 6px 20px rgba(0,0,0,.15); }
    .metric-card .icon { font-size:2.2rem; margin-bottom:10px; }
    .metric-card .chapter { font-size:.75rem; color:#636e72; text-transform:uppercase;
                            letter-spacing:.06em; font-weight:600; margin-bottom:4px; }
    .metric-card h3 { font-size:1.05rem; font-weight:700; color:#2d3436; margin-bottom:6px; }
    .metric-card p { font-size:.85rem; color:#636e72; line-height:1.5; flex-grow:1; }
    .metric-card .cta { display:inline-block; margin-top:12px; font-size:.85rem; font-weight:600;
                        color:#4CAF50; }
    .metric-card .cta::after { content:' →'; }

    /* DISABLED CARD */
    .metric-card.disabled { opacity:.5; border-top-color:#ccc; cursor:default; }
    .metric-card.disabled:hover { transform:none; box-shadow:0 2px 8px rgba(0,0,0,.1); }
    .metric-card.disabled .cta { color:#999; }

    footer { text-align:center; color:#636e72; font-size:.8rem; padding:20px; }
  </style>
</head>
<body>

<div class="topnav">
  <h1>🌿 MindSpace — Software Metrics Dashboard</h1>
  <a href="../admin/index.html">← Back to Admin Panel</a>
</div>

<div class="container">

  <!-- INTRO -->
  <div class="info-box">
    <strong>📊 Goal-Based Software Measurement Framework (GBM)</strong><br>
    MindSpace is measured using the Goal-Based Measurement Framework.
    Every metric traces back to a business goal.<br>
    <strong>Primary goal:</strong> Improve mental health support for MUST students.<br>
    <strong>Course:</strong> SWE 2204 Software Metrics — MUST BSE 2024
  </div>

  <!-- METRIC CATEGORY CARDS -->
  <h2>📋 Metric Categories</h2>
  <div class="metric-cards">

    <!-- Chapter 3: GQM -->
    <a href="../software_metrics/goal_based_software_measurement_framework.md" class="metric-card disabled">
      <div class="icon">📊</div>
      <div class="chapter">Chapter 3</div>
      <h3>GQM Goals</h3>
      <p>Goal-Question-Metric paradigm applied to MindSpace. Defines what we measure and why.</p>
      <span class="cta">View Documentation</span>
    </a>

    <!-- Chapter 4: Empirical -->
    <a href="../software_metrics/empirical_investigation.md" class="metric-card disabled">
      <div class="icon">🔬</div>
      <div class="chapter">Chapter 4</div>
      <h3>Empirical Study</h3>
      <p>A/B testing framework, telemetry pipeline, and experiment design for evidence-based decisions.</p>
      <span class="cta">View Documentation</span>
    </a>

    <!-- Chapter 5: Software Size -->
    <a href="size_dashboard.php" class="metric-card">
      <div class="icon">📏</div>
      <div class="chapter">Chapter 5</div>
      <h3>Software Size</h3>
      <p>Lines of Code (LOC), Function Points (FP), and code comment density across all MindSpace modules.</p>
      <span class="cta">View Dashboard</span>
    </a>

    <!-- Chapter 5: Halstead -->
    <a href="halstead_metrics.php" class="metric-card">
      <div class="icon">🧮</div>
      <div class="chapter">Chapter 5</div>
      <h3>Halstead Metrics</h3>
      <p>Operator/operand analysis, program volume, and estimated bugs using Halstead's Software Science.</p>
      <span class="cta">View Dashboard</span>
    </a>

    <!-- Chapter 6: Structural Complexity -->
    <a href="complexity_dashboard.php" class="metric-card">
      <div class="icon">🔀</div>
      <div class="chapter">Chapter 6</div>
      <h3>Structural Complexity</h3>
      <p>Cyclomatic complexity v(G), module cohesion, coupling, and information flow complexity (IFC).</p>
      <span class="cta">View Dashboard</span>
    </a>

  </div>

  <!-- QUICK STATS -->
  <h2>⚡ Quick Stats</h2>
  <?php
  try {
    $loc_stmt = $pdo->query("SELECT SUM(total_loc) AS total_loc, SUM(ncloc) AS total_ncloc, ROUND(AVG(comment_density),1) AS avg_cd FROM loc_measurements WHERE measured_date = (SELECT MAX(measured_date) FROM loc_measurements)");
    $loc = $loc_stmt->fetch(PDO::FETCH_ASSOC);

    $fp_stmt = $pdo->query("SELECT SUM(fp_points) AS fp FROM fp_measurements WHERE measured_date = (SELECT MAX(measured_date) FROM fp_measurements)");
    $fp = $fp_stmt->fetch(PDO::FETCH_ASSOC);

    $cc_stmt = $pdo->query("SELECT MAX(cyclomatic_complexity) AS max_cc FROM cyclomatic_measurements WHERE measured_date = (SELECT MAX(measured_date) FROM cyclomatic_measurements)");
    $cc = $cc_stmt->fetch(PDO::FETCH_ASSOC);

    $mod_stmt = $pdo->query("SELECT ROUND(AVG(cohesion_score),2) AS ch, ROUND(AVG(coupling_score),2) AS cp FROM module_complexity WHERE measured_date = (SELECT MAX(measured_date) FROM module_complexity)");
    $mod = $mod_stmt->fetch(PDO::FETCH_ASSOC);
  ?>
  <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-bottom:30px;">
    <div style="background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,.1); text-align:center; border-top:4px solid #4CAF50;">
      <div style="font-size:1.8rem; font-weight:700; color:#4CAF50;"><?= number_format($loc['total_loc']) ?></div>
      <div style="font-size:.82rem; color:#636e72;">Total LOC</div>
    </div>
    <div style="background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,.1); text-align:center; border-top:4px solid #4CAF50;">
      <div style="font-size:1.8rem; font-weight:700; color:#4CAF50;"><?= $fp['fp'] ?> FP</div>
      <div style="font-size:.82rem; color:#636e72;">Function Points</div>
    </div>
    <div style="background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,.1); text-align:center; border-top:4px solid #4CAF50;">
      <div style="font-size:1.8rem; font-weight:700; color:<?= $loc['avg_cd'] >= 20 ? '#4CAF50' : '#E65100' ?>;"><?= $loc['avg_cd'] ?>%</div>
      <div style="font-size:.82rem; color:#636e72;">Comment Density</div>
    </div>
    <div style="background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,.1); text-align:center; border-top:4px solid #4CAF50;">
      <div style="font-size:1.8rem; font-weight:700; color:<?= $cc['max_cc'] <= 10 ? '#4CAF50' : '#E65100' ?>;"><?= $cc['max_cc'] ?></div>
      <div style="font-size:.82rem; color:#636e72;">Max v(G)</div>
    </div>
    <div style="background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,.1); text-align:center; border-top:4px solid #4CAF50;">
      <div style="font-size:1.8rem; font-weight:700; color:<?= $mod['ch'] >= 0.60 ? '#4CAF50' : '#E65100' ?>;"><?= $mod['ch'] ?></div>
      <div style="font-size:.82rem; color:#636e72;">System Cohesion</div>
    </div>
    <div style="background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,.1); text-align:center; border-top:4px solid #4CAF50;">
      <div style="font-size:1.8rem; font-weight:700; color:<?= $mod['cp'] <= 0.40 ? '#4CAF50' : '#E65100' ?>;"><?= $mod['cp'] ?></div>
      <div style="font-size:.82rem; color:#636e72;">System Coupling</div>
    </div>
  </div>
  <?php } catch (PDOException $e) { ?>
  <div class="info-box" style="background:#FFF3E0; border-color:#FF9800;">
    <strong>⚠️ Database tables not found.</strong> Run <code>database/week7_8_metrics.sql</code> in phpMyAdmin first.
  </div>
  <?php } ?>

</div><!-- /container -->

<footer>SWE 2204 Software Metrics — MUST BSE 2024 | Chapter 5 & 6</footer>

</body>
</html>
