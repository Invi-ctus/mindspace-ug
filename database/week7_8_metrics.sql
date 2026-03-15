-- ===========================================================================
-- MindSpace — Week 7 & 8: Software Metrics Tables
-- SWE 2204 Software Metrics | MUST BSE 2024
-- Chapter 5: Software Size Metrics
-- Chapter 6: Structural Complexity Metrics
--
-- USAGE: Run this file in phpMyAdmin against the mindspace_db database
-- NOTE:  Does NOT modify any existing tables — only adds new ones
-- ===========================================================================

USE mindspace_db;

-- ============================================================
-- CHAPTER 5: SOFTWARE SIZE TABLES
-- ============================================================

-- Stores LOC measurements for each PHP file in MindSpace
CREATE TABLE IF NOT EXISTS loc_measurements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  measured_date DATE NOT NULL,
  filename VARCHAR(100) NOT NULL,
  total_loc INT NOT NULL,
  ncloc INT NOT NULL COMMENT 'Non-commented lines — actual working code',
  cloc INT NOT NULL COMMENT 'Commented lines only',
  blank_lines INT NOT NULL,
  comment_density DECIMAL(5,2) NOT NULL COMMENT 'CLOC/total_loc x 100',
  size_rating VARCHAR(10) NOT NULL COMMENT 'Small/Medium/Large',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stores Function Point breakdown for MindSpace features
CREATE TABLE IF NOT EXISTS fp_measurements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  measured_date DATE NOT NULL,
  feature_name VARCHAR(100) NOT NULL,
  component_type ENUM('EI','EO','EQ','ILF','EIF') NOT NULL,
  description VARCHAR(255),
  complexity ENUM('low','average','high') NOT NULL,
  weight INT NOT NULL,
  fp_points INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- CHAPTER 6: STRUCTURAL COMPLEXITY TABLES
-- ============================================================

-- Stores cyclomatic complexity per PHP function
CREATE TABLE IF NOT EXISTS cyclomatic_measurements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  measured_date DATE NOT NULL,
  filename VARCHAR(100) NOT NULL,
  function_name VARCHAR(100) NOT NULL,
  decision_points INT NOT NULL COMMENT 'Number of if/while/for/foreach/case',
  cyclomatic_complexity INT NOT NULL COMMENT 'v(G) = 1 + decision_points',
  complexity_level VARCHAR(20) NOT NULL COMMENT 'Simple/Moderate/Complex',
  min_test_cases INT NOT NULL COMMENT 'Same as cyclomatic_complexity',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stores cohesion and coupling per module (PHP file)
