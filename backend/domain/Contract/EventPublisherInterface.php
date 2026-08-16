<?php

declare(strict_types=1);

namespace app\domain\Contract;

use app\domain\Dto\EventMessage;

interface EventPublisherInterface
{
    public function publish(EventMessage $message): void;
}
