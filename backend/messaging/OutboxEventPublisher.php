<?php

declare(strict_types=1);

namespace app\messaging;

use app\domain\Contract\EventPublisherInterface;
use app\domain\Dto\EventMessage;
use app\models\OutboxMessage;

final class OutboxEventPublisher implements EventPublisherInterface
{
    public function publish(EventMessage $message): void
    {
        $record = new OutboxMessage([
            'message_id' => $message->id(),
            'event_name' => $message->name(),
            'payload' => $message->toJson(),
            'status' => OutboxMessage::STATUS_PENDING,
            'attempts' => 0,
            'created_at' => $message->occurredAt(),
        ]);

        $record->save(false);
    }
}
