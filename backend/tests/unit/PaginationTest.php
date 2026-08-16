<?php

declare(strict_types=1);

namespace app\tests\unit;

use app\domain\Dto\PagedResult;
use app\domain\Dto\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testDefaults(): void
    {
        $pagination = new Pagination();

        self::assertSame(1, $pagination->page());
        self::assertSame(Pagination::DEFAULT_LIMIT, $pagination->limit());
        self::assertSame(0, $pagination->offset());
    }

    public function testNegativeValuesAreNormalized(): void
    {
        $pagination = new Pagination(-5, -10);

        self::assertSame(1, $pagination->page());
        self::assertSame(1, $pagination->limit());
    }

    public function testLimitIsCapped(): void
    {
        $pagination = new Pagination(1, 5000);

        self::assertSame(Pagination::MAX_LIMIT, $pagination->limit());
    }

    public function testOffsetCalculation(): void
    {
        $pagination = new Pagination(4, 25);

        self::assertSame(75, $pagination->offset());
    }

    public function testFromRequest(): void
    {
        $pagination = Pagination::fromRequest(['page' => '3', 'limit' => '10']);

        self::assertSame(3, $pagination->page());
        self::assertSame(20, $pagination->offset());
    }

    public function testPagedResultMeta(): void
    {
        $result = new PagedResult([['id' => 1]], 42, new Pagination(2, 20));
        $array = $result->toArray();

        self::assertSame(42, $array['meta']['total']);
        self::assertSame(3, $array['meta']['pageCount']);
        self::assertCount(1, $array['items']);
    }
}
