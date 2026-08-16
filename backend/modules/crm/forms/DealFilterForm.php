<?php

declare(strict_types=1);

namespace app\modules\crm\forms;

use app\domain\Enum\DealStage;

final class DealFilterForm extends BaseForm
{
    public $query = null;
    public $stage = null;
    public $clientId = null;
    public $responsibleId = null;
    public $createdFrom = null;
    public $createdTo = null;
    public $amountFrom = null;
    public $onlyOpen = false;
    public $sort = '-id';

    public function rules(): array
    {
        return [
            [['query', 'sort'], 'trim'],
            [['query'], 'string', 'max' => 100],
            [['stage'], 'in', 'range' => DealStage::all()],
            [['clientId', 'responsibleId'], 'integer', 'min' => 1],
            [['amountFrom'], 'number', 'min' => 0],
            [['onlyOpen'], 'boolean'],
            [['createdFrom', 'createdTo'], 'date', 'format' => 'php:Y-m-d'],
            [['sort'], 'in', 'range' => ['id', '-id', 'amount', '-amount', 'createdAt', '-createdAt', 'updatedAt', '-updatedAt']],
        ];
    }

    public function toRepositoryFilter(): array
    {
        return [
            'query' => $this->query,
            'stage' => $this->stage,
            'clientId' => $this->clientId,
            'responsibleId' => $this->responsibleId,
            'amountFrom' => $this->amountFrom,
            'onlyOpen' => $this->onlyOpen,
            'createdFrom' => $this->createdFrom ? strtotime((string)$this->createdFrom) : null,
            'createdTo' => $this->createdTo ? strtotime((string)$this->createdTo . ' 23:59:59') : null,
            'sort' => $this->sort,
        ];
    }
}
