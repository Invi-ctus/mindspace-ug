<?php
/**
 * MetricsAnalyzer - Object-Oriented Metrics Analyzer
 *
 * Analyzes PHP source files to compute CK and other OO metrics.
 * Uses simple parsing to extract class information from PHP code.
 */

class MetricsAnalyzer
{
    private array $classes = [];
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }

    /**
     * Run complete analysis of the project
     */
    public function analyze(): array
    {
        $this->classes = [];
        $phpFiles = $this->findPhpFiles();

        foreach ($phpFiles as $file) {
            $this->analyzeFile($file);
        }

        $this->computeMetrics();

        return [
            'classes' => $this->classes,
            'summary' => $this->getSummary()
        ];
    }

    /**
     * Find all PHP files in the project
     */
    private function findPhpFiles(): array
    {
        $files = [];
        $this->scanDirectory($this->projectRoot, $files);
        return $files;
    }

    /**
     * Recursively scan directory for PHP files
     */
    private function scanDirectory(string $dir, array &$files): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                // Skip vendor, node_modules, etc.
                if (!in_array($item, ['vendor', 'node_modules', '.git'])) {
                    $this->scanDirectory($path, $files);
                }
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $files[] = $path;
            }
        }
    }

    /**
     * Analyze a single PHP file for classes
     */
    private function analyzeFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if (!$content) return;

        // Simple regex-based parsing for class definitions
        $classPattern = '/class\s+(\w+)(?:\s+extends\s+(\w+))?(?:\s+implements\s+(.+?))?\s*{/i';
        preg_match_all($classPattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $className = $match[1];
            $extends = $match[2] ?? null;
            $implements = $match[3] ?? null;

            $classInfo = [
                'name' => $className,
                'file' => str_replace($this->projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                'extends' => $extends,
                'implements' => $implements ? array_map('trim', explode(',', $implements)) : [],
                'methods' => $this->extractMethods($content, $className),
                'attributes' => $this->extractAttributes($content, $className),
                'loc' => $this->countLinesOfCode($content, $className)
            ];

            $this->classes[$className] = $classInfo;
        }
    }

    /**
     * Extract methods from class content
     */
    private function extractMethods(string $content, string $className): array
    {
        $methods = [];
        // Find class block
        $classPattern = '/class\s+' . preg_quote($className) . '.*?\s*\{(.*?)\}/s';
        if (!preg_match($classPattern, $content, $match)) return $methods;

        $classContent = $match[1];

        // Extract methods
        $methodPattern = '/(?:public|private|protected)?\s*function\s+(\w+)\s*\(/i';
        preg_match_all($methodPattern, $classContent, $methodMatches);

        foreach ($methodMatches[1] as $methodName) {
            $methods[] = [
                'name' => $methodName,
                'complexity' => $this->calculateCyclomaticComplexity($classContent, $methodName)
            ];
        }

        return $methods;
    }

    /**
     * Extract attributes from class content
     */
    private function extractAttributes(string $content, string $className): array
    {
        $attributes = [];
        $classPattern = '/class\s+' . preg_quote($className) . '.*?\s*\{(.*?)\}/s';
        if (!preg_match($classPattern, $content, $match)) return $attributes;

        $classContent = $match[1];

        // Extract properties
        $attrPattern = '/(?:public|private|protected)?\s*\$\s*(\w+)/i';
        preg_match_all($attrPattern, $classContent, $attrMatches);

        return $attrMatches[1];
    }

    /**
     * Count lines of code for a class
     */
    private function countLinesOfCode(string $content, string $className): int
    {
        $classPattern = '/class\s+' . preg_quote($className) . '.*?\s*\{(.*?)\}/s';
        if (!preg_match($classPattern, $content, $match)) return 0;

        $classContent = $match[1];
        $lines = explode("\n", $classContent);
        return count(array_filter($lines, fn($line) => trim($line) !== ''));
    }

    /**
     * Calculate cyclomatic complexity for a method (simplified)
     */
    private function calculateCyclomaticComplexity(string $classContent, string $methodName): int
    {
        // Find method content
        $methodPattern = '/function\s+' . preg_quote($methodName) . '\s*\(.*?\)\s*\{(.*?)\}/s';
        if (!preg_match($methodPattern, $classContent, $match)) return 1;

        $methodContent = $match[1];

        // Count control flow keywords
        $complexity = 1; // Base complexity
        $keywords = ['if', 'else', 'elseif', 'for', 'foreach', 'while', 'do', 'switch', 'case', 'catch', '&&', '||', '?'];
        foreach ($keywords as $keyword) {
            $complexity += substr_count(strtolower($methodContent), strtolower($keyword));
        }

        return $complexity;
    }

    /**
     * Compute CK and other metrics for all classes
     */
    private function computeMetrics(): void
    {
        foreach ($this->classes as &$class) {
            $class['wmc'] = $this->calculateWMC($class);
            $class['rfc'] = $this->calculateRFC($class);
            $class['lcom'] = $this->calculateLCOM($class);
            $class['cbo'] = $this->calculateCBO($class);
            $class['dit'] = $this->calculateDIT($class);
            $class['noc'] = $this->calculateNOC($class);
            $class['num_methods'] = count($class['methods']);
            $class['num_attributes'] = count($class['attributes']);
            $class['avg_complexity'] = $this->calculateAvgComplexity($class);
            $class['risk_level'] = $this->assessRisk($class);
        }
    }

    /**
     * Calculate Weighted Methods per Class
     */
    private function calculateWMC(array $class): int
    {
        return array_sum(array_column($class['methods'], 'complexity'));
    }

    /**
     * Calculate Response For a Class (simplified)
     */
    private function calculateRFC(array $class): int
    {
        // RFC = number of methods + number of methods called by other classes
        // Simplified: just count methods for now
        return count($class['methods']);
    }

    /**
     * Calculate Lack of Cohesion of Methods (simplified LCOM4)
     */
    private function calculateLCOM(array $class): float
    {
        if (count($class['methods']) < 2) return 0;

        // Simple LCOM: count method pairs that don't share attributes
        $methodAttrUsage = [];
        foreach ($class['methods'] as $method) {
            $methodAttrUsage[$method['name']] = $this->getAttributesUsed($class, $method['name']);
        }

        $unconnectedPairs = 0;
        $totalPairs = 0;
        $methodNames = array_keys($methodAttrUsage);

        for ($i = 0; $i < count($methodNames); $i++) {
            for ($j = $i + 1; $j < count($methodNames); $j++) {
                $totalPairs++;
                $sharedAttrs = array_intersect(
                    $methodAttrUsage[$methodNames[$i]],
                    $methodAttrUsage[$methodNames[$j]]
                );
                if (empty($sharedAttrs)) {
                    $unconnectedPairs++;
                }
            }
        }

        return $totalPairs > 0 ? $unconnectedPairs / $totalPairs : 0;
    }

    /**
     * Get attributes used by a method (simplified)
     */
    private function getAttributesUsed(array $class, string $methodName): array
    {
        // This is a simplification - in real implementation, would need AST parsing
        // For now, assume all attributes are used by all methods
        return $class['attributes'];
    }

    /**
     * Calculate Coupling Between Objects (simplified)
     */
    private function calculateCBO(array $class): int
    {
        $coupling = 0;
        if ($class['extends']) $coupling++;
        $coupling += count($class['implements']);
        // Add coupling from method parameters and return types (simplified)
        return $coupling;
    }

    /**
     * Calculate Depth of Inheritance Tree
     */
    private function calculateDIT(array $class): int
    {
        // Simplified: if extends, assume depth 1, else 0
        return $class['extends'] ? 1 : 0;
    }

    /**
     * Calculate Number of Children
     */
    private function calculateNOC(array $class): int
    {
        $children = 0;
        foreach ($this->classes as $otherClass) {
            if ($otherClass['extends'] === $class['name']) {
                $children++;
            }
        }
        return $children;
    }

    /**
     * Calculate average complexity
     */
    private function calculateAvgComplexity(array $class): float
    {
        if (empty($class['methods'])) return 0;
        return array_sum(array_column($class['methods'], 'complexity')) / count($class['methods']);
    }

    /**
     * Assess risk level
     */
    private function assessRisk(array $class): string
    {
        $risk = 'low';

        if ($class['num_methods'] > 20 || $class['num_attributes'] > 6 ||
            $class['dit'] > 5 || $class['cbo'] > 5 || $class['avg_complexity'] > 10 ||
            $class['lcom'] > 0.8) {
            $risk = 'high';
        } elseif ($class['num_methods'] > 10 || $class['num_attributes'] > 3 ||
                  $class['dit'] > 2 || $class['cbo'] > 2 || $class['avg_complexity'] > 5) {
            $risk = 'medium';
        }

        return $risk;
    }

    /**
     * Get summary statistics
     */
    private function getSummary(): array
    {
        if (empty($this->classes)) {
            return [
                'total_classes' => 0,
                'total_methods' => 0,
                'avg_methods_per_class' => 0,
                'avg_attributes_per_class' => 0,
                'avg_complexity' => 0,
                'high_risk_classes' => 0
            ];
        }

        $totalClasses = count($this->classes);
        $totalMethods = array_sum(array_column($this->classes, 'num_methods'));
        $totalAttributes = array_sum(array_column($this->classes, 'num_attributes'));
        $totalComplexity = array_sum(array_column($this->classes, 'avg_complexity'));
        $highRiskClasses = count(array_filter($this->classes, fn($c) => $c['risk_level'] === 'high'));

        return [
            'total_classes' => $totalClasses,
            'total_methods' => $totalMethods,
            'avg_methods_per_class' => round($totalMethods / $totalClasses, 2),
            'avg_attributes_per_class' => round($totalAttributes / $totalClasses, 2),
            'avg_complexity' => round($totalComplexity / $totalClasses, 2),
            'high_risk_classes' => $highRiskClasses
        ];
    }
}