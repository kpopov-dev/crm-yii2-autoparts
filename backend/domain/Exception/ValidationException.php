<?php

declare(strict_types=1);

namespace app\domain\Exception;

final class ValidationException extends DomainException
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Данные не прошли валидацию')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
