# Measuring internal product attributes: Software Size 

---

## 1. Overview

This document describes how Chapter 5 (Measuring Internal Product
Attributes: Software Size) was implemented and applied to MindSpace.

Software size was measured using three approaches:
1. **LOC (Lines of Code)** — physical size of each PHP/JS file
2. **Halstead Metrics** — complexity of the code vocabulary
3. **Function Points** — user-visible functionality delivered

All measurements are implemented in:
- `metrics/size_dashboard.php` — interactive LOC and FP dashboard
- `metrics/halstead_metrics.php` — Halstead metrics page
- `database/week7_8_metrics.sql` — all metric data stored in MySQL

---

## 2. Measurement Goal (GQIM Step 5)

| Component | Description |
|-----------|-------------|
| **Object of Interest** | MindSpace PHP and JS source files |
| **Purpose** | Evaluate physical and functional size to track productivity and quality over sprints |
| **Perspective** | From the viewpoint of the developer (Frank Murungi) |
| **Environment** | MUST BSE 2024, PHP/MySQL, XAMPP localhost, March 2026 |

---

## 3. LOC Measurements

### What is LOC?
Lines of Code (LOC) measures the physical size of the software.

| Term | Meaning |
|------|---------|
| **NCLOC** | Non-Commented Lines — actual working code |
| **CLOC** | Commented Lines — explanation lines only |
| **Total LOC** | NCLOC + CLOC + Blank lines |
| **Comment Density** | CLOC / Total LOC × 100 |

### Scale Type
- **Scale:** Ratio (true zero = 0 lines = no code exists)

### Results

| File | Total LOC | NCLOC | CLOC | Blank | Comment Density | Size |
|------|-----------|-------|------|-------|-----------------|------|
| php/login.php | 65 | 42 | 12 | 11 | 18.46% | Small ✅ |
| php/register.php | 72 | 47 | 13 | 12 | 18.06% | Small ✅ |
| php/checkin.php | 58 | 38 | 10 | 10 | 17.24% | Small ✅ |
| php/community.php | 84 | 55 | 16 | 13 | 19.05% | Small ✅ |
| php/dashboard_data.php | 91 | 60 | 18 | 13 | 19.78% | Small ✅ |
| php/db.php | 28 | 16 | 8 | 4 | 28.57% | Small ✅ |
| admin/admin_data.php | 110 | 72 | 22 | 16 | 20.00% | Medium ⚠️ |
| js/main.js | 145 | 98 | 28 | 19 | 19.31% | Medium ⚠️ |
| **TOTAL** | **653** | **428** | **127** | **98** | **19.4% avg** | |

### Observation
Comment density average is 19.4% — slightly below the recommended
target of ≥ 20%. Action: add more inline comments to checkin.php
and login.php to reach the target in the next sprint.

### LOC-Derived Metrics
- **Productivity** = NCLOC / development hours → track sprint by sprint
- **Quality** = defects / KLOC → target: < 5 defects per 1000 lines
- **Documentation** = CLOC / LOC → current: 19.4% (target: ≥ 20%)

---

## 4. Halstead Metrics

### What are Halstead Metrics?
Maurice Halstead (1971) proposed that every program is made of:
- **Operators** (μ1): keywords and symbols (if, =, +, &&, function, etc.)
- **Operands** (μ2): variables, constants, string values

### Key Formulas

| Formula | Meaning |
|---------|---------|
| μ = μ1 + μ2 | Program vocabulary |
| N = N1 + N2 | Program length (total token occurrences) |
| V = N × log₂(μ) | Program volume (mental effort to write it) |
| B = E^(2/3) / 3000 | Estimated bugs remaining at delivery |

### Results (Sample — php/login.php)

| Metric | Symbol | Value | Meaning |
|--------|--------|-------|---------|
| Distinct operators | μ1 | 18 | 18 different operators used |
| Distinct operands | μ2 | 14 | 14 different variables/constants |
| Total operator occurrences | N1 | 43 | operators appear 43 times |
| Total operand occurrences | N2 | 29 | operands appear 29 times |
| Vocabulary | μ | 32 | 32 unique tokens |
| Program length | N | 72 | 72 total token occurrences |
| Program volume | V | 360 | mental effort to write this file |
| Estimated bugs | B | 0.017 | effectively 0 remaining bugs |

