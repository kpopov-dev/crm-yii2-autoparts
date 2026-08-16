<?php

declare(strict_types=1);

namespace app\domain\Enum;

final class TaskStatus
{
    public const OPEN = 'open';
    public const IN_PROGRESS = 'in_progress';
    public const DONE = 'done';
    public const CANCELED = 'canceled';

    private const LABELS = [
        self::OPEN => 'Открыта',
        self::IN_PROGRESS => 'В работе',
        self::DONE => 'Выполнена',
        self::CANCELED => 'Отменена',
    ];

    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function exists(string $status): bool
    {
        return array_key_exists($status, self::LABELS);
    }

    public static function isFinal(string $status): bool
    {
        return in_array($status, [self::DONE, self::CANCELED], true);
    }

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function list(): array
    {
        $result = [];
        foreach (self::LABELS as $code => $label) {
            $result[] = ['code' => $code, 'label' => $label];
        }

        return $result;
    }
}
