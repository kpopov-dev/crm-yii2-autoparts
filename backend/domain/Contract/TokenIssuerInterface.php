<?php

declare(strict_types=1);

namespace app\domain\Contract;

interface TokenIssuerInterface
{
    public function issue(int $userId, string $role): array;

    public function parse(string $token): array;
}
