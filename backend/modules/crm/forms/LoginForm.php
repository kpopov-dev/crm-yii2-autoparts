<?php

declare(strict_types=1);

namespace app\modules\crm\forms;

final class LoginForm extends BaseForm
{
    public $email = '';
    public $password = '';

    public function rules(): array
    {
        return [
            [['email', 'password'], 'required', 'message' => 'Поле обязательно для заполнения'],
            [['email'], 'trim'],
            [['email'], 'email'],
            [['password'], 'string', 'min' => 6, 'max' => 72],
        ];
    }
}
