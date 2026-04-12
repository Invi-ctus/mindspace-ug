-- ===========================================================================
-- MindSpace — Cost Metrics Migration
-- SWE 2204 Software Metrics | Cost Estimation & Tracking
--
-- PURPOSE:
--   Adds project cost measurement tables and analytical views so the admin
--   dashboard can report planned vs actual cost, estimation variance,
--   rework rate, and cost efficiency per feature.
--
-- USAGE:
--   1. Back up your database.
--   2. Run this file in phpMyAdmin against mindspace_db.
--   3. Refresh admin panel to view cost metrics.
-- ===========================================================================

USE mindspace_db;

-- ============================================================
-- STEP 1: Cost Tracking Table
-- ============================================================
-- One row represents one feature/task effort entry in a sprint.
-- All currency values are stored as numeric values without symbols.
-- ============================================================

CREATE TABLE IF NOT EXISTS cost_tracking (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sprint_label VARCHAR(50) NOT NULL,
  feature_name VARCHAR(100) NOT NULL,
  planned_hours DECIMAL(8,2) NOT NULL,
  actual_hours DECIMAL(8,2) NOT NULL,
  hourly_rate DECIMAL(8,2) NOT NULL,
  defects_found INT UNSIGNED NOT NULL DEFAULT 0,
  rework_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
  notes VARCHAR(255) NULL,
  measured_date DATE NOT NULL DEFAULT (CURDATE()),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_sprint_label (sprint_label),
  INDEX idx_feature_name (feature_name),
  INDEX idx_measured_date (measured_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STEP 2: Cost Summary View
-- ============================================================
-- Provides headline KPI values used by the admin dashboard.
-- ============================================================

CREATE OR REPLACE VIEW v_cost_kpi_summary AS
SELECT
  COUNT(*) AS entry_count,
  ROUND(SUM(planned_hours), 2) AS total_planned_hours,
  ROUND(SUM(actual_hours), 2) AS total_actual_hours,
  ROUND(SUM(planned_hours * hourly_rate), 2) AS total_planned_cost,
  ROUND(SUM(actual_hours * hourly_rate), 2) AS total_actual_cost,
  ROUND(
    CASE
      WHEN SUM(planned_hours * hourly_rate) > 0 THEN
        ((SUM(actual_hours * hourly_rate) - SUM(planned_hours * hourly_rate))
          / SUM(planned_hours * hourly_rate)) * 100
      ELSE 0
    END,
    2
  ) AS cost_variance_pct,
  ROUND(
    CASE
      WHEN SUM(actual_hours) > 0 THEN (SUM(rework_hours) / SUM(actual_hours)) * 100
      ELSE 0
    END,
    2
  ) AS rework_pct,
  ROUND(
    CASE
      WHEN SUM(actual_hours) > 0 THEN SUM(defects_found) / SUM(actual_hours)
      ELSE 0
    END,
    3
  ) AS defects_per_hour
FROM cost_tracking;

-- ============================================================
-- STEP 3: Feature Efficiency View
-- ============================================================
-- Combines cost entries with function point data to compute:
--   - Cost per FP
--   - FP per hour
--   - Estimation variance per feature
-- ============================================================

CREATE OR REPLACE VIEW v_feature_cost_efficiency AS
SELECT
  ct.feature_name,
  COUNT(*) AS entries,
  ROUND(SUM(ct.planned_hours), 2) AS planned_hours,
  ROUND(SUM(ct.actual_hours), 2) AS actual_hours,
  ROUND(SUM(ct.planned_hours * ct.hourly_rate), 2) AS planned_cost,
  ROUND(SUM(ct.actual_hours * ct.hourly_rate), 2) AS actual_cost,
  ROUND(
    CASE
      WHEN SUM(ct.planned_hours) > 0 THEN
        ((SUM(ct.actual_hours) - SUM(ct.planned_hours)) / SUM(ct.planned_hours)) * 100
      ELSE 0
    END,
    2
  ) AS effort_variance_pct,
  ROUND(
    CASE
      WHEN SUM(fp.fp_points) > 0 THEN SUM(ct.actual_hours * ct.hourly_rate) / SUM(fp.fp_points)
      ELSE NULL
    END,
    2
  ) AS cost_per_fp,
  ROUND(
    CASE
      WHEN SUM(ct.actual_hours) > 0 THEN SUM(fp.fp_points) / SUM(ct.actual_hours)
      ELSE NULL
    END,
    3
  ) AS fp_per_hour
FROM cost_tracking ct
LEFT JOIN fp_measurements fp
  ON fp.feature_name = ct.feature_name
GROUP BY ct.feature_name
ORDER BY actual_cost DESC;

-- ============================================================
-- STEP 4: Seed Data (safe to run once)
-- ============================================================
-- Provides immediate demo data for cost dashboard validation.
-- ============================================================

INSERT INTO cost_tracking
  (sprint_label, feature_name, planned_hours, actual_hours, hourly_rate, defects_found, rework_hours, notes)
SELECT * FROM (
  SELECT 'Sprint 5' AS sprint_label, 'Mood Check-in' AS feature_name, 10.00 AS planned_hours, 12.00 AS actual_hours, 8.00 AS hourly_rate, 2 AS defects_found, 1.50 AS rework_hours, 'A/B layout experiment implementation' AS notes
  UNION ALL
  SELECT 'Sprint 5', 'Dashboard Nudge', 8.00, 9.50, 8.00, 1, 0.50, 'Retention nudge variant and telemetry hooks'
  UNION ALL
  SELECT 'Sprint 6', 'Community Feed', 12.00, 10.00, 8.00, 0, 0.00, 'Pagination and anti-spam refinements'
  UNION ALL
  SELECT 'Sprint 6', 'Resources Page', 6.00, 7.00, 8.00, 1, 0.75, 'Helpline card redesign'
  UNION ALL
  SELECT 'Sprint 6', 'Admin Statistics', 9.00, 11.00, 8.00, 2, 1.25, 'Telemetry + cost analytics integration'
) seed
WHERE NOT EXISTS (SELECT 1 FROM cost_tracking);

-- ===========================================================================
-- VERIFICATION
-- ===========================================================================
-- SELECT * FROM v_cost_kpi_summary;
-- SELECT * FROM v_feature_cost_efficiency;
-- SELECT COUNT(*) FROM cost_tracking;
-- ===========================================================================
