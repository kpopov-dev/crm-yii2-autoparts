<?php

declare(strict_types=1);

namespace app\commands;

use app\domain\Dto\EventMessage;
use app\messaging\EventDispatcher;
use app\messaging\RabbitMqConnection;
use app\messaging\RabbitMqConsumer;
use app\messaging\Topology;
use yii\console\Controller;
use yii\console\ExitCode;

final class ConsumeController extends Controller
{
    public $prefetch = 10;

    private RabbitMqConnection $connection;
    private EventDispatcher $dispatcher;

    public function __construct(
        $id,
        $module,
        RabbitMqConnection $connection,
        EventDispatcher $dispatcher,
        array $config = []
    ) {
        $this->connection = $connection;
        $this->dispatcher = $dispatcher;

        parent::__construct($id, $module, $config);
    }

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['prefetch']);
    }

    public function actionNotifications(): int
    {
        return $this->run(Topology::QUEUE_NOTIFICATIONS);
    }

    public function actionAnalytics(): int
    {
        return $this->run(Topology::QUEUE_ANALYTICS);
    }

    public function actionQueue(string $queue): int
    {
        if (!in_array($queue, Topology::queues(), true)) {
            $this->stderr("Неизвестная очередь: {$queue}\n");

            return ExitCode::USAGE;
        }

        return $this->run($queue);
    }

    private function run(string $queue): int
    {
        $this->stdout("Консьюмер очереди {$queue} запущен\n");

        $consumer = new RabbitMqConsumer($this->connection, $this->dispatcher);

        $consumer->consume($queue, (int)$this->prefetch, function (EventMessage $message, bool $handled): void {
            $this->stdout(sprintf(
                "[%s] %s %s\n",
                date('H:i:s'),
                $message->name(),
                $handled ? 'обработано' : 'пропущено как дубликат'
            ));
        });

        return ExitCode::OK;
    }
}
