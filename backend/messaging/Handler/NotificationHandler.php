<?php

declare(strict_types=1);

namespace app\messaging\Handler;

use app\domain\Contract\EventHandlerInterface;
use app\domain\Dto\EventMessage;
use app\domain\Enum\DealStage;
use app\domain\Enum\EventName;
use app\services\NotificationService;

final class NotificationHandler implements EventHandlerInterface
{
    private NotificationService $notifications;

    public function __construct(NotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    public function supports(string $eventName): bool
    {
        return in_array($eventName, [
            EventName::DEAL_STAGE_CHANGED,
            EventName::DEAL_WON,
            EventName::DEAL_LOST,
            EventName::TASK_ASSIGNED,
            EventName::TASK_COMPLETED,
        ], true);
    }

    public function handle(EventMessage $message): void
    {
        switch ($message->name()) {
            case EventName::DEAL_STAGE_CHANGED:
                $this->notifications->create(
                    (int)$message->get('responsibleId'),
                    $message->name(),
                    sprintf('Заказ %s перешёл на стадию "%s"',
                        (string)$message->get('number'),
                        DealStage::label((string)$message->get('stageTo'))
                    ),
                    (string)$message->get('title'),
                    'deal',
                    (int)$message->get('dealId')
                );
                break;

            case EventName::DEAL_WON:
            case EventName::DEAL_LOST:
                $this->notifications->create(
                    (int)$message->get('responsibleId'),
                    $message->name(),
                    sprintf('Заказ %s закрыт: %s',
                        (string)$message->get('number'),
                        DealStage::label((string)$message->get('stageTo'))
                    ),
                    sprintf('Сумма: %s %s',
                        number_format((float)$message->get('amount', 0), 2, '.', ' '),
                        (string)$message->get('currency', 'RUB')
                    ),
                    'deal',
                    (int)$message->get('dealId')
                );
                break;

            case EventName::TASK_ASSIGNED:
                $this->notifications->create(
                    (int)$message->get('assigneeId'),
                    $message->name(),
                    'Вам назначена новая задача',
                    (string)$message->get('title'),
                    'task',
                    (int)$message->get('taskId')
                );
                break;

            case EventName::TASK_COMPLETED:
                $this->notifications->create(
                    (int)$message->get('authorId'),
                    $message->name(),
                    'Задача выполнена',
                    (string)$message->get('title'),
                    'task',
                    (int)$message->get('taskId')
                );
                break;
        }
    }
}
