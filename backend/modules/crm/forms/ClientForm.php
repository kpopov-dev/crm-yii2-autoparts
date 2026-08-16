<?php

declare(strict_types=1);

namespace app\modules\crm\forms;

final class ClientForm extends BaseForm
{
    public $name = '';
    public $email = null;
    public $phone = null;
    public $inn = null;
    public $comment = null;
    public $managerId = null;
    public $isActive = true;

    public function rules(): array
    {
        return [
            [['name', 'managerId'], 'required'],
            [['name', 'email', 'phone', 'inn', 'comment'], 'trim'],
            [['name'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['phone'], 'match', 'pattern' => '/^[0-9+\-\s()]{5,32}$/', 'message' => 'Некорректный формат телефона'],
            [['inn'], 'match', 'pattern' => '/^\d{10}$|^\d{12}$/', 'message' => 'ИНН должен содержать 10 или 12 цифр'],
            [['comment'], 'string', 'max' => 2000],
            [['managerId'], 'integer', 'min' => 1],
            [['isActive'], 'boolean'],
        ];
    }
}
