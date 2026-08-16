<?php

declare(strict_types=1);

namespace app\modules\crm\controllers;

use app\services\NotificationService;

final class NotificationController extends BaseApiController
{
    private NotificationService $notifications;

    public function __construct($id, $module, NotificationService $notifications, array $config = [])
    {
        $this->notifications = $notifications;

        parent::__construct($id, $module, $config);
    }

    public function verbs(): array
    {
        return [
            'index' => ['GET'],
            'unread-count' => ['GET'],
            'read' => ['POST'],
            'read-all' => ['POST'],
        ];
    }

    public function actionIndex(): array
    {
        $onlyUnread = filter_var($this->query()['onlyUnread'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $this->notifications
            ->listForUser($this->currentUser()->getId(), $onlyUnread, $this->pagination())
            ->toArray();
    }

    public function actionUnreadCount(): array
    {
        return ['count' => $this->notifications->countUnread($this->currentUser()->getId())];
    }

    public function actionRead(): array
    {
        $ids = (array)($this->body()['ids'] ?? []);
        $ids = array_filter($ids, 'is_scalar');

        return ['updated' => $this->notifications->markRead($this->currentUser()->getId(), $ids)];
    }

    public function actionReadAll(): array
    {
        return ['updated' => $this->notifications->markAllRead($this->currentUser()->getId())];
    }
}
