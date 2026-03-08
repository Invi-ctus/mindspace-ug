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