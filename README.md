# 🌿 MindSpace

**A Youth Mental Health Support Web Application for Uganda**

MindSpace is a free, anonymous mental health platform designed for Ugandan youth. It provides daily mood tracking, a peer support community board, and curated mental health resources — all without requiring any paid subscription or complex setup.

---

## 📋 Features

| Feature                     | Description                                                  |
| --------------------------- | ------------------------------------------------------------ |
| 😊**Mood Tracker**    | Daily emoji-based check-in with optional journal note        |
| 📊**Dashboard**       | 7-day mood history table + bar chart (Chart.js)              |
| 🤝**Community Board** | Anonymous peer support posts (latest 20)                     |
| 📚**Resources**       | Ugandan helplines, coping tips for anxiety/stress/depression |
| 🔒**Auth**            | Secure registration & login with bcrypt password hashing     |
| 🛡️**Admin Panel**   | Site-wide stats: users, check-ins, top mood, breakdown chart |

---

## 🗂️ Project Structure

```
mindspace/
├── index.html              # Landing/Home page
├── register.html           # User registration form
├── login.html              # User login form
├── dashboard.html          # User mood dashboard (Chart.js)
├── checkin.html            # Daily mood check-in
├── community.html          # Anonymous support board
├── resources.html          # Crisis resources & self-help tips
├── admin/
│   ├── index.html          # Admin statistics panel
│   └── admin_data.php      # Admin JSON data API
├── css/
│   └── style.css           # Main stylesheet (Poppins, warm palette)
├── js/
│   └── main.js             # Frontend interactivity
├── php/
│   ├── db.php              # PDO database connection
│   ├── register.php        # Registration handler
│   ├── login.php           # Login handler
│   ├── logout.php          # Session logout
│   ├── checkin.php         # Save mood check-in
│   ├── community.php       # Post/fetch community messages
│   └── dashboard_data.php  # Dashboard JSON API
├── database/
│   └── mindspace.sql       # Full database schema + sample data
│   └── migration_cost_metrics.sql # Cost tracking schema + demo seed data
├── metrics/
│   ├── cost_dashboard.php  # Chapter 7 cost metrics dashboard (COCOMO/SLIM)
│   └── cost_data.php       # Cost metrics JSON API for dashboard
├── METRICS.md              # (reserved for metrics)
└── README.md               # This file
```

---

## ⚙️ Setup Instructions (XAMPP / WAMP)

### 1. Place the project folder

Copy `mindspace/` into your web server's root directory:

- **XAMPP (Windows):** `C:\xampp\htdocs\mindspace\`
- **WAMP (Windows):** `C:\wamp64\www\mindspace\`
- **Linux/Mac XAMPP:** `/opt/lampp/htdocs/mindspace/`

### 2. Import the database

1. Start Apache and MySQL in XAMPP/WAMP Control Panel
2. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
3. Click **Import** tab
4. Choose: `database/mindspace.sql`
5. Click **Go**

This creates the `mindspace` database with all tables and sample data.

### 3. Configure the database connection

Open `php/db.php` and edit if your credentials differ:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mindspace');
define('DB_USER', 'root');
define('DB_PASS', '');        // Leave empty for default XAMPP
```

### 4. Launch the app

Open your browser and visit:

```
http://localhost/mindspace-ug/
```

### 4.1 Enable Software Cost Metrics (optional but recommended)

To enable the admin cost dashboard (planned vs actual cost, estimate variance, rework %, FP/hour):

1. Open phpMyAdmin
2. Select database: `mindspace_db`
3. Import file: `database/migration_cost_metrics.sql`
4. Refresh `admin/index.html`

If you skip this step, the admin panel still works and shows a setup hint for cost metrics.

### 4.2 Open Cost Metrics Dashboard

After import, open:

```
http://localhost/mindspace-ug/metrics/cost_dashboard.php
```

Dashboard includes:
- Cost KPI cards (actual cost, variance, rework, cost/FP)
- Cost trend chart and feature breakdown table
- COCOMO (Basic/Intermediate) calculator
- COCOMO II (Application Composition, Early Design, Post-Architecture) calculator
- SLIM (Putnam-style constraint) calculator

### 5. Demo account

| Field    | Value                    |
| -------- | ------------------------ |
| Email    | `demo@mindspaceug.com` |
| Password | `password123`          |

---

## 🎨 Design System

| Token         | Value                     | Usage                   |
| ------------- | ------------------------- | ----------------------- |
| Primary green | `#4CAF50`               | CTAs, borders, headings |
| Accent yellow | `#FFD54F` / `#FFF9C4` | Highlights, warnings    |
| Calm blue     | `#42A5F5` / `#E3F2FD` | Info, backgrounds       |
| Dark text     | `#2d3436`               | Body copy               |
| Muted text    | `#636e72`               | Subtitles, labels       |
| Font          | Poppins (Google Fonts)    | All text                |

---

## 🔐 Security Notes

- Passwords hashed with **bcrypt** (`password_hash()` / `password_verify()`)
- All DB queries use **PDO prepared statements** (SQL injection protection)
- User input is **sanitised** with `htmlspecialchars()` before display
- Session ID regenerated on every login (session fixation protection)
- Community posts strip executable content at display time

