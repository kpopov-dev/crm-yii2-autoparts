<?php

declare(strict_types=1);

namespace app\tests\unit;

use app\domain\Dto\EventMessage;
use app\domain\Enum\EventName;
use PHPUnit\Framework\TestCase;

final class EventMessageTest extends TestCase
{
    public function testCreateGeneratesUniqueIdentifier(): void
    {
        $first = EventMessage::create(EventName::DEAL_CREATED, ['dealId' => 1]);
        $second = EventMessage::create(EventName::DEAL_CREATED, ['dealId' => 2]);

        self::assertNotSame($first->id(), $second->id());
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $first->id()
        );
    }

    public function testRoundTripThroughArray(): void
    {
        $original = EventMessage::create(EventName::DEAL_WON, ['dealId' => 7, 'amount' => 1500.5]);
        $restored = EventMessage::fromArray($original->toArray());

        self::assertSame($original->id(), $restored->id());
        self::assertSame($original->name(), $restored->name());
        self::assertSame(7, $restored->get('dealId'));
        self::assertSame(1500.5, $restored->get('amount'));
    }

    public function testJsonKeepsCyrillicReadable(): void
    {
        $message = EventMessage::create(EventName::TASK_ASSIGNED, ['title' => 'Позвонить клиенту']);

        self::assertStringContainsString('Позвонить клиенту', $message->toJson());
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $message = EventMessage::create(EventName::DEAL_LOST, []);

        self::assertSame('RUB', $message->get('currency', 'RUB'));
    }
}
