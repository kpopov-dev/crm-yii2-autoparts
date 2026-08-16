<?php

declare(strict_types=1);

namespace app\repositories;

use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;

final class NotificationRepository extends AbstractRepository
{
    public function searchByUser(int $userId, bool $onlyUnread, Pagination $pagination): PagedResult
    {
        $where = 'n.user_id = :userId';
        $params = [':userId' => $userId];

        if ($onlyUnread) {
            $where .= ' AND n.is_read = 0';
        }

        $sql = "SELECT n.id, n.type, n.title, n.body, n.entity_type, n.entity_id, n.is_read, n.created_at
                  FROM {{%notification}} n
                 WHERE {$where}
                 ORDER BY n.id DESC
                 LIMIT :limit OFFSET :offset";

        $rows = $this->fetchAll($sql, $params + [
            ':limit' => $pagination->limit(),
            ':offset' => $pagination->offset(),
        ]);

        $total = (int)$this->fetchScalar(
            "SELECT COUNT(*) FROM {{%notification}} n WHERE {$where}",
            $params
        );

        $items = array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'type' => (string)$row['type'],
                'title' => (string)$row['title'],
                'body' => (string)($row['body'] ?? ''),
                'entityType' => $row['entity_type'] !== null ? (string)$row['entity_type'] : null,
                'entityId' => $row['entity_id'] !== null ? (int)$row['entity_id'] : null,
                'isRead' => (bool)$row['is_read'],
                'createdAt' => (int)$row['created_at'],
            ];
        }, $rows);

        return new PagedResult($items, $total, $pagination);
    }

    public function countUnread(int $userId): int
    {
        return (int)$this->fetchScalar(
            "SELECT COUNT(*) FROM {{%notification}} WHERE user_id = :userId AND is_read = 0",
            [':userId' => $userId]
        );
    }

    public function markAsRead(int $userId, array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return 0;
        }

        return $this->db->createCommand()->update(
            '{{%notification}}',
            ['is_read' => 1, 'read_at' => time()],
            ['user_id' => $userId, 'id' => $ids, 'is_read' => 0]
        )->execute();
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->db->createCommand()->update(
            '{{%notification}}',
            ['is_read' => 1, 'read_at' => time()],
            ['user_id' => $userId, 'is_read' => 0]
        )->execute();
    }
}
