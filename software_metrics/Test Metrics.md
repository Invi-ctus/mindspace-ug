# Test Metrics

This part of the project covers the implementation of Software Test Metrics based on Lecture 10.

The goal was to apply some of the testing concepts in a simple way within the MindSpace system.

## What was added

For this lecture, we implemented a basic test case tracking system and used it to calculate some test metrics.

The following were added:
- A database table to store test cases
- An admin page to view test results
- Calculations for pass, fail and pending rates
- A simple idea of feature coverage

## Files created

- `database/test_cases.sql`  
- `admin/test_metrics.php`  

## Test Cases Table

A new table called `test_cases` was created to store manual test cases.

Fields included:
- id (primary key, auto increment)
- feature_name (e.g. Login, Mood Tracker, Community)
- test_description (what is being tested)
- status (pass, fail, pending)
- created_at (timestamp)

The SQL file is located at:

`database/test_cases.sql`

## Admin Test Metrics Page

A new admin page `test_metrics.php` was added inside the admin folder.

This page:
- connects to the database using the existing connection file  
- retrieves all test cases  
- counts total number of tests  
- separates them into passed, failed and pending  

## Metrics Implemented

Using the stored test cases, the following metrics were calculated:

- **Test Pass Rate** = (passed / total) * 100  
- **Test Failure Rate** = (failed / total) * 100  
- **Test Pending Rate** = (pending / total) * 100  

These are displayed on the page together with the raw numbers.

A small check was added so that if there are no test cases, the system does not crash (avoiding division by zero).

## Feature Coverage (Simplified)

Instead of using real code coverage tools, a simple version was implemented.

- The system counts how many different features appear in the test cases  
- Total features were assumed to be 5 (Login, Mood Tracker, Dashboard, Community, Resources)  
- Coverage is calculated as:

coverage = (number of tested features / total features) * 100  

This gives a rough idea of how much of the system has been tested.

## Output

The admin page displays:
- total number of test cases  
- number of passed, failed and pending tests  
- calculated percentages  
- a table showing all test cases  

The design is basic since the focus was on functionality.

## How to run

1. Import the SQL file using phpMyAdmin  
2. Insert some sample test cases manually  
3. Open the page in browser:

`admin/test_metrics.php`

4. Verify that the counts and percentages update correctly

## Challenges

One small issue was handling cases where there are no test records, which was fixed by checking before doing calculations.

## Conclusion

This implementation shows a simple way of applying software test metrics in a real system.  
It is not fully automated testing, but it demonstrates how test results can be stored and analyzed.