<?php

declare(strict_types=1);

namespace app\domain\Enum;

final class UserRole
{
    public const ADMIN = 'admin';
    public const MANAGER = 'manager';

    public static function all(): array
    {
        return [self::ADMIN, self::MANAGER];
    }

    public static function exists(string $role): bool
    {
        return in_array($role, self::all(), true);
    }
}
