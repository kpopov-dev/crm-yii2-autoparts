<?php

declare(strict_types=1);

namespace app\messaging;

use app\domain\Dto\EventMessage;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;

final class RabbitMqConsumer
{
    private RabbitMqConnection $connection;
    private EventDispatcher $dispatcher;

    public function __construct(RabbitMqConnection $connection, EventDispatcher $dispatcher)
    {
        $this->connection = $connection;
        $this->dispatcher = $dispatcher;
    }

    public function consume(string $queue, int $prefetch = 10, ?callable $onProcessed = null): void
    {
        $channel = $this->connection->channel();
        $channel->basic_qos(0, $prefetch, false);

        $channel->basic_consume(
            $queue,
            'consumer.' . $queue,
            false,
            false,
            false,
            false,
            function (AMQPMessage $amqpMessage) use ($queue, $onProcessed): void {
                $this->handle($amqpMessage, $queue, $onProcessed);
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    private function handle(AMQPMessage $amqpMessage, string $queue, ?callable $onProcessed): void
    {
        try {
            $payload = json_decode($amqpMessage->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $message = EventMessage::fromArray($payload);

            $handled = $this->dispatcher->dispatch($message, $queue);
            $amqpMessage->ack();

            if ($onProcessed !== null) {
                $onProcessed($message, $handled);
            }
        } catch (\Throwable $exception) {
            Yii::error($exception->getMessage(), 'messaging');
            $amqpMessage->nack(false);
        }
    }
}
