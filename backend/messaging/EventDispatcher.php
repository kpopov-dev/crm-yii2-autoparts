<?php

declare(strict_types=1);

namespace app\messaging;

use app\domain\Contract\EventHandlerInterface;
use app\domain\Dto\EventMessage;
use app\models\ProcessedMessage;
use yii\db\Connection;
use yii\db\Exception as DbException;

final class EventDispatcher
{
    private Connection $db;
    private array $handlers;

    public function __construct(Connection $db, array $handlers = [])
    {
        $this->db = $db;
        $this->handlers = array_values(array_filter(
            $handlers,
            static fn ($handler): bool => $handler instanceof EventHandlerInterface
        ));
    }

    public function dispatch(EventMessage $message, string $consumer): bool
    {
        if (!$this->markProcessed($message->id(), $consumer)) {
            return false;
        }

        $transaction = $this->db->beginTransaction();

        try {
            foreach ($this->handlers as $handler) {
                if ($handler->supports($message->name())) {
                    $handler->handle($message);
                }
            }

            $transaction->commit();

            return true;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $this->removeProcessed($message->id(), $consumer);

            throw $exception;
        }
    }

    private function markProcessed(string $messageId, string $consumer): bool
    {
        try {
            $this->db->createCommand()->insert('{{%processed_message}}', [
                'message_id' => $messageId,
                'consumer' => $consumer,
                'created_at' => time(),
            ])->execute();

            return true;
        } catch (DbException $exception) {
            return false;
        }
    }

    private function removeProcessed(string $messageId, string $consumer): void
    {
        $this->db->createCommand()->delete('{{%processed_message}}', [
            'message_id' => $messageId,
            'consumer' => $consumer,
        ])->execute();
    }
}
