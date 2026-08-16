<?php

declare(strict_types=1);

namespace app\domain\Policy;

use app\domain\Enum\DealStage;
use app\domain\Exception\StageTransitionException;

final class StageTransitionPolicy
{
    public function isAllowed(string $from, string $to): bool
    {
        if (!DealStage::exists($from) || !DealStage::exists($to)) {
            return false;
        }

        if ($from === $to) {
            return false;
        }

        if (DealStage::isClosed($from)) {
            return false;
        }

        if (DealStage::isClosed($to)) {
            return true;
        }

        return abs(DealStage::position($to) - DealStage::position($from)) === 1;
    }

    public function assert(string $from, string $to): void
    {
        if (!$this->isAllowed($from, $to)) {
            throw StageTransitionException::forbidden($from, $to);
        }
    }

    public function availableFrom(string $from): array
    {
        return array_values(array_filter(
            DealStage::all(),
            fn (string $stage): bool => $this->isAllowed($from, $stage)
        ));
    }
}
