<?php

declare(strict_types=1);

namespace app\messaging\Handler;

use app\domain\Contract\EventHandlerInterface;
use app\domain\Dto\EventMessage;
use app\domain\Enum\EventName;
use yii\db\Connection;

final class AnalyticsHandler implements EventHandlerInterface
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function supports(string $eventName): bool
    {
        return in_array($eventName, [
            EventName::DEAL_CREATED,
            EventName::DEAL_WON,
            EventName::DEAL_LOST,
        ], true);
    }

    public function handle(EventMessage $message): void
    {
        $managerId = (int)$message->get('responsibleId');

        if ($managerId <= 0) {
            return;
        }

        $date = date('Y-m-d', $message->occurredAt());
        $amount = (float)$message->get('amount', 0);

        $increments = [
            EventName::DEAL_CREATED => ['deals_created' => 1, 'deals_won' => 0, 'deals_lost' => 0, 'won_amount' => 0.0],
            EventName::DEAL_WON => ['deals_created' => 0, 'deals_won' => 1, 'deals_lost' => 0, 'won_amount' => $amount],
            EventName::DEAL_LOST => ['deals_created' => 0, 'deals_won' => 0, 'deals_lost' => 1, 'won_amount' => 0.0],
        ];

        $delta = $increments[$message->name()];

        $sql = "INSERT INTO {{%daily_stat}}
                    (stat_date, manager_id, deals_created, deals_won, deals_lost, won_amount, updated_at)
                VALUES
                    (:statDate, :managerId, :dealsCreated, :dealsWon, :dealsLost, :wonAmount, :updatedAt)
                ON DUPLICATE KEY UPDATE
                    deals_created = deals_created + VALUES(deals_created),
                    deals_won = deals_won + VALUES(deals_won),
                    deals_lost = deals_lost + VALUES(deals_lost),
                    won_amount = won_amount + VALUES(won_amount),
                    updated_at = VALUES(updated_at)";

        $this->db->createCommand($sql, [
            ':statDate' => $date,
            ':managerId' => $managerId,
            ':dealsCreated' => $delta['deals_created'],
            ':dealsWon' => $delta['deals_won'],
            ':dealsLost' => $delta['deals_lost'],
            ':wonAmount' => $delta['won_amount'],
            ':updatedAt' => time(),
        ])->execute();
    }
}
