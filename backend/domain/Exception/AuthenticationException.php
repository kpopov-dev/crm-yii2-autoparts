<?php

declare(strict_types=1);

namespace app\domain\Exception;

final class AuthenticationException extends DomainException
{
    public static function invalidCredentials(): self
    {
        return new self('Неверный e-mail или пароль');
    }

    public static function accountDisabled(): self
    {
        return new self('Учётная запись заблокирована');
    }
}
