<?php
/**
 * HostelEase — Audit Log Controller
 * Access: super_admin only
 */

require_once APP_ROOT . '/app/models/AuditLog.php';

class AuditController
{
    public function index(): void
    {
        requireRole(['super_admin']);

        $filters = [];
        if (!empty($_GET['action'])) $filters['action'] = sanitize($_GET['action']);
        if (!empty($_GET['table_name'])) $filters['table_name'] = sanitize($_GET['table_name']);
        if (!empty($_GET['date_from'])) $filters['date_from'] = sanitize($_GET['date_from']);
        if (!empty($_GET['date_to'])) $filters['date_to'] = sanitize($_GET['date_to']);

        $logs = AuditLog::all($filters, 100);
        $pageTitle = 'Audit Logs';

        ob_start();
        require_once APP_ROOT . '/views/audit/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }
}
