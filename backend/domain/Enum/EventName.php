<?php

declare(strict_types=1);

namespace app\domain\Enum;

final class EventName
{
    public const DEAL_CREATED = 'deal.created';
    public const DEAL_STAGE_CHANGED = 'deal.stage_changed';
    public const DEAL_WON = 'deal.won';
    public const DEAL_LOST = 'deal.lost';
    public const TASK_ASSIGNED = 'task.assigned';
    public const TASK_COMPLETED = 'task.completed';

    public static function all(): array
    {
        return [
            self::DEAL_CREATED,
            self::DEAL_STAGE_CHANGED,
            self::DEAL_WON,
            self::DEAL_LOST,
            self::TASK_ASSIGNED,
            self::TASK_COMPLETED,
        ];
    }
}
