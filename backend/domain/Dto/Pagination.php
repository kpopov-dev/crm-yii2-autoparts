<?php

declare(strict_types=1);

namespace app\domain\Dto;

final class Pagination
{
    public const DEFAULT_LIMIT = 20;
    public const MAX_LIMIT = 100;

    private int $page;
    private int $limit;

    public function __construct(int $page = 1, int $limit = self::DEFAULT_LIMIT)
    {
        $this->page = max(1, $page);
        $this->limit = min(self::MAX_LIMIT, max(1, $limit));
    }

    public static function fromRequest(array $params): self
    {
        return new self(
            (int)($params['page'] ?? 1),
            (int)($params['limit'] ?? self::DEFAULT_LIMIT)
        );
    }

    public function page(): int
    {
        return $this->page;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
