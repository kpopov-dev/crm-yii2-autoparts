<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

final class OutboxMessage extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    public static function tableName(): string
    {
        return '{{%outbox_message}}';
    }

    public function rules(): array
    {
        return [
            [['message_id', 'event_name', 'payload', 'status', 'created_at'], 'required'],
            [['message_id'], 'string', 'max' => 36],
            [['message_id'], 'unique'],
            [['event_name'], 'string', 'max' => 64],
            [['payload', 'last_error'], 'string'],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_PUBLISHED, self::STATUS_FAILED]],
            [['attempts', 'created_at', 'published_at'], 'integer'],
        ];
    }
}
