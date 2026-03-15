# MindSpace — Software Metrics (SWE 2204)

---

## Week 7 — Chapter 5: Software Size Metrics

### Measurement Goal (GQIM Step 5)
- **Object of Interest:** MindSpace source code (PHP and JS files)
- **Purpose:** Evaluate physical and functional size to track productivity and quality
- **Perspective:** From the viewpoint of the developer (Frank Murungi)
- **Environment:** MUST BSE 2024, PHP/MySQL, XAMPP localhost, March 2026

### LOC Measurements
| File | Total LOC | NCLOC | CLOC | Comment Density |
|------|-----------|-------|------|-----------------|
| php/login.php | 65 | 42 | 12 | 18.5% |
| php/register.php | 72 | 47 | 13 | 18.1% |
| php/checkin.php | 58 | 38 | 10 | 17.2% |
| php/community.php | 84 | 55 | 16 | 19.0% |
| php/dashboard_data.php | 91 | 60 | 18 | 19.8% |
| php/db.php | 28 | 16 | 8 | 28.6% |
| admin/admin_data.php | 110 | 72 | 22 | 20.0% |
| js/main.js | 145 | 98 | 28 | 19.3% |
| **TOTAL** | **653** | **428** | **127** | **19.4% avg** |

- **Scale type:** Ratio (true zero = 0 lines = no code)
- **NCLOC formula:** total_loc - cloc - blank_lines
- **Comment density formula:** CLOC / total_loc × 100
- **Target:** Comment density ≥ 20% — currently 19.4% ⚠️ (action: add more comments to checkin.php and login.php)

### Halstead Metrics (php/login.php — worked example)
| Metric | Symbol | Value | Meaning |
|--------|--------|-------|---------|
| Distinct operators | μ1 | 18 | 18 different operators used (if, =, +, ;, etc.) |
| Distinct operands | μ2 | 14 | 14 different variables/constants |
| Total operator occurrences | N1 | 43 | operators appear 43 times total |
| Total operand occurrences | N2 | 29 | operands appear 29 times total |
| Vocabulary | μ = μ1+μ2 | 32 | 32 unique tokens total |
| Program length | N = N1+N2 | 72 | 72 total token occurrences |
| Program volume | V = N×log₂(μ) | 360 | mental effort to write this file |
| Estimated bugs | B = E^(2/3)/3000 | 0.017 | effectively 0 bugs remaining |

**Interpretation:** login.php is small and simple. Volume=360 and B≈0 confirms it is well-contained with very low residual defect risk.

**Criticism of Halstead:** Developed for assembly language (1971). Too fine-grained for modern PHP. Used here as a theoretical framework exercise.

### Function Points
| Component | Feature | Complexity | Weight | FP |
|-----------|---------|-----------|--------|----|
| EI | User Login | Low | 3 | 3 |
| EI | User Registration | Low | 3 | 3 |
| EI | Mood Check-in | Low | 3 | 3 |
| EI | Community Post | Low | 3 | 3 |
| EO | 7-Day Mood Chart | Average | 5 | 5 |
| EO | Admin Stats Panel | Average | 5 | 5 |
| EQ | Community Feed | Low | 3 | 3 |
| EQ | Resources Page | Low | 3 | 3 |
| EQ | Mood History Table | Low | 3 | 3 |
| ILF | Users Table | Low | 7 | 7 |
| ILF | Mood Check-ins | Low | 7 | 7 |
| ILF | Community Posts | Low | 7 | 7 |
| ILF | Resources Table | Low | 7 | 7 |
| **UFC** | | | | **58** |
| **VAF** | 0.65 + 0.01×(sum of 14 factors) | = 1.00 | (average system) | |
| **Final FP** | UFC × VAF | 58 × 1.00 | | **58** |

**EO vs EQ distinction:** The 7-Day Mood Chart is EO (not EQ) because it involves CALCULATIONS — averaging mood ratings, deriving trend data. Community Feed is EQ because it simply retrieves and displays posts with no calculations.

**Interpretation:** 58 Function Points classifies MindSpace as a small-to-medium application. This is a language-independent measure — fair to compare with other student projects regardless of tech stack.

---

## Week 8 — Chapter 6: Structural Complexity Metrics

### Measurement Goal (GQIM Step 5)
- **Object of Interest:** MindSpace PHP module structure
- **Purpose:** Evaluate structural complexity to identify risky modules and guide testing
- **Perspective:** From the viewpoint of the developer and code reviewer
- **Environment:** MUST BSE 2024, PHP/MySQL, XAMPP localhost, March 2026

### Cyclomatic Complexity — v(G) = 1 + d
d = number of decision points (if, elseif, while, for, foreach, case, &&, ||)

| Function | File | d | v(G) | Level | Min Tests |
|----------|------|---|------|-------|-----------|
| handleLogin | php/login.php | 3 | 4 | Simple ✅ | 4 |
| handleRegister | php/register.php | 4 | 5 | Simple ✅ | 5 |
| saveCheckin | php/checkin.php | 2 | 3 | Simple ✅ | 3 |
| handlePost | php/community.php | 3 | 4 | Simple ✅ | 4 |
| fetchPosts | php/community.php | 1 | 2 | Simple ✅ | 2 |
| getMoodHistory | php/dashboard_data.php | 2 | 3 | Simple ✅ | 3 |
| getMoodSummary | php/dashboard_data.php | 3 | 4 | Simple ✅ | 4 |
| getAdminStats | admin/admin_data.php | 5 | 6 | Simple ✅ | 6 |
| renderMoodChart | js/main.js | 4 | 5 | Simple ✅ | 5 |
| validateForm | js/main.js | 3 | 4 | Simple ✅ | 4 |

**All functions are Simple (v(G) ≤ 10) ✅ — no refactoring needed.**
Scale type: Absolute (counting decision paths — 0 = no decisions, pure sequence)

### Cohesion and Coupling
CH = internal_relations / (internal + external) — higher is better
CP = external_relations / (internal + external) — lower is better
IFC = (fan_in × fan_out)² — information flow complexity

| Module | CH | CP | Fan-in | Fan-out | IFC | Assessment |
|--------|----|----|--------|---------|-----|------------|
| php/login.php | 0.75 | 0.25 | 1 | 2 | 4 | Excellent ✅ |
| php/register.php | 0.75 | 0.25 | 1 | 2 | 4 | Excellent ✅ |
| php/checkin.php | 0.75 | 0.25 | 2 | 2 | 16 | Good ✅ |
| php/community.php | 0.60 | 0.40 | 2 | 2 | 16 | Good ✅ |
| php/dashboard_data.php | 0.60 | 0.40 | 3 | 2 | 36 | Acceptable ⚠️ |
| admin/admin_data.php | 0.40 | 0.60 | 2 | 4 | 64 | Monitor ⚠️ |
| js/main.js | 0.57 | 0.43 | 4 | 3 | 144 | Needs attention 🔴 |

**System Cohesion CH = 0.63 ✅ (target ≥ 0.60)**
**System Coupling CP = 0.37 ✅ (target ≤ 0.40)**

**js/main.js has the highest IFC (144)** because it is the central JavaScript file
receiving data from 4 backend sources and updating 3 parts of the UI.
This is expected for a frontend hub — monitored but not alarming.

### GQM Connection (Chapter 3 traceability)
- **Goal 3 (Reliability):** All v(G) ≤ 10 → testable code → fewer defects → reliable app
- **Goal 1 (Reduce Dropout):** Low coupling CP=0.37 → fast bug fixes → less downtime for students
- **Goal 2 (Community):** Good cohesion in community.php CH=0.60 → post/reply work independently

---
