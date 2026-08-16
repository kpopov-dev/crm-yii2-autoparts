<?php

declare(strict_types=1);

namespace app\components;

use app\domain\Contract\PasswordHasherInterface;

final class PasswordHasher implements PasswordHasherInterface
{
    private int $cost;

    public function __construct(int $cost = 12)
    {
        $this->cost = $cost;
    }

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    public function verify(string $password, string $hash): bool
    {
        return $hash !== '' && password_verify($password, $hash);
    }
}
