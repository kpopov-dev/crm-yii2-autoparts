<?php

declare(strict_types=1);

namespace app\services;

use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;
use app\models\Notification;
use app\repositories\NotificationRepository;

final class NotificationService
{
    private NotificationRepository $repository;

    public function __construct(NotificationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create(
        int $userId,
        string $type,
        string $title,
        string $body = '',
        ?string $entityType = null,
        ?int $entityId = null
    ): void {
        if ($userId <= 0) {
            return;
        }

        $notification = new Notification([
            'user_id' => $userId,
            'type' => $type,
            'title' => mb_substr($title, 0, 255),
            'body' => $body,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'is_read' => 0,
            'created_at' => time(),
        ]);

        $notification->save(false);
    }

    public function listForUser(int $userId, bool $onlyUnread, Pagination $pagination): PagedResult
    {
        return $this->repository->searchByUser($userId, $onlyUnread, $pagination);
    }

    public function countUnread(int $userId): int
    {
        return $this->repository->countUnread($userId);
    }

    public function markRead(int $userId, array $ids): int
    {
        return $this->repository->markAsRead($userId, $ids);
    }

    public function markAllRead(int $userId): int
    {
        return $this->repository->markAllAsRead($userId);
    }
}
