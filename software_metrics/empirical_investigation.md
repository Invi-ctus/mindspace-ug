# 🔬 Empirical Investigation Framework - MindSpace Implementation

**Project:** MindSpace - Youth Mental Health Platform  
**Implementation Status:** ✅ PRODUCTION READY  
**Version:** 1.0.0  
**Date:** March 8, 2026

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

We use three main tables in our database to track everything:

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

### Experiment 2: Resource Page Engagement

**Question:** Do users who spend more time on the resources page actually call helplines more often?

**How we'll test:**
- Track how long users spend on resources page
- Track which users click helpline numbers
- Look for pattern: Does longer time = more clicks?

**Why it matters:** If spending time reading resources leads to help-seeking, we should make resources more engaging.

### Experiment 3: Journal Notes and Return Visits

**Question:** Do users who write journal notes come back to the site more often?

**How we'll test:**
- Track which users write notes during check-in
- Track which users return to the site within 7 days
- Compare: Do note-writers return more than non-writers?

**Why it matters:** If writing notes helps people engage more, we could encourage note-taking.

### Experiment 4: Dashboard Visualizations

**Question:** Do interactive charts on the dashboard bring users back more than static images?

**How we'll test:**
- Show half of users interactive charts they can click
- Show other half static images
- Track which group returns more often

**Why it matters:** Helps us decide if building fancy visualizations is worth the effort.

---

## 🚀 Getting Started (For Team Members)

### How to See Empirical Investigation in Action

**Step 1: Set up the database**
1. Open phpMyAdmin in your browser
2. Go to the SQL tab
3. Paste the contents of `database/migration_telemetry.sql`
4. Click "Go" to run the migration

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
-- See recent tracking logs
SELECT * FROM telemetry_logs ORDER BY created_at DESC LIMIT 10;

-- See experiment results so far
SELECT variant, COUNT(*) as users, SUM(converted) as completions
FROM ab_test_assignments
WHERE experiment_name = 'checkin_layout_test'
GROUP BY variant;
```

### Files Involved in Tracking

**Backend (PHP):**
- `php/telemetry.php` - Receives and saves tracking data
- `php/ab_test_api.php` - Assigns users to experiment groups
- `php/checkin.php` - Saves mood check-ins with experiment info

**Frontend (HTML/JavaScript):**
- `checkin.html` - Has both Layout A and Layout B, shows correct one based on assignment
- `resources.html` - Tracks which helpline buttons users click

**Database (SQL):**
- `database/telemetry_schema.sql` - Creates the tracking tables
- `database/migration_telemetry.sql` - Updates existing databases

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

### Core Code Files (4 files)

1. **php/telemetry.php**
   - Receives tracking data from the website
   - Validates and saves it to database
   - Keeps everything anonymous and secure

2. **php/ab_test_api.php**
   - Decides which users see Layout A vs Layout B
   - Random 50/50 assignment (like coin flip)
   - Remembers user's assignment during their session

3. **database/telemetry_schema.sql**
   - Creates the database tables for tracking
   - Sets up indexes for fast queries
   - Defines what data we store

4. **database/migration_telemetry.sql**
   - Updates existing databases with new tracking tables
   - Safe to run on databases that already have data

### Modified Website Pages (2 files)

1. **checkin.html**
   - Shows different layouts based on A/B test assignment
   - Tracks which mood users select
   - Logs form completions and time spent

2. **resources.html**
   - Tracks which helpline numbers users click
   - Logs page views and time spent reading resources
   - Uses anonymous beacon API for accurate tracking

### Verification Tool (1 file)

1. **install_check.php**
   - Tests that database tables exist
   - Verifies tracking is working
   - Shows green checkmarks if everything is OK

**Total:** About 800 lines of code for the entire empirical investigation system
