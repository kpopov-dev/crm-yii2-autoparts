<?php

declare(strict_types=1);

namespace app\tests\unit;

use app\domain\Enum\DealStage;
use app\domain\Exception\StageTransitionException;
use app\domain\Policy\StageTransitionPolicy;
use PHPUnit\Framework\TestCase;

final class StageTransitionPolicyTest extends TestCase
{
    private StageTransitionPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new StageTransitionPolicy();
    }

    public function testForwardTransitionByOneStepIsAllowed(): void
    {
        self::assertTrue($this->policy->isAllowed(DealStage::NEW, DealStage::QUALIFICATION));
        self::assertTrue($this->policy->isAllowed(DealStage::PROPOSAL, DealStage::NEGOTIATION));
    }

    public function testBackwardTransitionByOneStepIsAllowed(): void
    {
        self::assertTrue($this->policy->isAllowed(DealStage::NEGOTIATION, DealStage::PROPOSAL));
    }

    public function testSkippingStagesIsForbidden(): void
    {
        self::assertFalse($this->policy->isAllowed(DealStage::NEW, DealStage::NEGOTIATION));
    }

    public function testClosingIsAllowedFromAnyOpenStage(): void
    {
        foreach (DealStage::pipeline() as $stage) {
            self::assertTrue($this->policy->isAllowed($stage, DealStage::WON));
            self::assertTrue($this->policy->isAllowed($stage, DealStage::LOST));
        }
    }

    public function testClosedDealCannotBeReopened(): void
    {
        self::assertFalse($this->policy->isAllowed(DealStage::WON, DealStage::NEGOTIATION));
        self::assertFalse($this->policy->isAllowed(DealStage::LOST, DealStage::NEW));
    }

    public function testSameStageIsForbidden(): void
    {
        self::assertFalse($this->policy->isAllowed(DealStage::NEW, DealStage::NEW));
    }

    public function testUnknownStageIsForbidden(): void
    {
        self::assertFalse($this->policy->isAllowed(DealStage::NEW, 'archived'));
    }

    public function testAssertThrowsOnForbiddenTransition(): void
    {
        $this->expectException(StageTransitionException::class);

        $this->policy->assert(DealStage::WON, DealStage::NEW);
    }

    public function testAvailableFromNewStage(): void
    {
        $available = $this->policy->availableFrom(DealStage::NEW);

        self::assertSame(
            [DealStage::QUALIFICATION, DealStage::WON, DealStage::LOST],
            $available
        );
    }
}
