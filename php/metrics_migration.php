<?php
/**
 * OO Metrics Database Migration Runner
 * Executes the SQL migration to add metrics_reports table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS metrics_reports (
        id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        scan_date             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        total_classes         INT NOT NULL DEFAULT 0,
        total_methods         INT NOT NULL DEFAULT 0,
        avg_methods_per_class DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        avg_attributes_per_class DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        avg_complexity        DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        high_risk_classes     INT NOT NULL DEFAULT 0,
        json_results          JSON NOT NULL,
        created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_scan_date (scan_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);

    // Insert sample data
    $sampleSql = "
    INSERT INTO metrics_reports (total_classes, total_methods, avg_methods_per_class, avg_attributes_per_class, avg_complexity, high_risk_classes, json_results) VALUES
    (5, 25, 5.00, 3.20, 2.5, 1, '{\"classes\": {}, \"summary\": \"Sample metrics data\"}')
    ";
    $pdo->exec($sampleSql);

    echo "Migration completed successfully!\n";
    echo "Created metrics_reports table and inserted sample data.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}