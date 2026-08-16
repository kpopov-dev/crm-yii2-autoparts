<?php

declare(strict_types=1);

namespace app\domain\Contract;

use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;

interface ClientRepositoryInterface
{
    public function search(array $filter, Pagination $pagination): PagedResult;

    public function findById(int $id): ?array;

    public function findByIdWithStats(int $id): ?array;

    public function existsByEmail(string $email, ?int $exceptId = null): bool;
}
