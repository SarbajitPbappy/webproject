<?php
/**
 * Per-user in-app notifications (billing, etc.).
 */

require_once APP_ROOT . '/config/database.php';

class UserNotification
{
    private static function tableReady(Database $db): bool
    {
        try {
            $stmt = $db->query(
                "SELECT COUNT(*) AS c FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications'"
            );
            return (int) $stmt->fetch()['c'] > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function unreadCountForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $db = Database::getInstance();
        if (!self::tableReady($db)) {
            return 0;
        }
        $stmt = $db->query(
            'SELECT COUNT(*) AS c FROM user_notifications WHERE user_id = :u AND is_read = 0',
            ['u' => $userId]
        );
        return (int) ($stmt->fetch()['c'] ?? 0);
    }

    /**
     * Notify each student's login account after billing slips are issued.
     *
     * @param int[] $studentIds students.id values
     */
    public static function notifyBillingIssued(array $studentIds, string $periodMonth): void
    {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        if (empty($studentIds)) {
            return;
        }
        $db = Database::getInstance();
        if (!self::tableReady($db)) {
            error_log('user_notifications table missing; run database/migrations/migrate_user_notifications.php');

            return;
        }

        $title = 'New hall bill — ' . $periodMonth;
        $body = 'The office has posted new fee slip(s) for ' . $periodMonth
            . '. Open Payments, then Pay fees online to view and pay.';

        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $stmt = $db->getConnection()->prepare(
            "SELECT id, user_id FROM students WHERE id IN ($placeholders)"
        );
        $stmt->execute($studentIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ins = $db->getConnection()->prepare(
            'INSERT INTO user_notifications (user_id, title, body, notification_type)
             VALUES (:uid, :title, :body, :typ)'
        );
        foreach ($rows as $row) {
            $uid = (int) $row['user_id'];
            if ($uid <= 0) {
                continue;
            }
            $ins->execute([
                'uid'   => $uid,
                'title' => $title,
                'body'  => $body,
                'typ'   => 'billing',
            ]);
        }
    }

    /**
     * One notification for the login user tied to a students.id row.
     */
    public static function notifyStudentAccount(int $studentId, string $title, string $body, string $type = 'general'): void
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) {
            return;
        }
        $db = Database::getInstance();
        if (!self::tableReady($db)) {
            return;
        }
        $stmt = $db->query(
            'SELECT user_id FROM students WHERE id = :sid LIMIT 1',
            ['sid' => $studentId]
        );
        $row = $stmt->fetch();
        $uid = (int) ($row['user_id'] ?? 0);
        if ($uid <= 0) {
            return;
        }
        $db->query(
            'INSERT INTO user_notifications (user_id, title, body, notification_type)
             VALUES (:uid, :title, :body, :typ)',
            [
                'uid'   => $uid,
                'title' => $title,
                'body'  => $body,
                'typ'   => $type,
            ]
        );
    }

    public static function forUser(int $userId, int $limit = 50): array
    {
        $db = Database::getInstance();
        if (!self::tableReady($db)) {
            return [];
        }
        $stmt = $db->query(
            'SELECT * FROM user_notifications WHERE user_id = :u ORDER BY created_at DESC LIMIT ' . (int) $limit,
            ['u' => $userId]
        );
        return $stmt->fetchAll();
    }

    public static function markRead(int $notificationId, int $userId): bool
    {
        $db = Database::getInstance();
        if (!self::tableReady($db)) {
            return false;
        }
        $db->query(
            'UPDATE user_notifications SET is_read = 1 WHERE id = :id AND user_id = :u',
            ['id' => $notificationId, 'u' => $userId]
        );
        return true;
    }

    public static function markAllRead(int $userId): void
    {
        $db = Database::getInstance();
        if (!self::tableReady($db)) {
            return;
        }
        $db->query(
            'UPDATE user_notifications SET is_read = 1 WHERE user_id = :u AND is_read = 0',
            ['u' => $userId]
        );
    }
}
