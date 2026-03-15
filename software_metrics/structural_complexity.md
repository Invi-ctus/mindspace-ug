# Measuring internal product attributes: Software Complexity 
---

## 1. Overview

This document describes how Chapter 6 (Measuring Internal Product
Attributes: Structural Complexity) was implemented and applied to MindSpace.

Structural complexity was measured using three approaches:
1. **Cyclomatic Complexity v(G)** — number of independent paths through each function
2. **Cohesion CH** — how focused each module is on one task
3. **Coupling CP** — how dependent modules are on each other

All measurements are implemented in:
- `metrics/complexity_dashboard.php` — interactive complexity dashboard
- `database/week7_8_metrics.sql` — cyclomatic_measurements and module_complexity tables

---

## 2. Measurement Goal (GQIM Step 5)

| Component | Description |
|-----------|-------------|
| **Object of Interest** | MindSpace PHP module structure |
| **Purpose** | Evaluate structural complexity to identify risky modules and guide testing |
| **Perspective** | From the viewpoint of the developer and code reviewer |
| **Environment** | MUST BSE 2024, PHP/MySQL, XAMPP localhost, March 2026 |

---

## 3. Control Flow Graph (CFG)

### What is a CFG?
A Control Flow Graph CFG = {N, A} maps program execution:
- **N** = set of nodes (each = a block of code)
- **A** = set of arcs (arrows showing where control goes next)
- **Predicate nodes** = decision points with more than 1 outgoing arrow (if, while, for)

### Basic Control Structures in MindSpace PHP
| BCS Type | MindSpace Example |
|----------|------------------|
| Sequence | `$user = findUser($username);` |
| Selection | `if ($password_matches) { login() } else { error() }` |
| Iteration | `foreach ($posts as $post)` |
| Function call | `validatePassword($input, $hash)` |

---

## 4. Cyclomatic Complexity

### What is Cyclomatic Complexity?
v(G) measures the number of independent paths through a function.

### Two Formulas (give same result)
```
Formula 1 (graph):  v(G) = e - n + 2p
Formula 2 (simple): v(G) = 1 + d
```
Where:
- **e** = number of edges (arrows in CFG)
- **n** = number of nodes (code blocks)
- **p** = connected components (usually 1)
- **d** = number of decision points (if, elseif, while, for, foreach, case)

### Scale Type
- **Scale:** Absolute (counting decision paths — 0 = pure sequence, no decisions)

### Complexity Scale
| v(G) Range | Level | Meaning |
|-----------|-------|---------|
| 1 — 10 | Simple ✅ | Easy to test |
| 11 — 20 | Moderate ⚠️ | Manageable |
| 21 — 50 | Complex 🔴 | Needs refactoring |
| > 50 | Untestable ❌ | Must split |

### Results

| Function | File | d | v(G) | Level | Min Test Cases |
|----------|------|---|------|-------|---------------|
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

### Observation
**All MindSpace functions are in the Simple range (v(G) ≤ 10) ✅**
No refactoring needed. All functions are testable with ≤ 6 test cases.

### What v(G) Means for Testing
Example: handleLogin has v(G) = 4
- There are 4 independent paths through the function
- Minimum 4 test cases needed to test every path
- Simple range = easy to test and maintain

---

## 5. Cohesion

### What is Cohesion?
Cohesion measures how focused a module is on ONE clear task.
**Goal: HIGH cohesion** — like a hospital Maternity Ward that only
does maternity work, nothing else.

### Formula
```
CH(module) = internal_relations / (internal_relations + external_relations)
```

### 7 Types of Cohesion (Best to Worst)
| Type | Meaning | Example |
|------|---------|---------|
| Functional ✅ BEST | One single function | validatePassword() |
| Sequential | Output feeds next step | read→process→save |
| Communicational | Multiple functions on same data | all work on $user |
| Procedural | Related to one procedure | open, read, close file |
| Temporal | Run at same time | all startup functions |
| Logical | Logically similar | all print functions |
| Coincidental ❌ WORST | No relationship | random utilities |

### Scale Type
- **Scale:** Ratio (0 = no internal relations, 1 = fully internal)

### Results

| Module | Internal | External | CH | Assessment |
|--------|----------|----------|----|------------|
| php/login.php | 3 | 1 | 0.75 | Excellent ✅ |
| php/register.php | 3 | 1 | 0.75 | Excellent ✅ |
| php/checkin.php | 3 | 1 | 0.75 | Good ✅ |
| php/community.php | 3 | 2 | 0.60 | Good ✅ |
| php/dashboard_data.php | 3 | 2 | 0.60 | Acceptable ⚠️ |
| admin/admin_data.php | 2 | 3 | 0.40 | Monitor ⚠️ |
| js/main.js | 4 | 3 | 0.57 | Monitor ⚠️ |