> **Production checklist before going live:**
>
> - Add admin login to protect `admin/`
> - Enable HTTPS (SSL certificate)
> - Set `display_errors = Off` in `php.ini`
> - Use a strong MySQL password (not root/empty)

---

## 📊 Data & Privacy

### Empirical Research Framework

MindSpace implements a **data-driven approach** to continuously improve mental health support effectiveness through anonymous behavioral telemetry and controlled A/B testing.

#### What We Collect (2nd-Degree Data)

We collect **anonymous behavioral data** that helps us understand how users interact with the platform:

| Data Type | Purpose | Example |
|-----------|---------|----------|
| **Page Views** | Understand which resources are most accessed | `/resources.html` visited 150 times today |
| **Dwell Time** | Measure engagement and content relevance | Average 2.5 minutes spent on check-in page |
| **Click Patterns** | Identify helpful helplines and features | Befrienders Uganda clicked 45 times this week |
| **Form Completion Rates** | Optimize user experience | 78% completion rate for mood check-in |
| **A/B Test Assignments** | Compare design variants | Layout B shows 12% higher engagement |

#### What We DO NOT Collect

❌ **Personally Identifiable Information (PII)** - No names, emails, or locations in telemetry  
❌ **IP Addresses** - We don't track network identifiers  
❌ **Device Fingerprints** - No browser or hardware profiling  
❌ **Journal Content** - Your mood notes remain private (we only track whether you wrote one)  
❌ **Geolocation Data** - No GPS or location tracking

### A/B Testing (Controlled Experiments)

We run **randomized controlled trials** to test design improvements:

- **Current Experiment:** Check-in layout comparison (Layout A vs Layout B)
- **Assignment:** Random 50/50 split - you see one variant consistently
- **Purpose:** Determine which layout helps users complete their mood tracking more easily
- **Opt-Out:** You can use either layout - both are fully functional

See **[METRICS.md](METRICS.md)** for detailed information about active experiments and hypotheses.

### Telemetry Implementation

**Technical Safeguards:**

```sql
-- Telemetry schema (anonymized)
CREATE TABLE telemetry_logs (
    session_id      VARCHAR(64)  -- Anonymous token, not linked to user account
    event_type      ENUM('page_view', 'click', 'dwell_time', ...)
    page_url        VARCHAR(255) -- Which page was viewed
    dwell_seconds   INT          -- Time spent (capped at 1 hour)
    metadata        JSON         -- Contextual data (no PII)
);
```

**Data Retention:**
- Telemetry logs: **90 days** rolling window
- A/B test assignments: **Permanent** (for longitudinal analysis)
- Aggregated metrics: **Indefinite** (anonymized statistics)

### Ethical Compliance

✅ **Informed Consent:** By using MindSpace, you consent to anonymous data collection for service improvement  
✅ **Minimal Risk:** All experiments use existing evidence-based designs - no manipulation of mental health content  
✅ **Right to Withdraw:** Close your browser to stop data collection; contact us to request data deletion  
✅ **Do Not Track:** Respect browser DNT settings when configured  
✅ **Transparency:** Full methodology documented in METRICS.md

### How Your Data Improves MindSpace

The anonymous behavioral data we collect directly informs:

1. **Feature Prioritization:** Most-used features get priority development attention
2. **UX Improvements:** High drop-off pages trigger redesign efforts
3. **Resource Allocation:** Popular helplines get prominent placement
4. **Evidence-Based Design:** A/B test winners become the new default

### Privacy-First Analytics Dashboard

We provide aggregate metrics accessible to administrators:

```sql
-- Example: Daily active users (no individual identification possible)
SELECT DATE(created_at) as date, COUNT(DISTINCT session_id) as dau
FROM telemetry_logs
GROUP BY DATE(created_at);
```

**Individual Session Data Access:** Restricted to lead developers only  
**Aggregate Reporting:** Public metrics shared in METRICS.md

### GDPR Compliance Notes

While MindSpace targets Ugandan youth and may fall outside EU jurisdiction, we adhere to GDPR principles:

- **Lawful Basis:** Legitimate interest (service improvement) + consent
- **Purpose Limitation:** Data used only for stated research purposes
- **Data Minimization:** Only essential behavioral metrics collected
- **Storage Limitation:** 90-day retention for telemetry, 30-day purge cycle
- **Integrity & Confidentiality:** PDO prepared statements, input sanitization

### Questions or Concerns?

If you have questions about our data practices:

- Review full methodology: **[METRICS.md](METRICS.md)**
- View current experiments: Section "Active Hypotheses" in METRICS.md
- Request data deletion: Contact mindspace@example.com
- Opt-out options: Disable JavaScript or use Do Not Track browsersetting

---

## 📞 Crisis Resources (Uganda)

| Organisation         | Number                                    |
| -------------------- | ----------------------------------------- |
| Befrienders Uganda   | **0800 21 21 21** (Toll-free, 24/7) |
| Mental Health Uganda | +256 414 270 050                          |
| Uganda Emergency     | 999 / 112                                 |
| Butabika Hospital    | +256 312 117 200                          |

---

## 📄 License

Open source — free to use and distribute for non-commercial mental health support purposes.

---

*Built with ❤️ for Ugandan youth by the MindSpace team.*
