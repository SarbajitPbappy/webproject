# HostelEase — Web-Based Hostel Management System

![Version](https://img.shields.io/badge/version-1.1.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.x-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-blueviolet)

**HostelEase** is a production-oriented hostel management application built with PHP (custom MVC), MySQL, Bootstrap 5, and vanilla JavaScript. It supports student and room lifecycle management, fee payments, complaints with SLA tracking, notices, audit logging, payroll workflows, and a simple finance ledger.

For **architecture, security details, environment variables, and troubleshooting**, see [DOCUMENTATION.md](DOCUMENTATION.md).

---

## Table of contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration (`.env`)](#configuration-env)
- [Database setup](#database-setup)
- [Run locally](#run-locally)
- [Docker](#docker)
- [Deploy on Render](#deploy-on-render)
- [Default credentials](#default-credentials)
- [Demo accounts (full dataset)](#demo-accounts-full-dataset)
- [User roles](#user-roles)
- [Project structure](#project-structure)
- [Security highlights](#security-highlights)
- [Routes (quick reference)](#routes-quick-reference)
- [Contributing](#contributing)
- [License](#license)

---

## Features

### Operations

- **Multi-role access** — `super_admin`, `admin`, `student`, `staff` with role-aware menus and redirects.
- **Landing page** — Public marketing/home; logged-in users are sent to the correct dashboard.
- **Students** — CRUD, photos and documents, search; default password when none is set at creation.
- **Rooms & allocations** — Inventory, capacity, allocate / transfer / vacate, waitlist.
- **Automation** — When a student vacates and a room has capacity, the next waitlisted student can be allocated automatically (see [DOCUMENTATION.md](DOCUMENTATION.md)).
- **Payments** — Fee structures, manual recording (admin), online portal (students and staff/admin **with a linked student profile**), **pay all slips in one checkout**, **advance prepay** for a billing month before slips exist, **fee balance sheet**, receipts.
- **Billing intelligence** — Monthly issue matches room rent to allocation tier; **skips or reduces** lines when the student **already prepaid** that fee/month; **yearly fees** (e.g. **annual maintenance**) use **one charge per calendar year**: fully paid in-year → no new slip; partially paid → slip for the **balance** only.
- **Room lifecycle** — Waitlist-only **new allocation**; **transfer** with optional transfer fee; **student room change / cancellation requests** with admin queue; **auto room move** on approved change when a bed exists; **billing credits** when changing room tier.
- **Payroll** — Staff apply for monthly slips; admin review/approval; **super admin** distributes salary and records expenses in the ledger.
- **Super Admin notifications** — Pending payroll items are surfaced in the navbar/sidebar (badge/dot).
- **Complaints** — Ticketing, assignment, SLA (high 24h / medium 72h).
- **Notices** — Pinned announcements.
- **Audit log** — Super Admin–visible activity trail.
- **Profiles** — All users can update **name, phone, photo** (and related fields); **student ID / login identifiers** are not user-editable in self-service flows.

### Security & quality

- PDO prepared statements, CSRF on POST forms, XSS-safe output helpers, bcrypt passwords, login throttling, session hardening, validated uploads, `.htaccess` routing and upload protections.

---

## Tech stack

| Layer | Technology |
|--------|------------|
| Backend | PHP 8.2+ (custom MVC, no framework) |
| Database | MySQL 8.x (local or managed, e.g. Aiven) |
| Front end | HTML5, CSS3, Bootstrap 5, DataTables, vanilla JS |
| Server | Apache + `mod_rewrite` (XAMPP, Docker, or PaaS) |

---

## Prerequisites

- **PHP 8.2+** with extensions: `pdo_mysql`, `mbstring`, `fileinfo` (for uploads).
- **MySQL** (local or remote).
- **Composer** — not required; no Composer dependencies in core app.
- **Git** (optional).
- **Apache** with `AllowOverride All` **or** PHP built-in server for development (see below).

---

## Installation

### 1. Get the code

```bash
git clone https://github.com/SarbajitPbappy/webproject.git
cd webproject/hostelease
```

If your clone layout differs, `cd` into the folder that contains `index.php`, `config/`, and `app/`.

### 2. Copy environment template

```bash
cp .env.example .env
```

Edit `.env` with your database credentials and optional `BASE_URL`. Never commit `.env`.

### 3. Writable paths

```bash
mkdir -p public/uploads/students public/uploads/documents logs
chmod -R u+rwX public/uploads logs
```

---

## Configuration (`.env`)

| Variable | Purpose |
|----------|---------|
| `APP_ENV` | `development` or `production`. Affects error detail and `BASE_URL` behavior. |
| `BASE_URL` | Optional. Full base URL with trailing slash (e.g. `http://localhost:8000/`). In production, avoid leaving this as `localhost`; if unset, the app derives the URL from the request (HTTPS-aware behind proxies). |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | MySQL connection. |
| `DB_SSL` | `true` or `false`. Use `true` for providers that require TLS (e.g. Aiven). |
| `SUPER_ADMIN_PASS_HASH` | Optional bcrypt hash for a legacy/virtual super-admin login path; prefer seeding a real `super_admin` user in the database. |

See [DOCUMENTATION.md](DOCUMENTATION.md) for full behavior of `BASE_URL` and production checks.

---

## Database setup

1. **Import schema** (from the `hostelease` directory):

   ```bash
   mysql -h YOUR_HOST -P YOUR_PORT -u YOUR_USER -p YOUR_DATABASE < database/hostelease.sql
   ```

2. **Seed the Super Admin** (once):

   ```bash
   php database/seeds/admin_seed.php
   ```

3. **Payroll tables** (if you use **My payroll** / pay slips — safe for existing data):

   ```bash
   php database/migrations/migrate_payroll_tables_safe.php
   ```

   This creates `staff_details` and `pay_slips` if missing and adds default rows for staff/warden users. The older `migrate_payroll.php` also repopulates `transactions` from payments and **truncates** some tables — use only if you intend that reset.

4. **Optional** (run if your database was created before these features):

   ```bash
   php database/migrations/migrate_user_notifications.php
   php database/migrations/migrate_room_requests_transfer_fee.php
   php database/migrations/migrate_student_billing_credit.php
   php database/migrations/migrate_students_registration_profile.php
   ```

### Optional: full demo (recommended for presentations)

From the `hostelease` directory, after schema import and `admin_seed.php`:

```bash
# In-app notifications (billing alerts, etc.)
php database/migrations/migrate_user_notifications.php

# Student self-registration fields (if your DB predates this column)
php database/migrations/migrate_students_registration_profile.php

# 2 wardens, 10 staff, 100 students — all @hallportal.demo.bd
php database/seeds/bangladesh_demo_seed.php

# 10-floor DEMO tower + allocate all 100 students (optional sample billing)
php database/seeds/demo_hostel_building_allocate.php --force
# With sample meal/utility slips + notifications for current month:
# php database/seeds/demo_hostel_building_allocate.php --force --with-billing
```

Use **[Demo accounts (full dataset)](#demo-accounts-full-dataset)** below for logins. Super Admin remains `admin@hostelease.com` (separate from the demo domain).

---

## Run locally

The app expects front-controller routing (`?url=...` or pretty URLs via rewrite). **Apache with `mod_rewrite`** is the supported setup for clean paths.

### Option A — XAMPP / Apache (recommended)

Point the virtual host document root to the `hostelease` folder, enable `mod_rewrite`, set `AllowOverride All`, and set `BASE_URL` to match (e.g. `http://localhost/hostelease/`).

### Option B — PHP built-in server (limited)

The PHP built-in server **does not** read `.htaccess`, so paths like `/auth/login` are not rewritten automatically. From the `hostelease` directory you can still run:

```bash
php -S localhost:8000 -t .
```

Then open URLs **with the query string**, for example:

`http://localhost:8000/index.php?url=auth/login`

Set in `.env`:

```env
APP_ENV=development
BASE_URL=http://localhost:8000/
```

---

## Docker

A `Dockerfile` is included (PHP 8.2 + Apache). It enables `mod_rewrite` and `AllowOverride All` so the front controller and `.htaccess` rules work.

Build and run from the `hostelease` directory:

```bash
docker build -t hostelease .
docker run -p 8080:80 -e DB_HOST=... -e DB_PASS=... -e APP_ENV=production hostelease
```

Pass database and app settings via `-e` or an env file supported by your platform.

---

## Deploy on Render

1. **Repository** — Connect the GitHub repo; set the **Root Directory** to `hostelease` if the app lives in that subfolder.
2. **Environment** — Set at least: `APP_ENV=production`, `DB_*`, `DB_SSL=true` if your provider requires SSL, and `DB_PASS`.
3. **`BASE_URL`** — Either **omit** it (recommended) so the app builds URLs from `https://your-service.onrender.com/`, or set explicitly to your public URL with a trailing slash. Do **not** set production to a `localhost` URL.
4. **Build / start** — Use the Docker runtime with the provided `Dockerfile`, or a PHP/Apache service with document root = `hostelease` and rewrite rules enabled.
5. **Database** — Import `hostelease.sql`, run `admin_seed.php`, then `migrate_payroll.php` if needed.

---

## Default credentials

After `admin_seed.php`:

| Role | Email | Password |
|------|--------|----------|
| Super Admin | `admin@hostelease.com` | `Admin@123` |

Change this password immediately after first login.

Students created by admins without a password use **`Student@123`** by default (change after first login).

---

## Demo accounts (full dataset)

These accounts exist after you run **`database/seeds/bangladesh_demo_seed.php`** (see [Database setup](#database-setup)). Every address uses the domain **`@hallportal.demo.bd`** so you can find or delete demo users easily.

| Role | How many | Login email(s) | Password |
|------|----------|------------------|----------|
| **Super Admin** | 1 (separate seed) | `admin@hostelease.com` | `Admin@123` |
| **Warden (admin)** | 2 | `kamrul.hasan.warden@hallportal.demo.bd`<br>`farzana.chowdhury.warden@hallportal.demo.bd` | `Warden@123` |
| **Staff** | 10 | `staff01@hallportal.demo.bd` through `staff10@hallportal.demo.bd` | `Staff@123` |
| **Student** | 100 | `stu001.hall@hallportal.demo.bd` … `stu100.hall@hallportal.demo.bd` (three-digit index) | `Student@123` |

**One-demo walkthrough**

1. Log in as a **warden** — manage students, rooms, allocations, issue monthly bills.  
2. Log in as **`stu001.hall@hallportal.demo.bd`** / `Student@123` — student dashboard, **Pay fees online** (pay all slips in one checkout), notifications when new bills are issued.  
3. Log in as **Super Admin** — payroll review, finances, users (not the student portal).

After **`demo_hostel_building_allocate.php`**, demo students are placed in **`DEMO-*`** rooms across 10 floors; use **`--with-billing`** to create sample meal/utility slips and in-app notifications for the current month.

---

## User roles

| Role | Typical use |
|------|-------------|
| **Super Admin** | Full visibility, audit log, user management, **payroll distribution** (salary payment), finance overview. **Does not** use the student fee portal or record manual student payments (those are **admin**). |
| **Admin** | Students, rooms, allocations, complaints, notices, **record manual student payments**, review payroll slips (approval). |
| **Staff** | Complaints, dashboard; **payroll** self-service; **hostel fees** only if they have a **student** profile (e.g. “hostel occupant”) linked to their user. |
| **Student** | Own profile, room/payment/complaint views, online fee portal. |

**Hostel occupant** — When a Super Admin creates an admin/staff user, they can register a linked student profile so that user can be allocated a room and pay fees like other students.

---

## Project structure

```
hostelease/
├── index.php                 # Front controller / router
├── .htaccess                 # Rewrites, security headers, upload PHP block
├── Dockerfile                # PHP-Apache image for Render/Docker
├── .env.example              # Template for local/production secrets
├── config/
│   ├── config.php            # Constants, env loading, BASE_URL
│   ├── database.php          # PDO (SSL-capable DSN)
│   └── session.php
├── app/
│   ├── controllers/          # Auth, Admin, Landing, Student, Room, …
│   ├── models/
│   └── helpers/              # auth, csrf, sanitize, upload
├── views/                    # Layouts + feature views
├── public/                   # css, js, images, uploads
├── database/
│   ├── hostelease.sql
│   ├── migrations/
│   └── seeds/
├── logs/                     # error.log (gitignored)
├── README.md
└── DOCUMENTATION.md          # Technical deep-dive
```

---

## Security highlights

- Prepared statements only; CSRF on state-changing forms; output escaping; bcrypt; login lockout; upload MIME checks; sensitive paths blocked by `.htaccess`; errors logged to `logs/`, not shown in production.

---

## Routes (quick reference)

URLs use the query form: `BASE_URL?url=controller/action/id`

| Example `url` | Description |
|-----------------|-------------|
| *(empty)* | `LandingController::index` — home |
| `auth/login`, `auth/logout` | Authentication |
| `dashboard/index` | Admin / Super Admin dashboard |
| `dashboard/student`, `dashboard/staff` | Role dashboards |
| `students/index`, `students/create`, … | Student management |
| `rooms/index`, `allocations/allocate`, … | Rooms & allocations |
| `payments/index`, `payments/record`, `payments/makePayment`, `payments/processPrepay`, `payments/balanceSheet`, `payments/processPortalAll`, … | Payments |
| `allocations/roomRequests`, `students/roomRequests` | Room requests queue & student form |
| `profile/index`, `profile/edit` | Profile |
| `users/index`, `users/create` | Users (Super Admin) |
| `payroll/index`, `payroll/review`, `payroll/distribute` | Payroll |
| `finances/index` | Finance (Super Admin) |
| `audit/index` | Audit log (Super Admin) |

A longer route table lives in [DOCUMENTATION.md](DOCUMENTATION.md).

---

## Contributing

1. Fork the repository and create a branch for your change.
2. Keep commits focused; match existing code style.
3. Open a pull request with a clear description.

---

## License

This project is released under the **MIT License**. You may use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the software, subject to the usual MIT conditions.

---

**HostelEase** — built for university and hostel administration workflows.
