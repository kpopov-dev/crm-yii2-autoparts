<?php

declare(strict_types=1);

namespace app\domain\Contract;

interface PasswordHasherInterface
{
    public function hash(string $password): string;

    public function verify(string $password, string $hash): bool;
}
