<?php

declare(strict_types=1);

namespace app\domain\Dto;

final class DateRange
{
    private int $from;
    private int $to;

    public function __construct(int $from, int $to)
    {
        $this->from = min($from, $to);
        $this->to = max($from, $to);
    }

    public static function lastDays(int $days): self
    {
        $to = time();

        return new self($to - ($days * 86400), $to);
    }

    public static function fromRequest(array $params, int $defaultDays = 90): self
    {
        $from = isset($params['from']) ? strtotime((string)$params['from']) : false;
        $to = isset($params['to']) ? strtotime((string)$params['to'] . ' 23:59:59') : false;

        if ($from === false || $to === false) {
            return self::lastDays($defaultDays);
        }

        return new self($from, $to);
    }

    public function from(): int
    {
        return $this->from;
    }

    public function to(): int
    {
        return $this->to;
    }

    public function toArray(): array
    {
        return [
            'from' => date('Y-m-d', $this->from),
            'to' => date('Y-m-d', $this->to),
        ];
    }
}
