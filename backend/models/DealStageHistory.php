<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

final class DealStageHistory extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%deal_stage_history}}';
    }

    public function rules(): array
    {
        return [
            [['deal_id', 'stage_to', 'user_id', 'created_at'], 'required'],
            [['deal_id', 'user_id', 'created_at'], 'integer'],
            [['stage_from', 'stage_to'], 'string', 'max' => 30],
            [['comment'], 'string', 'max' => 500],
        ];
    }
}
