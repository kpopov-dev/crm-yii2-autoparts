<?php

declare(strict_types=1);

namespace app\components;

use app\domain\Exception\AuthenticationException;
use app\domain\Exception\DomainException;
use app\domain\Exception\EntityNotFoundException;
use app\domain\Exception\StageTransitionException;
use app\domain\Exception\ValidationException;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Exception;
use yii\web\Application;
use yii\web\HttpException;
use yii\web\Response;

final class ApiExceptionFormatter implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if (!$app instanceof Application) {
            return;
        }

        $app->getResponse()->on(Response::EVENT_BEFORE_SEND, [$this, 'format']);
    }

    public function format($event): void
    {
        $response = $event->sender;

        if (!$response instanceof Response || $response->data === null || $response->isSuccessful) {
            return;
        }

        $exception = Yii::$app->getErrorHandler()->exception;

        if ($exception instanceof ValidationException) {
            $response->statusCode = 422;
            $response->data = [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->getErrors(),
            ];

            return;
        }

        if ($exception instanceof EntityNotFoundException) {
            $response->statusCode = 404;
        } elseif ($exception instanceof StageTransitionException) {
            $response->statusCode = 409;
        } elseif ($exception instanceof AuthenticationException) {
            $response->statusCode = 401;
        } elseif ($exception instanceof DomainException) {
            $response->statusCode = 400;
        }

        $response->data = [
            'success' => false,
            'message' => $this->resolveMessage($exception, (array)$response->data),
            'code' => $response->statusCode,
        ];
    }

    private function resolveMessage(?\Throwable $exception, array $data): string
    {
        if ($exception instanceof DomainException
            || $exception instanceof HttpException
            || $exception instanceof Exception
        ) {
            return $exception->getMessage();
        }

        if ($exception !== null && YII_DEBUG) {
            return $exception->getMessage();
        }

        return (string)($data['message'] ?? 'Внутренняя ошибка сервера');
    }
}