### All Files Summary

| File | μ1 | μ2 | N | V | Est. Bugs B |
|------|----|----|---|---|-------------|
| php/login.php | 18 | 14 | 72 | 360 | 0.017 |
| php/register.php | 20 | 16 | 82 | 424 | 0.019 |
| php/checkin.php | 15 | 11 | 58 | 273 | 0.014 |
| admin/admin_data.php | 24 | 19 | 105 | 570 | 0.022 |

### Interpretation
All MindSpace PHP files have very low estimated bug counts (B < 0.03).
This confirms small, well-structured modules with minimal residual
defect risk.

### Academic Note
Halstead's work was designed for assembly language (1971) and is
considered too fine-grained for modern PHP. Applied here as a
theoretical framework exercise as required by SWE 2204.

---

## 5. Function Points

### What are Function Points?
Function Points (FP) measure HOW MUCH MindSpace does for users —
completely independent of programming language.

### The 5 Components

| Type | Meaning | Weight (Low) |
|------|---------|-------------|
| **EI** — External Input | Data coming IN from user | 3 |
| **EO** — External Output | Data OUT with calculations | 4-5 |
| **EQ** — External Inquiry | Data OUT without calculations | 3 |
| **ILF** — Internal Logical File | Data stored inside MindSpace | 7 |
| **EIF** — External Interface File | Data from another system | 5 |

### Key Distinction: EO vs EQ
- **EO** involves mathematical calculations or derived data
  → Example: 7-Day Mood Chart calculates averages using Chart.js
- **EQ** is simple retrieval with no calculations
  → Example: Community Feed just fetches and displays posts

### Function Point Count

| Feature | Type | Complexity | Weight | FP |
|---------|------|-----------|--------|----|
| User Login | EI | Low | 3 | 3 |
| User Registration | EI | Low | 3 | 3 |
| Mood Check-in | EI | Low | 3 | 3 |
| Community Post | EI | Low | 3 | 3 |
| 7-Day Mood Chart | EO | Average | 5 | 5 |
| Admin Stats Panel | EO | Average | 5 | 5 |
| Community Feed | EQ | Low | 3 | 3 |
| Resources Page | EQ | Low | 3 | 3 |
| Mood History Table | EQ | Low | 3 | 3 |
| Users Table | ILF | Low | 7 | 7 |
| Mood Check-ins Table | ILF | Low | 7 | 7 |
| Community Posts Table | ILF | Low | 7 | 7 |
| Resources Table | ILF | Low | 7 | 7 |

### Final Calculation

| Step | Value |
|------|-------|
| UFC (Unadjusted Function Points) | **58** |
| VAF (Value Adjustment Factor) | **1.00** |
| **Final FP = UFC × VAF** | **58** |

### VAF Explanation
VAF = 0.65 + 0.01 × (F1 + F2 + ... + F14)
For an average system all 14 factors ≈ 2.5 → sum = 35 → VAF = 1.00

**58 Function Points** classifies MindSpace as a small-to-medium
application. Language-independent — fair to compare with other
student projects regardless of tech stack.

---

## 6. GQM Connection (Chapter 3 Traceability)

| Metric | GQM Goal Connection |
|--------|-------------------|
| LOC growth | Goal 1: track codebase growth sprint by sprint |
| Comment density 19.4% | Goal 3: maintainability — below target, action needed |
| FP = 58 | Confirms small-to-medium application scope |
| Halstead B ≈ 0 | Goal 3: very low residual defect risk |

---

## 7. Implementation

All size metrics are stored in MySQL and visualized in:
- **`metrics/size_dashboard.php`** — LOC cards, table, Chart.js bar chart, FP breakdown
- **`metrics/halstead_metrics.php`** — Halstead calculations per file
- **`database/week7_8_metrics.sql`** — loc_measurements and fp_measurements tables

---

## 8. Scale Types Summary

| Metric | Scale Type | Reason |
|--------|-----------|--------|
| LOC | Ratio | True zero = no code |
| NCLOC | Ratio | True zero = no working code |
| Comment Density % | Ratio | 0% = no comments |
| Halstead Volume V | Ratio | 0 = empty program |
| Function Points FP | Ratio | 0 = no functionality |

---
