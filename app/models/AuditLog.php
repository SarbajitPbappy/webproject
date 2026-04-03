<?php
/**
 * HostelEase — Audit Log Model
 * 
 * Immutable record of every create/update/delete action.
 * Called from every controller after mutations.
 */

require_once APP_ROOT . '/config/database.php';

class AuditLog
{
    /**
     * Log an action. Static method for convenience.
     *
     * @param int|null $userId     The user performing the action
     * @param string   $action     Action type (CREATE, UPDATE, DELETE, LOGIN, etc.)
     * @param string   $tableName  Table affected
     * @param int|null $recordId   ID of the affected record
     * @param string   $details    Additional context
     */
    public static function log(
        ?int $userId,
        string $action,
        string $tableName,
        ?int $recordId = null,
        string $details = ''
    ): void {
        try {
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address)
                 VALUES (:user_id, :action, :table_name, :record_id, :details, :ip)",
                [
                    'user_id'    => $userId,
                    'action'     => $action,
                    'table_name' => $tableName,
                    'record_id'  => $recordId,
                    'details'    => $details,
                    'ip'         => getClientIP(),
                ]
            );
        } catch (\Exception $e) {
            // Audit logging should never break the main flow
            error_log('AuditLog::log() failed: ' . $e->getMessage());
        }
    }

    /**
     * Get all audit logs with optional filters.
     *
     * @param array $filters Optional filters (user_id, action, table_name, date_from, date_to)
     * @param int   $limit
     * @param int   $offset
     * @return array
     */
    public static function all(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance();
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "a.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = "a.action = :action";
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['table_name'])) {
            $where[] = "a.table_name = :table_name";
            $params['table_name'] = $filters['table_name'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "a.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "a.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT a.*, u.full_name as user_name, u.email as user_email
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                $whereClause
                ORDER BY a.created_at DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $db->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Count total audit records (for pagination).
     *
     * @param array $filters
     * @return int
     */
    public static function count(array $filters = []): int
    {
        $db = Database::getInstance();
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = "action = :action";
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['table_name'])) {
            $where[] = "table_name = :table_name";
            $params['table_name'] = $filters['table_name'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $db->query("SELECT COUNT(*) as total FROM audit_logs $whereClause", $params);
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Get recent activity (for dashboard).
     *
     * @param int $limit Number of recent entries
     * @return array
     */
    public static function recent(int $limit = 10): array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT a.*, u.full_name as user_name
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             ORDER BY a.created_at DESC
             LIMIT :lim",
            ['lim' => $limit]
        );
        return $stmt->fetchAll();
    }
}
