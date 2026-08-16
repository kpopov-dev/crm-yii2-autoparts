<?php

declare(strict_types=1);

namespace app\modules\crm\controllers;

use app\domain\Enum\DealStage;
use app\modules\crm\forms\DealFilterForm;
use app\modules\crm\forms\DealForm;
use app\modules\crm\forms\StageChangeForm;
use app\services\DealService;

final class DealController extends BaseApiController
{
    private DealService $deals;

    public function __construct($id, $module, DealService $deals, array $config = [])
    {
        $this->deals = $deals;

        parent::__construct($id, $module, $config);
    }

    public function verbs(): array
    {
        return [
            'index' => ['GET'],
            'board' => ['GET'],
            'view' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'change-stage' => ['POST'],
            'stages' => ['GET'],
        ];
    }

    public function actionIndex(): array
    {
        $this->requirePermission('deal.view');

        $filter = $this->buildFilter();

        return $this->deals->search($filter, $this->pagination())->toArray();
    }

    public function actionBoard(): array
    {
        $this->requirePermission('deal.view');

        return ['columns' => $this->deals->board($this->buildFilter())];
    }

    public function actionView(int $id): array
    {
        $this->requirePermission('deal.view');

        return $this->deals->view($id);
    }

    public function actionCreate(): array
    {
        $this->requirePermission('deal.manage');

        $form = new DealForm();
        $form->responsibleId = $this->currentUser()->getId();
        $form->fill($this->body());
        $form->validateOrFail();

        return $this->deals->create($form->toArray(), $this->currentUser()->getId());
    }

    public function actionUpdate(int $id): array
    {
        $this->requirePermission('deal.manage');

        $form = new DealForm();
        $form->fill(array_merge($this->deals->view($id), $this->body()));
        $form->validateOrFail();

        return $this->deals->update($id, $form->toArray());
    }

    public function actionChangeStage(int $id): array
    {
        $this->requirePermission('deal.manage');

        $form = new StageChangeForm();
        $form->fill($this->body());
        $form->validateOrFail();

        return $this->deals->changeStage(
            $id,
            (string)$form->stage,
            $this->currentUser()->getId(),
            (string)$form->comment
        );
    }

    public function actionStages(): array
    {
        return ['items' => DealStage::list()];
    }

    private function buildFilter(): array
    {
        $filter = new DealFilterForm();
        $filter->fill($this->query());
        $filter->validateOrFail();

        return $this->scopeToUser($filter->toRepositoryFilter(), 'responsibleId');
    }
}
