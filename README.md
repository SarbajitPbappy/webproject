# HostelEase — Web-Based Hostel Management System

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.x-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.x-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-blueviolet)
![License](https://img.shields.io/badge/license-MIT-green)

**HostelEase** is a production-level, web-based hostel management system built with PHP 8.x (OOP MVC), MySQL 8.x, Bootstrap 5, and Vanilla JavaScript. It provides comprehensive management of students, rooms, allocations, payments, complaints, and notices for hostel administrators.

---

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Prerequisites](#-prerequisites)
- [Installation & Setup](#-installation--setup)
- [MySQL Workbench Setup](#-mysql-workbench-setup)
- [Default Credentials](#-default-credentials)
- [Project Structure](#-project-structure)
- [User Roles & Permissions](#-user-roles--permissions)
- [Modules Overview](#-modules-overview)
- [Security Features](#-security-features)
- [Routes Reference](#-routes-reference)
- [Screenshots](#-screenshots)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### Core Functionality
- **Multi-Role Authentication** — Super Admin, Admin, Student, Staff with role-based dashboards
- **Student Management** — Full CRUD with photo upload, search, and status lifecycle
- **Room Management** — Room inventory, occupancy tracking, capacity management
- **Room Allocation** — Assign/transfer/vacate with waitlist queue
- **Payment Tracking** — Manual cash recording with auto-generated receipt numbers
- **Complaint System** — Ticket management with SLA tracking (24h high / 72h medium)
- **Notice Board** — Admin announcements with pinned notices
- **Audit Logging** — Complete activity trail (who, what, when, IP)

### Security
- PDO prepared statements (zero raw SQL concatenation)
- CSRF token protection on every form
- XSS prevention via `htmlspecialchars()` on all output
- bcrypt password hashing
- Login throttling (5 attempts → 15 min lockout)
- Secure session management with httponly cookies
- File upload validation with `finfo_file()` MIME checking
- `.htaccess` directory blocking and security headers

### UI/UX
- Modern, responsive design with Bootstrap 5
- Professional sidebar navigation
- KPI dashboard cards with real-time metrics
- DataTables for searchable/sortable tables
- Printable payment receipts
- Flash messaging system

---

## 🛠 Tech Stack

| Layer           | Technology                         |
|:----------------|:-----------------------------------|
| Backend         | PHP 8.x (OOP, MVC pattern)         |
| Database        | MySQL 8.x                          |
| DB Management   | MySQL Workbench                    |
| Frontend        | HTML5, CSS3, Bootstrap 5, Vanilla JS |
| Local Server    | XAMPP (Apache + MySQL)             |
| Font            | Google Fonts (Inter)               |
| Icons           | Bootstrap Icons                    |
| Tables          | DataTables.js                      |
| Version Control | Git + GitHub                       |

---

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

1. **XAMPP** (v8.2+) — [Download](https://www.apachefriends.org/download.html)
   - Includes Apache, MySQL, and PHP
2. **MySQL Workbench** (v8.0+) — [Download](https://dev.mysql.com/downloads/workbench/)
   - For database schema management and visualization
3. **Git** — [Download](https://git-scm.com/downloads)
4. **A modern web browser** (Chrome, Firefox, Edge)

---

## 🚀 Installation & Setup

### Step 1: Clone the Repository

```bash
cd /path/to/xampp/htdocs
git clone https://github.com/your-username/hostelease.git
cd hostelease
```

Or download and extract the ZIP file to `htdocs/hostelease/`.

### Step 2: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** (Web Server)
3. Start **MySQL** (Database Server)
4. Verify Apache is running on port `80` and MySQL on port `3306`

### Step 3: Database Configuration (Aiven Cloud)

This project is pre-configured to connect to a live **Aiven MySQL** database. The configuration inside `config/config.php` has been updated with the following credentials:
- **Host:** `hostelease-hostelease.a.aivencloud.com`
- **Port:** `19887`
- **Database:** `defaultdb`
- **Username:** `avnadmin`

*Note: The project uses `PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false` out-of-the-box to facilitate quick connections without a local CA file. For production, please download the `ca.pem` from Aiven and specify it in your PDO connection.*

To set up the schema and seed the initial admin account, run:
```bash
php database/seeds/admin_seed.php
```
*(Note: If the schema hasn't been imported yet, you can run `mysql -u avnadmin -p -h hostelease-hostelease.a.aivencloud.com -P 19887 defaultdb < database/hostelease.sql` first).*

### Step 4: Configure the Application

Edit `config/config.php` if your environment differs from defaults.

```php
// URL — update if your project is in a different folder
define('BASE_URL', 'http://localhost/hostelease/');
```

### Step 5: Create Required Directories

The following directories should already exist. If not, create them:

```bash
mkdir -p public/uploads/students
mkdir -p public/uploads/documents
mkdir -p logs
```

Ensure the web server has write permissions:
```bash
chmod 755 public/uploads/students
chmod 755 public/uploads/documents
chmod 755 logs
```

### Step 6: Access the Application

Open your browser and navigate to:
```
http://localhost/hostelease/
```

You should see the login page. Log in with the default credentials.

---

## 🔑 Default Credentials

| Role        | Email                    | Password   |
|:------------|:-------------------------|:-----------|
| Super Admin | `admin@hostelease.com`   | `Admin@123`|

> ⚠️ **Important:** Change the default credentials immediately after first login for security.

Students created through the system receive the default password: `Student@123`

---

## 📂 Project Structure

```
hostelease/
├── index.php                    # Entry point & router
├── .htaccess                    # URL rewriting & security
├── .gitignore                   # Git ignore rules
│
├── config/
│   ├── config.php               # App constants & DB credentials
│   ├── database.php             # PDO singleton connection
│   └── session.php              # Session management & CSRF init
│
├── app/
│   ├── controllers/
│   │   ├── AuthController.php       # Login, logout, password reset
│   │   ├── StudentController.php    # Student CRUD
│   │   ├── RoomController.php       # Room CRUD
│   │   ├── AllocationController.php # Allocate, transfer, vacate
│   │   ├── PaymentController.php    # Payment recording
│   │   ├── ComplaintController.php  # Complaint management
│   │   ├── NoticeController.php     # Notice CRUD
│   │   ├── AdminController.php      # Dashboards & profile
│   │   └── AuditController.php      # Audit log viewer
│   │
│   ├── models/
│   │   ├── User.php             # User CRUD + login tracking
│   │   ├── Student.php          # Student CRUD with user link
│   │   ├── Room.php             # Room management + occupancy
│   │   ├── Allocation.php       # Allocation + waitlist logic
│   │   ├── Payment.php          # Payment recording + receipts
│   │   ├── Complaint.php        # Complaint + SLA tracking
│   │   ├── Notice.php           # Notice CRUD
│   │   └── AuditLog.php         # Immutable audit logging
│   │
│   └── helpers/
│       ├── auth.php             # Auth checks, role guards, flash
│       ├── csrf.php             # CSRF token management
│       ├── sanitize.php         # Input sanitization functions
│       └── upload.php           # Secure file upload handler
│
├── views/
│   ├── layouts/
│   │   ├── main.php             # Dashboard layout (sidebar+nav)
│   │   ├── auth.php             # Login/reset layout
│   │   └── partials/
│   │       ├── navbar.php       # Top navigation bar
│   │       ├── sidebar.php      # Sidebar navigation
│   │       └── footer.php       # Footer
│   │
│   ├── auth/
│   │   ├── login.php            # Login form
│   │   └── forgot-password.php  # Password reset flow
│   │
│   ├── dashboard/
│   │   ├── admin.php            # Admin KPI dashboard
│   │   ├── student.php          # Student self-service dashboard
│   │   └── staff.php            # Staff ticket dashboard
│   │
│   ├── students/                # Student CRUD views
│   ├── rooms/                   # Room + allocation views
│   ├── payments/                # Payment + receipt views
│   ├── complaints/              # Complaint management views
│   ├── notices/                 # Notice board views
│   ├── audit/                   # Audit log viewer
│   └── errors/
│       └── 404.php              # Custom 404 page
│
├── public/
│   ├── css/custom.css           # Custom design system
│   ├── js/main.js               # Client-side interactions
│   ├── images/                  # Static images
│   └── uploads/                 # User uploads (gitignored)
│
├── database/
│   ├── hostelease.sql           # Complete MySQL schema
│   └── seeds/
│       └── admin_seed.php       # Default admin creator
│
├── logs/                        # Error logs (gitignored)
├── README.md                    # This file
└── DOCUMENTATION.md             # Full technical documentation
```

---

## 👥 User Roles & Permissions

| Role         | Access Level            | Capabilities                                    |
|:-------------|:------------------------|:------------------------------------------------|
| Super Admin  | Full system access      | Manage admins, audit logs, all reports          |
| Admin/Warden | Hostel-level access     | Students, rooms, payments, complaints, notices  |
| Student      | Own profile only        | View room, payment history, submit complaints   |
| Staff        | Assigned tasks only     | View & update assigned complaint tickets        |

---

## 📦 Modules Overview

### 1. Authentication Module
- Login with email/password
- bcrypt password hashing
- Login throttling: 5 failed attempts → 15 minute lockout
- Token-based password reset
- CSRF token on all forms
- Role-based dashboard redirect

### 2. Student Management
- Full CRUD (Create, Read, Update, Delete)
- Profile photo upload with MIME validation
- Student ID card/NID document upload
- Search by name, ID, or email
- Active/Suspended/Inactive status lifecycle

### 3. Room Management
- Room inventory with number, type, floor, capacity
- Real-time occupancy tracking with progress bars
- Filter by status, type, and floor
- Facilities description

### 4. Room Allocation
- Allocate student to room with capacity check
- Transfer between rooms (closes old, opens new)
- Vacate from room
- Waitlist queue when rooms are full
- Complete allocation history

### 5. Payment Management
- Manual payment recording (cash/bank/online)
- Auto-generated receipt numbers: `RCP-YYYYMM-XXXX`
- Fee structure management (monthly, one-time, yearly)
- Payment history with filters
- Printable receipt view
- Outstanding payment tracking

### 6. Complaint Management
- Student submits tickets with category and priority
- Admin assigns tickets to staff
- Status workflow: Open → In Progress → Resolved → Closed
- **SLA Tracking**: High priority flagged after 24h, Medium after 72h
- Overdue ticket highlighting

### 7. Notice Board
- Admin posts announcements
- Pinned notices appear first
- All users can view notices
- Edit/delete for admins

### 8. Audit Logging
- Every CUD operation logged automatically
- Records: user, action, table, record ID, details, IP, timestamp
- Viewable only by Super Admin
- Filterable by action, table, and date range

---

## 🔒 Security Features

| Layer              | Implementation                                      |
|:-------------------|:----------------------------------------------------|
| SQL Injection      | PDO prepared statements only — zero string concat   |
| XSS               | `htmlspecialchars()` on all output via `e()` helper  |
| CSRF               | Token on every POST form, `verifyToken()` in controllers |
| Passwords          | `password_hash()` bcrypt (cost 12)                  |
| Session            | Regenerate ID on login, httponly cookies, SameSite   |
| File Uploads       | `finfo_file()` MIME check + extension whitelist      |
| Login Throttle     | 5 failures → 15 min lockout via `login_attempts` table |
| Role Guard         | `requireRole()` at top of every protected action     |
| Directory Access   | `.htaccess` blocks `app/`, `config/`, `database/`    |
| Error Display      | `display_errors = Off`, logged to file only          |
| Security Headers   | X-Frame-Options, X-Content-Type-Options, Referrer-Policy |

---

## 🗺 Routes Reference

| URL                          | Controller::Action                  | Access                |
|:-----------------------------|:------------------------------------|:----------------------|
| `?url=auth/login`            | AuthController::login()             | Public                |
| `?url=auth/logout`           | AuthController::logout()            | Authenticated         |
| `?url=auth/forgot-password`  | AuthController::forgotPassword()    | Public                |
| `?url=dashboard/index`       | AdminController::dashboard()        | Admin, Super Admin    |
| `?url=dashboard/student`     | AdminController::student()          | Student               |
| `?url=dashboard/staff`       | AdminController::staff()            | Staff                 |
| `?url=students/index`        | StudentController::index()          | Admin, Super Admin    |
| `?url=students/create`       | StudentController::create()         | Admin, Super Admin    |
| `?url=students/show/{id}`    | StudentController::show()           | Admin, Student (own)  |
| `?url=students/edit/{id}`    | StudentController::edit()           | Admin, Super Admin    |
| `?url=rooms/index`           | RoomController::index()             | Admin, Super Admin    |
| `?url=rooms/create`          | RoomController::create()            | Admin, Super Admin    |
| `?url=allocations/allocate`  | AllocationController::allocate()    | Admin, Super Admin    |
| `?url=allocations/transfer`  | AllocationController::transfer()    | Admin, Super Admin    |
| `?url=payments/index`        | PaymentController::index()          | Admin, Super Admin    |
| `?url=payments/record`       | PaymentController::record()         | Admin, Super Admin    |
| `?url=complaints/index`      | ComplaintController::index()        | All authenticated     |
| `?url=complaints/create`     | ComplaintController::create()       | All authenticated     |
| `?url=notices/index`         | NoticeController::index()           | All authenticated     |
| `?url=audit/index`           | AuditController::index()            | Super Admin only      |

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## 📞 Support

For questions or issues, please open a GitHub Issue or contact the development team.

---

**Built with ❤️ by the HostelEase Team**
