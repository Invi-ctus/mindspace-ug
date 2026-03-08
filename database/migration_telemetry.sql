-- =========================================================
-- MindSpace Empirical Framework - Database Migration
-- =========================================================
-- This file upgrades your existing mindspace_db with telemetry
-- and A/B testing support.
--
-- USAGE:
-- 1. Backup your existing database first!
-- 2. In phpMyAdmin, go to Import tab or SQL tab
-- 3. Paste this entire file and click Go
-- =========================================================

USE mindspace_db;

-- =========================================================
-- STEP 1: Create Telemetry Logs Table
-- =========================================================

CREATE TABLE IF NOT EXISTS telemetry_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      VARCHAR(64)  NOT NULL,
    event_type      ENUM('page_view', 'click', 'dwell_time', 'form_interaction', 'resource_access') NOT NULL,
    page_url        VARCHAR(255) NOT NULL,
    element_id      VARCHAR(100) NULL,
    element_class   VARCHAR(100) NULL,
    dwell_seconds   INT UNSIGNED NULL,
    metadata        JSON NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_session_event (session_id, event_type),
    INDEX idx_page_url (page_url),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- STEP 2: Create A/B Test Assignments Table
-- =========================================================

CREATE TABLE IF NOT EXISTS ab_test_assignments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      VARCHAR(64)  NOT NULL,
    experiment_name VARCHAR(100) NOT NULL,
    variant         ENUM('A', 'B') NOT NULL,
    assigned_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    converted       TINYINT(1)   DEFAULT 0,
    conversion_data JSON NULL,
    
    UNIQUE KEY unique_session_experiment (session_id, experiment_name),
    INDEX idx_experiment_variant (experiment_name, variant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- STEP 3: Extend Moods Table for A/B Tracking
-- =========================================================

-- Add session_id column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'moods';
SET @columnname = 'session_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(64) NULL AFTER created_at')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add ab_variant column if it doesn't exist
SET @columnname = 'ab_variant';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' ENUM(\'A\', \'B\', \'control\') DEFAULT \'control\' AFTER session_id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index on session_id if it doesn't exist
SET @indexname = 'idx_session';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD INDEX ', @indexname, ' (session_id)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =========================================================
-- STEP 4: Create Analytics View
-- =========================================================

CREATE OR REPLACE VIEW ab_test_metrics AS
SELECT 
    a.experiment_name,
    a.variant,
    COUNT(DISTINCT a.session_id) AS total_users,
    SUM(CASE WHEN a.converted = 1 THEN 1 ELSE 0 END) AS conversions,
    ROUND(SUM(CASE WHEN a.converted = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT a.session_id), 2) AS conversion_rate,
    AVG(t.dwell_seconds) AS avg_dwell_time
FROM ab_test_assignments a
LEFT JOIN telemetry_logs t ON a.session_id = t.session_id
GROUP BY a.experiment_name, a.variant;

-- =========================================================
-- STEP 5: Verification Queries
-- =========================================================

-- Check that tables were created
SELECT 'Tables Created Successfully!' as status;

SELECT 
    table_name, 
    table_type 
FROM information_schema.tables 
WHERE table_schema = 'mindspace_db' 
  AND table_name IN ('telemetry_logs', 'ab_test_assignments', 'moods')
ORDER BY table_name;

-- Show column info for new tables
SELECT 
    table_name,
    column_name,
    data_type,
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'mindspace_db'
  AND table_name IN ('telemetry_logs', 'ab_test_assignments')
ORDER BY table_name, ordinal_position;

-- =========================================================
-- Migration Complete!
-- =========================================================
-- Your database now supports:
-- ✅ Anonymous telemetry logging
-- ✅ A/B test assignment tracking
-- ✅ Conversion rate analysis
-- ✅ Behavioral metrics collection
--
-- Next steps:
-- 1. Verify the PHP files are updated (checkin.php, telemetry.php)
-- 2. Test the check-in page to ensure A/B testing works
-- 3. Review METRICS.md for detailed documentation
-- =========================================================
