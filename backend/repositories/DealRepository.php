<?php

declare(strict_types=1);

namespace app\repositories;

use app\domain\Contract\DealRepositoryInterface;
use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;
use app\domain\Enum\DealStage;

final class DealRepository extends AbstractRepository implements DealRepositoryInterface
{
    private const SORT_WHITELIST = [
        'id' => 'd.id',
        'amount' => 'd.amount',
        'createdAt' => 'd.created_at',
        'updatedAt' => 'd.updated_at',
    ];

    private const BOARD_LIMIT_PER_STAGE = 50;

    public function search(array $filter, Pagination $pagination): PagedResult
    {
        [$where, $params] = $this->buildConditions($filter);

        $order = $this->resolveSort((string)($filter['sort'] ?? ''), self::SORT_WHITELIST, 'd.id DESC');

        $sql = "SELECT d.id, d.number, d.title, d.amount, d.currency, d.stage,
                       d.client_id, d.responsible_id, d.created_at, d.updated_at, d.closed_at,
                       c.name AS client_name,
                       u.full_name AS responsible_name,
                       (SELECT COUNT(*) FROM {{%task}} t
                         WHERE t.deal_id = d.id AND t.status IN ('open', 'in_progress')) AS open_tasks
                  FROM {{%deal}} d
                  INNER JOIN {{%client}} c ON c.id = d.client_id
                  INNER JOIN {{%user}} u ON u.id = d.responsible_id
                 WHERE {$where}
                 ORDER BY {$order}
                 LIMIT :limit OFFSET :offset";

        $rows = $this->fetchAll($sql, $params + [
            ':limit' => $pagination->limit(),
            ':offset' => $pagination->offset(),
        ]);

        $total = (int)$this->fetchScalar(
            "SELECT COUNT(*) FROM {{%deal}} d WHERE {$where}",
            $params
        );

        return new PagedResult(array_map([$this, 'hydrate'], $rows), $total, $pagination);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT d.*, c.name AS client_name, u.full_name AS responsible_name,
                       0 AS open_tasks
                  FROM {{%deal}} d
                  INNER JOIN {{%client}} c ON c.id = d.client_id
                  INNER JOIN {{%user}} u ON u.id = d.responsible_id
                 WHERE d.id = :id";

        $row = $this->fetchOne($sql, [':id' => $id]);
        if ($row === null) {
            return null;
        }

        $deal = $this->hydrate($row);
        $deal['description'] = (string)($row['description'] ?? '');

        return $deal;
    }

    public function board(array $filter): array
    {
        [$where, $params] = $this->buildConditions($filter);

        $sql = "SELECT d.id, d.number, d.title, d.amount, d.currency, d.stage,
                       d.client_id, d.responsible_id, d.created_at, d.updated_at, d.closed_at,
                       c.name AS client_name,
                       u.full_name AS responsible_name,
                       0 AS open_tasks
                  FROM (
                        SELECT id,
                               ROW_NUMBER() OVER (PARTITION BY stage ORDER BY updated_at DESC, id DESC) AS rn
                          FROM {{%deal}} d
                         WHERE {$where}
                       ) ranked
                  INNER JOIN {{%deal}} d ON d.id = ranked.id
                  INNER JOIN {{%client}} c ON c.id = d.client_id
                  INNER JOIN {{%user}} u ON u.id = d.responsible_id
                 WHERE ranked.rn <= :perStage
                 ORDER BY d.updated_at DESC, d.id DESC";

        $rows = $this->fetchAll($sql, $params + [':perStage' => self::BOARD_LIMIT_PER_STAGE]);

        $totals = $this->fetchAll(
            "SELECT stage, COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total
               FROM {{%deal}} d
              WHERE {$where}
              GROUP BY stage",
            $params
        );

        $totalsByStage = [];
        foreach ($totals as $row) {
            $totalsByStage[(string)$row['stage']] = [
                'count' => (int)$row['cnt'],
                'amount' => round((float)$row['total'], 2),
            ];
        }

        $columns = [];
        foreach (DealStage::all() as $stage) {
            $columns[$stage] = [
                'stage' => $stage,
                'label' => DealStage::label($stage),
                'count' => $totalsByStage[$stage]['count'] ?? 0,
                'amount' => $totalsByStage[$stage]['amount'] ?? 0.0,
                'items' => [],
            ];
        }

        foreach ($rows as $row) {
            $stage = (string)$row['stage'];
            if (!isset($columns[$stage])) {
                continue;
            }
            $columns[$stage]['items'][] = $this->hydrate($row);
        }

        return array_values($columns);
    }

