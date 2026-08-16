<?php

declare(strict_types=1);

namespace app\models;

use app\domain\Enum\DealStage;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

final class Deal extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%deal}}';
    }

    public function rules(): array
    {
        return [
            [['number', 'title', 'client_id', 'responsible_id', 'stage', 'created_at'], 'required'],
            [['number'], 'string', 'max' => 32],
            [['number'], 'unique'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['amount'], 'number', 'min' => 0],
            [['currency'], 'string', 'max' => 3],
            [['stage'], 'in', 'range' => DealStage::all()],
            [['client_id', 'responsible_id', 'created_at', 'updated_at', 'closed_at'], 'integer'],
            [['client_id'], 'exist', 'targetClass' => Client::class, 'targetAttribute' => 'id'],
            [['responsible_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function getClient(): ActiveQuery
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
    }

    public function getResponsible(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'responsible_id']);
    }

    public function getTasks(): ActiveQuery
    {
        return $this->hasMany(Task::class, ['deal_id' => 'id']);
    }

    public function isClosed(): bool
    {
        return DealStage::isClosed((string)$this->stage);
    }
}
