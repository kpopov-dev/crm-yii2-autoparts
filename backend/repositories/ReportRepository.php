<?php

declare(strict_types=1);

namespace app\repositories;

use app\domain\Contract\ReportRepositoryInterface;
use app\domain\Dto\DateRange;
use app\domain\Enum\DealStage;

final class ReportRepository extends AbstractRepository implements ReportRepositoryInterface
{
    public function funnel(DateRange $range, ?int $managerId): array
    {
        $where = 'd.created_at BETWEEN :from AND :to';
        $params = [':from' => $range->from(), ':to' => $range->to()];

        if ($managerId !== null) {
            $where = 'd.responsible_id = :managerId AND ' . $where;
            $params[':managerId'] = $managerId;
        }

        $sql = "SELECT d.stage,
                       COUNT(*) AS deals_count,
                       COALESCE(SUM(d.amount), 0) AS deals_amount,
                       COALESCE(AVG(d.amount), 0) AS avg_amount
                  FROM {{%deal}} d
                 WHERE {$where}
                 GROUP BY d.stage";

        $rows = $this->fetchAll($sql, $params);

        $byStage = [];
        foreach ($rows as $row) {
            $byStage[(string)$row['stage']] = $row;
        }

        $result = [];
        foreach (DealStage::all() as $stage) {
            $row = $byStage[$stage] ?? null;
            $result[] = [
                'stage' => $stage,
                'label' => DealStage::label($stage),
                'count' => (int)($row['deals_count'] ?? 0),
                'amount' => round((float)($row['deals_amount'] ?? 0), 2),
                'avgAmount' => round((float)($row['avg_amount'] ?? 0), 2),
            ];
        }

        return $result;
    }

    public function managerPerformance(DateRange $range): array
    {
        $sql = "SELECT u.id AS manager_id,
                       u.full_name AS manager_name,
                       COUNT(d.id) AS deals_total,
                       SUM(CASE WHEN d.stage = 'won' THEN 1 ELSE 0 END) AS deals_won,
                       SUM(CASE WHEN d.stage = 'lost' THEN 1 ELSE 0 END) AS deals_lost,
                       COALESCE(SUM(CASE WHEN d.stage = 'won' THEN d.amount ELSE 0 END), 0) AS won_amount,
                       COALESCE(SUM(CASE WHEN d.stage NOT IN ('won', 'lost') THEN d.amount ELSE 0 END), 0) AS open_amount,
                       COALESCE(AVG(CASE WHEN d.stage = 'won' THEN d.closed_at - d.created_at END), 0) AS avg_cycle
                  FROM {{%user}} u
                  LEFT JOIN {{%deal}} d
                         ON d.responsible_id = u.id
                        AND d.created_at BETWEEN :from AND :to
                 WHERE u.is_active = 1
                 GROUP BY u.id, u.full_name
                 ORDER BY won_amount DESC, deals_won DESC";

        $rows = $this->fetchAll($sql, [':from' => $range->from(), ':to' => $range->to()]);

        return array_map(static function (array $row): array {
            $total = (int)$row['deals_total'];
            $won = (int)$row['deals_won'];

            return [
                'managerId' => (int)$row['manager_id'],
                'managerName' => (string)$row['manager_name'],
                'dealsTotal' => $total,
                'dealsWon' => $won,
                'dealsLost' => (int)$row['deals_lost'],
                'wonAmount' => round((float)$row['won_amount'], 2),
                'openAmount' => round((float)$row['open_amount'], 2),
                'conversion' => $total > 0 ? round($won / $total * 100, 2) : 0.0,
                'avgCycleDays' => round((float)$row['avg_cycle'] / 86400, 1),
            ];
        }, $rows);
    }

    public function revenueByMonth(DateRange $range): array
    {
        $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(d.closed_at), '%Y-%m') AS period,
                       COUNT(*) AS deals_won,
                       COALESCE(SUM(d.amount), 0) AS revenue
                  FROM {{%deal}} d
                 WHERE d.stage = 'won'
                   AND d.closed_at BETWEEN :from AND :to
                 GROUP BY period
                 ORDER BY period ASC";

