<?php

declare(strict_types=1);

namespace app\services;

use app\domain\Contract\ReportRepositoryInterface;
use app\domain\Dto\DateRange;

final class ReportService
{
    private ReportRepositoryInterface $reports;

    public function __construct(ReportRepositoryInterface $reports)
    {
        $this->reports = $reports;
    }

    public function dashboard(DateRange $range, ?int $managerId): array
    {
        return [
            'range' => $range->toArray(),
            'summary' => $this->reports->conversion($range, $managerId),
            'funnel' => $this->reports->funnel($range, $managerId),
            'revenueByMonth' => $this->reports->revenueByMonth($range),
            'managers' => $this->reports->managerPerformance($range),
            'overdueTasks' => $this->reports->overdueTasks(),
        ];
    }

    public function funnel(DateRange $range, ?int $managerId): array
    {
        return $this->reports->funnel($range, $managerId);
    }

    public function managers(DateRange $range): array
    {
        return $this->reports->managerPerformance($range);
    }

    public function daily(DateRange $range): array
    {
        return $this->reports->dailyStats($range);
    }
}
