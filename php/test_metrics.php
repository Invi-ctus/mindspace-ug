<?php
/**
 * Test script for OO Metrics system
 * Tests the analyzer with sample PHP code
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include classes
require_once __DIR__ . '/metrics/MetricsAnalyzer.php';
require_once __DIR__ . '/metrics/RiskEvaluator.php';

// Create a temporary test file with sample classes
$testCode = '<?php
class UserManager {
    private $db;
    private $users = [];
    private $config;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createUser($name, $email) {
        if (empty($name) || empty($email)) {
            throw new Exception("Invalid data");
        }
        // Complex validation logic
        if (strlen($name) < 2) {
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    public function getUser($id) {
        return $this->users[$id] ?? null;
    }

    public function updateUser($id, $data) {
        if ($this->getUser($id)) {
            $this->users[$id] = array_merge($this->users[$id], $data);
            return true;
        }
        return false;
    }

    public function deleteUser($id) {
        if (isset($this->users[$id])) {
            unset($this->users[$id]);
            return true;
        }
        return false;
    }

    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function logAction($action, $userId) {
        // Logging logic
    }
}

class AuthService {
    private $userManager;
    private $session;

    public function __construct(UserManager $userManager) {
        $this->userManager = $userManager;
    }

    public function login($email, $password) {
        $user = $this->userManager->getUser($email);
        if ($user && password_verify($password, $user["password"])) {
            $this->session = $user;
            return true;
        }
        return false;
    }

    public function logout() {
        $this->session = null;
    }

    public function isLoggedIn() {
        return $this->session !== null;
    }
}
';

// Create temporary test file
$testFile = __DIR__ . '/test_classes.php';
file_put_contents($testFile, $testCode);

// Create temporary directory structure
$tempDir = __DIR__ . '/temp_test';
if (!is_dir($tempDir)) {
    mkdir($tempDir);
}
$testFileInTemp = $tempDir . '/TestClass.php';
file_put_contents($testFileInTemp, $testCode);

// Test the analyzer
echo "Testing OO Metrics Analyzer...\n\n";

$analyzer = new MetricsAnalyzer($tempDir);
$results = $analyzer->analyze();

echo "Analysis Results:\n";
echo "================\n";
echo "Total Classes: " . $results['summary']['total_classes'] . "\n";
echo "Total Methods: " . $results['summary']['total_methods'] . "\n";
echo "Avg Methods per Class: " . $results['summary']['avg_methods_per_class'] . "\n";
echo "Avg Attributes per Class: " . $results['summary']['avg_attributes_per_class'] . "\n";
echo "Avg Complexity: " . $results['summary']['avg_complexity'] . "\n";
echo "High Risk Classes: " . $results['summary']['high_risk_classes'] . "\n\n";

echo "Class Details:\n";
echo "==============\n";
foreach ($results['classes'] as $name => $class) {
    echo "Class: $name\n";
    echo "  File: {$class['file']}\n";
    echo "  WMC: {$class['wmc']}\n";
    echo "  RFC: {$class['rfc']}\n";
    echo "  LCOM: " . number_format($class['lcom'], 2) . "\n";
    echo "  CBO: {$class['cbo']}\n";
    echo "  DIT: {$class['dit']}\n";
    echo "  NOC: {$class['noc']}\n";
    echo "  Methods: {$class['num_methods']}\n";
    echo "  Attributes: {$class['num_attributes']}\n";
    echo "  Avg Complexity: " . number_format($class['avg_complexity'], 2) . "\n";
    echo "  Risk Level: {$class['risk_level']}\n\n";
}

// Test risk evaluator
echo "Risk Evaluation:\n";
echo "================\n";
$evaluator = new RiskEvaluator();

foreach ($results['classes'] as $name => $class) {
    $evaluation = $evaluator->evaluateClass($class);
    echo "Class: $name (Risk Score: {$evaluation['risk_score']}, Priority: {$evaluation['priority']})\n";
    if (!empty($evaluation['issues'])) {
        echo "  Issues:\n";
        foreach ($evaluation['issues'] as $issue) {
            echo "    - $issue\n";
        }
    }
    if (!empty($evaluation['suggestions'])) {
        echo "  Suggestions:\n";
        foreach ($evaluation['suggestions'] as $suggestion) {
            echo "    - $suggestion\n";
        }
    }
    echo "\n";
}

// Clean up
unlink($testFile);
unlink($testFileInTemp);
rmdir($tempDir);

echo "Test completed successfully!\n";