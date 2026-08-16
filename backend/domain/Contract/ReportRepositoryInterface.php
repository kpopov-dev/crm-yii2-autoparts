<?php

declare(strict_types=1);

namespace app\domain\Contract;

use app\domain\Dto\DateRange;

interface ReportRepositoryInterface
{
    public function funnel(DateRange $range, ?int $managerId): array;

    public function managerPerformance(DateRange $range): array;

    public function revenueByMonth(DateRange $range): array;

    public function conversion(DateRange $range, ?int $managerId): array;

    public function overdueTasks(): array;

    public function dailyStats(DateRange $range): array;
}
