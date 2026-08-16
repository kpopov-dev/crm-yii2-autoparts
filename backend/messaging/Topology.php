<?php

declare(strict_types=1);

namespace app\messaging;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

final class Topology
{
    public const EXCHANGE = 'crm.events';
    public const EXCHANGE_DLX = 'crm.events.dlx';

    public const QUEUE_NOTIFICATIONS = 'crm.notifications';
    public const QUEUE_ANALYTICS = 'crm.analytics';
    public const QUEUE_DEAD_LETTER = 'crm.dead-letter';

    private const BINDINGS = [
        self::QUEUE_NOTIFICATIONS => ['deal.*', 'task.*'],
        self::QUEUE_ANALYTICS => ['deal.*'],
    ];

    public static function queues(): array
    {
        return array_keys(self::BINDINGS);
    }

    public static function declare(AMQPChannel $channel): void
    {
        $channel->exchange_declare(self::EXCHANGE, 'topic', false, true, false);
        $channel->exchange_declare(self::EXCHANGE_DLX, 'fanout', false, true, false);

        $channel->queue_declare(self::QUEUE_DEAD_LETTER, false, true, false, false);
        $channel->queue_bind(self::QUEUE_DEAD_LETTER, self::EXCHANGE_DLX);

        $arguments = new AMQPTable([
            'x-dead-letter-exchange' => self::EXCHANGE_DLX,
        ]);

        foreach (self::BINDINGS as $queue => $routingKeys) {
            $channel->queue_declare($queue, false, true, false, false, false, $arguments);

            foreach ($routingKeys as $routingKey) {
                $channel->queue_bind($queue, self::EXCHANGE, $routingKey);
            }
        }
    }
}
