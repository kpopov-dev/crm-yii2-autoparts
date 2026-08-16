<?php

declare(strict_types=1);

namespace app\models;

use app\domain\Enum\TaskStatus;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

final class Task extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%task}}';
    }

    public function rules(): array
    {
        return [
            [['title', 'assignee_id', 'author_id', 'status', 'due_at', 'created_at'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['status'], 'in', 'range' => TaskStatus::all()],
            [['deal_id', 'client_id', 'assignee_id', 'author_id', 'due_at', 'created_at', 'updated_at', 'completed_at'], 'integer'],
            [['assignee_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            [['deal_id'], 'exist', 'targetClass' => Deal::class, 'targetAttribute' => 'id', 'skipOnEmpty' => true],
        ];
    }

    public function getDeal(): ActiveQuery
    {
        return $this->hasOne(Deal::class, ['id' => 'deal_id']);
    }

    public function getAssignee(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'assignee_id']);
    }

    public function isOverdue(): bool
    {
        return !TaskStatus::isFinal((string)$this->status) && (int)$this->due_at < time();
    }
}
