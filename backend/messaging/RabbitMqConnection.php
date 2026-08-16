<?php

declare(strict_types=1);

namespace app\messaging;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class RabbitMqConnection
{
    private string $host;
    private int $port;
    private string $user;
    private string $password;
    private string $vhost;
    private int $heartbeat;

    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        string $host,
        int $port,
        string $user,
        string $password,
        string $vhost = '/',
        int $heartbeat = 30
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->heartbeat = $heartbeat;
    }

    public function channel(): AMQPChannel
    {
        if ($this->channel !== null && $this->channel->is_open()) {
            return $this->channel;
        }

        $this->connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->vhost,
            false,
            'AMQPLAIN',
            null,
            'en_US',
            10.0,
            10.0,
            null,
            true,
            $this->heartbeat
        );

        $this->channel = $this->connection->channel();
        Topology::declare($this->channel);

        return $this->channel;
    }

    public function close(): void
    {
        if ($this->channel !== null && $this->channel->is_open()) {
            $this->channel->close();
        }

        if ($this->connection !== null && $this->connection->isConnected()) {
            $this->connection->close();
        }

        $this->channel = null;
        $this->connection = null;
    }
}
