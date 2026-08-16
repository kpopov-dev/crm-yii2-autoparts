<?php

declare(strict_types=1);

namespace app\modules\crm\forms;

final class ClientFilterForm extends BaseForm
{
    public $query = null;
    public $managerId = null;
    public $isActive = null;
    public $sort = '-id';

    public function rules(): array
    {
        return [
            [['query', 'sort'], 'trim'],
            [['query'], 'string', 'max' => 100],
            [['managerId'], 'integer', 'min' => 1],
            [['isActive'], 'boolean'],
            [['sort'], 'in', 'range' => ['id', '-id', 'name', '-name', 'createdAt', '-createdAt']],
        ];
    }

    public function toRepositoryFilter(): array
    {
        return [
            'query' => $this->query,
            'managerId' => $this->managerId,
            'isActive' => $this->isActive,
            'sort' => $this->sort,
        ];
    }
}
