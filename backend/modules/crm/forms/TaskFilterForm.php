<?php

declare(strict_types=1);

namespace app\modules\crm\forms;

use app\domain\Enum\TaskStatus;

final class TaskFilterForm extends BaseForm
{
    public $assigneeId = null;
    public $dealId = null;
    public $status = null;
    public $onlyActive = false;
    public $overdue = false;
    public $sort = 'dueAt';

    public function rules(): array
    {
        return [
            [['assigneeId', 'dealId'], 'integer', 'min' => 1],
            [['status'], 'in', 'range' => TaskStatus::all()],
            [['onlyActive', 'overdue'], 'boolean'],
            [['sort'], 'in', 'range' => ['id', '-id', 'dueAt', '-dueAt', 'createdAt', '-createdAt']],
        ];
    }

    public function toRepositoryFilter(): array
    {
        return [
            'assigneeId' => $this->assigneeId,
            'dealId' => $this->dealId,
            'status' => $this->status,
            'onlyActive' => $this->onlyActive,
            'overdue' => $this->overdue,
            'sort' => $this->sort,
        ];
    }
}
