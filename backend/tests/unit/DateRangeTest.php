<?php

declare(strict_types=1);

namespace app\tests\unit;

use app\domain\Dto\DateRange;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function testBoundariesAreOrdered(): void
    {
        $range = new DateRange(2000, 1000);

        self::assertSame(1000, $range->from());
        self::assertSame(2000, $range->to());
    }

    public function testLastDays(): void
    {
        $range = DateRange::lastDays(30);

        self::assertSame(30 * 86400, $range->to() - $range->from());
    }

    public function testFromRequestFallsBackToDefaultPeriod(): void
    {
        $range = DateRange::fromRequest(['from' => 'not-a-date'], 10);

        self::assertSame(10 * 86400, $range->to() - $range->from());
    }

    public function testFromRequestParsesIsoDates(): void
    {
        $range = DateRange::fromRequest(['from' => '2026-01-01', 'to' => '2026-01-31']);

        self::assertSame('2026-01-01', $range->toArray()['from']);
        self::assertSame('2026-01-31', $range->toArray()['to']);
    }
}
