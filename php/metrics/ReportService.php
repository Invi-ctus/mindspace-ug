<?php
/**
 * ReportService - Handles metrics report storage and retrieval
 *
 * Manages the persistence of OO metrics analysis results.
 */

require_once __DIR__ . '/../db.php';

class ReportService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Save a metrics analysis report
     */
    public function saveReport(array $analysisResults): int
    {
        $summary = $analysisResults['summary'];

        $stmt = $this->pdo->prepare("
            INSERT INTO metrics_reports
            (total_classes, total_methods, avg_methods_per_class, avg_attributes_per_class,
             avg_complexity, high_risk_classes, json_results)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $summary['total_classes'],
            $summary['total_methods'],
            $summary['avg_methods_per_class'],
            $summary['avg_attributes_per_class'],
            $summary['avg_complexity'],
            $summary['high_risk_classes'],
            json_encode($analysisResults)
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get the latest metrics report
     */
    public function getLatestReport(): ?array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM metrics_reports
            ORDER BY scan_date DESC
            LIMIT 1
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $row['json_results'] = json_decode($row['json_results'], true);
        return $row;
    }

    /**
     * Get all metrics reports
     */
    public function getAllReports(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM metrics_reports
            ORDER BY scan_date DESC
        ");

        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($reports as &$report) {
            $report['json_results'] = json_decode($report['json_results'], true);
        }

        return $reports;
    }

    /**
     * Get metrics for a specific class from the latest report
     */
    public function getClassMetrics(string $className): ?array
    {
        $latest = $this->getLatestReport();
        if (!$latest) return null;

        $classes = $latest['json_results']['classes'] ?? [];
        return $classes[$className] ?? null;
    }

    /**
     * Get report history summary
     */
    public function getReportHistory(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                DATE(scan_date) as date,
                total_classes,
                total_methods,
                avg_complexity,
                high_risk_classes
            FROM metrics_reports
            ORDER BY scan_date DESC
            LIMIT 30
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}