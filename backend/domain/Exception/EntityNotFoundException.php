<?php

declare(strict_types=1);

namespace app\domain\Exception;

final class EntityNotFoundException extends DomainException
{
    public static function for(string $entity, int $id): self
    {
        return new self(sprintf('%s с идентификатором %d не найден', $entity, $id));
    }
}
