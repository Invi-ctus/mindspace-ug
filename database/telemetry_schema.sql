-- =========================================================
-- TASK 1: Database Schema & Telemetry Setup
-- =========================================================
-- This file extends the existing mindspace.sql schema with
-- tables for empirical data collection and A/B testing.
-- =========================================================

USE mindspace_db;

-- ---------------------------------------------------------
-- 1.1 Telemetry Logs Table
-- ---------------------------------------------------------
-- Tracks anonymous user interactions for 2nd-degree data collection
-- Captures: page views, dwell time, click paths, resource interactions
-- ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS telemetry_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      VARCHAR(64)  NOT NULL,           -- Anonymous session identifier
    event_type      ENUM('page_view', 'click', 'dwell_time', 'form_interaction', 'resource_access') NOT NULL,
    page_url        VARCHAR(255) NOT NULL,
    element_id      VARCHAR(100) NULL,               -- For click events (e.g., helpline button)
    element_class   VARCHAR(100) NULL,
    dwell_seconds   INT UNSIGNED NULL,               -- Time spent on page/resource
    metadata        JSON NULL,                       -- Additional context (e.g., which resource was clicked)
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_session_event (session_id, event_type),
    INDEX idx_page_url (page_url),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 1.2 A/B Tests Assignment Table
-- ---------------------------------------------------------
-- Tracks which experimental variant each user was exposed to
-- Ensures local control through randomization logging
-- ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS ab_test_assignments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      VARCHAR(64)  NOT NULL,
    experiment_name VARCHAR(100) NOT NULL,            -- e.g., 'checkin_layout_test'
    variant         ENUM('A', 'B') NOT NULL,          -- Layout A or Layout B
    assigned_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    converted       TINYINT(1)   DEFAULT 0,           -- Whether user completed the desired action
    conversion_data JSON NULL,                        -- Additional conversion metrics
    
    UNIQUE KEY unique_session_experiment (session_id, experiment_name),
    INDEX idx_experiment_variant (experiment_name, variant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 1.3 Modify mood_checkins to link with A/B tests
-- ---------------------------------------------------------
-- Add experiment tracking to existing moods table
-- ---------------------------------------------------------

ALTER TABLE moods 
ADD COLUMN session_id VARCHAR(64) NULL AFTER created_at,
ADD COLUMN ab_variant ENUM('A', 'B', 'control') DEFAULT 'control' AFTER session_id,
ADD INDEX idx_session (session_id);

-- ---------------------------------------------------------
-- 1.4 Create aggregated metrics view for analysis
-- ---------------------------------------------------------

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
