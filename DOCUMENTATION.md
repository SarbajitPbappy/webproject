# HostelEase — Technical Documentation

**Version:** 1.0.0  
**Last updated:** April 3, 2026

This document describes architecture, configuration, security, database concepts, routing, and deployment for developers and operators. The **user-facing overview** is in [README.md](README.md).

---

## Table of contents

1. [Architecture overview](#1-architecture-overview)
2. [MVC and request lifecycle](#2-mvc-and-request-lifecycle)
3. [Routing](#3-routing)
4. [Configuration and environment](#4-configuration-and-environment)
5. [Database](#5-database)
6. [Authentication and sessions](#6-authentication-and-sessions)
7. [Authorization by role](#7-authorization-by-role)
8. [Module notes](#8-module-notes)
9. [Helpers](#9-helpers)
10. [Security](#10-security)
11. [Deployment](#11-deployment)
12. [Troubleshooting](#12-troubleshooting)
13. [Route reference](#13-route-reference)

---

## 1. Architecture overview

HostelEase is a **custom PHP MVC** application: no Composer framework. HTTP requests hit `index.php`, which loads configuration, session, helpers, resolves `controller` + `action` + `params`, instantiates the matching controller class, and invokes the action method.

```
Browser → Apache (.htaccess) → index.php?url=... → Controller → Model (PDO) → View (PHP templates)
```

---

## 2. MVC and request lifecycle

### Controllers (`app/controllers/`)

- One class per area (`AuthController`, `StudentController`, …).
- Protected actions call `requireAuth()` / `requireRole([...])` from `app/helpers/auth.php`.
- POST actions should call `verifyToken()` (CSRF) for `csrf.php`.
- Typical pattern: compute data → `ob_start()` → `require` view → `ob_get_clean()` → `require` layout `views/layouts/main.php` or `auth.php`.

### Models (`app/models/`)

- Use `Database::getInstance()` for PDO.
- Prepared statements only; return types documented in code.

### Views (`views/`)

- PHP templates; user-facing strings escaped with `e()` / `escape()` where appropriate.

---

## 3. Routing

### URL shape

```
GET /index.php?url=controller/action/param1/param2
```

With rewrite rules, the same path may appear as:

```
GET /controller/action/param1/param2
```

### Default route

If `url` is empty or `/`, the router uses `LandingController::index`.

### Controller map (`index.php`)

| URL segment (capitalized) | Controller class |
|---------------------------|------------------|
| `Landing` | `LandingController` |
| `Auth` | `AuthController` |
| `Users` / `User` | `UserController` |
| `Students` / `Student` | `StudentController` |
| `Rooms` / `Room` | `RoomController` |
| `Allocations` / `Allocation` | `AllocationController` |
| `Payments` / `Payment` | `PaymentController` |
| `Complaints` / `Complaint` | `ComplaintController` |
| `Notices` / `Notice` | `NoticeController` |
| `Dashboard` / `Admin` | `AdminController` |
| `Audit` | `AuditController` |
| `Profile` | `ProfileController` |
| `Payroll` | `PayrollController` |
| `Finances` / `Finance` | `FinanceController` |

### Action names

The second path segment is the action name. Hyphens are converted to **camelCase** (e.g. `forgot-password` → `forgotPassword`).

Remaining segments are passed as **arguments** to the action method.

---

## 4. Configuration and environment

### `config/config.php`

- Loads optional `.env` in `APP_ROOT` via `loadEnvFile()` (does not override real OS environment variables).
- Defines `APP_ENV`, `APP_NAME`, `BASE_URL`, database constants, upload limits, session name, CSRF name, SLA hours, receipt prefix, timezone (`Asia/Dhaka`), and error logging.

### `BASE_URL` behavior

- If `BASE_URL` is set in the environment **and** is not treated as invalid for production, it is normalized with a trailing slash.
- **Production safety:** If `APP_ENV` is not `development`, a `BASE_URL` containing `localhost`, `127.0.0.1`, or `0.0.0.0` is **ignored** so links are not forced to localhost on PaaS hosts.
- Otherwise `BASE_URL` is derived from the request: `HTTPS` / `X-Forwarded-Proto` for scheme, `HTTP_HOST` for host.

This is important on **Render** and similar proxies: set `APP_ENV=production` and either omit `BASE_URL` or set it to your public `https://…` URL.

### `config/database.php`

- Singleton PDO; DSN includes `sslmode=require` when `DB_SSL` is true (common for managed MySQL).
- Optional `PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false` when SSL is on to avoid CA file issues in some environments.

### Recommended `.env` keys

| Key | Notes |
|-----|--------|
| `APP_ENV` | `development` locally; `production` on Render. |
| `BASE_URL` | Optional; must match how users reach the app in dev. |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Required for DB access. |
| `DB_SSL` | `true` for Aiven and similar SSL-only endpoints. |
| `SUPER_ADMIN_PASS_HASH` | Optional; virtual super-admin fallback in auth flow. |

---

## 5. Database

### Base schema

`database/hostelease.sql` creates core tables: `users`, `students`, `rooms`, `allocations`, `waitlist`, `fee_structures`, `payments`, `complaints`, `notices`, `audit_logs`, `password_resets`, `login_attempts`, `transactions`, etc.

### Payroll migration

`database/migrations/migrate_payroll.php` ensures `transactions`, `staff_details`, and `pay_slips` exist and align with the ledger/payroll features. Run after schema import if you use payroll.

### Seeding

`database/seeds/admin_seed.php` creates the first `super_admin` if `admin@hostelease.com` does not exist.

### Entity relationships (high level)

- `users` ↔ `students` (one-to-one when a student account exists).
- `students` → `allocations` → `rooms`; `waitlist` queues students when rooms are full.
- `payments` link to `students` and `fee_structures`.
- `transactions` record income/expense for payments and payroll (`reference_type` / `reference_id`).
- `pay_slips` / `staff_details` support the payroll workflow.

---

## 6. Authentication and sessions

- Login validates credentials, checks account status, clears lockout on success, regenerates session ID, stores `user_id`, role, email, name, photo, etc.
- Failed attempts are tracked in `login_attempts` (see `MAX_LOGIN_ATTEMPTS`, `LOGIN_LOCKOUT_TIME` in `config.php`).
- Password reset uses `password_resets` with expiry.

---

## 7. Authorization by role

| Area | super_admin | admin | student | staff |
|------|-------------|-------|---------|-------|
| User management (`UserController`) | Yes | — | — | — |
| Student/room CRUD, allocations | Yes | Yes | — | — |
| Record manual student payment (`payments/record`, `store`) | No | Yes | — | — |
| Payment list / receipts (oversight) | Yes | Yes | Own / scoped | Own / scoped |
| Online fee portal (`makePayment`, `processPortal`) | **No** | Yes (if student profile) | Yes | Yes (if student profile) |
| Payroll: apply (`payroll/index`, `apply`) | — | Yes | — | Yes |
| Payroll: review/approve (`payroll/review`, `processApproval`) | Yes | Yes | — | — |
| Payroll: distribute/pay (`payroll/distribute`, `pay`) | **Yes** | — | — | — |
| Finance (`finances/index`) | Yes | — | — | — |
| Audit log | Yes | — | — | — |

**Super Admin** focuses on payroll disbursement and system-wide oversight; **student fee collection** (manual or portal) is handled by **admin** (and eligible students/staff with a student profile).

---

## 8. Module notes

### Payments

- Receipt numbers are generated with prefix `RECEIPT_PREFIX` (e.g. `RCP-YYYYMM-XXXX`).
- Portal amounts should be validated against `fee_structures` in the controller (not trusted blindly from the client).

### Allocations and automation

- When a student **vacates** and the room has capacity, logic can assign the next **waitlist** entry to that room (see `AllocationController::vacate`).

### Payroll

1. Staff (and admin with staff payroll) apply for a monthly slip.
2. Admin/super_admin review and approve with bonuses/deductions.
3. Super admin **distributes** salary and marks slips paid; an **expense** row is written to `transactions`.

### Super Admin UI indicators

- Pending payroll slip count may be shown in the navbar/sidebar for quick review.

### Profile

- `ProfileController` updates `users` (name, phone, photo). Student-linked rows may sync phone to `students` where applicable. Student IDs and immutable identifiers are not exposed for self-edit.

### User creation (Super Admin)

- Optional **hostel occupant** student record for admin/staff enables room allocation and billing like a student.

---

## 9. Helpers

| File | Purpose |
|------|---------|
| `auth.php` | `isLoggedIn`, `currentUser`, `hasRole`, `requireRole`, `requireAuth`, flash messages, redirect helpers |
| `csrf.php` | `generateToken`, `verifyToken`, `csrfField` |
| `sanitize.php` | `sanitize`, `sanitizeInt`, `sanitizeEmail`, `sanitizePhone`, `sanitizeDate`, `escape` / `e` |
| `upload.php` | Validated file uploads with size/type checks |

---

## 10. Security

- **SQL:** PDO prepared statements only.
- **XSS:** Escape output in views; use helpers consistently.
- **CSRF:** Tokens on POST forms; verify in controllers.
- **Passwords:** `password_hash` / `password_verify` (bcrypt).
- **Sessions:** Configured in `config/session.php`; secure cookie flags should be set for HTTPS in production.
- **Uploads:** MIME checks, whitelist extensions, unique filenames; `.htaccess` blocks executing PHP under `public/uploads`.
- **Errors:** `display_errors` off; log to `logs/error.log` in production.

---

## 11. Deployment

### Render (Docker)

1. **Root directory:** `hostelease` if the repo contains that subfolder.
2. **Environment** — Set `APP_ENV=production`, all `DB_*` variables, `DB_SSL=true` if required, and `DB_PASS`.
3. **`BASE_URL`** — Either unset (derive from request) or `https://<your-service>.onrender.com/`.
4. **Database** — Import SQL, run `admin_seed.php`, then `migrate_payroll.php` if needed.

### Apache

- Enable `mod_rewrite`, `mod_headers`, `mod_expires` as needed.
- `AllowOverride All` for the app directory so `.htaccess` applies.

### HTTPS

- Terminate TLS at the load balancer or reverse proxy; set session cookie `secure` when appropriate.

---

## 12. Troubleshooting

| Symptom | What to check |
|---------|----------------|
| All routes 404 | `mod_rewrite`, `AllowOverride`, document root points to `hostelease` |
| Redirects go to `localhost` on Render | `APP_ENV=production`; remove or fix `BASE_URL`; redeploy |
| Database connection failed | `DB_PASS`, host/port, firewall, `DB_SSL=true` for Aiven |
| `sslmode` / SSL errors | `DB_SSL=true` in `.env`; provider CA if you verify certs strictly |
| CSRF / session issues | Same-site cookies, `BASE_URL` consistency, clock skew |
| Payroll menu empty | Run `migrate_payroll.php`; ensure `staff_details` rows exist |
| Upload failures | `upload_max_filesize`, `post_max_size`, directory permissions |

### Development debugging

- Temporarily set `APP_ENV=development` for clearer DB error messages (still avoid enabling `display_errors` in shared hosting).

---

## 13. Route reference

Below, **access** is abbreviated: SA = super_admin, A = admin, St = student, T = staff.

| `url` (path after `?url=`) | Controller | Action | Access |
|----------------------------|------------|--------|--------|
| *(empty)* | Landing | index | Public |
| `auth/login`, `auth/logout` | Auth | login, logout | Public / auth |
| `auth/forgot-password` | Auth | forgotPassword | Public |
| `dashboard/index` | Admin | dashboard | SA, A |
| `dashboard/student` | Admin | student | St |
| `dashboard/staff` | Admin | staff | T |
| `students/*` | Student | * | SA, A (and student for own where implemented) |
| `students/editSelf` | Student | editSelf | St |
| `rooms/*` | Room | * | SA, A |
| `allocations/*` | Allocation | * | SA, A |
| `payments/index` | Payment | index | SA, A, St, T |
| `payments/record`, `payments/store` | Payment | record, store | A |
| `payments/makePayment`, `payments/processPortal` | Payment | makePayment, processPortal | St, A, T (not SA) |
| `payments/receiptView/{id}` | Payment | receiptView | SA, A, St, T |
| `complaints/*` | Complaint | * | Role-dependent |
| `notices/*` | Notice | * | SA, A (writes); read for others |
| `audit/index` | Audit | index | SA |
| `users/*` | User | * | SA |
| `profile/index`, `profile/edit` | Profile | index, edit | Authenticated |
| `payroll/index`, `payroll/apply` | Payroll | index, apply | T, A |
| `payroll/review`, `payroll/processApproval` | Payroll | review, processApproval | SA, A |
| `payroll/distribute`, `payroll/pay` | Payroll | distribute, pay | SA |
| `finances/index` | Finance | index | SA |

*Note:* Exact method names may vary; use `grep` in `app/controllers/` for the canonical list.

---

*End of technical documentation.*
