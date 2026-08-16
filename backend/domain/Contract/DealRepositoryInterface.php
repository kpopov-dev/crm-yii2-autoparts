<?php

declare(strict_types=1);

namespace app\domain\Contract;

use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;

interface DealRepositoryInterface
{
    public function search(array $filter, Pagination $pagination): PagedResult;

    public function findById(int $id): ?array;

    public function board(array $filter): array;

    public function stageHistory(int $dealId): array;

    public function nextNumber(): string;
}
