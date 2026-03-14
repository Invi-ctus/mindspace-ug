-- =========================================================
-- MindSpace Empirical Framework — Migration v2
-- =========================================================
-- Extends the v1 telemetry schema with:
--   1. Expanded event_type ENUM (scroll_depth, search, navigation, experiment_exposure)
--   2. experiments_config table — central registry of all A/B experiments
--   3. user_sessions table — funnel tracking across page visits per session
--   4. Useful analytical views
--
-- USAGE:
--   1. Ensure migration_telemetry.sql (v1) has already been run.
--   2. Back up your database before running this.
--   3. Run this file in phpMyAdmin → SQL tab, then click Go.
-- =========================================================

USE mindspace_db;

-- =========================================================
-- STEP 1: Expand telemetry_logs event_type ENUM
-- =========================================================
-- Adds scroll_depth, search, navigation, and experiment_exposure
-- to the allowed event types so the PHP whitelist change is
-- reflected in the database column definition.
-- =========================================================

ALTER TABLE telemetry_logs
  MODIFY COLUMN event_type
    ENUM(
      'page_view',
      'click',
      'dwell_time',
      'form_interaction',
      'resource_access',
      'scroll_depth',
      'search',
      'navigation',
      'experiment_exposure'
    ) NOT NULL;

-- =========================================================
-- STEP 2: experiments_config — Central experiment registry
-- =========================================================
-- Stores metadata about every A/B test so the API and admin
-- panel have a reliable source of truth without hard-coded lists.
-- =========================================================

CREATE TABLE IF NOT EXISTS experiments_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    experiment_name VARCHAR(100) NOT NULL UNIQUE,
    description     VARCHAR(255) NOT NULL,
    variant_a_desc  VARCHAR(255) NOT NULL,
    variant_b_desc  VARCHAR(255) NOT NULL,
    status          ENUM('active', 'paused', 'concluded') NOT NULL DEFAULT 'active',
    target_sample   INT UNSIGNED NOT NULL DEFAULT 800,   -- users per variant for confidence
    started_at      DATE NULL,
    concluded_at    DATE NULL,
    winning_variant ENUM('A', 'B') NULL,                 -- filled when concluded
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the three active experiments
INSERT IGNORE INTO experiments_config
    (experiment_name, description, variant_a_desc, variant_b_desc, status, target_sample, started_at)
VALUES
    (
        'checkin_layout_test',
        'Mood check-in layout comparison',
        'Classic vertical mood selection with emojis',
        'Compact grid layout with color-coded moods',
        'active', 400, CURDATE()
    ),
    (
        'dashboard_nudge_test',
        'Dashboard return-visit nudge comparison',
        'Static mood summary chart (control)',
        'Interactive chart with streak goal & nudge banner',
        'active', 400, CURDATE()
    ),
    (
        'resources_layout_test',
        'Resources page helpline card layout',
        'List layout with phone links (control)',
        'Card grid layout with call-to-action buttons',
        'active', 400, CURDATE()
    );

-- =========================================================
-- STEP 3: user_sessions — Cross-page funnel tracking
-- =========================================================
-- Each row represents one visit session and records which pages
-- were visited, enabling funnel analysis (e.g. homepage → check-in
-- → dashboard conversion).
-- =========================================================

CREATE TABLE IF NOT EXISTS user_sessions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      VARCHAR(64)  NOT NULL UNIQUE,
    first_page      VARCHAR(255) NULL,                   -- entry page
    pages_visited   INT UNSIGNED NOT NULL DEFAULT 0,     -- depth of session
    checkin_done    TINYINT(1)   NOT NULL DEFAULT 0,     -- converted to check-in
    resource_viewed TINYINT(1)   NOT NULL DEFAULT 0,     -- viewed resources page
    community_visit TINYINT(1)   NOT NULL DEFAULT 0,     -- visited community page
    total_dwell_sec INT UNSIGNED NULL,                   -- summed dwell across pages
    started_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_session (session_id),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- STEP 4: Analytical views
-- =========================================================

-- View: experiment summary with win/loss indication
CREATE OR REPLACE VIEW v_experiment_summary AS
SELECT
    ec.experiment_name,
    ec.description,
    ec.status,
    ec.target_sample,
    a.variant,
    COUNT(*)                                          AS total_assigned,
    SUM(a.converted)                                  AS conversions,
    ROUND(SUM(a.converted) * 100.0 / COUNT(*), 2)    AS conversion_rate,
    ec.winning_variant
FROM experiments_config ec
LEFT JOIN ab_test_assignments a USING (experiment_name)
GROUP BY ec.experiment_name, ec.description, ec.status,
         ec.target_sample, a.variant, ec.winning_variant;

-- View: daily page funnel (page views per day)
CREATE OR REPLACE VIEW v_daily_page_views AS
SELECT
    DATE(created_at)           AS day,
    page_url,
    COUNT(DISTINCT session_id) AS unique_sessions,
    COUNT(*)                   AS total_events,
    ROUND(AVG(dwell_seconds))  AS avg_dwell_seconds
FROM telemetry_logs
WHERE event_type = 'page_view'
GROUP BY DATE(created_at), page_url;

-- View: helpline engagement (resource_access + click on tel: links)
CREATE OR REPLACE VIEW v_helpline_engagement AS
SELECT
    element_id                          AS helpline_element,
    COUNT(*)                            AS total_clicks,
    COUNT(DISTINCT session_id)          AS unique_sessions,
    DATE(MIN(created_at))               AS first_click_date,
    DATE(MAX(created_at))               AS last_click_date
FROM telemetry_logs
WHERE event_type = 'click'
  AND element_id LIKE 'helpline_%'
GROUP BY element_id
ORDER BY total_clicks DESC;

-- =========================================================
-- Verification query — run this to confirm migration worked:
-- =========================================================
-- SELECT 'experiments_config' AS tbl, COUNT(*) AS rows FROM experiments_config
-- UNION ALL
-- SELECT 'user_sessions',    COUNT(*) FROM user_sessions
-- UNION ALL
-- SELECT 'telemetry_logs',   COUNT(*) FROM telemetry_logs
-- UNION ALL
-- SELECT 'ab_test_assignments', COUNT(*) FROM ab_test_assignments;
