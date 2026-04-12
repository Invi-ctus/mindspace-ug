-- MindSpace: Software Test Metrics table
-- Run this script in your MySQL database before opening admin/test_metrics.php

CREATE TABLE IF NOT EXISTS test_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feature_name VARCHAR(100) NOT NULL,
    test_description TEXT NOT NULL,
    status ENUM('pass', 'fail', 'pending') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