        $rows = $this->fetchAll($sql, [':from' => $range->from(), ':to' => $range->to()]);

        return array_map(static function (array $row): array {
            return [
                'period' => (string)$row['period'],
                'dealsWon' => (int)$row['deals_won'],
                'revenue' => round((float)$row['revenue'], 2),
            ];
        }, $rows);
    }

    public function conversion(DateRange $range, ?int $managerId): array
    {
        $where = 'd.created_at BETWEEN :from AND :to';
        $params = [':from' => $range->from(), ':to' => $range->to()];

        if ($managerId !== null) {
            $where = 'd.responsible_id = :managerId AND ' . $where;
            $params[':managerId'] = $managerId;
        }

        $sql = "SELECT COUNT(*) AS total,
                       SUM(CASE WHEN d.stage = 'won' THEN 1 ELSE 0 END) AS won,
                       SUM(CASE WHEN d.stage = 'lost' THEN 1 ELSE 0 END) AS lost,
                       SUM(CASE WHEN d.stage NOT IN ('won', 'lost') THEN 1 ELSE 0 END) AS active,
                       COALESCE(SUM(CASE WHEN d.stage = 'won' THEN d.amount ELSE 0 END), 0) AS won_amount,
                       COALESCE(SUM(d.amount), 0) AS total_amount
                  FROM {{%deal}} d
                 WHERE {$where}";

        $row = $this->fetchOne($sql, $params) ?? [];

        $total = (int)($row['total'] ?? 0);
        $won = (int)($row['won'] ?? 0);

        return [
            'total' => $total,
            'won' => $won,
            'lost' => (int)($row['lost'] ?? 0),
            'active' => (int)($row['active'] ?? 0),
            'wonAmount' => round((float)($row['won_amount'] ?? 0), 2),
            'totalAmount' => round((float)($row['total_amount'] ?? 0), 2),
            'conversion' => $total > 0 ? round($won / $total * 100, 2) : 0.0,
            'averageCheck' => $won > 0 ? round((float)($row['won_amount'] ?? 0) / $won, 2) : 0.0,
        ];
    }

    public function overdueTasks(): array
    {
        $sql = "SELECT u.id AS manager_id,
                       u.full_name AS manager_name,
                       COUNT(t.id) AS overdue_count,
                       MIN(t.due_at) AS oldest_due_at
                  FROM {{%task}} t
                  INNER JOIN {{%user}} u ON u.id = t.assignee_id
                 WHERE t.status IN ('open', 'in_progress')
                   AND t.due_at < :now
                 GROUP BY u.id, u.full_name
                 ORDER BY overdue_count DESC";

        $rows = $this->fetchAll($sql, [':now' => time()]);

        return array_map(static function (array $row): array {
            return [
                'managerId' => (int)$row['manager_id'],
                'managerName' => (string)$row['manager_name'],
                'overdueCount' => (int)$row['overdue_count'],
                'oldestDueAt' => (int)$row['oldest_due_at'],
            ];
        }, $rows);
    }

    public function dailyStats(DateRange $range): array
    {
        $sql = "SELECT s.stat_date,
                       SUM(s.deals_created) AS deals_created,
                       SUM(s.deals_won) AS deals_won,
                       SUM(s.deals_lost) AS deals_lost,
                       COALESCE(SUM(s.won_amount), 0) AS won_amount
                  FROM {{%daily_stat}} s
                 WHERE s.stat_date BETWEEN :from AND :to
                 GROUP BY s.stat_date
                 ORDER BY s.stat_date ASC";

        $rows = $this->fetchAll($sql, [
            ':from' => date('Y-m-d', $range->from()),
            ':to' => date('Y-m-d', $range->to()),
        ]);

        return array_map(static function (array $row): array {
            return [
                'date' => (string)$row['stat_date'],
                'dealsCreated' => (int)$row['deals_created'],
                'dealsWon' => (int)$row['deals_won'],
                'dealsLost' => (int)$row['deals_lost'],
                'wonAmount' => round((float)$row['won_amount'], 2),
            ];
        }, $rows);
    }

    public function explain(string $sql, array $params): array
    {
        return $this->fetchAll('EXPLAIN ' . $sql, $params);
    }
}
