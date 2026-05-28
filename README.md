# Adildata — Airtime, Data Resell & Bill Payments

A full-featured VTU (Virtual Top-Up) and bill payment platform for Nigerian users, operating at **https://adildata.com.ng**.

## What It Does

- **Airtime & Data Topup** — Buy airtime and data bundles for all Nigerian networks (MTN, Glo, Airtel, 9mobile)
- **Bill Payments** — Electricity, cable TV (DStv, GOtv, Startimes), exam pins (WAEC, NECO, JAMB)
- **Wallet System** — Fund and manage wallet balance, transfer between users
- **Reseller / Agent Network** — Onboard sub-agents and manage downlines
- **Verification Services** — NIN validation, BVN verification, CAC registration
- **Admin Dashboard** — Full control over plans, users, transactions, discounts, and settings

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, Bootstrap 4, jQuery |
| Backend | PHP 7.4 (OOP, MVC-style) |
| Database | MySQL (via MySQLi) |
| Payment | Paystack, Monnify, Flutterwave |
| VTU API | VTPass, Reloadly, Bardetech |
| Hosting | cPanel (server357.web-hosting.com) |

## Project Structure

```
public_html/
├── index.html              # Landing page
├── about.html              # About page
├── contact-us.html         # Contact page
├── faq.html                # FAQ page
├── policy.html             # Privacy policy
├── reseller.html           # Reseller info page
├── make-money.html         # Earnings info page
├── assets/                 # CSS, JS, images for landing pages
├── easyfinder/
│   ├── app/
│   │   ├── Controller/     # Business logic controllers
│   │   ├── DB/             # Database connection (Conn.php, db_credentials.ini)
│   │   ├── C_Base.php      # Base class
│   │   └── C_Model.php     # Model base class
│   ├── dashboard/          # All PHP dashboard pages
│   │   ├── login.php       # User login
│   │   ├── register.php    # User registration
│   │   ├── index.php       # Dashboard home
│   │   ├── topup.php       # Airtime topup
│   │   ├── data-topup.php  # Data bundle purchase
│   │   ├── cable-tv.php    # Cable TV subscription
│   │   ├── electricity-bill.php  # Electricity payment
│   │   ├── wallet-transaction.php # Wallet history
│   │   ├── layout/         # Shared header, sidebar, footer templates
│   │   └── ...             # Other service & admin pages
│   ├── inc/
│   │   ├── config.inc.php          # App bootstrap & OOP init
│   │   ├── siteconfig.inc.php      # Site settings from DB (SITE_URL, SITE_TITLE, etc.)
│   │   ├── payment-api.inc.php     # Payment gateway handlers
│   │   └── user_session.inc.php    # Session management
│   └── vendor/             # Composer dependencies
└── adildata.sql            # Full database dump
```

## Database

- **Host:** localhost
- **Database:** adiliqgs_adildata
- **Credentials:** stored in `easyfinder/app/DB/db_credentials.ini`

To restore the database, import `adildata.sql` via phpMyAdmin or:
```bash
mysql -u adiliqgs_adildata -p adiliqgs_adildata < adildata.sql
```

## Site Settings (stored in DB — `edutech_settings` table)

| Key | Value |
|-----|-------|
| `website_url` | https://adildata.com.ng/ |
| `website_title` | Adildata |
| `site_logo` | logo.png |
| `paystack_api` | (set in admin panel) |

## Dashboard Login

```
URL:  https://adildata.com.ng/easyfinder/dashboard/login
```

## Deployment

All files live in `/home/adiliqgs/public_html/` on the cPanel server.

To deploy updates:
1. Push changes to this GitHub repo
2. Upload changed files via cPanel File Manager or FTP to `public_html/`

## Branding

This platform was rebranded from **RahauSub** → **Adildata** in May 2026.  
All URLs, page titles, and database settings now reflect **adildata.com.ng**.
