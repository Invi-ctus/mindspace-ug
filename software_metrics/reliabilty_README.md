System Reliability Module – MindSpace

This module was added to track how reliable the MindSpace system is during use. It records system failures, calculates basic reliability metrics, and displays them on a dashboard.

1. Database Table

A table called system_failures is used to store all failures that happen in the system.

Fields:

id – unique identifier
failure_type – type of error (e.g. login_error, db_error)
module – part of the system where the error occurred
timestamp – time the failure happened
resolution_time – time taken to fix the issue (in seconds, optional)
description – short explanation of the error
2. Reliability Functions

A file reliability.php was created to handle all failure logging and calculations.

Main functions include:

logFailure($type, $module, $description)
Used to record a failure into the database
Called whenever an error occurs in the system
markFailureResolved($id)
Updates a failure after it has been fixed
Used to calculate repair time
getTotalFailures()
Returns the total number of failures recorded
getFailuresByModule()
Shows which parts of the system fail most
getFailuresByType()
Groups failures by their type
getFailureTrend()
Shows how failures change over time
3. Metrics API

A file metrics/reliability_data.php was created to calculate and return reliability metrics in JSON format.

The following metrics are calculated:

Mean Time To Failure (MTTF)
Mean Time To Repair (MTTR)
Mean Time Between Failures (MTBF)
Failure intensity (failures per day)
System availability
4. Dashboard

A dashboard page metrics/reliability_dashboard.php was created to display:

Total number of failures
Reliability metrics (MTTF, MTTR, MTBF, availability)
Failure trends over time (line chart)
Failures grouped by module and type

Charts are implemented using Chart.js.

5. Integration into System

Failure logging was added to key backend files:

login.php – logs login and validation errors
checkin.php – logs check-in related errors
community.php – logs community post errors

Example:

logFailure('db_error', 'dashboard', 'Error fetching user data');
6. How Metrics Are Calculated
MTTF = total time between failures ÷ number of failures
MTTR = average resolution time
MTBF = MTTF + MTTR
Availability = MTTF / (MTTF + MTTR)
Failure Intensity = number of failures ÷ time period
7. Usage

To view the dashboard, open:

http://localhost/mindspace-ug/metrics/reliability_dashboard.php

To get metrics as JSON:

metrics/reliability_data.php
8. Notes
If no failures are logged, metrics will return 0 (this is normal)
More data gives more accurate results
This module helps identify which parts of the system need improvement
9. Conclusion

This module applies concepts from Software Metrics (Lecture 9) by tracking failures and calculating reliability values based on real system data. It helps monitor system performance and supports decision-making during development.