<?php

declare(strict_types=1);

namespace app\modules\crm\forms;

use app\domain\Exception\ValidationException;
use yii\base\Model;

abstract class BaseForm extends Model
{
    public function fill(array $data): self
    {
        $this->load($data, '');

        return $this;
    }

    public function validateOrFail(): void
    {
        if (!$this->validate()) {
            throw new ValidationException($this->getErrors());
        }
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return $this->getAttributes();
    }
}
