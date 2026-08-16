<?php

declare(strict_types=1);

namespace app\modules\crm\controllers;

use app\domain\Dto\DateRange;
use app\services\ReportService;

final class ReportController extends BaseApiController
{
    private ReportService $reports;

    public function __construct($id, $module, ReportService $reports, array $config = [])
    {
        $this->reports = $reports;

        parent::__construct($id, $module, $config);
    }

    public function verbs(): array
    {
        return [
            'dashboard' => ['GET'],
            'funnel' => ['GET'],
            'managers' => ['GET'],
            'daily' => ['GET'],
        ];
    }

    public function actionDashboard(): array
    {
        $this->requirePermission('report.view');

        return $this->reports->dashboard($this->range(), $this->managerId());
    }

    public function actionFunnel(): array
    {
        $this->requirePermission('report.view');

        return ['items' => $this->reports->funnel($this->range(), $this->managerId())];
    }

    public function actionManagers(): array
    {
        $this->requirePermission('report.view');

        return ['items' => $this->reports->managers($this->range())];
    }

    public function actionDaily(): array
    {
        $this->requirePermission('report.view');

        return ['items' => $this->reports->daily($this->range())];
    }

    private function range(): DateRange
    {
        return DateRange::fromRequest($this->query());
    }

    private function managerId(): ?int
    {
        $user = $this->currentUser();

        if (!$user->isAdmin()) {
            return $user->getId();
        }

        $requested = (int)($this->query()['managerId'] ?? 0);

        return $requested > 0 ? $requested : null;
    }
}
