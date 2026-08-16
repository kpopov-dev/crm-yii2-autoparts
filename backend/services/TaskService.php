<?php

declare(strict_types=1);

namespace app\services;

use app\domain\Contract\EventPublisherInterface;
use app\domain\Contract\TaskRepositoryInterface;
use app\domain\Dto\EventMessage;
use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;
use app\domain\Enum\EventName;
use app\domain\Enum\TaskStatus;
use app\domain\Exception\DomainException;
use app\domain\Exception\EntityNotFoundException;
use app\domain\Exception\ValidationException;
use app\models\Task;
use yii\db\Connection;

final class TaskService
{
    private Connection $db;
    private TaskRepositoryInterface $tasks;
    private EventPublisherInterface $publisher;

    public function __construct(
        Connection $db,
        TaskRepositoryInterface $tasks,
        EventPublisherInterface $publisher
    ) {
        $this->db = $db;
        $this->tasks = $tasks;
        $this->publisher = $publisher;
    }

    public function search(array $filter, Pagination $pagination): PagedResult
    {
        return $this->tasks->search($filter, $pagination);
    }

    public function view(int $id): array
    {
        $task = $this->tasks->findById($id);

        if ($task === null) {
            throw EntityNotFoundException::for('Задача', $id);
        }

        return $task;
    }

    public function create(array $attributes, int $authorId): array
    {
        $transaction = $this->db->beginTransaction();

        try {
            $task = new Task([
                'title' => trim((string)($attributes['title'] ?? '')),
                'description' => trim((string)($attributes['description'] ?? '')),
                'status' => TaskStatus::OPEN,
                'deal_id' => !empty($attributes['dealId']) ? (int)$attributes['dealId'] : null,
                'client_id' => !empty($attributes['clientId']) ? (int)$attributes['clientId'] : null,
                'assignee_id' => (int)($attributes['assigneeId'] ?? $authorId),
                'author_id' => $authorId,
                'due_at' => $this->parseDate($attributes['dueAt'] ?? null),
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            if (!$task->validate()) {
                throw new ValidationException($task->getErrors());
            }

            $task->save(false);

            $this->publisher->publish(EventMessage::create(EventName::TASK_ASSIGNED, [
                'taskId' => (int)$task->id,
                'title' => (string)$task->title,
                'assigneeId' => (int)$task->assignee_id,
                'authorId' => $authorId,
                'dealId' => $task->deal_id !== null ? (int)$task->deal_id : null,
                'dueAt' => (int)$task->due_at,
            ]));

            $transaction->commit();

            return $this->view((int)$task->id);
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }

    public function changeStatus(int $id, string $status, int $userId): array
    {
        if (!TaskStatus::exists($status)) {
            throw new DomainException('Неизвестный статус задачи: ' . $status);
        }

        $transaction = $this->db->beginTransaction();

        try {
            $row = $this->db->createCommand(
                "SELECT id, title, status, assignee_id, author_id
                   FROM {{%task}}
                  WHERE id = :id
                  FOR UPDATE",
                [':id' => $id]
            )->queryOne();

            if ($row === false) {
                throw EntityNotFoundException::for('Задача', $id);
            }

            if (TaskStatus::isFinal((string)$row['status'])) {
                throw new DomainException('Задача уже закрыта и не может быть изменена');
            }

            $now = time();

            $this->db->createCommand()->update(
                '{{%task}}',
                [
                    'status' => $status,
                    'updated_at' => $now,
                    'completed_at' => TaskStatus::isFinal($status) ? $now : null,
                ],
                ['id' => $id]
            )->execute();

            if ($status === TaskStatus::DONE) {
                $this->publisher->publish(EventMessage::create(EventName::TASK_COMPLETED, [
                    'taskId' => $id,
                    'title' => (string)$row['title'],
                    'assigneeId' => (int)$row['assignee_id'],
                    'authorId' => (int)$row['author_id'],
                    'completedBy' => $userId,
                ]));
            }

            $transaction->commit();

            return $this->view($id);
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }

    public function reassign(int $id, int $assigneeId, int $authorId): array
    {
        $affected = $this->db->createCommand()->update(
            '{{%task}}',
            ['assignee_id' => $assigneeId, 'updated_at' => time()],
            ['id' => $id]
        )->execute();

        if ($affected === 0) {
            throw EntityNotFoundException::for('Задача', $id);
        }

        $task = $this->view($id);

        $this->publisher->publish(EventMessage::create(EventName::TASK_ASSIGNED, [
            'taskId' => $id,
            'title' => $task['title'],
            'assigneeId' => $assigneeId,
            'authorId' => $authorId,
            'dealId' => $task['dealId'],
            'dueAt' => $task['dueAt'],
        ]));

        return $task;
    }

    private function parseDate($value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        $timestamp = is_string($value) ? strtotime($value) : false;

        return $timestamp === false ? time() + 86400 : $timestamp;
    }
}
