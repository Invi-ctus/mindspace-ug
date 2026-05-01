<?php
/**
 * RiskEvaluator - Evaluates code quality risks and provides suggestions
 *
 * Analyzes metrics to identify potential issues and recommend improvements.
 */

class RiskEvaluator
{
    /**
     * Evaluate risks for a class and provide suggestions
     */
    public function evaluateClass(array $classMetrics): array
    {
        $issues = [];
        $suggestions = [];

        // WMC (Weighted Methods per Class) evaluation
        if ($classMetrics['wmc'] > 50) {
            $issues[] = 'Very high complexity (WMC > 50)';
            $suggestions[] = 'Consider breaking down the class into smaller, more focused classes';
        } elseif ($classMetrics['wmc'] > 20) {
            $issues[] = 'High complexity (WMC > 20)';
            $suggestions[] = 'Review methods for potential refactoring opportunities';
        }

        // RFC (Response For a Class) evaluation
        if ($classMetrics['rfc'] > 50) {
            $issues[] = 'Large response set (RFC > 50)';
            $suggestions[] = 'Class has too many responsibilities - consider splitting';
        }

        // LCOM (Lack of Cohesion) evaluation
        if ($classMetrics['lcom'] > 0.8) {
            $issues[] = 'Poor cohesion (LCOM > 0.8)';
            $suggestions[] = 'Methods are not well-related - consider separating concerns';
        } elseif ($classMetrics['lcom'] > 0.5) {
            $issues[] = 'Moderate cohesion issues (LCOM > 0.5)';
            $suggestions[] = 'Review method relationships and attribute usage';
        }

        // CBO (Coupling Between Objects) evaluation
        if ($classMetrics['cbo'] > 10) {
            $issues[] = 'High coupling (CBO > 10)';
            $suggestions[] = 'Reduce dependencies - use dependency injection or interfaces';
        } elseif ($classMetrics['cbo'] > 5) {
            $issues[] = 'Moderate coupling (CBO > 5)';
            $suggestions[] = 'Consider reducing external dependencies';
        }

        // DIT (Depth of Inheritance) evaluation
        if ($classMetrics['dit'] > 5) {
            $issues[] = 'Deep inheritance hierarchy (DIT > 5)';
            $suggestions[] = 'Inheritance is too deep - consider composition over inheritance';
        } elseif ($classMetrics['dit'] > 2) {
            $issues[] = 'Moderate inheritance depth (DIT > 2)';
            $suggestions[] = 'Review inheritance hierarchy for simplification';
        }

        // NOC (Number of Children) evaluation
        if ($classMetrics['noc'] > 10) {
            $issues[] = 'Many subclasses (NOC > 10)';
            $suggestions[] = 'Base class is heavily extended - ensure proper abstraction';
        }

        // Method count evaluation
        if ($classMetrics['num_methods'] > 20) {
            $issues[] = 'Too many methods ( > 20)';
            $suggestions[] = 'Class violates Single Responsibility Principle - split functionality';
        } elseif ($classMetrics['num_methods'] > 10) {
            $issues[] = 'Many methods ( > 10)';
            $suggestions[] = 'Consider grouping related methods or extracting classes';
        }

        // Attribute count evaluation
        if ($classMetrics['num_attributes'] > 6) {
            $issues[] = 'Too many attributes ( > 6)';
            $suggestions[] = 'Class holds too much state - consider extracting value objects';
        }

        // Cyclomatic complexity evaluation
        if ($classMetrics['avg_complexity'] > 10) {
            $issues[] = 'High method complexity (avg > 10)';
            $suggestions[] = 'Break down complex methods into smaller, simpler ones';
        } elseif ($classMetrics['avg_complexity'] > 5) {
            $issues[] = 'Moderate method complexity (avg > 5)';
            $suggestions[] = 'Review methods for complexity reduction opportunities';
        }

        // LOC evaluation
        if ($classMetrics['loc'] > 300) {
            $issues[] = 'Very large class (LOC > 300)';
            $suggestions[] = 'Class is too large - split into multiple classes';
        } elseif ($classMetrics['loc'] > 100) {
            $issues[] = 'Large class (LOC > 100)';
            $suggestions[] = 'Consider refactoring to reduce class size';
        }

        return [
            'issues' => $issues,
            'suggestions' => $suggestions,
            'risk_score' => $this->calculateRiskScore($issues),
            'priority' => $this->determinePriority($issues)
        ];
    }

