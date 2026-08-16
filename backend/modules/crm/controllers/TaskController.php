<?php

declare(strict_types=1);

namespace app\modules\crm\controllers;

use app\domain\Enum\TaskStatus;
use app\modules\crm\forms\TaskFilterForm;
use app\modules\crm\forms\TaskForm;
use app\services\TaskService;

final class TaskController extends BaseApiController
{
    private TaskService $tasks;

    public function __construct($id, $module, TaskService $tasks, array $config = [])
    {
        $this->tasks = $tasks;

        parent::__construct($id, $module, $config);
    }

    public function verbs(): array
    {
        return [
            'index' => ['GET'],
            'view' => ['GET'],
            'create' => ['POST'],
            'change-status' => ['POST'],
            'reassign' => ['POST'],
            'statuses' => ['GET'],
        ];
    }

    public function actionIndex(): array
    {
        $this->requirePermission('task.view');

        $filter = new TaskFilterForm();
        $filter->fill($this->query());
        $filter->validateOrFail();

        $criteria = $this->scopeToUser($filter->toRepositoryFilter(), 'assigneeId');

        return $this->tasks->search($criteria, $this->pagination())->toArray();
    }

    public function actionView(int $id): array
    {
        $this->requirePermission('task.view');

        return $this->tasks->view($id);
    }

    public function actionCreate(): array
    {
        $this->requirePermission('task.manage');

        $form = new TaskForm();
        $form->assigneeId = $this->currentUser()->getId();
        $form->fill($this->body());
        $form->validateOrFail();

        return $this->tasks->create($form->toArray(), $this->currentUser()->getId());
    }

    public function actionChangeStatus(int $id): array
    {
        $this->requirePermission('task.manage');

        $status = $this->body()['status'] ?? '';
        $status = is_scalar($status) ? (string)$status : '';

        return $this->tasks->changeStatus($id, $status, $this->currentUser()->getId());
    }

    public function actionReassign(int $id): array
    {
        $this->requirePermission('task.manage');

        $assigneeId = $this->body()['assigneeId'] ?? 0;
        $assigneeId = is_scalar($assigneeId) ? (int)$assigneeId : 0;

        return $this->tasks->reassign($id, $assigneeId, $this->currentUser()->getId());
    }

    public function actionStatuses(): array
    {
        return ['items' => TaskStatus::list()];
    }
}
