<?php

declare(strict_types=1);

namespace app\components;

final class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), '"\'');

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }

            $_ENV[$key] = $_ENV[$key] ?? $value;
        }

        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string)$value;
    }

    public static function int(string $key, int $default): int
    {
        $value = self::get($key);

        return $value === null ? $default : (int)$value;
    }

    public static function bool(string $key, bool $default): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return in_array(mb_strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}
