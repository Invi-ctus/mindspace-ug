# 🔬 Empirical Investigation Framework - MindSpace Implementation

**Project:** MindSpace - Youth Mental Health Platform  
**Implementation Status:** ✅ PRODUCTION READY  
**Version:** 2.0.0  
**Date:** March 14, 2026  
**Changelog:** Added multi-experiment A/B engine, dashboard nudge experiment (Experiment 4), full-site telemetry funnel, admin empirical dashboard, and v2 database migration.

---

## 📖 Overview

This document explains how we track user behavior and run experiments on the MindSpace platform to improve mental health support for Ugandan youth.

### What is Empirical Investigation?

Empirical investigation means **making decisions based on actual data** rather than guesses. On our site, this works by:

1. **Tracking how users interact** with features (which buttons they click, which pages they visit)
2. **Running experiments** where different users see different versions of a feature
3. **Analyzing the results** to see which version works better
4. **Using evidence** to decide what improvements to make

### Simple Example: Testing Two Layouts

Imagine we want to know if a new design for the mood check-in page works better than the current one:

- **User A** visits the check-in page → sees **Layout A** (original design) → completes the form
- **User B** visits the check-in page → sees **Layout B** (new design) → completes the form
- We compare: Did more users complete the form with Layout A or Layout B?
- Result: If Layout B has higher completion rates, we adopt it for everyone

This is called an **A/B test**, and it's built into our platform.

---

## 🎯 How It Works (Simple Explanation)

### The Three Main Parts

1. **Tracking User Actions** (Telemetry)
   - When you visit a page, click a button, or spend time reading - we log it
   - All tracking is **anonymous** - we don't know who you are, just what you did
   - Example: "User session X clicked the helpline button" (no name attached)

2. **Running Experiments** (A/B Tests)
   - Different users see different versions of a feature
   - Assignment is random (like flipping a coin)
   - We track which version leads to better outcomes

3. **Analyzing Results**
   - We look at the data to see patterns
   - Example: "80% of users completed the form with Layout B, but only 65% with Layout A"
   - This tells us which design works better

---

## 🗄️ How We Store Data

We use **five** main tables and three analytical views in our database to track everything:

| Table / View | Purpose |
|---|---|
| `telemetry_logs` | Stores every anonymous event (page views, clicks, dwell time, scroll depth, etc.) |
| `ab_test_assignments` | Records which experiment variant each session was assigned to |
| `moods` | Mood check-in records — linked to sessions and A/B variants |
| `experiments_config` *(v2)* | Central registry of all A/B experiments and their status |
| `user_sessions` *(v2)* | Cross-page funnel tracking — entry page, pages visited, conversions |
| `v_experiment_summary` *(view)* | Live conversion rates per experiment and variant |
| `v_daily_page_views` *(view)* | Page views and dwell time per page per day |
| `v_helpline_engagement` *(view)* | Helpline click counts and unique sessions per number |

---

## 🔧 How Tracking Works on the Site

### Real Example: User Visits Check-in Page

Here's exactly what happens step-by-step when a user visits the mood check-in page:

**Step 1: User Arrives at Page**
```
User clicks: http://localhost/mindspace-ug/checkin.html
↓
Browser loads checkin.html
↓
JavaScript runs and asks the server: "Which layout should I show?"
↓
Server flips a coin (random 50/50) and says: "Show Layout B"
↓
Server records in database: "Visitor abc123 saw Layout B"
↓
Page displays Layout B (colorful grid of mood cards)
```

**Step 2: User Interacts with Page**
```
User clicks on "Happy" emoji card
↓
JavaScript sends anonymous log: 
  {
    "event_type": "click",
    "page_url": "/checkin.html",
    "element_id": "mood_happy_b"
  }
↓
Database stores: "Visitor abc123 clicked Happy mood in Layout B"
```

**Step 3: User Submits Form**
```
User fills out mood and clicks "Submit"
↓
Form sends data to php/checkin.php
↓
Server saves the mood response to database
↓
Server updates experiment record: "Visitor abc123 COMPLETED the form"
↓
JavaScript also logs: "Visitor abc123 spent 45 seconds on page"
```

### Another Example: User Clicks Helpline

When a user visits the resources page and clicks a helpline number:

**On resources.html:**
```html
<button class="helpline-btn" data-phone="0800212121">
  Call Befrienders Uganda: 0800 21 21 21
</button>
```