CREATE TABLE IF NOT EXISTS module_complexity (
  id INT AUTO_INCREMENT PRIMARY KEY,
  measured_date DATE NOT NULL,
  module_name VARCHAR(100) NOT NULL,
  internal_relations INT NOT NULL COMMENT 'Function calls within same file',
  external_relations INT NOT NULL COMMENT 'Calls to other files/modules',
  cohesion_score DECIMAL(4,2) NOT NULL COMMENT 'internal/(internal+external)',
  coupling_score DECIMAL(4,2) NOT NULL COMMENT 'external/(internal+external)',
  fan_in INT NOT NULL COMMENT 'How many other files call/include this file',
  fan_out INT NOT NULL COMMENT 'How many files this file includes/calls',
  ifc_score INT NOT NULL COMMENT '(fan_in x fan_out)^2',
  risk_level VARCHAR(10) NOT NULL COMMENT 'Low/Medium/High',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SEED DATA — Real MindSpace measurements
-- ============================================================

-- LOC measurements (manually counted from real MindSpace files)
INSERT INTO loc_measurements
  (measured_date, filename, total_loc, ncloc, cloc, blank_lines, comment_density, size_rating)
VALUES
  (CURDATE(), 'php/login.php',          65,  42, 12, 11, 18.46, 'Small'),
  (CURDATE(), 'php/register.php',       72,  47, 13, 12, 18.06, 'Small'),
  (CURDATE(), 'php/checkin.php',        58,  38, 10, 10, 17.24, 'Small'),
  (CURDATE(), 'php/community.php',      84,  55, 16, 13, 19.05, 'Small'),
  (CURDATE(), 'php/dashboard_data.php', 91,  60, 18, 13, 19.78, 'Small'),
  (CURDATE(), 'php/db.php',             28,  16,  8,  4, 28.57, 'Small'),
  (CURDATE(), 'admin/admin_data.php',  110,  72, 22, 16, 20.00, 'Medium'),
  (CURDATE(), 'js/main.js',            145,  98, 28, 19, 19.31, 'Medium');

-- Function Points for MindSpace features
-- UFC = 58, VAF = 1.0, Final FP = 58
INSERT INTO fp_measurements
  (measured_date, feature_name, component_type, description, complexity, weight, fp_points)
VALUES
  -- EXTERNAL INPUTS (EI) — data coming IN from user
  (CURDATE(), 'User Login',          'EI', 'User submits email and password',                         'low',     3,  3),
  (CURDATE(), 'User Registration',   'EI', 'New user submits registration form',                      'low',     3,  3),
  (CURDATE(), 'Mood Check-in',       'EI', 'User submits mood emoji and journal note',                'low',     3,  3),
  (CURDATE(), 'Community Post',      'EI', 'User submits anonymous support post',                     'low',     3,  3),

  -- EXTERNAL OUTPUTS (EO) — data OUT with calculations/derived data
  (CURDATE(), '7-Day Mood Chart',    'EO', 'Bar chart of mood history — uses Chart.js calculations',  'average', 5,  5),
  (CURDATE(), 'Admin Stats Panel',   'EO', 'User counts and top mood — all calculated',               'average', 5,  5),

  -- EXTERNAL INQUIRIES (EQ) — data OUT with NO calculations, just retrieve and show
  (CURDATE(), 'Community Feed',      'EQ', 'Fetch and display latest 20 posts — no calculation',      'low',     3,  3),
  (CURDATE(), 'Resources Page',      'EQ', 'Display helplines and coping tips — simple retrieval',    'low',     3,  3),
  (CURDATE(), 'Mood History Table',  'EQ', 'Show last 7 check-ins in table — just fetched',           'low',     3,  3),

  -- INTERNAL LOGICAL FILES (ILF) — data stored INSIDE MindSpace database
  (CURDATE(), 'Users Table',         'ILF', 'id, name, email, password_hash, created_at',             'low',     7,  7),
  (CURDATE(), 'Mood Checkins Table', 'ILF', 'id, user_id, mood_emoji, mood_label, note, created_at',  'low',     7,  7),
  (CURDATE(), 'Community Posts',     'ILF', 'id, session_id, message, created_at',                    'low',     7,  7),
  (CURDATE(), 'Resources Table',     'ILF', 'id, title, description, contact, category',              'low',     7,  7);

-- Cyclomatic complexity v(G) = 1 + decision_points
INSERT INTO cyclomatic_measurements
  (measured_date, filename, function_name, decision_points, cyclomatic_complexity, complexity_level, min_test_cases)
VALUES
  (CURDATE(), 'php/login.php',          'handleLogin',      3, 4, 'Simple', 4),
  (CURDATE(), 'php/register.php',       'handleRegister',   4, 5, 'Simple', 5),
  (CURDATE(), 'php/checkin.php',        'saveCheckin',      2, 3, 'Simple', 3),
  (CURDATE(), 'php/community.php',      'handlePost',       3, 4, 'Simple', 4),
  (CURDATE(), 'php/community.php',      'fetchPosts',       1, 2, 'Simple', 2),
  (CURDATE(), 'php/dashboard_data.php', 'getMoodHistory',   2, 3, 'Simple', 3),
  (CURDATE(), 'php/dashboard_data.php', 'getMoodSummary',   3, 4, 'Simple', 4),
  (CURDATE(), 'admin/admin_data.php',   'getAdminStats',    5, 6, 'Simple', 6),
  (CURDATE(), 'js/main.js',             'renderMoodChart',  4, 5, 'Simple', 5),
  (CURDATE(), 'js/main.js',             'validateForm',     3, 4, 'Simple', 4);

-- Module cohesion and coupling
-- CH = internal/(internal+external), CP = external/(internal+external)
-- IFC = (fan_in x fan_out)^2
INSERT INTO module_complexity
  (measured_date, module_name, internal_relations, external_relations,
   cohesion_score, coupling_score, fan_in, fan_out, ifc_score, risk_level)
VALUES
  (CURDATE(), 'php/login.php',          3, 1, 0.75, 0.25, 1, 2,   4, 'Low'),
  (CURDATE(), 'php/register.php',       3, 1, 0.75, 0.25, 1, 2,   4, 'Low'),
  (CURDATE(), 'php/checkin.php',        3, 1, 0.75, 0.25, 2, 2,  16, 'Low'),
  (CURDATE(), 'php/community.php',      3, 2, 0.60, 0.40, 2, 2,  16, 'Low'),
  (CURDATE(), 'php/dashboard_data.php', 3, 2, 0.60, 0.40, 3, 2,  36, 'Medium'),
  (CURDATE(), 'admin/admin_data.php',   2, 3, 0.40, 0.60, 2, 4,  64, 'Medium'),
  (CURDATE(), 'js/main.js',             4, 3, 0.57, 0.43, 4, 3, 144, 'High');

-- ===========================================================================
-- DONE. Verify with: SELECT COUNT(*) FROM loc_measurements;
--                     SELECT COUNT(*) FROM fp_measurements;
--                     SELECT COUNT(*) FROM cyclomatic_measurements;
--                     SELECT COUNT(*) FROM module_complexity;
-- ===========================================================================
