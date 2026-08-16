<?php

declare(strict_types=1);

namespace app\services;

use app\domain\Contract\DealRepositoryInterface;
use app\domain\Contract\EventPublisherInterface;
use app\domain\Dto\EventMessage;
use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;
use app\domain\Enum\DealStage;
use app\domain\Enum\EventName;
use app\domain\Exception\EntityNotFoundException;
use app\domain\Exception\ValidationException;
use app\domain\Policy\StageTransitionPolicy;
use app\models\Deal;
use app\models\DealStageHistory;
use yii\db\Connection;

final class DealService
{
    private Connection $db;
    private DealRepositoryInterface $deals;
    private StageTransitionPolicy $policy;
    private EventPublisherInterface $publisher;

    public function __construct(
        Connection $db,
        DealRepositoryInterface $deals,
        StageTransitionPolicy $policy,
        EventPublisherInterface $publisher
    ) {
        $this->db = $db;
        $this->deals = $deals;
        $this->policy = $policy;
        $this->publisher = $publisher;
    }

    public function search(array $filter, Pagination $pagination): PagedResult
    {
        return $this->deals->search($filter, $pagination);
    }

    public function board(array $filter): array
    {
        return $this->deals->board($filter);
    }

    public function view(int $id): array
    {
        $deal = $this->deals->findById($id);

        if ($deal === null) {
            throw EntityNotFoundException::for('Заказ', $id);
        }

        $deal['history'] = $this->deals->stageHistory($id);
        $deal['availableStages'] = array_map(
            static fn (string $stage): array => ['code' => $stage, 'label' => DealStage::label($stage)],
            $this->policy->availableFrom((string)$deal['stage'])
        );

        return $deal;
    }

    public function create(array $attributes, int $authorId): array
    {
        $transaction = $this->db->beginTransaction();

        try {
            $deal = new Deal([
                'number' => $this->deals->nextNumber(),
                'title' => trim((string)($attributes['title'] ?? '')),
                'description' => trim((string)($attributes['description'] ?? '')),
                'amount' => round((float)($attributes['amount'] ?? 0), 2),
                'currency' => mb_strtoupper((string)($attributes['currency'] ?? 'RUB')),
                'stage' => DealStage::NEW,
                'client_id' => (int)($attributes['clientId'] ?? 0),
                'responsible_id' => (int)($attributes['responsibleId'] ?? $authorId),
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            if (!$deal->validate()) {
                throw new ValidationException($deal->getErrors());
            }

            $deal->save(false);

            $this->writeHistory((int)$deal->id, null, DealStage::NEW, $authorId, 'Заявка принята от клиента');

            $this->publisher->publish(EventMessage::create(EventName::DEAL_CREATED, [
                'dealId' => (int)$deal->id,
                'number' => (string)$deal->number,
                'title' => (string)$deal->title,
                'amount' => (float)$deal->amount,
                'currency' => (string)$deal->currency,
                'clientId' => (int)$deal->client_id,
                'responsibleId' => (int)$deal->responsible_id,
            ]));

            $transaction->commit();

            return $this->view((int)$deal->id);
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }

    public function update(int $id, array $attributes): array
    {
        $deal = Deal::findOne(['id' => $id]);

        if ($deal === null) {
            throw EntityNotFoundException::for('Заказ', $id);
        }

        if (array_key_exists('title', $attributes)) {
            $deal->title = trim((string)$attributes['title']);
        }

        if (array_key_exists('description', $attributes)) {
            $deal->description = trim((string)$attributes['description']);
        }

        if (array_key_exists('amount', $attributes)) {
            $deal->amount = round((float)$attributes['amount'], 2);
        }

        if (array_key_exists('currency', $attributes)) {
            $deal->currency = mb_strtoupper((string)$attributes['currency']);
        }

        if (array_key_exists('responsibleId', $attributes)) {
            $deal->responsible_id = (int)$attributes['responsibleId'];
        }

        if (array_key_exists('clientId', $attributes)) {
            $deal->client_id = (int)$attributes['clientId'];
        }

        $deal->updated_at = time();

        if (!$deal->validate()) {
            throw new ValidationException($deal->getErrors());
        }

        $deal->save(false);

        return $this->view($id);
    }

    public function changeStage(int $id, string $stageTo, int $userId, string $comment = ''): array
    {
        $transaction = $this->db->beginTransaction();

        try {
            $row = $this->db->createCommand(
                "SELECT id, number, title, stage, amount, currency, responsible_id
                   FROM {{%deal}}
                  WHERE id = :id
                  FOR UPDATE",
                [':id' => $id]
            )->queryOne();

            if ($row === false) {
                throw EntityNotFoundException::for('Заказ', $id);
            }

            $stageFrom = (string)$row['stage'];
            $this->policy->assert($stageFrom, $stageTo);

            $now = time();

            $this->db->createCommand()->update(
                '{{%deal}}',
                [
                    'stage' => $stageTo,
                    'updated_at' => $now,
                    'closed_at' => DealStage::isClosed($stageTo) ? $now : null,
                ],
                ['id' => $id]
            )->execute();

            $this->writeHistory($id, $stageFrom, $stageTo, $userId, $comment);

            $payload = [
                'dealId' => $id,
                'number' => (string)$row['number'],
                'title' => (string)$row['title'],
                'amount' => (float)$row['amount'],
                'currency' => (string)$row['currency'],
                'stageFrom' => $stageFrom,
                'stageTo' => $stageTo,
                'responsibleId' => (int)$row['responsible_id'],
                'changedBy' => $userId,
            ];

            $this->publisher->publish(EventMessage::create(EventName::DEAL_STAGE_CHANGED, $payload));

            if ($stageTo === DealStage::WON) {
                $this->publisher->publish(EventMessage::create(EventName::DEAL_WON, $payload));
            }

            if ($stageTo === DealStage::LOST) {
                $this->publisher->publish(EventMessage::create(EventName::DEAL_LOST, $payload));
            }

            $transaction->commit();

            return $this->view($id);
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }

    private function writeHistory(int $dealId, ?string $stageFrom, string $stageTo, int $userId, string $comment): void
    {
        $history = new DealStageHistory([
            'deal_id' => $dealId,
            'stage_from' => $stageFrom,
            'stage_to' => $stageTo,
            'user_id' => $userId,
            'comment' => mb_substr($comment, 0, 500),
            'created_at' => time(),
        ]);

        $history->save(false);
    }
}
