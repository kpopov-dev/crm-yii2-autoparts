<?php

declare(strict_types=1);

namespace app\domain\Exception;

final class StageTransitionException extends DomainException
{
    public static function forbidden(string $from, string $to): self
    {
        return new self(sprintf('Переход из стадии "%s" в стадию "%s" запрещён', $from, $to));
    }
}
