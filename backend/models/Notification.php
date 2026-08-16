<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

final class Notification extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%notification}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'type', 'title', 'created_at'], 'required'],
            [['user_id', 'created_at', 'read_at', 'entity_id'], 'integer'],
            [['type', 'entity_type'], 'string', 'max' => 64],
            [['title'], 'string', 'max' => 255],
            [['body'], 'string'],
            [['is_read'], 'boolean'],
        ];
    }
}
