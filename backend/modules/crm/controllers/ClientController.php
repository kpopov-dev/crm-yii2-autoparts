<?php

declare(strict_types=1);

namespace app\modules\crm\controllers;

use app\modules\crm\forms\ClientFilterForm;
use app\modules\crm\forms\ClientForm;
use app\services\ClientService;

final class ClientController extends BaseApiController
{
    private ClientService $clients;

    public function __construct($id, $module, ClientService $clients, array $config = [])
    {
        $this->clients = $clients;

        parent::__construct($id, $module, $config);
    }

    public function verbs(): array
    {
        return [
            'index' => ['GET'],
            'view' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'archive' => ['DELETE'],
        ];
    }

    public function actionIndex(): array
    {
        $this->requirePermission('client.view');

        $filter = new ClientFilterForm();
        $filter->fill($this->query());
        $filter->validateOrFail();

        $criteria = $this->scopeToUser($filter->toRepositoryFilter(), 'managerId');

        return $this->clients->search($criteria, $this->pagination())->toArray();
    }

    public function actionView(int $id): array
    {
        $this->requirePermission('client.view');

        return $this->clients->view($id);
    }

    public function actionCreate(): array
    {
        $this->requirePermission('client.manage');

        $form = new ClientForm();
        $form->managerId = $this->currentUser()->getId();
        $form->fill($this->body());
        $form->validateOrFail();

        return $this->clients->create($form->toArray());
    }

    public function actionUpdate(int $id): array
    {
        $this->requirePermission('client.manage');

        $form = new ClientForm();
        $form->fill(array_merge($this->clients->view($id), $this->body()));
        $form->validateOrFail();

        return $this->clients->update($id, $form->toArray());
    }

    public function actionArchive(int $id): array
    {
        $this->requirePermission('client.manage');

        $this->clients->archive($id);

        return ['success' => true];
    }
}
