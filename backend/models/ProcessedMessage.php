<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

final class ProcessedMessage extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%processed_message}}';
    }

    public function rules(): array
    {
        return [
            [['message_id', 'consumer', 'created_at'], 'required'],
            [['message_id'], 'string', 'max' => 36],
            [['consumer'], 'string', 'max' => 64],
            [['created_at'], 'integer'],
        ];
    }
}