**JavaScript tracks the click:**
```javascript
// When user clicks the button
document.querySelector('.helpline-btn').addEventListener('click', function() {
    // Send anonymous log to server
    fetch('php/telemetry.php', {
        method: 'POST',
        body: JSON.stringify({
            event_type: 'click',
            page_url: '/resources.html',
            element_id: 'helpline_0800212121',
            metadata: {
                phone_number: '0800212121',
                resource_type: 'crisis_line'
            }
        })
    });
});
```

**What gets stored:**
- Anonymous session ID (not the user's identity)
- Which helpline was clicked
- Which page they were on
- Timestamp of the click

**Why we track this:** To understand which resources are most helpful to users.

---

## 🎨 How A/B Testing Looks to Users

### What Users See on Check-in Page

**Layout A (Classic Vertical):**
```
┌────────────────────────────────────┐
│  How are you feeling today?       │
├────────────────────────────────────┤
│  😊 Happy                         │
│  😐 Neutral                       │
│  😔 Sad                           │
│  😰 Anxious                       │
│  😠 Angry                         │
├────────────────────────────────────┤
│  [Optional Note Field]            │
│  [Submit Button]                  │
└────────────────────────────────────┘
```

**Layout B (Compact Grid):**
```
┌────────────────────────────────────┐
│  How are you feeling today?       │
├────────────────────────────────────┤
│  [😊 Happy]  [😐 Neutral]        │
│  [😔 Sad]   [😰 Anxious]         │
│  [😠 Angry]                       │
├────────────────────────────────────┤
│  [Optional Note Field]            │
│  [Colorful Submit Button]         │
└────────────────────────────────────┘
```

### Behind the Scenes

When you visit the page:

1. **Yellow banner appears** (for testing purposes):
   ```
   ⚠️ You are seeing Layout B
   ```

2. **JavaScript fetches your assigned layout:**
   ```javascript
   // Ask server which layout to show
   fetch('php/ab_test_api.php')
     .then(response => response.json())
     .then(data => {
         if (data.variant === 'B') {
             // Show colorful grid layout
             document.getElementById('layout-b').style.display = 'block';
         } else {
             // Show classic vertical layout
             document.getElementById('layout-a').style.display = 'block';
         }
     });
   ```

3. **Your choice is recorded:**
   - If you complete the form → marked as "converted" in the experiment
   - If you leave without submitting → marked as "did not convert"

4. **Later, we compare:**
   - Layout A: 65% completion rate (100 visitors, 65 completed)
   - Layout B: 80% completion rate (100 visitors, 80 completed)
   - **Conclusion:** Layout B works better! Let's use it for everyone.

---

## 📊 Our First Experiment: Testing Check-in Layouts
## 🧪 Active Experiments (v2)

As of v2.0, the platform runs **three concurrent A/B experiments** through a shared multi-experiment engine (`php/ab_test_api.php`). Each experiment is registered in the `experiments_config` table and can be queried independently via `?experiment=<name>`.

### Experiment 1: Check-In Layout (Original)

### The Question We're Asking

**Hypothesis:** Will a colorful grid layout (Layout B) help more people complete their mood check-ins compared to the classic vertical list (Layout A)?

### How It Works

**Setup:**
- Every visitor to the check-in page is randomly assigned to see either Layout A or Layout B
- Like flipping a coin: heads = Layout A, tails = Layout B
- We need 800 total users (400 per layout) to get reliable results

**What We Measure:**
1. **Completion Rate:** How many users finish the form vs. leave without submitting
2. **Time Spent:** How long it takes to complete
3. **Note Usage:** Whether users write optional notes

**Success Criteria:**
- Layout B needs to have at least 10% higher completion rate to be considered "better"
- Example: If Layout A has 65% completion, Layout B needs 75% or higher

### Timeline

- **Weeks 1-2:** Collect baseline data (how many users normally complete check-ins)
- **Weeks 3-6:** Run the experiment (track 800 users)
- **Week 7:** Analyze results and decide which layout to use going forward

### How We Analyze Results

**Simple comparison:**
```
Layout A Results:
- Total users: 400
- Completed forms: 260
- Completion rate: 65%

Layout B Results:
- Total users: 400
- Completed forms: 320
- Completion rate: 80%

Difference: +15% (Layout B performs better!)
```

**Statistical check:**
- We run a statistical test to make sure the difference isn't just luck
- If the result is "statistically significant" (p < 0.05), we can be confident Layout B truly works better
- Then we update the site to use Layout B for everyone

---

## 🛡️ Privacy: What We Track and What We Don't

### ✅ What We DO Track (Anonymous)

We collect information about **what you do** on the site:

- Which pages you visit (e.g., `/checkin.html`, `/resources.html`)
- Which buttons you click (e.g., helpline numbers, mood selections)
- How long you spend on each page
- Whether you complete forms (like the mood check-in)
- Which experiment version you saw (Layout A or B)

**All of this is anonymous** - we assign you a random session ID like "abc123" but don't know your name, email, or who you are.

### ❌ What We DON'T Track

We do NOT collect:

- Your name, email, or any personal information
- Your IP address or location
- Your device information or browser fingerprint
- The actual content of your journal notes (we only track whether you wrote one or not)
- Any activity outside of our MindSpace website

### Your Rights

You have the right to:

- **Know what we track:** Read this document!
- **Opt out:** Enable "Do Not Track" in your browser settings
- **Request deletion:** Contact us to delete your data
- **Access your data:** Ask us what data we have about your session

---

## 📈 How We Use the Data

### Everyday Questions We Can Answer

With our tracking system, we can answer questions like:

**1. How many people use our site daily?**
```sql
Count unique session IDs per day
Result: "We had 47 unique visitors yesterday"
```

**2. Which helpline numbers are most popular?**
```sql
Count clicks on each helpline button
Result: "Befrienders Uganda (0800 21 21 21) was clicked 23 times this week"
```

**3. Are users completing the mood check-in?**
```sql
Compare form submissions vs. page views
Result: "72% of visitors complete the check-in form"
```

**4. Which layout works better?**
```sql
Compare completion rates between Layout A and Layout B
Result: "Layout B has 15% higher completion rate - let's use it!"
```

### Making Decisions Based on Data

**Before empirical investigation:**
- Team argues about which design is better
- loudest voice wins
- No way to know if change actually helped

**After empirical investigation:**
- Run experiment with real users
- Collect data on actual behavior
- Make decision based on evidence
- Know whether change improved the site

---

## 🔮 Future Experiments We Plan

### Experiment 2 (Planned): Resource Page Engagement

**Question:** Do users who spend more time on the resources page actually call helplines more often?

**How we'll test:**
- Track how long users spend on resources page
- Track which users click helpline numbers
- Look for pattern: Does longer time = more clicks?

**Why it matters:** If spending time reading resources leads to help-seeking, we should make resources more engaging.

### Experiment 3: Journal Notes and Return Visits
### Experiment 3 (Active): Resources Page Layout

**Question:** Does a card-grid layout for helpline listings get more clicks than the current list layout?

**How we test:**
- Variant A: Current list layout with phone links (control)
- Variant B: Card grid layout with prominent call-to-action buttons
- Tracked in `ab_test_assignments` under `resources_layout_test`
- Target: 400 users per variant

**Why it matters:** If a more visual layout makes helplines easier to find, more users in crisis will reach out.

---

### Experiment 3b (Planned): Journal Notes and Return Visits

**Question:** Do users who write journal notes come back to the site more often?

**How we'll test:**
- Track which users write notes during check-in
- Track which users return to the site within 7 days
- Compare: Do note-writers return more than non-writers?

**Why it matters:** If writing notes helps people engage more, we could encourage note-taking.

### Experiment 4: Dashboard Visualizations
### Experiment 4 (Active): Dashboard Nudge Banner

**Question:** Does showing a streak-goal banner and motivational nudge on the dashboard increase return visits compared to no banner?

**How we test:**
- Variant A: Standard dashboard — static mood summary chart only (control)
- Variant B: Adds a green nudge banner at the top of the dashboard: streak goal, motivational message, and a "Check In" CTA button
- The CTA click is tracked as a `click` event (`nudge_banner_cta`)
- Page dwell time is also captured on unload for both variants
- Tracked in `ab_test_assignments` under `dashboard_nudge_test`
- Target: 400 users per variant

**What the nudge looks like (Variant B):**
```
┌────────────────────────────────────────────────────┐
│  🔥  Keep your streak going!                       │
│      Check in today to maintain your streak.       │
│      Every day counts.           [+ Check In]      │
└────────────────────────────────────────────────────┘
```

**Why it matters:** Return visits mean more consistent mood tracking, which leads to better mental health awareness for the user.

---

## 🚀 Getting Started (For Team Members)

### How to See Empirical Investigation in Action

**Step 1: Set up the v1 database**
1. Open phpMyAdmin in your browser
2. Go to the SQL tab
3. Paste the contents of `database/migration_telemetry.sql`
4. Click "Go" to run the migration

**Step 1b: Apply the v2 migration (new in v2.0)**
1. In phpMyAdmin, go to the SQL tab
2. Paste the contents of `database/migration_empirical_v2.sql`
3. Click "Go" to run it
4. This adds: expanded event types, `experiments_config`, `user_sessions`, and three analytical views

**Step 2: Verify it's working**
1. Visit: `http://localhost/mindspace-ug/install_check.php`
2. You should see green checkmarks ✅ for all tests

**Step 3: Try the A/B test yourself**
1. Visit: `http://localhost/mindspace-ug/checkin.html`
2. You'll see either Layout A or Layout B (with yellow banner showing which)
3. Clear your browser session and reload - you might see the other layout!
4. Fill out the mood form
5. Check the database - your action was logged!

**Step 4: See the data**
Open phpMyAdmin and run:
```sql
-- See recent tracking logs (now includes scroll_depth, experiment_exposure, etc.)
SELECT * FROM telemetry_logs ORDER BY created_at DESC LIMIT 10;

-- See experiment results so far
SELECT variant, COUNT(*) as users, SUM(converted) as completions
FROM ab_test_assignments
WHERE experiment_name = 'checkin_layout_test'
GROUP BY variant;

-- NEW: See all active experiments and live conversion rates
SELECT * FROM v_experiment_summary;

-- NEW: See page funnel (which pages have most unique visitors)
SELECT * FROM v_daily_page_views ORDER BY day DESC, unique_sessions DESC;

-- NEW: See which helplines are clicked most
SELECT * FROM v_helpline_engagement;

-- NEW: See dashboard nudge experiment results
SELECT variant, COUNT(*) as users, SUM(converted) as completions,
       ROUND(SUM(converted)*100.0/COUNT(*),1) AS conversion_rate
FROM ab_test_assignments
WHERE experiment_name = 'dashboard_nudge_test'
GROUP BY variant;
```

**Step 5: View the admin empirical dashboard**
1. Visit: `http://localhost/mindspace-ug/admin/index.html`
2. Scroll down to see:
   - **A/B Experiment Results** — live conversion rates for all 3 experiments, with leading variant highlighted and sample size progress
   - **Page Funnel** — unique sessions and average dwell time per page (last 30 days)
   - **Daily Active Sessions** — line chart of the last 14 days of activity

### Files Involved in Tracking

**Backend (PHP):**
- `php/telemetry.php` - Receives and saves tracking data (now supports 9 event types)
- `php/ab_test_api.php` - Multi-experiment engine: assigns variants, logs exposure events
- `php/checkin.php` - Saves mood check-ins with experiment info
- `admin/admin_data.php` - Returns experiment results, page funnel, and daily activity to the admin panel

**Frontend (HTML/JavaScript):**
- `checkin.html` - Has both Layout A and Layout B, shows correct one based on assignment
- `resources.html` - Tracks which helpline buttons users click
- `dashboard.html` - Participates in `dashboard_nudge_test`; logs page views and dwell time
- `community.html` - Now logs page views and dwell time (new in v2.0)
- `profile.html` - Now logs page views and dwell time (new in v2.0)
- `admin/index.html` - Admin empirical dashboard: experiment cards, page funnel, activity chart

**Database (SQL):**
- `database/telemetry_schema.sql` - Creates the tracking tables
- `database/migration_telemetry.sql` - Updates existing databases
- `database/migration_empirical_v2.sql` - *(new)* Adds `experiments_config`, `user_sessions`, expanded event types, and 3 analytical views

---

**Verification:**
- `install_check.php` - Tests that everything is set up correctly

---

## 📚 Related Documentation

Other documents in this project:

- **[measurement_theory.md](measurement_theory.md)** - Lists all the metrics we track with examples
- **[README.md](../README.md)** - Main project documentation with privacy section

*(Note: We've simplified our documentation to focus on what's actually in the project)*

---

## 🎓 Why This Matters

### Better Decisions Through Evidence

Our empirical investigation approach helps us:

1. **Stop guessing** what works → **Start knowing** what works
2. **Make small improvements** based on real user behavior
3. **Avoid wasting time** on features that don't help users
4. **Prove that changes** actually make the site better

### Real Impact on Mental Health Support

By continuously testing and improving our platform:

- More young people will complete mood check-ins → better emotional awareness
- Easier access to helplines → more people getting professional help
- Better user experience → more return visits → ongoing support

Every improvement we make through data-driven decisions helps us serve Ugandan youth better.  

---

## 📞 Questions or Concerns?

**For users:** If you have questions about what data we collect or want to opt out, contact us at mindspace@example.com.

**For team members:** If you want to add new tracking or run a new experiment:
1. Read this document to understand the current system
2. Discuss with the team whether the tracking is necessary
3. Ensure it follows our privacy guidelines (anonymous, minimal data)
4. Document the new experiment in future updates to this file

**Next review:** We update this document quarterly as we add new experiments and improve our tracking.

---

## 📋 Appendix: Files Used in Empirical Investigation

### Core Code Files (6 files)

1. **php/telemetry.php**
   - Receives tracking data from the website
   - Validates and saves it to database
   - Keeps everything anonymous and secure
   - **v2:** Accepts 9 event types: `page_view`, `click`, `dwell_time`, `form_interaction`, `resource_access`, `scroll_depth`, `search`, `navigation`, `experiment_exposure`

2. **php/ab_test_api.php**
   - **v2 rewrite:** Multi-experiment engine — supports any number of named experiments
   - Call with `?experiment=<name>` to get assignment for a specific experiment
   - Logs an `experiment_exposure` telemetry event on every assignment lookup
   - Uses `random_int()` for cryptographically secure 50/50 split

3. **database/telemetry_schema.sql**
   - Creates the database tables for tracking
   - Sets up indexes for fast queries
   - Defines what data we store

4. **database/migration_telemetry.sql**
   - Updates existing databases with new tracking tables
   - Safe to run on databases that already have data

5. **database/migration_empirical_v2.sql** *(new)*
   - Expands `event_type` ENUM with 4 new values
   - Creates `experiments_config` table — single source of truth for all experiments
   - Creates `user_sessions` table — cross-page funnel tracking
   - Creates 3 analytical SQL views for fast reporting
   - Seeds the three active experiments into `experiments_config`

6. **admin/admin_data.php** *(enhanced)*
   - Returns A/B experiment results (conversion rates per variant for all experiments)
   - Returns page funnel data (unique sessions + avg dwell per page, last 30 days)
   - Returns top clicked elements (last 30 days)
   - Returns daily active sessions (last 14 days)

### Modified Website Pages (5 files, was 2)

1. **checkin.html**
   - Shows different layouts based on A/B test assignment
   - Tracks which mood users select
   - Logs form completions and time spent

2. **resources.html**
   - Tracks which helpline numbers users click
   - Logs page views and time spent reading resources
   - Uses anonymous beacon API for accurate tracking

3. **dashboard.html** *(enhanced)*
   - Participates in `dashboard_nudge_test` (Experiment 4)
   - Variant B: injects a streak-goal nudge banner with a Check-In CTA
   - Logs `page_view` with variant context on load
   - Logs `click` when nudge CTA is clicked
   - Logs `dwell_time` via `sendBeacon` on page unload

4. **community.html** *(new tracking)*
   - Logs `page_view` on load with `page_type: community`
   - Logs `dwell_time` on unload

5. **profile.html** *(new tracking)*
   - Logs `page_view` on load with `page_type: profile`
   - Logs `dwell_time` on unload

### Verification Tool (1 file)

1. **install_check.php**
   - Tests that database tables exist
   - Verifies tracking is working
   - Shows green checkmarks if everything is OK

### Admin Empirical Dashboard (1 file)

1. **admin/index.html** *(enhanced)*
   - **A/B Experiment Results section:** shows conversion rates for all active experiments, highlights the leading variant, and displays sample-size progress toward 800 users
   - **Page Funnel section:** table of top pages by unique sessions with average dwell time
   - **Daily Active Sessions chart:** line chart of the last 14 days powered by Chart.js

**Total:** Approximately 1,400+ lines of code for the entire empirical investigation system (v2)