    public function stageHistory(int $dealId): array
    {
        $sql = "SELECT h.id, h.stage_from, h.stage_to, h.comment, h.created_at,
                       u.full_name AS user_name
                  FROM {{%deal_stage_history}} h
                  INNER JOIN {{%user}} u ON u.id = h.user_id
                 WHERE h.deal_id = :dealId
                 ORDER BY h.id DESC";

        return array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'stageFrom' => $row['stage_from'] !== null ? (string)$row['stage_from'] : null,
                'stageFromLabel' => $row['stage_from'] !== null ? DealStage::label((string)$row['stage_from']) : null,
                'stageTo' => (string)$row['stage_to'],
                'stageToLabel' => DealStage::label((string)$row['stage_to']),
                'comment' => (string)($row['comment'] ?? ''),
                'userName' => (string)$row['user_name'],
                'createdAt' => (int)$row['created_at'],
            ];
        }, $this->fetchAll($sql, [':dealId' => $dealId]));
    }

    public function nextNumber(): string
    {
        $year = (int)date('Y');
        $prefix = sprintf('ZP-%d-', $year);

        $last = $this->fetchScalar(
            "SELECT number FROM {{%deal}}
              WHERE number LIKE :prefix
              ORDER BY id DESC
              LIMIT 1",
            [':prefix' => $prefix . '%']
        );

        $sequence = 1;
        if (is_string($last)) {
            $sequence = (int)substr($last, strlen($prefix)) + 1;
        }

        return $prefix . str_pad((string)$sequence, 5, '0', STR_PAD_LEFT);
    }

    private function buildConditions(array $filter): array
    {
        $conditions = ['1 = 1'];
        $params = [];

        if (!empty($filter['responsibleId'])) {
            $conditions[] = 'd.responsible_id = :responsibleId';
            $params[':responsibleId'] = (int)$filter['responsibleId'];
        }

        if (!empty($filter['clientId'])) {
            $conditions[] = 'd.client_id = :clientId';
            $params[':clientId'] = (int)$filter['clientId'];
        }

        if (!empty($filter['stage']) && DealStage::exists((string)$filter['stage'])) {
            $conditions[] = 'd.stage = :stage';
            $params[':stage'] = (string)$filter['stage'];
        }

        if (!empty($filter['onlyOpen'])) {
            $conditions[] = "d.stage NOT IN ('won', 'lost')";
        }

        if (!empty($filter['createdFrom'])) {
            $conditions[] = 'd.created_at >= :createdFrom';
            $params[':createdFrom'] = (int)$filter['createdFrom'];
        }

        if (!empty($filter['createdTo'])) {
            $conditions[] = 'd.created_at <= :createdTo';
            $params[':createdTo'] = (int)$filter['createdTo'];
        }

        if (!empty($filter['amountFrom'])) {
            $conditions[] = 'd.amount >= :amountFrom';
            $params[':amountFrom'] = (float)$filter['amountFrom'];
        }

        if (!empty($filter['query'])) {
            $conditions[] = '(d.number LIKE :query OR d.title LIKE :query)';
            $params[':query'] = $this->likePrefix((string)$filter['query']);
        }

        return [implode(' AND ', $conditions), $params];
    }

    private function hydrate(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'number' => (string)$row['number'],
            'title' => (string)$row['title'],
            'amount' => round((float)$row['amount'], 2),
            'currency' => (string)$row['currency'],
            'stage' => (string)$row['stage'],
            'stageLabel' => DealStage::label((string)$row['stage']),
            'clientId' => (int)$row['client_id'],
            'clientName' => (string)($row['client_name'] ?? ''),
            'responsibleId' => (int)$row['responsible_id'],
            'responsibleName' => (string)($row['responsible_name'] ?? ''),
            'openTasks' => (int)($row['open_tasks'] ?? 0),
            'createdAt' => (int)$row['created_at'],
            'updatedAt' => (int)($row['updated_at'] ?? 0),
            'closedAt' => $row['closed_at'] !== null ? (int)$row['closed_at'] : null,
        ];
    }
}
