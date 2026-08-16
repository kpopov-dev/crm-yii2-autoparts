<?php

declare(strict_types=1);

namespace app\modules\crm\controllers;

use app\components\JwtAuth;
use app\domain\Dto\Pagination;
use app\models\User;
use Yii;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\UnauthorizedHttpException;

abstract class BaseApiController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => Yii::$app->params['corsOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['Authorization', 'Content-Type'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 3600,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => JwtAuth::class,
            'except' => $this->publicActions(),
        ];

        return $behaviors;
    }

    protected function publicActions(): array
    {
        return ['options'];
    }

    protected function currentUser(): User
    {
        $identity = Yii::$app->user->identity;

        if (!$identity instanceof User) {
            throw new UnauthorizedHttpException('Требуется авторизация');
        }

        return $identity;
    }

    protected function requirePermission(string $permission): void
    {
        if (!Yii::$app->user->can($permission)) {
            throw new ForbiddenHttpException('Недостаточно прав для выполнения операции');
        }
    }

    protected function body(): array
    {
        return (array)Yii::$app->request->getBodyParams();
    }

    protected function query(): array
    {
        return (array)Yii::$app->request->getQueryParams();
    }

    protected function pagination(): Pagination
    {
        return Pagination::fromRequest($this->query());
    }

    protected function scopeToUser(array $filter, string $field): array
    {
        $user = $this->currentUser();

        if (!$user->isAdmin()) {
            $filter[$field] = $user->getId();
        }

        return $filter;
    }
}
