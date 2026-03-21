<?php
// Cost dashboard is a read-only UI fed by metrics/cost_data.php
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MindSpace Cost Metrics Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    :root {
      --bg: #f4f7f9;
      --card: #ffffff;
      --ink: #1f2933;
      --muted: #607080;
      --green: #2e7d32;
      --teal: #00897b;
      --amber: #ffb300;
      --red: #d84315;
      --line: #e2e8ee;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: radial-gradient(circle at top right, #e8f5e9 0%, var(--bg) 35%, #ecf2f7 100%);
      color: var(--ink);
    }

    .wrap {
      max-width: 1240px;
      margin: 0 auto;
      padding: 1.2rem;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.8rem;
      margin-bottom: 1rem;
      flex-wrap: wrap;
    }

    .title {
      font-weight: 700;
      font-size: 1.35rem;
      margin: 0;
    }

    .muted { color: var(--muted); }

    .btn {
      background: var(--green);
      color: #fff;
      border: 0;
      border-radius: 8px;
      padding: 0.55rem 0.9rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }

    .btn.secondary { background: #546e7a; }

    .grid4 {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 1rem;
    }

    .grid2 {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1rem;
    }

    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 1rem;
      box-shadow: 0 4px 16px rgba(0,0,0,0.05);
    }

    .kpi {
      font-size: 1.6rem;
      font-weight: 700;
      margin: 0.25rem 0;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.92rem;
    }

    .table th, .table td {
      border-bottom: 1px solid var(--line);
      padding: 0.55rem 0.45rem;
      text-align: left;
    }

    .pill {
      display: inline-block;
      font-size: 0.74rem;
      background: #edf7ed;
      color: var(--green);
      padding: 0.2rem 0.5rem;
      border-radius: 999px;
      font-weight: 600;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 0.7rem;
      margin-top: 0.8rem;
    }

    .form-grid.two {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    label {
      display: block;
      font-size: 0.78rem;
      color: var(--muted);
      margin-bottom: 0.2rem;
      font-weight: 600;
    }

    input, select {
      width: 100%;
      border: 1px solid #ccd7e1;
      border-radius: 8px;
      padding: 0.5rem 0.55rem;
      font-family: inherit;
      font-size: 0.9rem;
    }

    .result {
      margin-top: 0.8rem;
      padding: 0.65rem;
      border-radius: 8px;
      background: #f1f8f6;
      border: 1px solid #d5e9e4;
      font-size: 0.9rem;
      color: #27544d;
    }

    ul.clean {
      margin: 0.4rem 0 0;
      padding-left: 1rem;
      color: #334e68;
      font-size: 0.9rem;
    }

    @media (max-width: 980px) {
      .grid4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .grid2 { grid-template-columns: 1fr; }
      .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 640px) {
      .grid4, .form-grid, .form-grid.two { grid-template-columns: 1fr; }
      .kpi { font-size: 1.35rem; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div>
        <h1 class="title"><i class="fa-solid fa-coins" style="color:var(--green);"></i> Software Cost Metrics Dashboard</h1>
        <div class="muted">Covers cost techniques, COCOMO/COCOMO II, SLIM, and model limitations from your course PDF.</div>
      </div>
      <div style="display:flex; gap:0.5rem;">
        <a class="btn secondary" href="../admin/index.html"><i class="fa-solid fa-arrow-left"></i> Admin</a>
        <button class="btn" onclick="loadData()"><i class="fa-solid fa-rotate"></i> Refresh</button>
      </div>
    </div>

    <div class="grid4">
      <div class="card">
        <div class="muted">Actual Cost</div>
        <div class="kpi" id="kActual">UGX 0</div>
      </div>
      <div class="card">
        <div class="muted">Estimation Variance</div>
        <div class="kpi" id="kVariance">0.0%</div>
      </div>
      <div class="card">
        <div class="muted">Rework Rate</div>
        <div class="kpi" id="kRework">0.0%</div>
      </div>
      <div class="card">
        <div class="muted">Cost per FP</div>
        <div class="kpi" id="kCostPerFp">N/A</div>
      </div>
    </div>

    <div class="grid2" style="margin-top:1rem;">
      <div class="card">
        <h3 style="margin:0 0 0.6rem;">Cost Trend</h3>
        <canvas id="trendChart" style="max-height:250px;"></canvas>
        <div id="trendEmpty" class="muted" style="display:none;">No cost entries yet. Run migration_cost_metrics.sql first.</div>
      </div>
      <div class="card">
        <h3 style="margin:0 0 0.6rem;">Feature Cost Breakdown</h3>
        <div style="overflow:auto; max-height:280px;">
          <table class="table">
            <thead><tr><th>Feature</th><th>Sprint</th><th>Cost</th><th>Variance</th></tr></thead>
            <tbody id="featureBody"><tr><td colspan="4" class="muted">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="grid2" style="margin-top:1rem;">
      <div class="card">
        <h3 style="margin:0;">COCOMO Calculator</h3>
        <span class="pill">Basic + Intermediate</span>
        <div class="form-grid">
          <div>
            <label>Model</label>
            <select id="cModel">
              <option value="basic">Basic</option>
              <option value="intermediate">Intermediate</option>
            </select>
          </div>
          <div>
            <label>Mode</label>
            <select id="cMode">
              <option value="organic">Organic</option>
              <option value="semi_detached">Semi-detached</option>
              <option value="embedded">Embedded</option>
            </select>
          </div>
          <div>
            <label>KLOC</label>
            <input id="cKloc" type="number" min="0" step="0.1" value="32" />
          </div>
          <div>
            <label>EAF (Intermediate only)</label>
            <input id="cEaf" type="number" min="0.1" step="0.01" value="1.00" />
          </div>
        </div>
        <div style="margin-top:0.7rem;">
          <button class="btn" onclick="computeCocomo()">Compute COCOMO</button>
        </div>
        <div class="result" id="cocomoResult">Enter values and click compute.</div>
      </div>

      <div class="card">
        <h3 style="margin:0;">COCOMO II Calculator</h3>
        <span class="pill">Application + Early + Post</span>
        <div class="form-grid two">
          <div>
            <label>Model</label>
            <select id="c2Model">
              <option value="acm">Application Composition</option>
              <option value="early">Early Design</option>
              <option value="post">Post-Architecture</option>
            </select>
          </div>
          <div>
            <label>Size Input</label>
            <input id="c2Size" type="number" min="0" step="0.1" value="36" />
          </div>
          <div>
            <label>Productivity / EAF</label>
            <input id="c2Adj" type="number" min="0.1" step="0.01" value="13" />
          </div>
          <div>
            <label>Scale Factor Sum (Post only)</label>
            <input id="c2Sf" type="number" min="0" step="0.01" value="16" />
          </div>
        </div>
        <div style="margin-top:0.7rem;">
          <button class="btn" onclick="computeCocomo2()">Compute COCOMO II</button>
        </div>
        <div class="result" id="cocomo2Result">Use OP/PROD for ACM, KLOC/EAF for Early and Post-Architecture.</div>
      </div>
    </div>

    <div class="grid2" style="margin-top:1rem;">
      <div class="card">
        <h3 style="margin:0;">SLIM Constraint Calculator</h3>
        <span class="pill">Putnam-style tradeoff</span>
        <div class="form-grid two">
          <div>
            <label>Size S (LOC)</label>
            <input id="sLoc" type="number" min="1000" step="1000" value="200000" />
          </div>
          <div>
            <label>Productivity C</label>
            <input id="sC" type="number" min="100" step="10" value="4000" />
          </div>
          <div>
            <label>Manpower Acceleration D</label>
            <select id="sD">
              <option value="12.3">12.3 (new software with interfaces)</option>
              <option value="15">15 (standalone)</option>
              <option value="27">27 (reimplementation)</option>
            </select>
          </div>
          <div>
            <label>Delivery Time T (years)</label>
            <input id="sT" type="number" min="0.5" step="0.1" value="2.0" />
          </div>
        </div>
        <div style="margin-top:0.7rem;">
          <button class="btn" onclick="computeSlim()">Compute SLIM</button>
        </div>
        <div class="result" id="slimResult">Uses: B = (S/C)^3 / T^4, E = 0.3945 * B, D = B / T^3.</div>
      </div>

      <div class="card">
        <h3 style="margin:0 0 0.5rem;">Techniques and Critiques</h3>
        <div id="techniquesList" class="muted">Loading techniques...</div>
        <ul class="clean">
          <li>Structure risk: size-effort-time relationships vary by environment.</li>
          <li>Size risk: early LOC/FP/OP estimates are subjective and can drift.</li>
          <li>Complexity risk: driver ratings can be subjective and interdependent.</li>
          <li>Validation benchmark: aim for 75% of estimates within 25% of actuals.</li>
        </ul>
      </div>
    </div>
  </div>

  <script>
    let trendChart = null;
    let cocomoConstants = null;

    function fmtUGX(v) {
      return `UGX ${Number(v || 0).toLocaleString('en-UG', { maximumFractionDigits: 2 })}`;
    }

    async function loadData() {
      const res = await fetch('cost_data.php');
      const data = await res.json();
      if (!data.success) {
        alert('Failed to load cost metrics data.');
        return;
      }

      cocomoConstants = data.cocomo;

      const o = data.overview || {};
      document.getElementById('kActual').textContent = fmtUGX(o.total_actual_cost);
      document.getElementById('kVariance').textContent = `${Number(o.cost_variance_pct || 0).toFixed(1)}%`;
      document.getElementById('kRework').textContent = `${Number(o.rework_pct || 0).toFixed(1)}%`;
      document.getElementById('kCostPerFp').textContent = o.cost_per_fp == null ? 'N/A' : fmtUGX(o.cost_per_fp);

      renderFeatureTable(data.featureBreakdown || []);
      renderTrend(data.sprintCostTrend || []);
      renderTechniques(data.techniques || []);
    }

    function renderFeatureTable(rows) {
      const body = document.getElementById('featureBody');
      if (!rows.length) {
        body.innerHTML = '<tr><td colspan="4" class="muted">No cost entries found.</td></tr>';
        return;
      }
      body.innerHTML = rows.map(r => {
        const variance = Number(r.effort_variance_pct || 0);
        const sign = variance > 0 ? '+' : '';
        const color = variance > 0 ? 'var(--red)' : 'var(--green)';
        return `<tr>
          <td><strong>${r.feature_name}</strong></td>
          <td>${r.sprint_label}</td>
          <td>${fmtUGX(r.actual_cost)}</td>
          <td style="color:${color}; font-weight:600;">${sign}${variance.toFixed(1)}%</td>
        </tr>`;
      }).join('');
    }

    function renderTrend(rows) {
      const chart = document.getElementById('trendChart');
      const empty = document.getElementById('trendEmpty');
      if (!rows.length) {
        chart.style.display = 'none';
        empty.style.display = 'block';
        return;
      }
      chart.style.display = 'block';
      empty.style.display = 'none';

      const labels = rows.map(r => new Date(`${r.day}T00:00:00`).toLocaleDateString('en-UG', { month: 'short', day: 'numeric' }));
      const values = rows.map(r => Number(r.cost));

      if (trendChart) trendChart.destroy();
      trendChart = new Chart(chart, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Actual Cost',
            data: values,
            tension: 0.35,
            fill: true,
            borderColor: '#00897b',
            backgroundColor: 'rgba(0,137,123,0.12)',
            pointRadius: 3,
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (val) => `UGX ${Number(val).toLocaleString('en-UG')}`
              }
            }
          }
        }
      });
    }

    function renderTechniques(items) {
      const box = document.getElementById('techniquesList');
      if (!items.length) {
        box.textContent = 'No techniques data available.';
        return;
      }
      box.innerHTML = items.map(t =>
        `<div style="padding:0.45rem 0; border-bottom:1px dashed #dce6ee;">
          <strong>${t.name}</strong> <span class="pill" style="margin-left:0.35rem;">${t.category}</span>
          <div style="font-size:0.86rem; color:#40596f; margin-top:0.15rem;">${t.summary}</div>
        </div>`
      ).join('');
    }

    function computeCocomo() {
      if (!cocomoConstants) return;
      const model = document.getElementById('cModel').value;
      const mode = document.getElementById('cMode').value;
      const kloc = Number(document.getElementById('cKloc').value || 0);
      const eaf = Number(document.getElementById('cEaf').value || 1);
      let effort = 0;
      let schedule = null;

      if (model === 'basic') {
        const m = cocomoConstants.basic_modes[mode];
        effort = m.a * Math.pow(kloc, m.b);
      } else {
        const m = cocomoConstants.intermediate_modes[mode];
        effort = m.a * Math.pow(kloc, m.b) * eaf;
        schedule = m.c * Math.pow(effort, m.d);
      }

      const result = document.getElementById('cocomoResult');
      result.innerHTML = model === 'basic'
        ? `Estimated effort: <strong>${effort.toFixed(2)} PM</strong>. Formula: E = a * KLOC^b`
        : `Estimated effort: <strong>${effort.toFixed(2)} PM</strong>, estimated schedule: <strong>${schedule.toFixed(2)} months</strong>. Formula: E = a * KLOC^b * EAF, Tdev = c * E^d`;
    }

    function computeCocomo2() {
      const model = document.getElementById('c2Model').value;
      const size = Number(document.getElementById('c2Size').value || 0);
      const adj = Number(document.getElementById('c2Adj').value || 1);
      const sf = Number(document.getElementById('c2Sf').value || 0);
      const out = document.getElementById('cocomo2Result');

      if (model === 'acm') {
        const effort = size / Math.max(adj, 0.1);
        out.innerHTML = `Application Composition effort: <strong>${effort.toFixed(2)} PM</strong>. Formula: E = OP / PROD`;
        return;
      }

      if (model === 'early') {
        const effort = 2.45 * size * adj;
        out.innerHTML = `Early Design effort: <strong>${effort.toFixed(2)} PM</strong>. Formula: E = 2.45 * KLOC * EAF`;
        return;
      }

      const b = 0.91 + 0.01 * sf;
      const effort = 2.45 * Math.pow(size, b) * adj;
      out.innerHTML = `Post-Architecture effort: <strong>${effort.toFixed(2)} PM</strong> (b=${b.toFixed(3)}). Formula: E = 2.45 * KLOC^b * EAF`;
    }

    function computeSlim() {
      const s = Number(document.getElementById('sLoc').value || 0);
      const c = Number(document.getElementById('sC').value || 1);
      const d = Number(document.getElementById('sD').value || 15);
      const t = Number(document.getElementById('sT').value || 1);

      const b = Math.pow(s / c, 3) / Math.pow(t, 4);
      const e = 0.3945 * b;
      const dObserved = b / Math.pow(t, 3);

      document.getElementById('slimResult').innerHTML =
        `Effort B: <strong>${b.toFixed(2)} staff-years</strong>, equivalent E: <strong>${e.toFixed(2)} staff-years</strong>, observed D: <strong>${dObserved.toFixed(2)}</strong>. Selected baseline D: <strong>${d}</strong>.`;
    }

    loadData();
  </script>
</body>
</html>
