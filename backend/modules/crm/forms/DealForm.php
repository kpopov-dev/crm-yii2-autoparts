<?php

declare(strict_types=1);

namespace app\modules\crm\forms;

final class DealForm extends BaseForm
{
    public $title = '';
    public $description = null;
    public $amount = 0;
    public $currency = 'RUB';
    public $clientId = null;
    public $responsibleId = null;

    public function rules(): array
    {
        return [
            [['title', 'clientId'], 'required'],
            [['title', 'description', 'currency'], 'trim'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 5000],
            [['amount'], 'number', 'min' => 0, 'max' => 999999999],
            [['currency'], 'in', 'range' => ['RUB', 'USD', 'EUR', 'CNY']],
            [['clientId', 'responsibleId'], 'integer', 'min' => 1],
        ];
    }
}
