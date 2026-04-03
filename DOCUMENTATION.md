# HostelEase — Technical Documentation

**Version:** 1.0.0  
**Last Updated:** April 3, 2026

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [MVC Pattern Implementation](#2-mvc-pattern-implementation)
3. [Database Schema Documentation](#3-database-schema-documentation)
4. [Authentication System](#4-authentication-system)
5. [Routing System](#5-routing-system)
6. [Security Implementation](#6-security-implementation)
7. [Module Documentation](#7-module-documentation)
8. [Helper Functions Reference](#8-helper-functions-reference)
9. [Configuration Reference](#9-configuration-reference)
10. [Deployment Guide](#10-deployment-guide)
11. [Troubleshooting](#11-troubleshooting)
12. [API / Route Reference](#12-api--route-reference)

---

## 1. Architecture Overview

HostelEase follows a custom **MVC (Model-View-Controller)** architecture without any PHP framework.

```
┌─────────────────────────────────────────────────┐
│                    Browser                       │
│             (HTTP Request)                       │
└────────────────────┬────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────┐
│              .htaccess                           │
│    URL Rewriting → index.php?url=...             │
└────────────────────┬────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────┐
│            index.php (Router)                    │
│    ┌──────────────────────────────────┐          │
│    │ 1. Load config, session, helpers │          │
│    │ 2. Parse URL → controller/action │          │
│    │ 3. Instantiate controller        │          │
│    │ 4. Call action method            │          │
│    └──────────────────────────────────┘          │
└────────────────────┬────────────────────────────┘
                     │
         ┌───────────┼───────────┐
         ▼           ▼           ▼
   ┌──────────┐ ┌──────────┐ ┌──────────┐
   │Controller│ │  Model   │ │  View    │
   │          │→│(Database)│ │(HTML/PHP)│
   │ Business │ │  PDO     │ │Bootstrap │
   │ Logic    │ │ Queries  │ │Templates │
   └──────────┘ └──────────┘ └──────────┘
```

### Request Lifecycle

1. User sends HTTP request
2. `.htaccess` rewrites URL to `index.php?url=controller/action/params`
3. `index.php` loads configuration, starts session, includes helpers
4. Router parses the URL and maps to a controller class and method
5. Controller checks authentication and authorization
6. Controller interacts with Models (database operations)
7. Controller passes data to View for rendering
8. View outputs HTML using the layout template
9. Response is sent back to the browser

---

## 2. MVC Pattern Implementation

### Controllers (`app/controllers/`)

Controllers handle the business logic. Each controller:
- Extends no base class (pure PHP, no framework)
- Calls `requireRole()` at the start of each protected method
- Validates CSRF tokens on POST requests
- Uses Models for database operations
- Passes data to Views via PHP variables
- Calls `AuditLog::log()` after mutations

**Pattern:**
```php
class ExampleController {
    private ExampleModel $model;
    
    public function __construct() {
        $this->model = new ExampleModel();
    }
    
    public function index(): void {
        requireRole(['admin', 'super_admin']);
        
        $data = $this->model->all();
        $pageTitle = 'Example List';
        
        ob_start();
        require_once APP_ROOT . '/views/example/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }
}
```

### Models (`app/models/`)

Models handle all database operations using PDO prepared statements.

**Key Principles:**
- Every query uses parameterized placeholders (`:param` syntax)
- Models include the `Database` singleton via `Database::getInstance()`
- Return types are strictly typed (`array|false`, `int`, `bool`)
- Complex operations use database transactions

### Views (`views/`)

Views are PHP template files that render HTML.

**Rendering Pattern:**
```php
// In the controller:
ob_start();
require_once APP_ROOT . '/views/example/index.php';
$viewContent = ob_get_clean();
require_once APP_ROOT . '/views/layouts/main.php';
```

The view file generates HTML, captured into `$viewContent` via output buffering. The layout template (`main.php` or `auth.php`) wraps it with the sidebar, navbar, and footer.

---

## 3. Database Schema Documentation

### Entity Relationship Overview

```
users (1) ─────── (1) students
  │                     │
  │                     ├── (N) allocations ──── (1) rooms
  │                     ├── (N) payments ─────── (1) fee_structures
  │                     ├── (N) complaints
  │                     └── (N) waitlist
  │
  ├── (N) notices
  ├── (N) audit_logs
  ├── (N) password_resets
  └── (N) login_attempts
```

### Table Details

#### `users`
| Column         | Type          | Constraints          | Description                    |
|:---------------|:-------------|:---------------------|:-------------------------------|
| id             | INT UNSIGNED  | PK, AUTO_INCREMENT   | Unique user ID                 |
| full_name      | VARCHAR(100)  | NOT NULL             | User's full name               |
| email          | VARCHAR(150)  | UNIQUE, NOT NULL     | Login email                    |
| password_hash  | VARCHAR(255)  | NOT NULL             | bcrypt hashed password         |
| role           | ENUM          | NOT NULL             | super_admin/admin/student/staff|
| status         | ENUM          | DEFAULT 'active'     | active/suspended/inactive      |
| profile_photo  | VARCHAR(255)  | NULL                 | Photo filename                 |
| created_at     | TIMESTAMP     | AUTO                 | Creation timestamp             |
| updated_at     | TIMESTAMP     | AUTO ON UPDATE       | Last update timestamp          |

#### `students`
| Column         | Type          | Constraints          | Description                    |
|:---------------|:-------------|:---------------------|:-------------------------------|
| id             | INT UNSIGNED  | PK, AUTO_INCREMENT   | Unique student ID              |
| user_id        | INT UNSIGNED  | FK → users, UNIQUE   | Linked user account            |
| student_id_no  | VARCHAR(30)   | UNIQUE, NOT NULL     | Student identification number  |
| phone          | VARCHAR(20)   | NULL                 | Phone number                   |
| guardian_name  | VARCHAR(100)  | NULL                 | Guardian's name                |
| guardian_phone | VARCHAR(20)   | NULL                 | Guardian's phone               |
| nid_or_card    | VARCHAR(255)  | NULL                 | ID document filename           |
| enrolled_date  | DATE          | NULL                 | Enrollment date                |
| checkout_date  | DATE          | NULL                 | Checkout date (if left)        |

#### `rooms`
| Column      | Type          | Constraints        | Description                     |
|:------------|:-------------|:-------------------|:--------------------------------|
| id          | INT UNSIGNED  | PK, AUTO_INCREMENT | Unique room ID                  |
| room_number | VARCHAR(20)   | UNIQUE, NOT NULL   | Room identification number      |
| floor       | TINYINT       | NULL               | Floor number                    |
| type        | ENUM          | DEFAULT 'single'   | single/double/triple/dormitory  |
| capacity    | TINYINT       | NOT NULL, DEFAULT 1| Maximum occupants               |
| facilities  | TEXT          | NULL               | Room facilities description     |
| status      | ENUM          | DEFAULT 'available'| available/full/maintenance      |

#### `allocations`
| Column       | Type         | Constraints        | Description                     |
|:-------------|:------------|:-------------------|:--------------------------------|
| id           | INT UNSIGNED | PK, AUTO_INCREMENT | Unique allocation ID            |
| student_id   | INT UNSIGNED | FK → students      | Allocated student               |
| room_id      | INT UNSIGNED | FK → rooms         | Target room                     |
| allocated_by | INT UNSIGNED | FK → users, NULL   | Admin who allocated             |
| start_date   | DATE         | NOT NULL           | Start of allocation             |
| end_date     | DATE         | NULL               | End (if transferred/vacated)    |
| status       | ENUM         | DEFAULT 'active'   | active/transferred/vacated      |
| notes        | TEXT         | NULL               | Additional notes                |

#### `payments`
| Column         | Type          | Constraints        | Description                  |
|:---------------|:-------------|:-------------------|:-----------------------------|
| id             | INT UNSIGNED  | PK, AUTO_INCREMENT | Unique payment ID            |
| student_id     | INT UNSIGNED  | FK → students      | Paying student               |
| fee_id         | INT UNSIGNED  | FK → fee_structures| Fee type                     |
| amount_paid    | DECIMAL(10,2) | NOT NULL           | Amount paid                  |
| payment_date   | DATE          | NOT NULL           | Date of payment              |
| receipt_no     | VARCHAR(50)   | UNIQUE, NOT NULL   | Auto-generated receipt number|
| payment_method | ENUM          | DEFAULT 'cash'     | cash/bank/online             |
| month_year     | VARCHAR(10)   | NULL               | Payment period (YYYY-MM)     |
| recorded_by    | INT UNSIGNED  | FK → users, NULL   | Admin who recorded           |

#### `complaints`
| Column      | Type         | Constraints        | Description                     |
|:------------|:------------|:-------------------|:--------------------------------|
| id          | INT UNSIGNED | PK, AUTO_INCREMENT | Unique complaint ID             |
| student_id  | INT UNSIGNED | FK → students      | Submitting student              |
| category    | VARCHAR(100) | NOT NULL           | Complaint category              |
| description | TEXT         | NOT NULL           | Detailed description            |
| priority    | ENUM         | DEFAULT 'medium'   | low/medium/high                 |
| status      | ENUM         | DEFAULT 'open'     | open/in_progress/resolved/closed|
| assigned_to | INT UNSIGNED | FK → users, NULL   | Assigned staff member           |
| resolved_at | TIMESTAMP    | NULL               | Resolution timestamp            |

**SLA Rules:**
- **High Priority:** Flagged as overdue if unresolved after **24 hours**
- **Medium Priority:** Flagged as overdue if unresolved after **72 hours**
- Low priority complaints have no SLA

---

## 4. Authentication System

### Login Flow

```
User submits email + password
  │
  ├── Check login throttle (max 5 attempts per 15 min)
  │   └── If locked out → show error
  │
  ├── Find user by email
  │   └── If not found → record attempt, show error
  │
  ├── Verify password with password_verify()
  │   └── If wrong → record attempt, show error
  │
  ├── Check user status (must be 'active')
  │   └── If suspended → show error
  │
  ├── Clear login attempts
  ├── Regenerate session ID (prevent fixation)
  ├── Store user data in session
  ├── Log audit entry
  └── Redirect to role-based dashboard
```

### Session Data Structure

```php
$_SESSION = [
    'user_id'      => 1,
    'user_name'    => 'System Administrator',
    'user_email'   => 'admin@hostelease.com',
    'user_role'    => 'super_admin',
    'user_photo'   => null,
    'logged_in_at' => 1712137620,
    'last_activity'=> 1712137620,
    '_csrf_token'  => 'a1b2c3d4...',
];
```

### Password Reset Flow

1. User enters email → system generates random token
2. Token stored in `password_resets` table with expiry (1 hour)
3. User enters token + new password
4. System validates token (not expired, not used)
5. Password updated, token marked as used

---

## 5. Routing System

### URL Format

```
http://localhost/hostelease/?url=controller/action/param1/param2
```

### Controller Mapping

```php
$controllerMap = [
    'Auth'        => 'AuthController',
    'Students'    => 'StudentController',
    'Rooms'       => 'RoomController',
    'Allocations' => 'AllocationController',
    'Payments'    => 'PaymentController',
    'Complaints'  => 'ComplaintController',
    'Notices'     => 'NoticeController',
    'Dashboard'   => 'AdminController',
    'Admin'       => 'AdminController',
    'Audit'       => 'AuditController',
];
```

### URL Parsing Logic

1. Read `$_GET['url']`, sanitize, split by `/`
2. First segment → controller name (ucfirst)
3. Second segment → action name (camelCase from hyphens)
4. Remaining segments → method parameters

---

## 6. Security Implementation

### CSRF Protection

Every POST form includes:
```html
<input type="hidden" name="_csrf_token" value="abc123...">
```

Generated via `csrfField()` helper. Verified in controller:
```php
if (!verifyToken()) {
    setFlash('error', 'Invalid security token.');
    // redirect back
}
```

Token is regenerated after each verification to prevent replay attacks.

### Input Sanitization

All user input is sanitized before processing:
- `sanitize()` — strips tags, trims whitespace
- `sanitizeEmail()` — validates email format
- `sanitizeInt()` — casts to integer
- `sanitizeFloat()` — casts to float
- `sanitizeDate()` — validates Y-m-d format
- `sanitizePhone()` — removes non-phone characters

All output is escaped:
- `e()` / `escape()` — wraps `htmlspecialchars(ENT_QUOTES, UTF-8)`

### File Upload Security

```php
// 1. Validate extension (whitelist)
// 2. Validate MIME type via finfo_file() (NOT trusting $_FILES['type'])
// 3. Rename to uniqid() (prevent directory traversal)
// 4. Move to designated upload directory
// 5. .htaccess blocks PHP execution in upload dirs
```

---

## 7. Module Documentation

### Payment Receipt Number Format

```
RCP-YYYYMM-XXXX
│    │       │
│    │       └── Sequential 4-digit number (auto-incremented per month)
│    └────────── Year and month (e.g., 202604)
└──────────────── Fixed prefix
```

Example: `RCP-202604-0001`, `RCP-202604-0002`

### Allocation Business Logic

```
1. Admin selects student + room
2. System checks: does student have active allocation?
   → Yes: Error "Transfer or vacate first"
3. System checks room capacity
   → Full: Add to waitlist, show warning
   → Available: Proceed to allocate
4. Create allocation record (status: 'active')
5. Refresh room status (auto-update to 'full' if at capacity)
6. Log audit entry
```

### Complaint SLA Engine

```
For each unresolved complaint:
  hours_open = NOW() - created_at (in hours)
  
  If priority == 'high' AND hours_open > 24:
    → Mark as SLA overdue
  If priority == 'medium' AND hours_open > 72:
    → Mark as SLA overdue
    
  Display red row in complaints table
  Show alert on admin dashboard
```

---

## 8. Helper Functions Reference

### auth.php

| Function            | Returns   | Description                              |
|:--------------------|:----------|:-----------------------------------------|
| `isLoggedIn()`      | `bool`    | Checks if user has active session        |
| `currentUser()`     | `?array`  | Returns current user's session data      |
| `hasRole($roles)`   | `bool`    | Checks if user has specified role(s)     |
| `requireRole($r)`   | `void`    | Redirects if unauthorized                |
| `requireAuth()`     | `void`    | Redirects if not logged in               |
| `setFlash($t, $m)`  | `void`    | Sets a flash message                     |
| `getFlash($type)`   | `?string` | Gets and clears a flash message          |
| `getClientIP()`     | `string`  | Returns client's IP address              |

### csrf.php

| Function          | Returns   | Description                               |
|:------------------|:----------|:------------------------------------------|
| `generateToken()` | `string`  | Returns the current CSRF token            |
| `verifyToken($t)` | `bool`    | Validates submitted token, regenerates    |
| `csrfField()`     | `string`  | Outputs hidden HTML input with token      |

### sanitize.php

| Function           | Returns   | Description                              |
|:-------------------|:----------|:-----------------------------------------|
| `sanitize($str)`   | `string`  | Strip tags + trim                        |
| `sanitizeEmail()`  | `string`  | Validate + sanitize email                |
| `e($str)`          | `string`  | htmlspecialchars shorthand               |
| `sanitizeInt()`    | `int`     | Cast to integer                          |
| `sanitizeFloat()`  | `float`   | Cast to float                            |
| `sanitizeDate()`   | `?string` | Validate Y-m-d format                   |
| `sanitizePhone()`  | `string`  | Keep digits, +, -, spaces only           |

---

## 9. Configuration Reference

### config.php Constants

| Constant              | Default Value                  | Description                   |
|:----------------------|:-------------------------------|:------------------------------|
| `APP_NAME`            | `'HostelEase'`                 | Application display name      |
| `APP_VERSION`         | `'1.0.0'`                      | Current version               |
| `BASE_URL`            | `'http://localhost/hostelease/'`| Base URL with trailing slash  |
| `DB_HOST`             | `'127.0.0.1'`                  | MySQL host                    |
| `DB_PORT`             | `'3306'`                       | MySQL port                    |
| `DB_NAME`             | `'hostelease'`                 | Database name                 |
| `DB_USER`             | `'root'`                       | Database username             |
| `DB_PASS`             | `''`                           | Database password             |
| `UPLOAD_MAX_SIZE`     | `5242880` (5MB)                | Max upload size in bytes      |
| `SESSION_LIFETIME`    | `3600` (1 hour)                | Session timeout in seconds    |
| `MAX_LOGIN_ATTEMPTS`  | `5`                            | Max failed logins             |
| `LOGIN_LOCKOUT_TIME`  | `900` (15 min)                 | Lockout duration in seconds   |
| `SLA_HIGH_PRIORITY_HOURS`   | `24`                     | SLA for high priority         |
| `SLA_MEDIUM_PRIORITY_HOURS` | `72`                     | SLA for medium priority       |
| `RECEIPT_PREFIX`      | `'RCP'`                        | Payment receipt prefix        |

---

## 10. Deployment Guide

### Production Checklist

1. **Change database credentials** in `config/config.php`
2. **Update `BASE_URL`** to your production domain
3. **Change default admin password** immediately after first login
4. **Enable HTTPS** and set `'secure' => true` in `session.php` cookie params
5. **Disable error display** (already configured in `config.php`)
6. **Set file permissions:**
   ```bash
   chmod 755 public/uploads/students
   chmod 755 public/uploads/documents
   chmod 755 logs
   chmod 644 config/config.php
   ```
7. **Enable Apache modules:** `mod_rewrite`, `mod_headers`, `mod_expires`
8. **Verify `.htaccess`** is being processed (AllowOverride All)
9. **Set timezone** in `config.php` to your production timezone
10. **Back up database** before any schema changes

### Environment-Specific Settings

```php
// Production
define('BASE_URL', 'https://yourdomain.com/');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Development (XAMPP)
define('BASE_URL', 'http://localhost/hostelease/');
ini_set('display_errors', '1'); // Only for debugging
```

---

## 11. Troubleshooting

### Common Issues

| Issue                              | Solution                                          |
|:-----------------------------------|:--------------------------------------------------|
| 404 on all pages                   | Enable `mod_rewrite` in Apache, check `.htaccess` |
| Database connection error          | Verify MySQL is running, check `config.php` creds |
| CSRF token invalid                 | Clear browser cookies, start a fresh session       |
| File upload fails                  | Check `upload_max_filesize` in `php.ini`          |
| Session expires too quickly        | Increase `SESSION_LIFETIME` in `config.php`       |
| Blank page                         | Enable `display_errors` temporarily for debugging |
| Login always fails                 | Run `admin_seed.php` again, check password hash   |
| Photos not showing                 | Check `public/uploads/students/` permissions      |

### Enabling Debug Mode (Development Only)

Temporarily edit `config/config.php`:
```php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
```

**⚠️ Never enable this in production!**

---

## 12. API / Route Reference

### Full Route Table

| Method | URL Pattern                | Controller           | Action          | Access              |
|:-------|:---------------------------|:---------------------|:----------------|:--------------------|
| GET    | `auth/login`               | AuthController       | login           | Public              |
| POST   | `auth/login`               | AuthController       | login           | Public              |
| GET    | `auth/logout`              | AuthController       | logout          | Authenticated       |
| GET    | `auth/forgot-password`     | AuthController       | forgotPassword  | Public              |
| POST   | `auth/forgot-password`     | AuthController       | forgotPassword  | Public              |
| GET    | `dashboard/index`          | AdminController      | dashboard       | Admin, Super Admin  |
| GET    | `dashboard/student`        | AdminController      | student         | Student             |
| GET    | `dashboard/staff`          | AdminController      | staff           | Staff               |
| GET    | `dashboard/profile`        | AdminController      | profile         | Authenticated       |
| GET    | `students/index`           | StudentController    | index           | Admin, Super Admin  |
| GET    | `students/create`          | StudentController    | create          | Admin, Super Admin  |
| POST   | `students/store`           | StudentController    | store           | Admin, Super Admin  |
| GET    | `students/show/{id}`       | StudentController    | show            | Admin, Student (own)|
| GET    | `students/edit/{id}`       | StudentController    | edit            | Admin, Super Admin  |
| POST   | `students/update/{id}`     | StudentController    | update          | Admin, Super Admin  |
| POST   | `students/delete/{id}`     | StudentController    | delete          | Admin, Super Admin  |
| GET    | `rooms/index`              | RoomController       | index           | Admin, Super Admin  |
| GET    | `rooms/create`             | RoomController       | create          | Admin, Super Admin  |
| POST   | `rooms/store`              | RoomController       | store           | Admin, Super Admin  |
| GET    | `rooms/edit/{id}`          | RoomController       | edit            | Admin, Super Admin  |
| POST   | `rooms/update/{id}`        | RoomController       | update          | Admin, Super Admin  |
| GET    | `allocations/allocate`     | AllocationController | allocate        | Admin, Super Admin  |
| POST   | `allocations/allocate`     | AllocationController | allocate        | Admin, Super Admin  |
| GET    | `allocations/transfer`     | AllocationController | transfer        | Admin, Super Admin  |
| POST   | `allocations/transfer`     | AllocationController | transfer        | Admin, Super Admin  |
| POST   | `allocations/vacate`       | AllocationController | vacate          | Admin, Super Admin  |
| GET    | `payments/index`           | PaymentController    | index           | Admin, Super Admin  |
| GET    | `payments/record`          | PaymentController    | record          | Admin, Super Admin  |
| POST   | `payments/store`           | PaymentController    | store           | Admin, Super Admin  |
| GET    | `payments/receiptView/{id}`| PaymentController    | receiptView     | Authenticated       |
| GET    | `complaints/index`         | ComplaintController   | index           | All Authenticated   |
| GET    | `complaints/create`        | ComplaintController   | create          | All Authenticated   |
| POST   | `complaints/store`         | ComplaintController   | store           | All Authenticated   |
| GET    | `complaints/show/{id}`     | ComplaintController   | show            | All Authenticated   |
| POST   | `complaints/update/{id}`   | ComplaintController   | update          | Admin, Staff        |
| POST   | `complaints/assign/{id}`   | ComplaintController   | assign          | Admin, Super Admin  |
| GET    | `notices/index`            | NoticeController     | index           | All Authenticated   |
| GET    | `notices/create`           | NoticeController     | create          | Admin, Super Admin  |
| POST   | `notices/store`            | NoticeController     | store           | Admin, Super Admin  |
| GET    | `notices/edit/{id}`        | NoticeController     | edit            | Admin, Super Admin  |
| POST   | `notices/update/{id}`      | NoticeController     | update          | Admin, Super Admin  |
| POST   | `notices/delete/{id}`      | NoticeController     | delete          | Admin, Super Admin  |
| GET    | `audit/index`              | AuditController      | index           | Super Admin         |

---

*End of Technical Documentation*
