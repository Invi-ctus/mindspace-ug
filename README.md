# 🌿 MindSpace

**A Youth Mental Health Support Web Application for Uganda**

MindSpace is a free, anonymous mental health platform designed for Ugandan youth. It provides daily mood tracking, a peer support community board, and curated mental health resources — all without requiring any paid subscription or complex setup.

---

## 📋 Features

| Feature | Description |
|---|---|
| 😊 **Mood Tracker** | Daily emoji-based check-in with optional journal note |
| 📊 **Dashboard** | 7-day mood history table + bar chart (Chart.js) |
| 🤝 **Community Board** | Anonymous peer support posts (latest 20) |
| 📚 **Resources** | Ugandan helplines, coping tips for anxiety/stress/depression |
| 🔒 **Auth** | Secure registration & login with bcrypt password hashing |
| 🛡️ **Admin Panel** | Site-wide stats: users, check-ins, top mood, breakdown chart |

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
http://localhost/mindspace/
```

### 5. Demo account

| Field | Value |
|---|---|
| Email | `demo@mindspaceug.com` |
| Password | `password123` |

---

## 🎨 Design System

| Token | Value | Usage |
|---|---|---|
| Primary green | `#4CAF50` | CTAs, borders, headings |
| Accent yellow | `#FFD54F` / `#FFF9C4` | Highlights, warnings |
| Calm blue | `#42A5F5` / `#E3F2FD` | Info, backgrounds |
| Dark text | `#2d3436` | Body copy |
| Muted text | `#636e72` | Subtitles, labels |
| Font | Poppins (Google Fonts) | All text |

---

## 🔐 Security Notes

- Passwords hashed with **bcrypt** (`password_hash()` / `password_verify()`)
- All DB queries use **PDO prepared statements** (SQL injection protection)
- User input is **sanitised** with `htmlspecialchars()` before display
- Session ID regenerated on every login (session fixation protection)
- Community posts strip executable content at display time

> **Production checklist before going live:**
> - Add admin login to protect `admin/`
> - Enable HTTPS (SSL certificate)
> - Set `display_errors = Off` in `php.ini`
> - Use a strong MySQL password (not root/empty)

---

## 📞 Crisis Resources (Uganda)

| Organisation | Number |
|---|---|
| Befrienders Uganda | **0800 21 21 21** (Toll-free, 24/7) |
| Mental Health Uganda | +256 414 270 050 |
| Uganda Emergency | 999 / 112 |
| Butabika Hospital | +256 312 117 200 |

---

## 📄 License

Open source — free to use and distribute for non-commercial mental health support purposes.

---

*Built with ❤️ for Ugandan youth by the MindSpace team.*
