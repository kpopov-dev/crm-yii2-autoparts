<?php

declare(strict_types=1);

namespace app\repositories;

use app\domain\Contract\TaskRepositoryInterface;
use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;
use app\domain\Enum\TaskStatus;

final class TaskRepository extends AbstractRepository implements TaskRepositoryInterface
{
    private const SORT_WHITELIST = [
        'id' => 't.id',
        'dueAt' => 't.due_at',
        'createdAt' => 't.created_at',
    ];

    public function search(array $filter, Pagination $pagination): PagedResult
    {
        [$where, $params] = $this->buildConditions($filter);

        $order = $this->resolveSort((string)($filter['sort'] ?? ''), self::SORT_WHITELIST, 't.due_at ASC');

        $sql = "SELECT t.id, t.title, t.status, t.due_at, t.created_at, t.completed_at,
                       t.deal_id, t.assignee_id,
                       d.number AS deal_number,
                       d.title AS deal_title,
                       u.full_name AS assignee_name
                  FROM {{%task}} t
                  INNER JOIN {{%user}} u ON u.id = t.assignee_id
                  LEFT JOIN {{%deal}} d ON d.id = t.deal_id
                 WHERE {$where}
                 ORDER BY {$order}
                 LIMIT :limit OFFSET :offset";

        $rows = $this->fetchAll($sql, $params + [
            ':limit' => $pagination->limit(),
            ':offset' => $pagination->offset(),
        ]);

        $total = (int)$this->fetchScalar(
            "SELECT COUNT(*) FROM {{%task}} t WHERE {$where}",
            $params
        );

        return new PagedResult(array_map([$this, 'hydrate'], $rows), $total, $pagination);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT t.*, d.number AS deal_number, d.title AS deal_title, u.full_name AS assignee_name
                  FROM {{%task}} t
                  INNER JOIN {{%user}} u ON u.id = t.assignee_id
                  LEFT JOIN {{%deal}} d ON d.id = t.deal_id
                 WHERE t.id = :id";

        $row = $this->fetchOne($sql, [':id' => $id]);
        if ($row === null) {
            return null;
        }

        $task = $this->hydrate($row);
        $task['description'] = (string)($row['description'] ?? '');

        return $task;
    }

    public function countOverdueByAssignee(int $assigneeId): int
    {
        return (int)$this->fetchScalar(
            "SELECT COUNT(*)
               FROM {{%task}}
              WHERE assignee_id = :assigneeId
                AND status IN ('open', 'in_progress')
                AND due_at < :now",
            [':assigneeId' => $assigneeId, ':now' => time()]
        );
    }

    private function buildConditions(array $filter): array
    {
        $conditions = ['1 = 1'];
        $params = [];

        if (!empty($filter['assigneeId'])) {
            $conditions[] = 't.assignee_id = :assigneeId';
            $params[':assigneeId'] = (int)$filter['assigneeId'];
        }

        if (!empty($filter['dealId'])) {
            $conditions[] = 't.deal_id = :dealId';
            $params[':dealId'] = (int)$filter['dealId'];
        }

        if (!empty($filter['status']) && TaskStatus::exists((string)$filter['status'])) {
            $conditions[] = 't.status = :status';
            $params[':status'] = (string)$filter['status'];
        }

        if (!empty($filter['onlyActive'])) {
            $conditions[] = "t.status IN ('open', 'in_progress')";
        }

        if (!empty($filter['overdue'])) {
            $conditions[] = "t.status IN ('open', 'in_progress') AND t.due_at < :now";
            $params[':now'] = time();
        }

        if (!empty($filter['dueTo'])) {
            $conditions[] = 't.due_at <= :dueTo';
            $params[':dueTo'] = (int)$filter['dueTo'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    private function hydrate(array $row): array
    {
        $status = (string)$row['status'];

        return [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'status' => $status,
            'statusLabel' => TaskStatus::label($status),
            'dueAt' => (int)$row['due_at'],
            'isOverdue' => !TaskStatus::isFinal($status) && (int)$row['due_at'] < time(),
            'dealId' => $row['deal_id'] !== null ? (int)$row['deal_id'] : null,
            'dealNumber' => $row['deal_number'] !== null ? (string)$row['deal_number'] : null,
            'dealTitle' => $row['deal_title'] !== null ? (string)$row['deal_title'] : null,
            'assigneeId' => (int)$row['assignee_id'],
            'assigneeName' => (string)($row['assignee_name'] ?? ''),
            'createdAt' => (int)$row['created_at'],
            'completedAt' => $row['completed_at'] !== null ? (int)$row['completed_at'] : null,
        ];
    }
}
