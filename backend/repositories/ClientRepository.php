<?php

declare(strict_types=1);

namespace app\repositories;

use app\domain\Contract\ClientRepositoryInterface;
use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;

final class ClientRepository extends AbstractRepository implements ClientRepositoryInterface
{
    private const SORT_WHITELIST = [
        'id' => 'c.id',
        'name' => 'c.name',
        'createdAt' => 'c.created_at',
    ];

    public function search(array $filter, Pagination $pagination): PagedResult
    {
        [$where, $params] = $this->buildConditions($filter);

        $order = $this->resolveSort((string)($filter['sort'] ?? ''), self::SORT_WHITELIST, 'c.id DESC');

        $sql = "SELECT c.id, c.name, c.email, c.phone, c.inn, c.is_active, c.created_at,
                       c.manager_id, u.full_name AS manager_name
                  FROM {{%client}} c
                  INNER JOIN {{%user}} u ON u.id = c.manager_id
                 WHERE {$where}
                 ORDER BY {$order}
                 LIMIT :limit OFFSET :offset";

        $rows = $this->fetchAll($sql, $params + [
            ':limit' => $pagination->limit(),
            ':offset' => $pagination->offset(),
        ]);

        $total = (int)$this->fetchScalar(
            "SELECT COUNT(*) FROM {{%client}} c WHERE {$where}",
            $params
        );

        return new PagedResult(array_map([$this, 'hydrate'], $rows), $total, $pagination);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT c.*, u.full_name AS manager_name
                  FROM {{%client}} c
                  INNER JOIN {{%user}} u ON u.id = c.manager_id
                 WHERE c.id = :id";

        $row = $this->fetchOne($sql, [':id' => $id]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function findByIdWithStats(int $id): ?array
    {
        $client = $this->findById($id);
        if ($client === null) {
            return null;
        }

        $sql = "SELECT COUNT(*) AS deals_total,
                       SUM(CASE WHEN d.stage = 'won' THEN 1 ELSE 0 END) AS deals_won,
                       COALESCE(SUM(CASE WHEN d.stage = 'won' THEN d.amount ELSE 0 END), 0) AS won_amount,
                       COALESCE(SUM(CASE WHEN d.stage NOT IN ('won', 'lost') THEN d.amount ELSE 0 END), 0) AS open_amount
                  FROM {{%deal}} d
                 WHERE d.client_id = :id";

        $stats = $this->fetchOne($sql, [':id' => $id]) ?? [];

        $client['stats'] = [
            'dealsTotal' => (int)($stats['deals_total'] ?? 0),
            'dealsWon' => (int)($stats['deals_won'] ?? 0),
            'wonAmount' => round((float)($stats['won_amount'] ?? 0), 2),
            'openAmount' => round((float)($stats['open_amount'] ?? 0), 2),
        ];

        return $client;
    }

    public function existsByEmail(string $email, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM {{%client}} WHERE email = :email";
        $params = [':email' => mb_strtolower(trim($email))];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptId;
        }

        return $this->fetchScalar($sql . ' LIMIT 1', $params) !== false;
    }

    private function buildConditions(array $filter): array
    {
        $conditions = ['1 = 1'];
        $params = [];

        if (!empty($filter['managerId'])) {
            $conditions[] = 'c.manager_id = :managerId';
            $params[':managerId'] = (int)$filter['managerId'];
        }

        if (isset($filter['isActive']) && $filter['isActive'] !== '') {
            $conditions[] = 'c.is_active = :isActive';
            $params[':isActive'] = (int)(bool)$filter['isActive'];
        }

        if (!empty($filter['query'])) {
            $conditions[] = '(c.name LIKE :query OR c.email LIKE :query OR c.inn LIKE :query)';
            $params[':query'] = $this->likePrefix((string)$filter['query']);
        }

        if (!empty($filter['createdFrom'])) {
            $conditions[] = 'c.created_at >= :createdFrom';
            $params[':createdFrom'] = (int)$filter['createdFrom'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    private function hydrate(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'email' => $row['email'] !== null ? (string)$row['email'] : null,
            'phone' => $row['phone'] !== null ? (string)$row['phone'] : null,
            'inn' => $row['inn'] !== null ? (string)$row['inn'] : null,
            'comment' => isset($row['comment']) ? (string)$row['comment'] : null,
            'isActive' => (bool)$row['is_active'],
            'managerId' => (int)$row['manager_id'],
            'managerName' => (string)($row['manager_name'] ?? ''),
            'createdAt' => (int)$row['created_at'],
        ];
    }
}
