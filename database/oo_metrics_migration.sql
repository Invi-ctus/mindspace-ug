-- Migration: Add OO Metrics Report Table
-- Adds table to store object-oriented software metrics reports

USE mindspace_db;

CREATE TABLE IF NOT EXISTS metrics_reports (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scan_date             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_classes         INT NOT NULL DEFAULT 0,
    total_methods         INT NOT NULL DEFAULT 0,
    avg_methods_per_class DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    avg_attributes_per_class DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    avg_complexity        DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    high_risk_classes     INT NOT NULL DEFAULT 0,
    json_results          JSON NOT NULL,  -- Store detailed metrics data
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_scan_date (scan_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing
INSERT INTO metrics_reports (total_classes, total_methods, avg_methods_per_class, avg_attributes_per_class, avg_complexity, high_risk_classes, json_results) VALUES
(5, 25, 5.00, 3.20, 2.5, 1, '{"classes": [], "summary": "Sample metrics data"}');