    /**
     * Calculate overall risk score (0-100)
     */
    private function calculateRiskScore(array $issues): int
    {
        $score = 0;

        // Weight different types of issues
        $weights = [
            'Very high complexity' => 20,
            'High complexity' => 10,
            'Large response set' => 15,
            'Poor cohesion' => 15,
            'Moderate cohesion issues' => 8,
            'High coupling' => 15,
            'Moderate coupling' => 8,
            'Deep inheritance hierarchy' => 12,
            'Moderate inheritance depth' => 6,
            'Many subclasses' => 10,
            'Too many methods' => 15,
            'Many methods' => 8,
            'Too many attributes' => 12,
            'High method complexity' => 15,
            'Moderate method complexity' => 8,
            'Very large class' => 20,
            'Large class' => 10
        ];

        foreach ($issues as $issue) {
            foreach ($weights as $pattern => $weight) {
                if (strpos($issue, $pattern) !== false) {
                    $score += $weight;
                    break;
                }
            }
        }

        return min($score, 100);
    }

    /**
     * Determine refactoring priority
     */
    private function determinePriority(array $issues): string
    {
        $criticalCount = 0;
        $highCount = 0;

        $criticalPatterns = ['Very high', 'Poor cohesion', 'Too many', 'Very large'];
        $highPatterns = ['High', 'Large response', 'Deep inheritance', 'High coupling'];

        foreach ($issues as $issue) {
            foreach ($criticalPatterns as $pattern) {
                if (strpos($issue, $pattern) !== false) {
                    $criticalCount++;
                    break;
                }
            }
            foreach ($highPatterns as $pattern) {
                if (strpos($issue, $pattern) !== false) {
                    $highCount++;
                    break;
                }
            }
        }

        if ($criticalCount > 0) return 'Critical';
        if ($highCount > 1) return 'High';
        if ($highCount > 0 || count($issues) > 2) return 'Medium';
        return 'Low';
    }

    /**
     * Get general project health assessment
     */
    public function assessProjectHealth(array $summary): array
    {
        $health = [
            'overall' => 'good',
            'concerns' => [],
            'recommendations' => []
        ];

        if ($summary['total_classes'] === 0) {
            $health['overall'] = 'no-classes';
            $health['concerns'][] = 'No classes found in the project';
            $health['recommendations'][] = 'Consider refactoring procedural code into object-oriented classes';
            return $health;
        }

        if ($summary['high_risk_classes'] > $summary['total_classes'] * 0.3) {
            $health['overall'] = 'poor';
            $health['concerns'][] = 'High proportion of risky classes';
            $health['recommendations'][] = 'Prioritize refactoring of high-risk classes';
        } elseif ($summary['high_risk_classes'] > $summary['total_classes'] * 0.1) {
            $health['overall'] = 'fair';
            $health['concerns'][] = 'Several classes need attention';
            $health['recommendations'][] = 'Review and refactor medium to high-risk classes';
        }

        if ($summary['avg_methods_per_class'] > 15) {
            $health['concerns'][] = 'Classes have too many methods on average';
            $health['recommendations'][] = 'Apply Single Responsibility Principle more consistently';
        }

        if ($summary['avg_complexity'] > 8) {
            $health['concerns'][] = 'High average method complexity';
            $health['recommendations'][] = 'Focus on simplifying complex methods';
        }

        return $health;
    }
}