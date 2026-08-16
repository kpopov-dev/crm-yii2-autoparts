<?php

declare(strict_types=1);

namespace app\modules\crm\forms;

use app\domain\Enum\DealStage;

final class StageChangeForm extends BaseForm
{
    public $stage = '';
    public $comment = '';

    public function rules(): array
    {
        return [
            [['stage'], 'required'],
            [['stage'], 'in', 'range' => DealStage::all(), 'message' => 'Недопустимая стадия заказа'],
            [['comment'], 'trim'],
            [['comment'], 'string', 'max' => 500],
        ];
    }
}
