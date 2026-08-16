<?php

declare(strict_types=1);

namespace app\modules\crm\controllers;

use app\modules\crm\forms\LoginForm;
use app\services\AuthService;
use yii\web\Response;

final class AuthController extends BaseApiController
{
    private AuthService $auth;

    public function __construct($id, $module, AuthService $auth, array $config = [])
    {
        $this->auth = $auth;

        parent::__construct($id, $module, $config);
    }

    public function verbs(): array
    {
        return [
            'login' => ['POST'],
            'me' => ['GET'],
        ];
    }

    protected function publicActions(): array
    {
        return ['options', 'login'];
    }

    public function actionLogin(): array
    {
        $form = new LoginForm();
        $form->fill($this->body());
        $form->validateOrFail();

        return $this->auth->login((string)$form->email, (string)$form->password);
    }

    public function actionMe(): array
    {
        return $this->auth->profile($this->currentUser());
    }

    public function actionOptions(): Response
    {
        return \Yii::$app->response;
    }
}
