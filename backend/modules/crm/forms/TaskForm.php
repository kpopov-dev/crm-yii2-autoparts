<?php

declare(strict_types=1);

namespace app\modules\crm\forms;

final class TaskForm extends BaseForm
{
    public $title = '';
    public $description = null;
    public $dealId = null;
    public $clientId = null;
    public $assigneeId = null;
    public $dueAt = null;

    public function rules(): array
    {
        return [
            [['title', 'assigneeId', 'dueAt'], 'required'],
            [['title', 'description'], 'trim'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 5000],
            [['dealId', 'clientId', 'assigneeId'], 'integer', 'min' => 1],
            [['dueAt'], 'date', 'format' => 'php:Y-m-d H:i', 'message' => 'Ожидается дата в формате Y-m-d H:i'],
        ];
    }
}
