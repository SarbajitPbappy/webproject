<?php
/**
 * HostelEase — Application Entry Point & Router
 * 
 * All requests are routed through this file via .htaccess.
 * URL format: ?url=controller/action/param1/param2
 * 
 * Example:
 *   /students/create  → StudentController::create()
 *   /auth/login       → AuthController::login()
 *   /rooms/edit/5      → RoomController::edit(5)
 */

// ─── Define application root ────────────────────────────────────────
define('APP_ROOT', __DIR__);

// ─── Load core configuration and session ─────────────────────────────
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/session.php';
require_once APP_ROOT . '/config/database.php';

// ─── Load helpers ────────────────────────────────────────────────────
require_once APP_ROOT . '/app/helpers/auth.php';
require_once APP_ROOT . '/app/helpers/csrf.php';
require_once APP_ROOT . '/app/helpers/sanitize.php';

// ─── Parse URL ───────────────────────────────────────────────────────
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$url = filter_var($url, FILTER_SANITIZE_URL);
$parts = $url ? explode('/', $url) : [];

// ─── Determine controller, action, and parameters ───────────────────
$controllerName = !empty($parts[0]) ? ucfirst(strtolower($parts[0])) : 'Auth';
$actionName     = !empty($parts[1]) ? $parts[1] : 'login';
$params         = array_slice($parts, 2);

// ─── Map URL segments to controller classes ─────────────────────────
$controllerMap = [
    'Landing'    => 'LandingController',
    'Auth'       => 'AuthController',
    'Users'      => 'UserController', 'User' => 'UserController',
    'Students'   => 'StudentController', 'Student' => 'StudentController',
    'Rooms'      => 'RoomController', 'Room' => 'RoomController',
    'Allocations'=> 'AllocationController', 'Allocation' => 'AllocationController',
    'Payments'   => 'PaymentController', 'Payment' => 'PaymentController',
    'Complaints' => 'ComplaintController', 'Complaint' => 'ComplaintController',
    'Notices'    => 'NoticeController', 'Notice' => 'NoticeController',
    'Dashboard'  => 'AdminController',
    'Admin'      => 'AdminController',
    'Audit'      => 'AuditController',
    'Profile'    => 'ProfileController',
    'Payroll'    => 'PayrollController',
    'Finances'   => 'FinanceController', 'Finance' => 'FinanceController',
    'Billing'    => 'BillingController',
];

// ─── Default route: redirect logged-in users to dashboard ───────────
if (empty($url) || $url === '/') {
    $controllerName = 'Landing';
    $actionName = 'index';
}

// ─── Resolve controller ─────────────────────────────────────────────
if (!isset($controllerMap[$controllerName])) {
    http_response_code(404);
    require_once APP_ROOT . '/views/errors/404.php';
    exit;
}

$controllerClass = $controllerMap[$controllerName];
$controllerFile  = APP_ROOT . '/app/controllers/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    require_once APP_ROOT . '/views/errors/404.php';
    exit;
}

require_once $controllerFile;

// ─── Instantiate controller and call action ─────────────────────────
$controller = new $controllerClass();

// Convert action names with hyphens to camelCase (e.g., forgot-password → forgotPassword)
$actionName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $actionName))));

if (!method_exists($controller, $actionName)) {
    http_response_code(404);
    require_once APP_ROOT . '/views/errors/404.php';
    exit;
}

// ─── Execute the controller action ──────────────────────────────────
call_user_func_array([$controller, $actionName], $params);
