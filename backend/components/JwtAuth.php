<?php

declare(strict_types=1);

namespace app\components;

use app\domain\Contract\TokenIssuerInterface;
use app\models\User;
use Yii;
use yii\filters\auth\AuthMethod;
use yii\web\UnauthorizedHttpException;

final class JwtAuth extends AuthMethod
{
    public function authenticate($user, $request, $response)
    {
        $header = (string)$request->getHeaders()->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            $this->handleFailure($response);
        }

        try {
            $payload = $this->issuer()->parse(trim($matches[1]));
        } catch (\Throwable $exception) {
            throw new UnauthorizedHttpException('Токен недействителен или истёк');
        }

        $identity = User::findIdentity((int)($payload['sub'] ?? 0));

        if ($identity === null) {
            throw new UnauthorizedHttpException('Пользователь не найден или заблокирован');
        }

        $user->setIdentity($identity);

        return $identity;
    }

    public function challenge($response): void
    {
        $response->getHeaders()->set('WWW-Authenticate', 'Bearer realm="crm-api"');
    }

    private function issuer(): TokenIssuerInterface
    {
        return Yii::$container->get(TokenIssuerInterface::class);
    }
}
