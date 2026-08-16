<?php

declare(strict_types=1);

namespace app\messaging;

use app\domain\Dto\EventMessage;
use app\models\OutboxMessage;
use yii\db\Connection;

final class OutboxRelay
{
    private const MAX_ATTEMPTS = 5;

    private Connection $db;
    private RabbitMqPublisher $publisher;

    public function __construct(Connection $db, RabbitMqPublisher $publisher)
    {
        $this->db = $db;
        $this->publisher = $publisher;
    }

    public function relayBatch(int $limit = 100): int
    {
        $transaction = $this->db->beginTransaction();

        try {
            $rows = $this->db->createCommand(
                "SELECT id, message_id, event_name, payload, attempts
                   FROM {{%outbox_message}}
                  WHERE status = :status
                    AND attempts < :maxAttempts
                  ORDER BY id ASC
                  LIMIT :limit
                  FOR UPDATE SKIP LOCKED",
                [
                    ':status' => OutboxMessage::STATUS_PENDING,
                    ':maxAttempts' => self::MAX_ATTEMPTS,
                    ':limit' => $limit,
                ]
            )->queryAll();

            $published = 0;

            foreach ($rows as $row) {
                $published += $this->relayOne($row) ? 1 : 0;
            }

            $transaction->commit();

            return $published;
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }

    private function relayOne(array $row): bool
    {
        $id = (int)$row['id'];

        try {
            $payload = json_decode((string)$row['payload'], true, 512, JSON_THROW_ON_ERROR);
            $this->publisher->publish(EventMessage::fromArray($payload));

            $this->db->createCommand()->update(
                '{{%outbox_message}}',
                [
                    'status' => OutboxMessage::STATUS_PUBLISHED,
                    'published_at' => time(),
                    'attempts' => (int)$row['attempts'] + 1,
                    'last_error' => null,
                ],
                ['id' => $id]
            )->execute();

            return true;
        } catch (\Throwable $exception) {
            $attempts = (int)$row['attempts'] + 1;

            $this->db->createCommand()->update(
                '{{%outbox_message}}',
                [
                    'attempts' => $attempts,
                    'status' => $attempts >= self::MAX_ATTEMPTS
                        ? OutboxMessage::STATUS_FAILED
                        : OutboxMessage::STATUS_PENDING,
                    'last_error' => mb_substr($exception->getMessage(), 0, 1000),
                ],
                ['id' => $id]
            )->execute();

            return false;
        }
    }

    public function pendingCount(): int
    {
        return (int)$this->db->createCommand(
            "SELECT COUNT(*) FROM {{%outbox_message}} WHERE status = :status",
            [':status' => OutboxMessage::STATUS_PENDING]
        )->queryScalar();
    }
}
