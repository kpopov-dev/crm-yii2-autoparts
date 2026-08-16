<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

final class DailyStat extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%daily_stat}}';
    }

    public function rules(): array
    {
        return [
            [['stat_date', 'manager_id'], 'required'],
            [['stat_date'], 'string', 'max' => 10],
            [['manager_id', 'deals_created', 'deals_won', 'deals_lost', 'updated_at'], 'integer'],
            [['won_amount'], 'number'],
        ];
    }
}
