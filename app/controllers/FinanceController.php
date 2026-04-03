<?php
/**
 * HostelEase — Finance Controller (Super Admin Only)
 */

require_once APP_ROOT . '/app/models/AuditLog.php';

class FinanceController
{
    /**
     * Track all income and expenses
     */
    public function index(): void
    {
        requireRole(['super_admin']);

        $db = Database::getInstance();

        // Totals
        $income = $db->query("SELECT SUM(amount) as s FROM transactions WHERE type='income'")->fetch()['s'] ?? 0;
        $expense = $db->query("SELECT SUM(amount) as s FROM transactions WHERE type='expense'")->fetch()['s'] ?? 0;
        $balance = $income - $expense;

        // Recent 100 Transactions
        $transactions = $db->query("
            SELECT t.*, u.full_name as recorder_name 
            FROM transactions t
            LEFT JOIN users u ON t.recorded_by = u.id
            ORDER BY t.created_at DESC
            LIMIT 100
        ")->fetchAll();

        $pageTitle = 'Hostel Finances';
        ob_start();
        require_once APP_ROOT . '/views/finances/index.php';
        $viewContent = ob_get_clean();
        require_once APP_ROOT . '/views/layouts/main.php';
    }
}
