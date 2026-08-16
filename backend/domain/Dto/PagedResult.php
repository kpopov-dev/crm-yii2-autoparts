<?php

declare(strict_types=1);

namespace app\domain\Dto;

final class PagedResult
{
    private array $items;
    private int $total;
    private Pagination $pagination;

    public function __construct(array $items, int $total, Pagination $pagination)
    {
        $this->items = $items;
        $this->total = $total;
        $this->pagination = $pagination;
    }

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function pageCount(): int
    {
        return (int)ceil($this->total / $this->pagination->limit());
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'meta' => [
                'total' => $this->total,
                'page' => $this->pagination->page(),
                'limit' => $this->pagination->limit(),
                'pageCount' => $this->pageCount(),
            ],
        ];
    }
}
