Paradigm chosen: Goal-Question-Metrics
---
## Goal: Evaluate the user engagement of the MindSpace application from the perspective of the system administrator.

---

## Questions
1. (Q1)How many users log into the system each day?
2. (Q2)What is the average time users spend in the system?
3. (Q3)Which system features are most used?

---

## Metrics
1. (M1)Daily Active Users: The number of unique users who use the system in a single day.
2. (M2)Average Session Duration: The average time users spend in the application during a session.
3. (M3)Feature Usage Frequency: How often specific features of the system are used.

---

## GQM Graph
```
                Goal
   ----------------------------------------
    |                |                   |
    Q1               Q2                  Q3
    |                |                   |
    M1               M2                  M3
```
## Implementation

    M1: The system records user activity in a log table whenever a user logs into the application or performs an action. Each record typically includes the user email and the timestamp of the activity. To compute the Daily Users Metric, the system counts the number of unique user emails that appear in the activity log for a given date.
---
    M2: To calculate the average session duration, the system records the login  time and the logout time for each user session. 
    Average session duration = Logout time - Login time
---
    M3: Each time a user accesses or performs an action using a feature, the system records the interaction in a feature usage log. The metric is calculated by counting the number of times each feature appears in the log.

---

## Cost Metrics Extension (Implemented)

### Goal: Evaluate development cost efficiency of MindSpace features from the perspective of the technical lead.

### Questions
1. (CQ1) How accurate are our effort and cost estimates?
2. (CQ2) Which features consume the highest implementation cost?
3. (CQ3) How much rework effort is being spent?
4. (CQ4) What is our productivity relative to function points?

### Metrics
1. (CM1) Estimation Variance (%)
2. (CM2) Actual Cost by Feature
3. (CM3) Rework Rate (%)
4. (CM4) FP per Hour and Cost per FP

### Formulas
1. Estimation Variance:
    ((Actual Cost - Planned Cost) / Planned Cost) * 100
2. Rework Rate:
    (Rework Hours / Actual Hours) * 100
3. FP per Hour:
    Total Function Points / Total Actual Hours
4. Cost per FP:
    Total Actual Cost / Total Function Points

### Data Sources
1. `cost_tracking` table (database/migration_cost_metrics.sql)
2. `fp_measurements` table (database/week7_8_metrics.sql)
3. `admin/admin_data.php` JSON API
4. `admin/index.html` cost dashboard widgets and charts