**System Cohesion CH = 0.63 ✅ (target: ≥ 0.60)**

---

## 6. Coupling

### What is Coupling?
Coupling measures how dependent modules are on each other.
**Goal: LOW coupling** — like earphones with a jack (loose) vs
soldered earphones (tight). Loose = easy to change independently.

### Formula
```
CP(module) = external_relations / (internal_relations + external_relations)
```

### 5 Types of Coupling (Best to Worst)
| Type | Code | Meaning |
|------|------|---------|
| Independence R0 ✅ | No connection | Modules don't talk |
| Data coupling R1 ✅ | Pass parameters | `savePost($title, $content)` |
| Stamp coupling R2 | Pass records | `savePost($post_object)` |
| Control coupling R3 ⚠️ | Pass flags | `process($user, $is_admin=true)` |
| Content coupling R4 ❌ | Direct access | Module reads another's internals |

### Scale Type
- **Scale:** Ratio (0 = fully independent, 1 = fully dependent)

### Results

| Module | Internal | External | CP | Risk |
|--------|----------|----------|----|------|
| php/login.php | 3 | 1 | 0.25 | Low ✅ |
| php/register.php | 3 | 1 | 0.25 | Low ✅ |
| php/checkin.php | 3 | 1 | 0.25 | Low ✅ |
| php/community.php | 3 | 2 | 0.40 | Low ✅ |
| php/dashboard_data.php | 3 | 2 | 0.40 | Medium ⚠️ |
| admin/admin_data.php | 2 | 3 | 0.60 | Medium ⚠️ |
| js/main.js | 4 | 3 | 0.43 | High 🔴 |

**System Coupling CP = 0.37 ✅ (target: ≤ 0.40)**

---

## 7. Information Flow (Fan-in / Fan-out)

### What is IFC?
- **Fan-in** = how many modules call/depend on this module
- **Fan-out** = how many modules this module calls/depends on
- **IFC = (fan_in × fan_out)²**

### Results

| Module | Fan-in | Fan-out | IFC | Assessment |
|--------|--------|---------|-----|------------|
| php/login.php | 1 | 2 | 4 | Low — isolated ✅ |
| php/register.php | 1 | 2 | 4 | Low — isolated ✅ |
| php/checkin.php | 2 | 2 | 16 | Low ✅ |
| php/community.php | 2 | 2 | 16 | Low ✅ |
| php/dashboard_data.php | 3 | 2 | 36 | Medium ⚠️ |
| admin/admin_data.php | 2 | 4 | 64 | Medium ⚠️ |
| js/main.js | 4 | 3 | 144 | High — monitor 🔴 |

**js/main.js has the highest IFC (144)** — it is the central frontend
hub connecting 4 backend sources to 3 UI areas. Expected for a
central JS file — monitored but not alarming.

---

## 8. Design Quality Assessment

| Design Goal | Target | Current | Status |
|-------------|--------|---------|--------|
| All functions v(G) ≤ 10 | 100% | 10/10 ✅ | Excellent |
| System Cohesion CH | ≥ 0.60 | 0.63 | ✅ Good |
| System Coupling CP | ≤ 0.40 | 0.37 | ✅ Good |
| Functions needing refactoring | 0 | 0 | ✅ None |

---

## 9. Trade-off Observation

There is always a trade-off between cyclomatic and data complexity:
- **Higher cyclomatic** (more branches) → **lower data structure complexity**
- **Lower cyclomatic** (fewer branches) → **higher data structure complexity**

MindSpace achieves a good balance — all functions v(G) ≤ 6 while
keeping data structures simple and readable.

---

## 10. GQM Connection (Chapter 3 Traceability)

| Metric | GQM Goal Connection |
|--------|-------------------|
| All v(G) ≤ 10 | Goal 3 (Reliability): testable code → fewer defects |
| CP = 0.37 ✅ | Goal 1 (Reduce Dropout): fast bug fixes → less downtime |
| CH = 0.63 ✅ | Goal 2 (Community): focused modules → predictable behaviour |
| IFC of js/main.js = 144 | Monitor closely — central dependency point |

---

## 11. Implementation

All complexity metrics are stored in MySQL and visualized in:
- **`metrics/complexity_dashboard.php`** — cyclomatic table, cohesion/coupling charts, IFC table, design quality assessment
- **`database/week7_8_metrics.sql`** — cyclomatic_measurements and module_complexity tables

---

