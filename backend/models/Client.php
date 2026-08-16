<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

final class Client extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%client}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'manager_id', 'created_at'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 190],
            [['phone'], 'string', 'max' => 32],
            [['inn'], 'string', 'max' => 12],
            [['comment'], 'string'],
            [['manager_id', 'created_at', 'updated_at'], 'integer'],
            [['is_active'], 'boolean'],
            [['manager_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function getManager(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'manager_id']);
    }

    public function getDeals(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Deal::class, ['client_id' => 'id']);
    }
}
