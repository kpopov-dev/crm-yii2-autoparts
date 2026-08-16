<?php

declare(strict_types=1);

namespace app\messaging;

use app\domain\Contract\EventPublisherInterface;
use app\domain\Dto\EventMessage;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMqPublisher implements EventPublisherInterface
{
    private RabbitMqConnection $connection;

    public function __construct(RabbitMqConnection $connection)
    {
        $this->connection = $connection;
    }

    public function publish(EventMessage $message): void
    {
        $amqpMessage = new AMQPMessage($message->toJson(), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $message->id(),
            'timestamp' => $message->occurredAt(),
            'type' => $message->name(),
        ]);

        $this->connection->channel()->basic_publish(
            $amqpMessage,
            Topology::EXCHANGE,
            $message->name()
        );
    }
}
