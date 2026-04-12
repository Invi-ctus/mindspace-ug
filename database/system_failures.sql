-- MindSpace System Failures Tracking
-- ===================================
-- This table tracks system failures for reliability metrics calculation

CREATE TABLE IF NOT EXISTS system_failures (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    failure_type    VARCHAR(50) NOT NULL,  -- e.g., login_error, db_error, api_error, validation_error
    module          VARCHAR(50) NOT NULL,  -- e.g., auth, dashboard, checkin, community
    timestamp       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolution_time INT UNSIGNED NULL,    -- seconds to resolve, NULL if still unresolved
    description     TEXT NULL,             -- optional details about the failure
    
    INDEX idx_failure_type (failure_type),
    INDEX idx_module (module),
    INDEX idx_timestamp (timestamp),
    INDEX idx_unresolved (resolution_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
