<?php

declare(strict_types=1);

namespace app\domain\Enum;

final class DealStage
{
    public const NEW = 'new';
    public const QUALIFICATION = 'qualification';
    public const PROPOSAL = 'proposal';
    public const NEGOTIATION = 'negotiation';
    public const WON = 'won';
    public const LOST = 'lost';

    private const LABELS = [
        self::NEW => 'Заявка',
        self::QUALIFICATION => 'Подбор и наличие',
        self::PROPOSAL => 'Коммерческое предложение',
        self::NEGOTIATION => 'Согласование условий',
        self::WON => 'Отгружена',
        self::LOST => 'Отказ',
    ];

    private const PIPELINE = [
        self::NEW,
        self::QUALIFICATION,
        self::PROPOSAL,
        self::NEGOTIATION,
    ];

    private const CLOSED = [
        self::WON,
        self::LOST,
    ];

    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function pipeline(): array
    {
        return self::PIPELINE;
    }

    public static function closed(): array
    {
        return self::CLOSED;
    }

    public static function exists(string $stage): bool
    {
        return array_key_exists($stage, self::LABELS);
    }

    public static function isClosed(string $stage): bool
    {
        return in_array($stage, self::CLOSED, true);
    }

    public static function label(string $stage): string
    {
        return self::LABELS[$stage] ?? $stage;
    }

    public static function position(string $stage): int
    {
        $index = array_search($stage, self::PIPELINE, true);

        return $index === false ? count(self::PIPELINE) : (int)$index;
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
