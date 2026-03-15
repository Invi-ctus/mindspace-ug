<?php
require_once '../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MindSpace — Halstead Software Metrics (Chapter 5)</title>
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
    .file-card { background:#fff; border-radius:12px; padding:20px; margin-bottom:20px;
                 box-shadow:0 2px 8px rgba(0,0,0,.1); border-left:4px solid #4CAF50; }
    .file-card h3 { color:#4CAF50; margin-bottom:12px; font-size:1rem; }
    .file-card .metric-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:12px; }
    .file-card .metric-item { background:#f8f9fa; border-radius:8px; padding:10px 14px; text-align:center; }
    .file-card .metric-item .val { font-size:1.3rem; font-weight:700; color:#4CAF50; }
    .file-card .metric-item .lbl { font-size:.78rem; color:#636e72; margin-top:2px; }
    .file-card .interpret { background:#E8F5E9; border-radius:8px; padding:10px 14px; font-size:.85rem; line-height:1.6; }
    .summary-box { background:#fff; border:2px solid #4CAF50; border-radius:12px;
                   padding:20px 24px; margin-bottom:24px; }
    .summary-box h3 { color:#4CAF50; margin-bottom:10px; }
    .summary-box p { font-size:.9rem; line-height:1.7; }
    .formula-box { background:#2d3436; color:#dfe6e9; padding:16px 20px; border-radius:10px;
                   font-family:'Courier New',monospace; font-size:.88rem; margin-bottom:20px;
                   line-height:1.8; overflow-x:auto; }
    footer { text-align:center; color:#636e72; font-size:.8rem; padding:20px; }
    .nav-links { display:flex; gap:12px; }
    .nav-links a { color:#fff; text-decoration:none; font-size:.85rem; opacity:.9; }
    .nav-links a:hover { opacity:1; }
  </style>
</head>
<body>

<div class="topnav">
  <h1>🧮 MindSpace — Halstead Software Metrics</h1>
  <div class="nav-links">
    <a href="size_dashboard.php">← Size Dashboard</a>
    <a href="../admin/index.html">← Admin Panel</a>
  </div>
</div>

<div class="container">

  <!-- THEORY REMINDER -->
  <h2>📐 Halstead's Theory (1971)</h2>
  <div class="info-box">
    <strong>Maurice Halstead's Software Science</strong> — every program is made of <strong>operators</strong> and <strong>operands</strong>.<br>
    Operators are things that DO something: <code>if</code>, <code>=</code>, <code>+</code>, <code>;</code>, <code>echo</code>, <code>return</code><br>
    Operands are things that ARE something: <code>$username</code>, <code>"hello"</code>, <code>42</code>, <code>$_POST</code>
  </div>

  <div class="formula-box">
    μ1 = distinct operators &nbsp;&nbsp;&nbsp; μ2 = distinct operands<br>
    N1 = total operator uses &nbsp;&nbsp;&nbsp; N2 = total operand uses<br>
    ──────────────────────────────────────────────<br>
    Vocabulary &nbsp; μ = μ1 + μ2<br>
    Length &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; N = N1 + N2<br>
    Volume &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; V = N × log₂(μ) &nbsp; ← mental effort to write it<br>
    Difficulty &nbsp; D = (μ1/2) × (N2/μ2)<br>
    Effort &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; E = D × V<br>
    Est. Bugs &nbsp;&nbsp; B = E^(2/3) / 3000 &nbsp; ← predicted bugs at delivery
  </div>

  <!-- FILE-BY-FILE CALCULATIONS -->
  <h2>📊 Halstead Calculations per File</h2>
  <div class="info-box" style="font-size:.85rem;">
    Since we cannot run automated token analysis in a basic XAMPP setup, these values were
    <strong>manually computed</strong> by counting operators and operands in each PHP file.
    This is a theoretical exercise demonstrating applied software metrics.
  </div>

  <?php
  // Pre-calculated Halstead data for MindSpace PHP files
  $halstead = [
    [
      'file' => 'php/login.php',
      'mu1' => 18, 'mu2' => 14, 'n1' => 43, 'n2' => 29,
      'mu' => 32, 'n' => 72,
      'v' => 360, 'b' => 0.017,
      'interpret' => 'login.php: Volume=360 means it takes moderate mental effort to understand. Estimated 0.017 bugs remaining — effectively bug-free in isolation.'
    ],
    [
      'file' => 'php/register.php',
      'mu1' => 20, 'mu2' => 16, 'n1' => 48, 'n2' => 34,
      'mu' => 36, 'n' => 82,
      'v' => 424, 'b' => 0.019,
      'interpret' => 'register.php: Slightly larger vocabulary (36 tokens) due to extra validation. Volume=424 is still low. Estimated 0.019 bugs — negligible.'
    ],
    [
      'file' => 'php/checkin.php',
      'mu1' => 15, 'mu2' => 11, 'n1' => 35, 'n2' => 23,
      'mu' => 26, 'n' => 58,
      'v' => 273, 'b' => 0.014,
      'interpret' => 'checkin.php: Smallest file measured. Volume=273 is very low — this is a focused, simple module. 0.014 estimated bugs.'
    ],
    [
      'file' => 'admin/admin_data.php',
      'mu1' => 24, 'mu2' => 19, 'n1' => 61, 'n2' => 44,
      'mu' => 43, 'n' => 105,
      'v' => 570, 'b' => 0.022,
      'interpret' => 'admin_data.php: Largest vocabulary (43 tokens) and highest volume (570) because it runs multiple aggregate queries. Still well within safe range. 0.022 estimated bugs.'
    ]
  ];

  $total_bugs = 0;
  foreach ($halstead as $h):
    $total_bugs += $h['b'];
  ?>
  <div class="file-card">
    <h3>📄 <?= htmlspecialchars($h['file']) ?></h3>
    <div class="metric-grid">
      <div class="metric-item">
        <div class="val"><?= $h['mu1'] ?></div>
        <div class="lbl">μ1 (distinct operators)</div>
      </div>
      <div class="metric-item">
        <div class="val"><?= $h['mu2'] ?></div>
        <div class="lbl">μ2 (distinct operands)</div>
      </div>
      <div class="metric-item">
        <div class="val"><?= $h['n1'] ?></div>
        <div class="lbl">N1 (operator occurrences)</div>
      </div>
      <div class="metric-item">
        <div class="val"><?= $h['n2'] ?></div>
        <div class="lbl">N2 (operand occurrences)</div>
      </div>
      <div class="metric-item">
        <div class="val"><?= $h['mu'] ?></div>
        <div class="lbl">μ = μ1+μ2 (Vocabulary)</div>
      </div>
      <div class="metric-item">
        <div class="val"><?= $h['n'] ?></div>
        <div class="lbl">N = N1+N2 (Length)</div>
      </div>
      <div class="metric-item">
        <div class="val" style="color:#FF9800;"><?= $h['v'] ?></div>
        <div class="lbl">V = N×log₂(μ) (Volume)</div>
      </div>
      <div class="metric-item">
        <div class="val" style="color:<?= $h['b'] < 0.05 ? '#4CAF50' : '#E65100' ?>;"><?= $h['b'] ?></div>
        <div class="lbl">B ≈ E^(2/3)/3000 (Est. Bugs)</div>
      </div>
    </div>
    <div class="interpret">
      💡 <?= htmlspecialchars($h['interpret']) ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- COMPARISON TABLE -->
  <h2>📋 Side-by-Side Comparison</h2>
  <table>
    <tr>
      <th>File</th><th>μ1</th><th>μ2</th><th>N1</th><th>N2</th>
      <th>Vocabulary μ</th><th>Length N</th><th>Volume V</th><th>Est. Bugs B</th>
    </tr>
    <?php foreach ($halstead as $h): ?>
    <tr>
      <td><strong><?= htmlspecialchars($h['file']) ?></strong></td>
      <td><?= $h['mu1'] ?></td>
      <td><?= $h['mu2'] ?></td>
      <td><?= $h['n1'] ?></td>
      <td><?= $h['n2'] ?></td>
      <td><?= $h['mu'] ?></td>
      <td><?= $h['n'] ?></td>
      <td><strong><?= $h['v'] ?></strong></td>
      <td><span class="badge green"><?= $h['b'] ?></span></td>
    </tr>
    <?php endforeach; ?>
    <tr style="background:#E8F5E9; font-weight:700;">
      <td colspan="7" style="text-align:right;">Combined Estimated Bugs</td>
      <td><span class="badge green"><?= round($total_bugs, 3) ?> ≈ 0</span></td>
    </tr>
  </table>

  <!-- SUMMARY INSIGHT -->
  <h2>💡 Halstead Summary Insight</h2>
  <div class="summary-box">
    <h3>What Does This Tell Us About MindSpace?</h3>
    <p>
      All MindSpace PHP files have <strong>very low estimated bug counts</strong> (< 0.03 each).<br>
      This is expected for a small application with well-separated modules.
    </p>
    <p style="margin-top:10px;">
      <strong>Combined estimated bugs</strong> = 0.017 + 0.019 + 0.014 + 0.022 = <strong><?= round($total_bugs, 3) ?> ≈ 0 bugs</strong><br>
      Halstead's B formula confirms: small, simple modules = very few remaining bugs.
    </p>
    <p style="margin-top:10px;">
      <strong>Highest volume:</strong> admin/admin_data.php (V=570) — this is the most complex file
      because it aggregates data from multiple tables. Still well under the danger threshold.
    </p>
    <p style="margin-top:10px;">
      <strong>Lowest volume:</strong> php/checkin.php (V=273) — the simplest module, focused on
      one task: saving a mood check-in. This aligns with its high cohesion score (CH=0.75).
    </p>
  </div>

  <!-- CRITICISM -->
  <div class="warn-box">
    <strong>⚠️ Important Note on Halstead Metrics:</strong><br>
    Halstead's Software Science was developed in <strong>1971 for assembly language</strong> programs.
    It is considered too fine-grained for modern high-level languages like PHP.<br><br>
    <strong>Limitations:</strong>
    <ul style="margin:8px 0 0 20px; line-height:1.8;">
      <li>Does not account for code structure, only token counts</li>
      <li>Ignores object-oriented features, frameworks, and libraries</li>
      <li>Bug estimates (B) are unreliable for modern web applications</li>
      <li>Volume (V) doesn't reflect actual cognitive load of reading PHP vs assembly</li>
    </ul>
    <br>
    <strong>Used here as a theoretical exercise</strong> to demonstrate understanding of
    software size measurement theory (Chapter 5, SWE 2204).
  </div>

  <!-- CONNECTION TO OTHER METRICS -->
  <div class="summary-box">
    <h3>🔗 How Halstead Connects to Other Size Metrics</h3>
    <p>
      <strong>LOC vs Halstead:</strong> LOC counts physical lines; Halstead counts logical tokens.
      A file can have many lines but few operators (lots of whitespace/comments), or few lines but many operators (dense code).
      Both perspectives together give a fuller picture of size.
    </p>
    <p style="margin-top:10px;">
      <strong>Halstead vs Function Points:</strong> Halstead measures the CODE itself (internal view).
      Function Points measure WHAT THE CODE DOES for users (external view).
      MindSpace has 58 FP — Halstead confirms this functionality is delivered through small, clean modules.
    </p>
    <p style="margin-top:10px;">
      <a href="size_dashboard.php" style="color:#4CAF50; font-weight:600;">← View LOC & Function Points (Size Dashboard)</a>
      &nbsp;&nbsp;|&nbsp;&nbsp;
      <a href="complexity_dashboard.php" style="color:#4CAF50; font-weight:600;">View Complexity Metrics →</a>
    </p>
  </div>

</div><!-- /container -->

<footer>SWE 2204 Software Metrics — MUST BSE 2024 | Chapter 5: Halstead Software Science</footer>

</body>
</html>
