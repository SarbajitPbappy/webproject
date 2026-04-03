<?php
/**
 * HostelEase — Notice Model
 */

require_once APP_ROOT . '/config/database.php';

class Notice
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT n.*, u.full_name as posted_by_name
             FROM notices n
             LEFT JOIN users u ON n.posted_by = u.id
             ORDER BY n.is_pinned DESC, n.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $this->db->query(
            "INSERT INTO notices (title, body, posted_by, is_pinned)
             VALUES (:title, :body, :posted_by, :is_pinned)",
            [
                'title'     => $data['title'],
                'body'      => $data['body'],
                'posted_by' => $data['posted_by'],
                'is_pinned' => $data['is_pinned'] ?? false,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->query(
            "SELECT n.*, u.full_name as posted_by_name
             FROM notices n LEFT JOIN users u ON n.posted_by = u.id
             WHERE n.id = :id LIMIT 1",
            ['id' => $id]
        );
        return $stmt->fetch();
    }

    public function update(int $id, array $data): bool
    {
        $this->db->query(
            "UPDATE notices SET title = :title, body = :body, is_pinned = :is_pinned WHERE id = :id",
            [
                'title'     => $data['title'],
                'body'      => $data['body'],
                'is_pinned' => $data['is_pinned'] ?? false,
                'id'        => $id,
            ]
        );
        return true;
    }

    public function delete(int $id): bool
    {
        $this->db->query("DELETE FROM notices WHERE id = :id", ['id' => $id]);
        return true;
    }

    public function getPinned(): array
    {
        $stmt = $this->db->query(
            "SELECT n.*, u.full_name as posted_by_name
             FROM notices n LEFT JOIN users u ON n.posted_by = u.id
             WHERE n.is_pinned = TRUE ORDER BY n.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function getRecent(int $limit = 5): array
    {
        $stmt = $this->db->query(
            "SELECT n.*, u.full_name as posted_by_name
             FROM notices n LEFT JOIN users u ON n.posted_by = u.id
             ORDER BY n.is_pinned DESC, n.created_at DESC LIMIT :lim",
            ['lim' => $limit]
        );
        return $stmt->fetchAll();
    }
}
