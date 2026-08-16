<?php

declare(strict_types=1);

namespace app\domain\Contract;

use app\domain\Dto\EventMessage;

interface EventHandlerInterface
{
    public function supports(string $eventName): bool;

    public function handle(EventMessage $message): void;
}
