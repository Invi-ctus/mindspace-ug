<?php
/**
 * MindSpace — Auto Installer
 * =================================
 * This script creates the database, all tables, and sample data
 * automatically. No phpMyAdmin needed.
 *
 * HOW TO USE:
 *   1. Place this file at: mindspace/install.php
 *   2. Open browser:       http://localhost/mindspace/install.php
 *   3. Click "Run Installer"
 *   4. Delete this file after installation is complete.
 *
 * ⚠️  DELETE THIS FILE after setup — it should never be
 *     accessible on a production server.
 */

// ── Configuration — edit these if your XAMPP/WAMP differs ─────
define('DB_HOST',      'localhost');
define('DB_USER',      'root');
define('DB_PASS',      '');           // Default XAMPP: empty
define('DB_NAME',      'mindspace');
define('DB_PORT',      3307);

// ── Run installer only when the form is submitted ──────────────
$ran     = false;
$steps   = [];
$success = false;

if (isset($_POST['install'])) {
    $ran    = true;
    $errors = false;

    // ─────────────────────────────────────────────────────────
    // STEP 1: Connect to MySQL (no DB selected yet)
    // ─────────────────────────────────────────────────────────
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $steps[] = ['ok', 'Connected to MySQL server at <strong>' . DB_HOST . '</strong>.'];
    } catch (PDOException $e) {
        $steps[] = ['err', 'Could not connect to MySQL: <strong>' . htmlspecialchars($e->getMessage()) . '</strong><br>
            Check that MySQL is running and your credentials in <code>install.php</code> are correct.'];
        $errors = true;
    }

    // ─────────────────────────────────────────────────────────
    // STEP 2: Create database
    // ─────────────────────────────────────────────────────────
    if (!$errors) {
        try {
            $pdo->exec(
                'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '`
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            $pdo->exec('USE `' . DB_NAME . '`');
            $steps[] = ['ok', 'Database <strong>' . DB_NAME . '</strong> created (or already exists).'];
        } catch (PDOException $e) {
            $steps[] = ['err', 'Failed to create database: ' . htmlspecialchars($e->getMessage())];
            $errors = true;
        }
    }

    // ─────────────────────────────────────────────────────────
    // STEP 3: Create table — users
    // ─────────────────────────────────────────────────────────
    if (!$errors) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    username    VARCHAR(50)  NOT NULL UNIQUE,
                    email       VARCHAR(100) NOT NULL UNIQUE,
                    password    VARCHAR(255) NOT NULL,
                    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email    (email),
                    INDEX idx_username (username)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $steps[] = ['ok', 'Table <strong>users</strong> created.'];
        } catch (PDOException $e) {
            $steps[] = ['err', 'Failed to create <strong>users</strong> table: ' . htmlspecialchars($e->getMessage())];
            $errors = true;
        }
    }

    // ─────────────────────────────────────────────────────────
    // STEP 4: Create table — moods
    // ─────────────────────────────────────────────────────────
    if (!$errors) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS moods (
                    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id     INT UNSIGNED NOT NULL,
                    mood        ENUM('happy','neutral','sad','frustrated','anxious') NOT NULL,
                    note        TEXT         NULL,
                    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_date (user_id, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $steps[] = ['ok', 'Table <strong>moods</strong> created.'];
        } catch (PDOException $e) {
            $steps[] = ['err', 'Failed to create <strong>moods</strong> table: ' . htmlspecialchars($e->getMessage())];
            $errors = true;
        }
    }

    // ─────────────────────────────────────────────────────────
    // STEP 5: Create table — community_posts
    // ─────────────────────────────────────────────────────────
    if (!$errors) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS community_posts (
                    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id     INT UNSIGNED NOT NULL,
                    message     TEXT         NOT NULL,
                    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $steps[] = ['ok', 'Table <strong>community_posts</strong> created.'];
        } catch (PDOException $e) {
            $steps[] = ['err', 'Failed to create <strong>community_posts</strong> table: ' . htmlspecialchars($e->getMessage())];
            $errors = true;
        }
    }

    // ─────────────────────────────────────────────────────────
    // STEP 6: Insert demo users (skip if already exist)
    // ─────────────────────────────────────────────────────────
    if (!$errors) {
        try {
            // Check if demo user exists
            $check = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'demo@mindspaceug.com'");
            if ((int) $check->fetchColumn() === 0) {
                // Hash for "password123" — generated fresh with bcrypt cost 12
                $hash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
                );
                $stmt->execute(['demo_user',  'demo@mindspaceug.com',  $hash]);
                $demoId = (int) $pdo->lastInsertId();
                $stmt->execute(['test_youth', 'youth@mindspaceug.com', $hash]);
                $youthId = (int) $pdo->lastInsertId();
                $steps[] = ['ok', 'Demo users inserted. Login: <code>demo@mindspaceug.com</code> / <code>password123</code>'];
            } else {
                // Get existing IDs for sample mood data below
                $r       = $pdo->query("SELECT id FROM users WHERE email = 'demo@mindspaceug.com' LIMIT 1")->fetch();
                $demoId  = (int) $r['id'];
                $r2      = $pdo->query("SELECT id FROM users WHERE email = 'youth@mindspaceug.com' LIMIT 1")->fetch();
                $youthId = $r2 ? (int) $r2['id'] : $demoId;
                $steps[] = ['info', 'Demo users already exist — skipped.'];
            }
        } catch (PDOException $e) {
            $steps[] = ['err', 'Failed to insert demo users: ' . htmlspecialchars($e->getMessage())];
            $errors = true;
        }
    }

    // ─────────────────────────────────────────────────────────
    // STEP 7: Insert sample moods (only if table is empty)
    // ─────────────────────────────────────────────────────────
    if (!$errors) {
        try {
            $existing = (int) $pdo->query("SELECT COUNT(*) FROM moods")->fetchColumn();
            if ($existing === 0) {
                $stmt = $pdo->prepare(
                    "INSERT INTO moods (user_id, mood, note, created_at) VALUES (?, ?, ?, ?)"
                );
                $sampleMoods = [
                    [$demoId,  'happy',      'Had a great day today!',                  date('Y-m-d H:i:s', strtotime('-6 days'))],
                    [$demoId,  'neutral',    'Just an ordinary day.',                   date('Y-m-d H:i:s', strtotime('-5 days'))],
                    [$demoId,  'anxious',    'Exam stress is getting to me.',            date('Y-m-d H:i:s', strtotime('-4 days'))],
                    [$demoId,  'sad',        'Feeling lonely today.',                   date('Y-m-d H:i:s', strtotime('-3 days'))],
                    [$demoId,  'neutral',    'Better than yesterday.',                  date('Y-m-d H:i:s', strtotime('-2 days'))],
                    [$demoId,  'happy',      'Talked to a friend, feels better!',       date('Y-m-d H:i:s', strtotime('-1 day'))],
                    [$demoId,  'happy',      'Good morning vibes.',                     date('Y-m-d H:i:s')],
                    [$youthId, 'frustrated', 'Work was really tough today.',            date('Y-m-d H:i:s', strtotime('-3 days'))],
                    [$youthId, 'neutral',    '',                                        date('Y-m-d H:i:s', strtotime('-2 days'))],
                    [$youthId, 'happy',      'Feeling grateful.',                       date('Y-m-d H:i:s', strtotime('-1 day'))],
                ];
                foreach ($sampleMoods as $row) {
                    $stmt->execute($row);
                }
                $steps[] = ['ok', '10 sample mood entries inserted.'];
            } else {
                $steps[] = ['info', 'Mood table already has data — skipped sample moods.'];
            }
        } catch (PDOException $e) {
            $steps[] = ['err', 'Failed to insert sample moods: ' . htmlspecialchars($e->getMessage())];
            $errors = true;
        }
    }

    // ─────────────────────────────────────────────────────────
    // STEP 8: Insert sample community posts (only if table empty)
    // ─────────────────────────────────────────────────────────
    if (!$errors) {
        try {
            $existing = (int) $pdo->query("SELECT COUNT(*) FROM community_posts")->fetchColumn();
            if ($existing === 0) {
                $stmt = $pdo->prepare(
                    "INSERT INTO community_posts (user_id, message, created_at) VALUES (?, ?, ?)"
                );
                $posts = [
                    [$demoId,  'Remember: it is okay not to be okay. You are not alone.',                          date('Y-m-d H:i:s', strtotime('-2 days'))],
                    [$youthId, 'I have been struggling with anxiety lately. Any tips from the community?',         date('Y-m-d H:i:s', strtotime('-1 day'))],
                    [$demoId,  'Breathing exercises have really helped me calm down during stressful moments!',    date('Y-m-d H:i:s', strtotime('-3 hours'))],
                    [$youthId, 'Sending love and positive energy to everyone here today. You are stronger than you think!', date('Y-m-d H:i:s')],
                ];
                foreach ($posts as $row) {
                    $stmt->execute($row);
                }
                $steps[] = ['ok', '4 sample community posts inserted.'];
            } else {
                $steps[] = ['info', 'Community posts table already has data — skipped.'];
            }
        } catch (PDOException $e) {
            $steps[] = ['err', 'Failed to insert community posts: ' . htmlspecialchars($e->getMessage())];
            $errors = true;
        }
    }

    // ─────────────────────────────────────────────────────────
    // STEP 9: Verify db.php config matches
    // ─────────────────────────────────────────────────────────
    if (!$errors) {
        $dbFile = __DIR__ . '/php/db.php';
        if (file_exists($dbFile)) {
            $steps[] = ['ok', 'Found <code>php/db.php</code> — database connection file is in place.'];
        } else {
            $steps[] = ['err', '<code>php/db.php</code> not found. Make sure the full project was copied correctly.'];
            $errors = true;
        }
    }

    // ─────────────────────────────────────────────────────────
    // Final verdict
    // ─────────────────────────────────────────────────────────
    if (!$errors) {
        $steps[] = ['done', '🎉 Installation complete! MindSpace is ready to use.'];
        $success = true;
    } else {
        $steps[] = ['err', '⚠️ Installation stopped due to an error above. Fix it and run again.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MindSpace — Installer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #E8F5E9 0%, #E3F2FD 100%);
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 2rem 1rem;
    }
    .card {
      background: #fff;
      border-radius: 20px;
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 620px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.1);
    }
    .brand {
      color: #4CAF50;
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 0.3rem;
    }
    h2 { font-size: 1.2rem; color: #2d3436; margin-bottom: 0.5rem; }
    p  { color: #636e72; font-size: 0.92rem; line-height: 1.6; }

    /* Config table */
    .config-table { width: 100%; border-collapse: collapse; margin: 1.2rem 0; font-size: 0.88rem; }
    .config-table td { padding: 0.5rem 0.8rem; border: 1px solid #e0e0e0; }
    .config-table tr:first-child td { background: #f5f5f5; font-weight: 600; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-size: 0.87em; }

    /* Warning box */
    .warn {
      background: #FFF9C4;
      border-left: 4px solid #FFD54F;
      border-radius: 8px;
      padding: 0.8rem 1rem;
      font-size: 0.85rem;
      color: #5d4037;
      margin: 1.2rem 0;
    }

    /* Big install button */
    .btn {
      display: block;
      width: 100%;
      padding: 0.85rem;
      border: none;
      border-radius: 50px;
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      background: #4CAF50;
      color: #fff;
      transition: background 0.2s ease, transform 0.2s ease;
      margin-top: 1.2rem;
    }
    .btn:hover { background: #388E3C; transform: translateY(-2px); }
    .btn:disabled { background: #b2bec3; cursor: not-allowed; transform: none; }

    /* Step log */
    .steps { margin-top: 1.5rem; list-style: none; }
    .steps li {
      padding: 0.7rem 1rem;
      border-radius: 9px;
      font-size: 0.88rem;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      line-height: 1.5;
    }
    .step-ok   { background: #E8F5E9; color: #2E7D32; border-left: 4px solid #4CAF50; }
    .step-err  { background: #FFEBEE; color: #C62828; border-left: 4px solid #ef5350; }
    .step-info { background: #E3F2FD; color: #1565C0; border-left: 4px solid #42A5F5; }
    .step-done { background: linear-gradient(90deg, #E8F5E9, #E3F2FD); color: #1B5E20; border-left: 4px solid #4CAF50; font-weight: 700; font-size: 0.95rem; }

    /* Success actions */
    .actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.8rem;
      margin-top: 1.5rem;
    }
    .action-btn {
      display: block;
      text-align: center;
      padding: 0.7rem 1rem;
      border-radius: 50px;
      font-family: 'Poppins', sans-serif;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .action-primary { background: #4CAF50; color: #fff; }
    .action-primary:hover { background: #388E3C; }
    .action-outline { border: 2px solid #4CAF50; color: #4CAF50; }
    .action-outline:hover { background: #4CAF50; color: #fff; }

    .delete-notice {
      background: #FFEBEE;
      border-left: 4px solid #ef5350;
      border-radius: 8px;
      padding: 0.8rem 1rem;
      font-size: 0.83rem;
      color: #C62828;
      margin-top: 1.2rem;
    }
  </style>
</head>
<body>
<div class="card">

  <!-- Header -->
  <div class="brand">🌿 MindSpace</div>
  <h2>Database Installer</h2>
  <p>This script will automatically create the database, all three tables, and sample data — no phpMyAdmin required.</p>

  <?php if (!$ran): ?>
  <!-- ── Pre-install view ── -->

  <h2 style="margin-top:1.5rem; font-size:1rem;">Connection Settings</h2>
  <table class="config-table">
    <tr><td>Setting</td><td>Value</td></tr>
    <tr><td>MySQL Host</td><td><code><?= DB_HOST ?></code></td></tr>
    <tr><td>MySQL User</td><td><code><?= DB_USER ?></code></td></tr>
    <tr><td>MySQL Password</td><td><code><?= DB_PASS === '' ? '(empty)' : '••••••' ?></code></td></tr>
    <tr><td>Database to Create</td><td><code><?= DB_NAME ?></code></td></tr>
    <tr><td>Port</td><td><code><?= DB_PORT ?></code></td></tr>
  </table>

  <p style="font-size:0.83rem; color:#636e72;">
    These values come from the top of <code>install.php</code>. If they are wrong,
    edit the <code>define()</code> lines at the top of this file before continuing.
  </p>

  <div class="warn">
    ⚠️ <strong>Make sure XAMPP/WAMP is running</strong> with both
    <strong>Apache</strong> and <strong>MySQL</strong> active before clicking install.
  </div>

  <!-- What will be created -->
  <div style="margin:1.2rem 0; font-size:0.88rem; color:#636e72;">
    <strong style="color:#2d3436;">Will create:</strong>
    <ul style="margin-top:0.5rem; padding-left:1.3rem; list-style:disc;">
      <li>Database: <code>mindspace</code></li>
      <li>Table: <code>users</code></li>
      <li>Table: <code>moods</code></li>
      <li>Table: <code>community_posts</code></li>
      <li>2 demo user accounts</li>
      <li>10 sample mood entries</li>
      <li>4 sample community posts</li>
    </ul>
  </div>

  <form method="POST">
    <!-- Hidden input carries the flag — button name alone fails when button is disabled -->
    <input type="hidden" name="install" value="1" />
    <button type="submit" class="btn" id="installBtn"
            onclick="setTimeout(() => { this.disabled=true; this.textContent='Installing… please wait'; }, 50);">
      ▶ Run Installer
    </button>
  </form>

  <?php else: ?>
  <!-- ── Post-install results view ── -->

  <ul class="steps">
    <?php foreach ($steps as [$type, $msg]): ?>
      <?php
        $cls = match($type) {
          'ok'   => 'step-ok',
          'err'  => 'step-err',
          'info' => 'step-info',
          'done' => 'step-done',
          default => 'step-info'
        };
        $icon = match($type) {
          'ok'   => '✅',
          'err'  => '❌',
          'info' => 'ℹ️',
          'done' => '🎉',
          default => '•'
        };
      ?>
      <li class="<?= $cls ?>"><span><?= $icon ?></span><span><?= $msg ?></span></li>
    <?php endforeach; ?>
  </ul>

  <?php if ($success): ?>
    <!-- Success: show launch buttons -->
    <div class="actions">
      <a href="index.html"     class="action-btn action-primary">🏠 Open App</a>
      <a href="login.html"     class="action-btn action-outline">🔑 Login Page</a>
      <a href="dashboard.html" class="action-btn action-outline">📊 Dashboard</a>
      <a href="admin/index.html" class="action-btn action-primary">🛡️ Admin Panel</a>
    </div>

    <div class="delete-notice">
      🗑️ <strong>Security reminder:</strong> Please delete <code>install.php</code>
      from your server now that setup is complete. It should not remain accessible.
      <br /><br />
      <strong>Demo login:</strong> <code>demo@mindspaceug.com</code> / <code>password123</code>
    </div>

  <?php else: ?>
    <!-- Error: show retry button -->
    <form method="POST" style="margin-top:1rem;">
      <input type="hidden" name="install" value="1" />
      <button type="submit" class="btn" style="background:#ef5350;">
        🔄 Retry Installation
      </button>
    </form>
  <?php endif; ?>

  <?php endif; ?>

</div>
</body>
</html>
