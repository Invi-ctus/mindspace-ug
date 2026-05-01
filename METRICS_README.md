# Object-Oriented Metrics Module for MindSpace

This module implements comprehensive object-oriented software metrics analysis for the MindSpace project, focusing on code quality assessment and maintainability evaluation.

## Features

### Core CK Metrics
- **WMC (Weighted Methods per Class)**: Measures class complexity
- **RFC (Response For a Class)**: Counts methods that can be invoked
- **LCOM (Lack of Cohesion of Methods)**: Measures method relatedness
- **CBO (Coupling Between Objects)**: Counts class couplings
- **DIT (Depth of Inheritance Tree)**: Measures inheritance depth
- **NOC (Number of Children)**: Counts immediate subclasses

### Additional Metrics
- Number of Classes
- Number of Methods per Class
- Number of Attributes per Class
- Cyclomatic Complexity per method
- Average Method Complexity
- LOC (Lines of Code) per class

### Risk Assessment
- Automatic risk scoring (Low/Medium/High)
- Refactoring suggestions
- Project health evaluation

## Architecture

### Backend Components

#### 1. MetricsAnalyzer (`php/metrics/MetricsAnalyzer.php`)
- Scans PHP source files for classes
- Extracts methods, attributes, and inheritance relationships
- Computes all CK and additional metrics
- Uses regex-based parsing for PHP code analysis

#### 2. ReportService (`php/metrics/ReportService.php`)
- Handles database operations for metrics reports
- Stores analysis results as JSON
- Provides retrieval methods for latest/history data

#### 3. RiskEvaluator (`php/metrics/RiskEvaluator.php`)
- Evaluates classes against quality thresholds
- Generates risk scores and refactoring suggestions
- Assesses overall project health

### Database Schema

```sql
CREATE TABLE metrics_reports (
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
```

### API Endpoints

#### POST `/php/metrics_run.php`
Runs a new metrics analysis and stores results.

#### GET `/php/metrics_latest.php`
Returns the most recent analysis report.

#### GET `/php/metrics_history.php`
Returns all historical reports.

#### GET `/php/metrics_class.php?class=ClassName`
Returns detailed metrics for a specific class.

## Frontend Dashboard

### Main Dashboard (`admin/metrics.html`)
- Overview cards showing key metrics
- Interactive charts (WMC distribution, risk breakdown)
- Classes table with risk indicators
- Real-time analysis trigger

### Documentation Page (`admin/metrics_about.html`)
- Detailed explanation of each metric
- Threshold definitions
- Risk assessment guidelines

## Installation & Setup

1. **Database Migration**:
   ```bash
   # Run the migration script via web browser or PHP CLI
   php php/metrics_migration.php
   ```

2. **Access the Dashboard**:
   - Navigate to `admin/metrics.html`
   - Click "Run Analysis" to perform initial scan
   - View results in charts and tables

## Usage

### Running Analysis
1. Open the admin metrics dashboard
2. Click the "Run Analysis" button
3. Wait for the scan to complete
4. Review results in the dashboard

### Interpreting Results

#### Risk Levels
- **Low Risk (Green)**: Healthy class design
- **Medium Risk (Yellow)**: Needs attention
- **High Risk (Red)**: Requires refactoring

#### Common Issues & Solutions
- **High WMC**: Split large classes into smaller ones
- **High LCOM**: Group related methods, separate concerns
- **High CBO**: Reduce dependencies, use interfaces
- **Deep DIT**: Favor composition over deep inheritance

## Technical Details

### Parsing Approach
- Uses regex patterns to identify PHP classes
- Extracts method signatures and attributes
- Calculates complexity using control flow keywords
- Handles inheritance and interface relationships

### Limitations
- Regex-based parsing may miss complex PHP constructs
- Cyclomatic complexity calculation is simplified
- Does not analyze runtime coupling
- Limited to PHP files only

## Future Enhancements

1. **Advanced Parsing**: Integrate PHP-Parser for AST-based analysis
2. **More Metrics**: Add additional OO metrics (NPM, etc.)
3. **Historical Trends**: Time-series analysis of metrics
4. **Export Features**: PDF reports and CSV downloads
5. **Integration**: CI/CD pipeline integration
6. **Multi-language**: Support for JavaScript, Python, etc.

## Testing

Run the test script to verify functionality:
```bash
php php/test_metrics.php
```

This creates sample classes and demonstrates the analysis pipeline.